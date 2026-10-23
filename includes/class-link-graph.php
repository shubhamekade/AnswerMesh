<?php
/**
 * Sitewide Internal Link Graph Engine and Smart Anchor Discovery.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class LinkGraph
{
    /**
     * Build the sitewide internal link graph.
     *
     * @return array<string, mixed>
     */
    public static function build_graph(): array
    {
        $posts = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => 200,
        ]);

        $home_url = untrailingslashit(home_url());
        $nodes = [];
        $edges = [];
        $total_links = 0;

        foreach ($posts as $p) {
            $nodes[$p->ID] = [
                'post_id'     => $p->ID,
                'title'       => $p->post_title ?: __('(Untitled)', 'answermesh'),
                'in_degree'   => 0,
                'out_degree'  => 0,
                'in_anchors'  => [],
                'out_targets' => [],
            ];
        }

        foreach ($posts as $source_post) {
            $content = $source_post->post_content;
            if (empty($content)) {
                continue;
            }

            // Extract all <a href="..."> anchors
            if (preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $url = $match[1];
                    $anchor = wp_strip_all_tags($match[2]);

                    // Check if internal link
                    if (!str_starts_with($url, $home_url) && !str_starts_with($url, '/')) {
                        continue;
                    }

                    // Resolve target post ID
                    $target_id = url_to_postid($url);
                    if ($target_id > 0 && isset($nodes[$target_id]) && $target_id !== $source_post->ID) {
                        $total_links++;
                        $nodes[$source_post->ID]['out_degree']++;
                        $nodes[$source_post->ID]['out_targets'][] = $target_id;

                        $nodes[$target_id]['in_degree']++;
                        $nodes[$target_id]['in_anchors'][] = $anchor;

                        $edges[] = [
                            'source' => $source_post->ID,
                            'target' => $target_id,
                            'anchor' => $anchor,
                        ];
                    }
                }
            }
        }

        // Identify orphan pages (in_degree === 0)
        $orphans = [];
        foreach ($nodes as $node) {
            if ($node['in_degree'] === 0) {
                $orphans[] = $node;
            }
        }

        return [
            'total_posts'  => count($nodes),
            'total_links'  => $total_links,
            'orphan_count' => count($orphans),
            'orphans'      => $orphans,
            'nodes'        => array_values($nodes),
            'edges'        => $edges,
        ];
    }

    /**
     * Get link metrics for a specific post.
     *
     * @param int $post_id Post ID.
     * @return array<string, mixed>
     */
    public static function get_post_link_metrics(int $post_id): array
    {
        $graph = self::build_graph();
        foreach ($graph['nodes'] as $node) {
            if ((int) $node['post_id'] === $post_id) {
                return $node;
            }
        }

        return [
            'post_id'     => $post_id,
            'in_degree'   => 0,
            'out_degree'  => 0,
            'in_anchors'  => [],
            'out_targets' => [],
        ];
    }

    /**
     * Get true orphan pages with 0 internal incoming links.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_orphan_pages(): array
    {
        $graph = self::build_graph();
        return $graph['orphans'] ?? [];
    }

    /**
     * Discover contextual internal linking opportunities.
     * Searches articles for relevant entity/subtopic phrases that can link to a pillar or target post.
     *
     * @param int $target_post_id Optional post ID to find opportunities FOR.
     * @return array<int, array<string, mixed>>
     */
    public static function find_link_opportunities(int $target_post_id = 0): array
    {
        $posts = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => 100,
        ]);

        if (empty($posts)) {
            return [];
        }

        $targets = [];
        if ($target_post_id > 0) {
            $tpost = get_post($target_post_id);
            if ($tpost) {
                $targets[] = $tpost;
            }
        } else {
            // Find opportunities for all pillar posts or high-priority articles
            $clusters = Cluster::get_all();
            foreach ($clusters as $c) {
                if ($c['pillar_post_id'] > 0) {
                    $p = get_post($c['pillar_post_id']);
                    if ($p) {
                        $targets[] = $p;
                    }
                }
            }
            if (empty($targets)) {
                $targets = array_slice($posts, 0, 5);
            }
        }

        $opportunities = [];

        foreach ($targets as $target) {
            $target_title = $target->post_title;
            $target_url = get_permalink($target->ID);

            // Clean keywords: remove stop words and short terms
            $words = array_filter(
                explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $target_title)),
                fn($w) => mb_strlen($w) >= 4
            );

            if (empty($words)) {
                continue;
            }

            // Test anchor: 2-3 word phrase or full title
            $anchor_candidates = [
                $target_title,
                implode(' ', array_slice($words, 0, 3)),
                implode(' ', array_slice($words, 0, 2)),
            ];
            $anchor_candidates = array_unique(array_filter($anchor_candidates));

            foreach ($posts as $source) {
                if ($source->ID === $target->ID) {
                    continue;
                }

                $content = $source->post_content;
                if (empty($content)) {
                    continue;
                }

                // Strip existing <a> tags so we don't suggest linking text already inside a link
                $text_only = preg_replace('/<a\s+[^>]*>.*?<\/a>/is', '', $content);

                foreach ($anchor_candidates as $candidate) {
                    $pos = stripos($text_only, $candidate);
                    if ($pos !== false) {
                        // Extract sentence context around match
                        $start = max(0, $pos - 60);
                        $length = strlen($candidate) + 120;
                        $snippet = '...' . trim(substr($text_only, $start, $length)) . '...';

                        $opportunities[] = [
                            'source_post_id'   => $source->ID,
                            'source_title'     => $source->post_title,
                            'target_post_id'   => $target->ID,
                            'target_title'     => $target_title,
                            'target_url'       => $target_url,
                            'anchor'           => $candidate,
                            'context_snippet'  => $snippet,
                            'relevance'        => 'High',
                        ];

                        if (count($opportunities) >= 15) {
                            break 3;
                        }
                        break; // One opportunity per source/target pair
                    }
                }
            }
        }

        return $opportunities;
    }
}

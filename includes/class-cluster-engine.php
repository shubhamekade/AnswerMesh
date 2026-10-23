<?php
/**
 * Topic Cluster discovery, gap analysis, and orphan classifier engine.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class ClusterEngine
{
    /**
     * Discover potential topic clusters across published posts.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function discover_clusters(): array
    {
        $posts = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => 100,
        ]);

        if (empty($posts)) {
            return [];
        }

        // Group posts by primary category or first tag
        $grouped = [];
        $existing_clusters = Cluster::get_all();
        $existing_names = array_map(fn($c) => strtolower($c['name']), $existing_clusters);

        foreach ($posts as $post) {
            $categories = get_the_category($post->ID);
            $primary_group = !empty($categories) ? $categories[0]->name : 'General Topics';

            if (!isset($grouped[$primary_group])) {
                $grouped[$primary_group] = [];
            }
            $grouped[$primary_group][] = $post->ID;
        }

        $discovered = [];

        foreach ($grouped as $group_name => $post_ids) {
            // Require at least 2 posts to form a cluster
            if (count($post_ids) < 2) {
                continue;
            }

            // Skip if cluster already exists
            if (in_array(strtolower($group_name), $existing_names, true)) {
                continue;
            }

            $pillar_id = self::evaluate_pillar_candidate($post_ids);
            $supporting_ids = array_values(array_diff($post_ids, [$pillar_id]));

            $discovered[] = [
                'name'            => $group_name,
                'slug'            => sanitize_title($group_name),
                'pillar_post_id'  => $pillar_id,
                'pillar_title'    => get_the_title($pillar_id),
                'candidate_posts' => array_map(function ($pid) {
                    return [
                        'post_id' => $pid,
                        'title'   => get_the_title($pid),
                    ];
                }, $supporting_ids),
                'total_posts'     => count($post_ids),
            ];
        }

        return $discovered;
    }

    /**
     * Nominate the best pillar page among a list of candidate post IDs.
     *
     * @param array<int> $post_ids Candidate post IDs.
     * @return int Best pillar post ID.
     */
    public static function evaluate_pillar_candidate(array $post_ids): int
    {
        if (empty($post_ids)) {
            return 0;
        }

        if (count($post_ids) === 1) {
            return reset($post_ids);
        }

        global $wpdb;
        $table_scores = $wpdb->prefix . 'aiseo_scores';

        $best_id = $post_ids[0];
        $best_index = -1.0;

        foreach ($post_ids as $pid) {
            $post = get_post($pid);
            if (!$post) {
                continue;
            }

            // 1. Content depth / word count
            $words = str_word_count(wp_strip_all_tags($post->post_content));
            $word_score = min(100, ($words / 1500) * 100);

            // 2. Heading structure depth
            preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/i', $post->post_content, $headings);
            $heading_score = min(100, count($headings[0] ?? []) * 15);

            // 3. Diagnostic overall score if available
            $overall_score = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT overall_score FROM {$table_scores} WHERE post_id = %d LIMIT 1", $pid)
            );

            // Weighted composite Pillar Index
            $pillar_index = ($word_score * 0.40) + ($heading_score * 0.35) + ($overall_score * 0.25);

            if ($pillar_index > $best_index) {
                $best_index = $pillar_index;
                $best_id = $pid;
            }
        }

        return $best_id;
    }

    /**
     * Identify subtopic gaps across cluster member posts.
     *
     * @param int $cluster_id Cluster ID.
     * @return array<int, array<string, mixed>>
     */
    public static function analyze_subtopic_gaps(int $cluster_id): array
    {
        $cluster = Cluster::get($cluster_id);
        if (!$cluster) {
            return [];
        }

        $pillar_id = (int) ($cluster['pillar_post_id'] ?? 0);
        $posts = $cluster['posts'] ?? [];

        if ($pillar_id <= 0 && empty($posts)) {
            return [];
        }

        global $wpdb;
        $table_questions = $wpdb->prefix . 'aiseo_questions';

        $all_ids = array_filter(array_merge([$pillar_id], array_column($posts, 'post_id')));
        if (empty($all_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($all_ids), '%d'));
        $questions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, question, intent, status FROM {$table_questions} WHERE post_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                ...$all_ids
            ),
            ARRAY_A
        );

        $gaps = [];
        $pillar_questions = [];
        $cluster_questions = [];

        foreach ($questions as $q) {
            $pid = (int) $q['post_id'];
            if ($pid === $pillar_id) {
                $pillar_questions[] = strtolower(trim($q['question']));
            } else {
                $cluster_questions[] = [
                    'question' => $q['question'],
                    'intent'   => $q['intent'],
                    'post_id'  => $pid,
                ];
            }
        }

        // Subtopics present in supporting articles but missing from pillar
        foreach ($cluster_questions as $cq) {
            $norm = strtolower(trim($cq['question']));
            if (!in_array($norm, $pillar_questions, true)) {
                $gaps[] = [
                    'type'               => 'missing_in_pillar',
                    'subtopic'           => $cq['question'],
                    'intent'             => $cq['intent'],
                    'covered_in_post_id' => $cq['post_id'],
                    'covered_in_title'   => get_the_title($cq['post_id']),
                    'recommendation'     => sprintf(
                        __('Add dedicated sub-section or FAQ addressing "%s" in the pillar page to reinforce topical authority.', 'answermesh'),
                        $cq['question']
                    ),
                ];
            }
        }

        // Check if cluster has no pillar
        if ($pillar_id <= 0) {
            $gaps[] = [
                'type'           => 'no_pillar',
                'subtopic'       => __('Pillar Page Assignment', 'answermesh'),
                'intent'         => 'Structural',
                'recommendation' => __('Assign a primary cornerstone/pillar post to this topic cluster.', 'answermesh'),
            ];
        }

        return $gaps;
    }

    /**
     * Get published posts not assigned to any cluster.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_orphan_posts(): array
    {
        $all_posts = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => 100,
        ]);

        $clusters = Cluster::get_all();
        $assigned_ids = [];

        foreach ($clusters as $c) {
            if ($c['pillar_post_id'] > 0) {
                $assigned_ids[] = (int) $c['pillar_post_id'];
            }
            foreach ($c['posts'] as $p) {
                $assigned_ids[] = (int) ($p['post_id'] ?? 0);
            }
        }
        $assigned_ids = array_unique($assigned_ids);

        $orphans = [];

        foreach ($all_posts as $post) {
            if (!in_array($post->ID, $assigned_ids, true)) {
                $categories = get_the_category($post->ID);
                $cat_name = !empty($categories) ? $categories[0]->name : 'Uncategorized';

                // Look for existing cluster with matching name/category
                $suggested_cluster_id = 0;
                $suggested_cluster_name = '';

                foreach ($clusters as $c) {
                    if (strcasecmp($c['name'], $cat_name) === 0) {
                        $suggested_cluster_id = $c['id'];
                        $suggested_cluster_name = $c['name'];
                        break;
                    }
                }

                $orphans[] = [
                    'post_id'                => $post->ID,
                    'title'                  => $post->post_title ?: __('(Untitled)', 'answermesh'),
                    'post_type'              => $post->post_type,
                    'category'               => $cat_name,
                    'edit_url'               => get_edit_post_link($post->ID, 'raw'),
                    'suggested_cluster_id'   => $suggested_cluster_id,
                    'suggested_cluster_name' => $suggested_cluster_name,
                ];
            }
        }

        return $orphans;
    }

    /**
     * Calculate comprehensive cluster health and metrics.
     *
     * @param int $cluster_id Cluster ID.
     * @return array<string, mixed>
     */
    public static function calculate_cluster_health(int $cluster_id): array
    {
        $cluster = Cluster::get($cluster_id);
        if (!$cluster) {
            return [];
        }

        $coverage = Cluster::calculate_cluster_score($cluster_id);
        $gaps = self::analyze_subtopic_gaps($cluster_id);
        $post_count = count($cluster['posts'] ?? []);
        $has_pillar = ($cluster['pillar_post_id'] > 0);

        $rating = 'Needs Attention';
        if ($coverage >= 80) {
            $rating = 'Strong';
        } elseif ($coverage >= 55) {
            $rating = 'Developing';
        }

        return [
            'cluster_id'     => $cluster_id,
            'name'           => $cluster['name'],
            'coverage_score' => $coverage,
            'rating'         => $rating,
            'has_pillar'     => $has_pillar,
            'pillar_title'   => $cluster['pillar_title'],
            'member_count'   => $post_count,
            'gap_count'      => count($gaps),
            'gaps'           => $gaps,
        ];
    }
}

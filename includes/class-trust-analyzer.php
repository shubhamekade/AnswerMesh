<?php
/**
 * Trust signals and citation readiness analyzer.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class TrustAnalyzer
{
    /**
     * Evaluate trust and citation readiness signals for a post.
     *
     * @param int $post_id Post ID.
     * @return array<string, mixed>
     */
    public static function evaluate_post(int $post_id): array
    {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $content = (string) apply_filters('the_content', $post->post_content);
        $author_id = (int) $post->post_author;

        // Author signals.
        $author_name = get_the_author_meta('display_name', $author_id);
        $author_bio = (string) get_the_author_meta('description', $author_id);
        $has_author_bio = (!empty(trim($author_bio)));
        $has_author_url = (!empty(get_the_author_meta('user_url', $author_id)));

        // Dates & freshness.
        $published_date = get_the_date('Y-m-d H:i:s', $post);
        $modified_date = get_the_modified_date('Y-m-d H:i:s', $post);
        $days_since_modified = (int) floor((time() - strtotime((string) $modified_date)) / DAY_IN_SECONDS);

        // Outbound citations.
        $citations = self::extract_citations($content);

        // Unbacked numerical / statistical claims.
        $unbacked_stats = self::detect_unbacked_statistics($content);

        // Calculate 0–100 Citation Readiness diagnostic score.
        $score = 50; // base score.
        if ($has_author_bio) {
            $score += 15;
        }
        if ($days_since_modified < 180) {
            $score += 10;
        } elseif ($days_since_modified > 365) {
            $score -= 10;
        }
        if ($citations['authoritative_count'] >= 1) {
            $score += 15;
        }
        if (count($unbacked_stats) > 0) {
            $score -= min(20, count($unbacked_stats) * 5);
        }
        $citation_score = max(0, min(100, $score));

        return [
            'author'                  => [
                'name'    => $author_name,
                'has_bio' => $has_author_bio,
                'has_url' => $has_author_url,
            ],
            'dates'                   => [
                'published'           => $published_date,
                'modified'            => $modified_date,
                'days_since_modified' => $days_since_modified,
                'is_fresh'            => ($days_since_modified < 180),
            ],
            'citations'               => $citations,
            'unbacked_statistics'     => $unbacked_stats,
            'citation_readiness'      => $citation_score,
        ];
    }

    /**
     * Extract external citation links and detect authoritative TLDs/domains.
     *
     * @return array{total_external: int, authoritative_count: int, sources: array}
     */
    public static function extract_citations(string $html): array
    {
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $citations = [];
        $auth_count = 0;

        preg_match_all('/<a\s+[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        $authoritative_domains = [
            '.gov', '.edu', 'wikipedia.org', 'reuters.com', 'bloomberg.com',
            'nature.com', 'sciencedirect.com', 'ncbi.nlm.nih.gov', 'w3.org', 'github.com',
        ];

        foreach ($matches as $match) {
            $href = trim($match[1]);
            $host = wp_parse_url($href, PHP_URL_HOST);

            if (empty($host) || $host === $site_host) {
                continue;
            }

            $is_auth = false;
            foreach ($authoritative_domains as $auth) {
                if (str_ends_with($host, $auth)) {
                    $is_auth = true;
                    $auth_count++;
                    break;
                }
            }

            $citations[] = [
                'url'             => $href,
                'domain'          => $host,
                'anchor'          => trim(wp_strip_all_tags($match[2])),
                'is_authoritative' => $is_auth,
            ];
        }

        return [
            'total_external'      => count($citations),
            'authoritative_count' => $auth_count,
            'sources'             => $citations,
        ];
    }

    /**
     * Detect numbers/percentages in sentences lacking citations.
     *
     * @return array<int, string>
     */
    public static function detect_unbacked_statistics(string $html): array
    {
        $flagged = [];

        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $matches);

        foreach ($matches[1] as $para) {
            // Check if paragraph has numbers like 75%, 2.5 million, 10x, etc.
            if (preg_match('/\b\d+(\.\d+)?%\b|\b\d{2,}\b/i', $para) && !str_contains($para, '<a ')) {
                $clean = wp_strip_all_tags($para);
                $flagged[] = mb_strimwidth(trim($clean), 0, 100, '...');
            }
        }

        return array_slice($flagged, 0, 5); // Return up to 5 examples.
    }
}

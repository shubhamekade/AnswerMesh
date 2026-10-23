<?php
/**
 * Content Freshness and Decay Evaluation Engine.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class DecayEngine
{
    public const TIER_FRESH = 'fresh';
    public const TIER_AGING = 'aging';
    public const TIER_DECAYING = 'decaying';
    public const TIER_CRITICAL = 'critical';

    /**
     * Calculate content freshness metrics for a single post.
     *
     * @param int $post_id WordPress post ID.
     * @return array<string, mixed>
     */
    public static function calculate_post_freshness(int $post_id): array
    {
        $post = get_post($post_id);
        if (!$post) {
            return [
                'post_id'         => $post_id,
                'freshness_score' => 0,
                'tier'            => self::TIER_CRITICAL,
                'tier_label'      => __('Critical', 'answermesh'),
                'days_modified'   => 999,
                'days_published'  => 999,
            ];
        }

        $now = current_time('timestamp');
        $mod_time = get_post_modified_time('U', false, $post);
        $pub_time = get_post_time('U', false, $post);

        $days_modified = max(0, (int) round(($now - $mod_time) / DAY_IN_SECONDS));
        $days_published = max(0, (int) round(($now - $pub_time) / DAY_IN_SECONDS));

        // Base score: 100 max, decays with days since modified
        // 0-60 days: 90-100
        // 61-180 days: 70-89
        // 181-365 days: 40-69
        // 365+ days: 0-39
        if ($days_modified <= 60) {
            $base_score = 100 - (int) round(($days_modified / 60) * 10);
            $tier = self::TIER_FRESH;
            $tier_label = __('Fresh', 'answermesh');
        } elseif ($days_modified <= 180) {
            $base_score = 89 - (int) round((($days_modified - 60) / 120) * 19);
            $tier = self::TIER_AGING;
            $tier_label = __('Aging', 'answermesh');
        } elseif ($days_modified <= 365) {
            $base_score = 69 - (int) round((($days_modified - 180) / 185) * 29);
            $tier = self::TIER_DECAYING;
            $tier_label = __('Decaying', 'answermesh');
        } else {
            $base_score = max(5, 39 - (int) round((($days_modified - 365) / 365) * 34));
            $tier = self::TIER_CRITICAL;
            $tier_label = __('Critical', 'answermesh');
        }

        // Adjustments:
        // Check diagnostic score if available
        global $wpdb;
        $table_scores = $wpdb->prefix . 'aiseo_scores';
        $overall_score = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT overall_score FROM {$table_scores} WHERE post_id = %d LIMIT 1", $post_id)
        );

        // High diagnostic score protects freshness slightly
        if ($overall_score >= 80) {
            $base_score = min(100, $base_score + 5);
        } elseif ($overall_score > 0 && $overall_score < 45) {
            $base_score = max(0, $base_score - 10);
            if ($tier === self::TIER_FRESH) {
                $tier = self::TIER_AGING;
                $tier_label = __('Aging', 'answermesh');
            }
        }

        return [
            'post_id'         => $post_id,
            'title'           => $post->post_title ?: __('(Untitled)', 'answermesh'),
            'freshness_score' => $base_score,
            'tier'            => $tier,
            'tier_label'      => $tier_label,
            'days_modified'   => $days_modified,
            'days_published'  => $days_published,
            'last_modified'   => get_the_modified_date('Y-m-d', $post),
            'diagnostic_score'=> $overall_score,
            'edit_url'        => get_edit_post_link($post_id, 'raw'),
        ];
    }

    /**
     * Get inventory of published posts with freshness and decay details.
     *
     * @param array<string, mixed> $args Filter arguments.
     * @return array<int, array<string, mixed>>
     */
    public static function get_decay_inventory(array $args = []): array
    {
        $posts = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => $args['limit'] ?? 100,
            'orderby'        => 'modified',
            'order'          => 'ASC', // Oldest modified first
        ]);

        $inventory = [];
        $filter_tier = $args['tier'] ?? null;

        foreach ($posts as $post) {
            $freshness = self::calculate_post_freshness($post->ID);

            if ($filter_tier && $freshness['tier'] !== $filter_tier) {
                continue;
            }

            $inventory[] = $freshness;
        }

        return $inventory;
    }

    /**
     * Get site-wide summary of content decay.
     *
     * @return array<string, mixed>
     */
    public static function get_site_decay_summary(): array
    {
        $all = self::get_decay_inventory(['limit' => 200]);
        $total = count($all);

        $fresh_count = 0;
        $aging_count = 0;
        $decaying_count = 0;
        $critical_count = 0;
        $score_sum = 0;

        foreach ($all as $item) {
            $score_sum += $item['freshness_score'];
            switch ($item['tier']) {
                case self::TIER_FRESH:
                    $fresh_count++;
                    break;
                case self::TIER_AGING:
                    $aging_count++;
                    break;
                case self::TIER_DECAYING:
                    $decaying_count++;
                    break;
                case self::TIER_CRITICAL:
                    $critical_count++;
                    break;
            }
        }

        $avg_freshness = $total > 0 ? (int) round($score_sum / $total) : 100;

        return [
            'total_posts'     => $total,
            'avg_freshness'   => $avg_freshness,
            'fresh_count'     => $fresh_count,
            'aging_count'     => $aging_count,
            'decaying_count'  => $decaying_count,
            'critical_count'  => $critical_count,
            'needs_refresh'   => $decaying_count + $critical_count,
        ];
    }
}

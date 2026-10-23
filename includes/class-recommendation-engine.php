<?php
/**
 * Evidence Model recommendation generator and priority sorter.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class RecommendationEngine
{
    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_INFO = 'info';

    /**
     * Generate structured Evidence Model recommendations for a post.
     *
     * @param int $post_id Post ID.
     * @param array<string, mixed> $analysis
     * @param array<string, mixed> $trust
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $scores
     * @return array<int, array<string, mixed>>
     */
    public static function generate_recommendations(int $post_id, array $analysis, array $trust, array $questions, array $scores): array
    {
        $recommendations = [];

        // 1. Missing or Short Title.
        $title = Meta::get_title($post_id);
        if (mb_strlen($title) < 25) {
            $recommendations[] = [
                'type'             => 'title_length',
                'priority'         => self::PRIORITY_HIGH,
                'confidence'       => 'High',
                'title'            => __('Expand SEO Title for Clear Topical Coverage', 'answermesh'),
                'description'      => __('Your title tag is concise but misses critical entity or topical clarification needed for search and AI engine understanding.', 'answermesh'),
                'evidence'         => sprintf(__('Current title length is %d characters ("%s"). Target is 40–60 characters.', 'answermesh'), mb_strlen($title), $title),
                'impact'           => __('AI query matchers and search snippets rely heavily on explicit titles to determine topical relevance.', 'answermesh'),
                'recommendation'   => __('Add specific service, topic, or location qualifiers to the title tag.', 'answermesh'),
                'acceptance_check' => __('Title tag contains between 30 and 65 characters with primary entity included.', 'answermesh'),
            ];
        }

        $skips = $analysis['heading_audit']['level_skips'] ?? [];
        if (!empty($skips)) {
            $skip_strings = array_map(function ($s) {
                return is_array($s) ? sprintf('H%d to H%d', $s['from'] ?? 0, $s['to'] ?? 0) : (string) $s;
            }, $skips);
            $evidence_text = sprintf(__('Detected heading jump: %s.', 'answermesh'), implode(', ', $skip_strings));
                $recommendations[] = [
                    'type'             => 'heading_order',
                    'priority'         => self::PRIORITY_MEDIUM,
                    'confidence'       => 'High',
                    'title'            => __('Fix Skipped Heading Hierarchy Levels', 'answermesh'),
                    'description'      => __('The content skips heading hierarchy levels, which confuses machine document parsers.', 'answermesh'),
                    'evidence'         => $evidence_text,
                    'impact'           => __('Generative answer engines extract answers within hierarchical sub-trees; skipped levels degrade outline extraction.', 'answermesh'),
                    'recommendation'   => __('Nest headings sequentially without skipping levels (e.g. follow H2 with H3, not H4).', 'answermesh'),
                    'acceptance_check' => __('All headings strictly follow sequential numerical order without skips.', 'answermesh'),
                ];
        }

        // 3. Question coverage gaps.
        $unanswered = 0;
        $weak = 0;
        foreach ($questions as $q) {
            $status = $q['status'] ?? $q['coverage_status'] ?? 'answered';
            if ($status === 'missing') {
                $unanswered++;
            } elseif ($status === 'weak') {
                $weak++;
            }
        }
        if ($unanswered > 0 || $weak > 0) {
            $recommendations[] = [
                'type'             => 'unanswered_questions',
                'priority'         => self::PRIORITY_HIGH,
                'confidence'       => 'High',
                'title'            => __('Strengthen Direct Answers for Detected Questions', 'answermesh'),
                'description'      => __('Questions were identified in headings or text, but direct concise answers are either missing or too brief.', 'answermesh'),
                'evidence'         => sprintf(__('%d questions missing answers, %d questions have weak answers.', 'answermesh'), $unanswered, $weak),
                'impact'           => __('AEO engines require concise 50–100 word answers directly beneath questions to cite pages as answers.', 'answermesh'),
                'recommendation'   => __('Add direct, informative answers immediately after question headings.', 'answermesh'),
                'acceptance_check' => __('Each detected question has at least 30 words of explanatory answer text.', 'answermesh'),
            ];
        }

        // 4. Author expertise & bio.
        if (empty($trust['author']['has_bio'])) {
            $recommendations[] = [
                'type'             => 'author_bio',
                'priority'         => self::PRIORITY_MEDIUM,
                'confidence'       => 'High',
                'title'            => __('Add Author Expertise and Biographical Evidence', 'answermesh'),
                'description'      => __('Post author profile does not contain a biographical description or credentials.', 'answermesh'),
                'evidence'         => sprintf(__('Author "%s" has empty profile biographical information.', 'answermesh'), $trust['author']['name'] ?? 'Author'),
                'impact'           => __('Trust evaluation (E-E-A-T) and citation readiness degrade when author credentials cannot be confirmed.', 'answermesh'),
                'recommendation'   => __('Update WordPress User Profile with author bio, credentials, and social links.', 'answermesh'),
                'acceptance_check' => __('Author biography exists with more than 20 words.', 'answermesh'),
            ];
        }

        // 5. Unbacked statistics.
        $unbacked = $trust['unbacked_statistics'] ?? [];
        if (!empty($unbacked)) {
            $recommendations[] = [
                'type'             => 'unbacked_statistics',
                'priority'         => self::PRIORITY_MEDIUM,
                'confidence'       => 'Medium',
                'title'            => __('Provide Citations for Statistical and Numerical Claims', 'answermesh'),
                'description'      => __('Sentences containing numbers or percentages were detected without outbound reference links.', 'answermesh'),
                'evidence'         => sprintf(__('Example: "%s"', 'answermesh'), $unbacked[0]),
                'impact'           => __('AI engines prioritize citing claims verified by authoritative external sources.', 'answermesh'),
                'recommendation'   => __('Link statistical assertions to authoritative industry, academic, or research sources.', 'answermesh'),
                'acceptance_check' => __('External link exists within or adjacent to statistical claims.', 'answermesh'),
            ];
        }

        // 6. Image Alt text missing.
        $missing_alt = $analysis['images']['missing_alt'] ?? 0;
        if ($missing_alt > 0) {
            $recommendations[] = [
                'type'             => 'missing_alt',
                'priority'         => self::PRIORITY_LOW,
                'confidence'       => 'High',
                'title'            => __('Add Descriptive Alt Text to Content Images', 'answermesh'),
                'description'      => __('Images lack alt attributes, reducing accessibility and visual entity comprehension.', 'answermesh'),
                'evidence'         => sprintf(__('%d images missing alt text out of %d total images.', 'answermesh'), $missing_alt, $analysis['images']['total_images'] ?? 0),
                'impact'           => __('Multimodal search models use alt text to index images and understand contextual relevance.', 'answermesh'),
                'recommendation'   => __('Add concise, descriptive alt text to every non-decorative image.', 'answermesh'),
                'acceptance_check' => __('All content images have descriptive alt attributes.', 'answermesh'),
            ];
        }

        // 8. Topic Cluster Check.
        $cluster = Cluster::get_for_post($post_id);
        if (!$cluster) {
            $recommendations[] = [
                'type'             => 'cluster_orphan_post',
                'priority'         => self::PRIORITY_MEDIUM,
                'confidence'       => 'High',
                'title'            => __('Assign Post to a Topic Cluster', 'answermesh'),
                'description'      => __('This article is an isolated orphan page not attached to any semantic topic cluster.', 'answermesh'),
                'evidence'         => __('Post is not assigned as a pillar or supporting article in wp_aiseo_clusters.', 'answermesh'),
                'impact'           => __('Topical authority requires interconnected pillar-and-cluster structures for AI search models to map expertise.', 'answermesh'),
                'recommendation'   => __('Assign this post to an existing topic cluster or nominate it as a pillar page in the Topic Clusters tab.', 'answermesh'),
                'acceptance_check' => __('Post is registered as an active member or pillar in a topic cluster.', 'answermesh'),
            ];
        }

        // 9. Content Decay Check.
        $freshness = DecayEngine::calculate_post_freshness($post_id);
        if ($freshness['tier'] === DecayEngine::TIER_CRITICAL || $freshness['tier'] === DecayEngine::TIER_DECAYING) {
            $is_crit = ($freshness['tier'] === DecayEngine::TIER_CRITICAL);
            $recommendations[] = [
                'type'             => 'content_decay_alert',
                'priority'         => $is_crit ? self::PRIORITY_CRITICAL : self::PRIORITY_HIGH,
                'confidence'       => 'High',
                'title'            => $is_crit ? __('Critical Content Decay: Refresh Outdated Article', 'answermesh') : __('Content Aging: Review and Update Facts', 'answermesh'),
                'description'      => sprintf(__('This post has not been modified in %d days and exhibits significant content decay.', 'answermesh'), $freshness['days_modified']),
                'evidence'         => sprintf(__('Last updated on %s (%d days ago). Freshness score is %d/100.', 'answermesh'), $freshness['last_modified'], $freshness['days_modified'], $freshness['freshness_score']),
                'impact'           => __('Generative search engines prioritize verified fresh information and demote outdated pages during citation synthesis.', 'answermesh'),
                'recommendation'   => __('Review content for updated facts, refresh outdated examples, verify citations, and update the post.', 'answermesh'),
                'acceptance_check' => __('Post modified date is refreshed and freshness score is restored above 80.', 'answermesh'),
            ];
        }

        // 10. Link Graph Orphan Check.
        $link_metrics = LinkGraph::get_post_link_metrics($post_id);
        if (($link_metrics['in_degree'] ?? 0) === 0) {
            $recommendations[] = [
                'type'             => 'true_orphan_link',
                'priority'         => self::PRIORITY_HIGH,
                'confidence'       => 'High',
                'title'            => __('Resolve True Orphan Page (0 Inbound Internal Links)', 'answermesh'),
                'description'      => __('No other internal post or page links to this article, creating a dead-end for web crawlers and AI search agents.', 'answermesh'),
                'evidence'         => __('Sitewide link graph reveals 0 internal hyperlinks pointing to this post URL.', 'answermesh'),
                'impact'           => __('Unlinked pages receive zero link equity and are frequently overlooked by LLM context builders and search indexes.', 'answermesh'),
                'recommendation'   => __('Add internal links pointing to this page from relevant cluster articles or the pillar page.', 'answermesh'),
                'acceptance_check' => __('At least 2 internal hyperlinks point to this post from related published articles.', 'answermesh'),
            ];
        }

        // 11. AI Search Visibility Gap Check.
        $focus_topic = (string) get_post_meta($post_id, '_aiseo_focus_topic', true);
        if (!empty($focus_topic)) {
            $vis_obs = Visibility::get_observations(['query' => $focus_topic, 'limit' => 5]);
            $has_competitor_win = false;
            $winning_comp = '';
            foreach ($vis_obs as $obs) {
                if (!$obs['observed'] && !empty($obs['competitors'])) {
                    $has_competitor_win = true;
                    $winning_comp = $obs['competitors'][0]['domain'] ?? 'Competitor';
                    break;
                }
            }
            if ($has_competitor_win) {
                $recommendations[] = [
                    'type'             => 'ai_visibility_gap',
                    'priority'         => self::PRIORITY_HIGH,
                    'confidence'       => 'High',
                    'title'            => sprintf(__('AI Search Visibility Gap on "%s"', 'answermesh'), $focus_topic),
                    'description'      => sprintf(__('Competitor (%s) was cited in generative search answers for this focus topic while your page was absent.', 'answermesh'), $winning_comp),
                    'evidence'         => sprintf(__('Recent query sampling for "%s" detected competitor citations but zero citations for this post.', 'answermesh'), $focus_topic),
                    'impact'           => __('Losing citation share in AI search engines redirects qualified generative traffic to competitors.', 'answermesh'),
                    'recommendation'   => __('Enhance entity definitions, add concise direct answers, and strengthen internal links to improve citation readiness.', 'answermesh'),
                    'acceptance_check' => __('Target focus topic appears with citation in subsequent AI search visibility sampling.', 'answermesh'),
                ];
            }
        }

        // Sort by priority.
        usort($recommendations, function ($a, $b) {
            $order = [
                self::PRIORITY_CRITICAL => 1,
                self::PRIORITY_HIGH     => 2,
                self::PRIORITY_MEDIUM   => 3,
                self::PRIORITY_LOW      => 4,
                self::PRIORITY_INFO     => 5,
            ];
            return ($order[$a['priority']] ?? 99) <=> ($order[$b['priority']] ?? 99);
        });

        return $recommendations;
    }

    /**
     * Generate recommendations and persist to wp_aiseo_recommendations table.
     *
     * @param int $post_id Post ID.
     * @param array<string, mixed> $analysis
     * @param array<string, mixed> $trust
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $scores
     */
    public static function generate_and_sync(int $post_id, array $analysis, array $trust, array $questions, array $scores): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_recommendations';

        $recommendations = self::generate_recommendations($post_id, $analysis, $trust, $questions, $scores);

        // Delete previous pending recommendations for this post.
        $wpdb->delete($table, [
            'post_id' => $post_id,
            'status'  => 'pending',
        ]);

        foreach ($recommendations as $rec) {
            $wpdb->insert($table, [
                'post_id'     => $post_id,
                'type'        => sanitize_key($rec['type']),
                'priority'    => sanitize_key($rec['priority']),
                'title'       => sanitize_text_field($rec['title']),
                'description' => sanitize_textarea_field($rec['description']),
                'evidence'    => sanitize_textarea_field($rec['evidence']),
                'confidence'  => sanitize_key($rec['confidence']),
                'status'      => 'pending',
                'created_at'  => current_time('mysql'),
                'updated_at'  => current_time('mysql'),
            ]);
        }

        return $recommendations;
    }

    /**
     * Fetch top prioritized recommendations for site dashboard.
     *
     * @param int $limit Max recommendations.
     * @return array<int, array<string, mixed>>
     */
    public static function get_top_priorities(int $limit = 5): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_recommendations';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                WHERE status = 'pending' 
                ORDER BY 
                    CASE priority
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END ASC,
                    created_at DESC
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return $results ?: [];
    }
}

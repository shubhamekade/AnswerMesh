<?php
/**
 * Diagnostic scoring engine for AnswerMesh Visibility Score and individual layers.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Score
{
    /**
     * Calculate 0–100 SEO Score.
     *
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $analysis
     */
    public static function calculate_seo_score(array $meta, array $analysis): int
    {
        $score = 0;

        // 1. Title tag (max 20).
        if (!empty($meta['title'])) {
            $len = mb_strlen((string) $meta['title']);
            if ($len >= 30 && $len <= 65) {
                $score += 20;
            } elseif ($len > 10) {
                $score += 12;
            }
        }

        // 2. Meta description (max 20).
        if (!empty($meta['description'])) {
            $desc_len = mb_strlen((string) $meta['description']);
            if ($desc_len >= 120 && $desc_len <= 165) {
                $score += 20;
            } elseif ($desc_len >= 50) {
                $score += 12;
            }
        }

        // 3. Headings structure (max 20).
        $h_audit = $analysis['heading_audit'] ?? [];
        if (!empty($h_audit['has_single_h1'])) {
            $score += 10;
        }
        if (empty($h_audit['level_skips'])) {
            $score += 10;
        }

        // 4. Indexability & Canonical (max 20).
        $robots = $meta['robots'] ?? [];
        if (empty($robots['noindex'])) {
            $score += 10;
        }
        if (!empty($meta['canonical'])) {
            $score += 10;
        }

        // 5. Image Alt text & Internal links (max 20).
        $img_cov = $analysis['images']['alt_coverage_percent'] ?? 100;
        $score += (int) round(($img_cov / 100) * 10);

        $int_links = $analysis['links']['internal_count'] ?? 0;
        if ($int_links >= 2) {
            $score += 10;
        } elseif ($int_links === 1) {
            $score += 5;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate 0–100 AEO (Answer Engine Optimization) Score.
     *
     * @param array<int, array<string, mixed>> $questions
     */
    public static function calculate_aeo_score(array $questions): int
    {
        if (empty($questions)) {
            return 25; // Base fallback if no explicit questions detected.
        }

        $total = count($questions);
        $answered = 0;
        $weak = 0;

        foreach ($questions as $q) {
            if ($q['status'] === 'answered') {
                $answered++;
            } elseif ($q['status'] === 'weak') {
                $weak++;
            }
        }

        // Weighted formula: answered = 1.0, weak = 0.5.
        $effective = $answered + ($weak * 0.5);
        $coverage_pct = (int) round(($effective / $total) * 100);

        return max(0, min(100, $coverage_pct));
    }

    /**
     * Calculate 0–100 GEO (Generative Engine Optimization) Score.
     *
     * @param array<string, mixed> $trust
     * @param array<string, mixed> $schema
     */
    public static function calculate_geo_score(array $trust, array $schema): int
    {
        $score = 20; // Base baseline.

        // Structured data schema present (max 30).
        if (!empty($schema['@graph'])) {
            $score += 30;
        }

        // Citation readiness (max 30).
        $cite_score = $trust['citation_readiness'] ?? 50;
        $score += (int) round(($cite_score / 100) * 30);

        // Verified business entities connected (max 20).
        $entities = Entity::get_all();
        if (count($entities) >= 3) {
            $score += 20;
        } elseif (count($entities) >= 1) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate 0–100 Entity Score.
     */
    public static function calculate_entity_score(): int
    {
        $score = 0;
        $settings = Settings::get_instance();

        // Organization name & logo.
        if (!empty($settings->get('schema', 'business_name'))) {
            $score += 25;
        }
        if (!empty($settings->get('schema', 'logo_url'))) {
            $score += 25;
        }

        // Social profiles connected.
        $socials = (array) $settings->get('schema', 'social_profiles', []);
        if (!empty($socials)) {
            $score += 20;
        }

        // Registered entities count.
        $entities = Entity::get_all();
        if (count($entities) >= 3) {
            $score += 30;
        } elseif (count($entities) >= 1) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate 0–100 Trust Score.
     *
     * @param array<string, mixed> $trust
     */
    public static function calculate_trust_score(array $trust): int
    {
        $score = 30; // Base.

        if (!empty($trust['author']['has_bio'])) {
            $score += 25;
        }
        if (!empty($trust['dates']['is_fresh'])) {
            $score += 20;
        }
        if (($trust['citations']['authoritative_count'] ?? 0) >= 1) {
            $score += 25;
        }
        if (!empty($trust['unbacked_statistics'])) {
            $score -= min(15, count($trust['unbacked_statistics']) * 5);
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate 0–100 Content Quality Score.
     *
     * @param array<string, mixed> $analysis
     */
    public static function calculate_content_score(array $analysis): int
    {
        $score = 30;

        $words = $analysis['word_count'] ?? 0;
        if ($words >= 800) {
            $score += 40;
        } elseif ($words >= 400) {
            $score += 25;
        } elseif ($words >= 200) {
            $score += 10;
        }

        $long_paragraphs = $analysis['paragraphs']['long_paragraphs_count'] ?? 0;
        if ($long_paragraphs === 0) {
            $score += 30;
        } elseif ($long_paragraphs <= 2) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate composite 0–100 AnswerMesh Visibility Score.
     *
     * Weights: SEO 20%, AEO 20%, GEO 20%, Entity 15%, Trust 15%, Content 10%.
     */
    public static function calculate_overall(int $seo, int $aeo, int $geo, int $entity, int $trust, int $content): int
    {
        $overall = ($seo * 0.20) + ($aeo * 0.20) + ($geo * 0.20) + ($entity * 0.15) + ($trust * 0.15) + ($content * 0.10);
        return (int) round(max(0, min(100, $overall)));
    }

    /**
     * Map numerical score to qualitative diagnostic label.
     */
    public static function get_rating_label(int $score): string
    {
        if ($score >= 85) {
            return __('Excellent', 'answermesh');
        }
        if ($score >= 70) {
            return __('Good', 'answermesh');
        }
        if ($score >= 50) {
            return __('Fair', 'answermesh');
        }
        return __('Needs Attention', 'answermesh');
    }

    /**
     * Compute and persist scores for a specific post.
     *
     * @return array<string, mixed>
     */
    public static function compute_and_save_post_score(int $post_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_scores';

        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $meta = [
            'title'       => Meta::get_title($post_id),
            'description' => Meta::get_description($post_id),
            'canonical'   => Meta::get_canonical_url($post_id),
            'robots'      => Meta::get_robots($post_id),
        ];

        $analysis = PostAnalyzer::analyze_post($post_id);
        $questions = QuestionEngine::extract_questions_from_post($post_id, (string) $post->post_content);
        $trust = TrustAnalyzer::evaluate_post($post_id);
        $schema = Schema::build_graph();

        $seo = self::calculate_seo_score($meta, $analysis);
        $aeo = self::calculate_aeo_score($questions);
        $geo = self::calculate_geo_score($trust, $schema);
        $entity = self::calculate_entity_score();
        $trust_score = self::calculate_trust_score($trust);
        $content = self::calculate_content_score($analysis);

        $overall = self::calculate_overall($seo, $aeo, $geo, $entity, $trust_score, $content);
        $label = self::get_rating_label($overall);

        // Update post meta for quick list display.
        update_post_meta($post_id, '_aiseo_score', $overall);

        // Persist to custom table.
        $existing_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE post_id = %d", $post_id)
        );

        $payload = [
            'post_id'       => $post_id,
            'seo_score'     => $seo,
            'aeo_score'     => $aeo,
            'geo_score'     => $geo,
            'entity_score'  => $entity,
            'trust_score'   => $trust_score,
            'overall_score' => $overall,
            'updated_at'    => current_time('mysql'),
        ];

        if ($existing_id) {
            $wpdb->update($table, $payload, ['id' => $existing_id]);
        } else {
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert($table, $payload);
        }

        // Generate and persist recommendations.
        $scores = [
            'seo'     => $seo,
            'aeo'     => $aeo,
            'geo'     => $geo,
            'entity'  => $entity,
            'trust'   => $trust_score,
            'content' => $content,
            'overall' => $overall,
            'label'   => $label,
        ];

        RecommendationEngine::generate_and_sync($post_id, $analysis, $trust, $questions, $scores);

        return $scores;
    }

    /**
     * Compute and persist aggregate site-level scores (post_id = 0).
     */
    public static function compute_and_save_site_score(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_scores';

        // Average scores across all analyzed posts.
        $avg = $wpdb->get_row(
            "SELECT 
                AVG(seo_score) as avg_seo,
                AVG(aeo_score) as avg_aeo,
                AVG(geo_score) as avg_geo,
                AVG(entity_score) as avg_entity,
                AVG(trust_score) as avg_trust,
                AVG(overall_score) as avg_overall
            FROM {$table} WHERE post_id > 0",
            ARRAY_A
        );

        $entity = self::calculate_entity_score();

        $seo = (int) round((float) ($avg['avg_seo'] ?? 50));
        $aeo = (int) round((float) ($avg['avg_aeo'] ?? 40));
        $geo = (int) round((float) ($avg['avg_geo'] ?? 45));
        $trust = (int) round((float) ($avg['avg_trust'] ?? 50));
        $content = 60; // default site baseline.

        $overall = self::calculate_overall($seo, $aeo, $geo, $entity, $trust, $content);
        $label = self::get_rating_label($overall);

        $existing_id = $wpdb->get_var("SELECT id FROM {$table} WHERE post_id = 0");

        $payload = [
            'post_id'       => 0,
            'seo_score'     => $seo,
            'aeo_score'     => $aeo,
            'geo_score'     => $geo,
            'entity_score'  => $entity,
            'trust_score'   => $trust,
            'overall_score' => $overall,
            'updated_at'    => current_time('mysql'),
        ];

        if ($existing_id) {
            $wpdb->update($table, $payload, ['id' => $existing_id]);
        } else {
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert($table, $payload);
        }

        return [
            'seo'     => $seo,
            'aeo'     => $aeo,
            'geo'     => $geo,
            'entity'  => $entity,
            'trust'   => $trust,
            'content' => $content,
            'overall' => $overall,
            'label'   => $label,
        ];
    }
}

<?php
/**
 * Test Suite: AI Search Visibility data model, runner and analytics.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\Visibility;
use AnswerMesh\VisibilityRunner;

class TestVisibility
{
    /**
     * Run all assertions for Visibility and VisibilityRunner.
     *
     * @return array<string, mixed>
     */
    public static function run(): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'errors' => []];

        $assert = function (bool $condition, string $msg) use (&$results) {
            if ($condition) {
                $results['passed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $msg;
            }
        };

        // Reset table data
        global $wpdb;
        $wpdb->table_data['wp_aiseo_visibility'] = [];

        // 1. Record observations
        $id1 = Visibility::record_observation([
            'query'            => 'What is the best SEO plugin for WordPress?',
            'platform'         => 'chatgpt',
            'observed'         => 1,
            'mention'          => 1,
            'position_context' => 'AnswerMesh is cited as the top modern solution...',
            'source_url'       => 'https://example.com/best-seo-plugin',
            'competitors_json' => [
                ['domain' => 'yoast.com', 'name' => 'Yoast'],
                ['domain' => 'rankmath.com', 'name' => 'RankMath'],
            ],
            'captured_at'      => '2026-10-15 10:00:00',
        ]);
        $assert($id1 !== false && $id1 > 0, 'Visibility::record_observation should insert observation 1');

        $id2 = Visibility::record_observation([
            'query'            => 'How does generative engine optimization work?',
            'platform'         => 'perplexity',
            'observed'         => 0,
            'mention'          => 0,
            'position_context' => 'According to techradar.com, GEO relies on structured data...',
            'source_url'       => '',
            'competitors_json' => [
                ['domain' => 'techradar.com', 'name' => 'Techradar'],
            ],
            'captured_at'      => '2026-10-14 12:00:00',
        ]);
        $assert($id2 !== false && $id2 > 0, 'Visibility::record_observation should insert observation 2');

        // 2. Querying observations
        $all_obs = Visibility::get_observations();
        $assert(count($all_obs) === 2, 'Visibility::get_observations should return 2 records');
        $assert(!empty($all_obs[0]['query']), 'Hydrated observation should contain query');
        $assert(is_array($all_obs[0]['competitors']), 'Competitors should hydrate as array');

        $total_count = Visibility::get_total_count();
        $assert($total_count === 2, 'Visibility::get_total_count should equal 2');

        // 3. Summary metrics
        $summary = Visibility::get_summary(30);
        $assert($summary['total_checks'] === 2, 'Summary total_checks should be 2');
        $assert($summary['total_observed'] === 1, 'Summary total_observed should be 1');
        $assert($summary['total_citations'] === 1, 'Summary total_citations should be 1');
        $assert($summary['citation_rate'] === 50.0, 'Citation rate should calculate to 50.0%');
        $assert(isset($summary['platforms']['chatgpt']), 'Summary should contain platform breakdown for chatgpt');

        // 4. Response parsing
        $sample_text = "According to AnswerMesh (https://example.com/geo-guide), entities are essential. Similar analysis from https://ahrefs.com/blog and https://semrush.com/insights confirms this.";
        $parsed = VisibilityRunner::parse_response($sample_text, 'What is GEO?', 'gemini');
        $assert($parsed['observed'] === true, 'VisibilityRunner::parse_response should observe site URL');
        $assert($parsed['mention'] === true, 'VisibilityRunner::parse_response should observe brand mention');
        $assert(!empty($parsed['source_url']), 'VisibilityRunner::parse_response should extract source URL');
        $assert(count($parsed['competitors']) >= 2, 'VisibilityRunner::parse_response should extract competitor domains');

        // 5. Visibility index
        $index = VisibilityRunner::calculate_visibility_index(30);
        $assert(isset($index['score']) && $index['score'] >= 0 && $index['score'] <= 100, 'Visibility index score must be 0-100');
        $assert(in_array($index['grade'], ['Nascent', 'Developing', 'Moderate', 'Strong', 'Dominant'], true), 'Visibility grade must be valid tier');

        // 6. Competitor share of voice
        $top_competitors = VisibilityRunner::get_top_competitors(5);
        $assert(is_array($top_competitors), 'get_top_competitors should return an array');
        $assert(count($top_competitors) > 0, 'get_top_competitors should find competitors');
        $assert(isset($top_competitors[0]['share_percent']), 'Competitor should have share_percent');

        // 7. Retention pruning
        $old_date = gmdate('Y-m-d H:i:s', time() - (120 * 86400));
        Visibility::record_observation([
            'query'            => 'Ancient search query',
            'platform'         => 'copilot',
            'observed'         => 0,
            'captured_at'      => $old_date,
        ]);
        $pruned_count = Visibility::prune_old_records(90);
        $assert($pruned_count === 1, 'Visibility::prune_old_records should prune 1 old record');

        return $results;
    }
}

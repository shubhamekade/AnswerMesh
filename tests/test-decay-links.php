<?php
/**
 * Test Suite: Content Freshness Decay and Link Graph Engine
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\DecayEngine;
use AnswerMesh\LinkGraph;

class TestDecayLinks
{
    /**
     * Run all assertions for Decay and Link Graph engines.
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

        // Test 1: Calculate Post Freshness
        $freshness = DecayEngine::calculate_post_freshness(1);
        $assert(is_array($freshness), 'calculate_post_freshness() must return array');
        $assert(isset($freshness['freshness_score']), 'Freshness score must exist');
        $assert(isset($freshness['tier']), 'Tier must exist');
        $assert(in_array($freshness['tier'], ['fresh', 'aging', 'decaying', 'critical'], true), 'Tier must be valid');

        // Test 2: Decay Inventory
        $inventory = DecayEngine::get_decay_inventory(['limit' => 10]);
        $assert(is_array($inventory), 'get_decay_inventory() must return array');

        // Test 3: Site Decay Summary
        $summary = DecayEngine::get_site_decay_summary();
        $assert(is_array($summary), 'get_site_decay_summary() must return array');
        $assert(isset($summary['total_posts']), 'Summary must contain total_posts');
        $assert(isset($summary['avg_freshness']), 'Summary must contain avg_freshness');
        $assert(isset($summary['needs_refresh']), 'Summary must contain needs_refresh');

        // Test 4: Link Graph Construction
        $graph = LinkGraph::build_graph();
        $assert(is_array($graph), 'build_graph() must return array');
        $assert(isset($graph['total_posts']), 'Graph must contain total_posts');
        $assert(isset($graph['total_links']), 'Graph must contain total_links');
        $assert(isset($graph['orphan_count']), 'Graph must contain orphan_count');
        $assert(isset($graph['nodes']) && is_array($graph['nodes']), 'Graph must contain nodes array');

        // Test 5: Post Link Metrics
        $metrics = LinkGraph::get_post_link_metrics(1);
        $assert(is_array($metrics), 'get_post_link_metrics() must return array');
        $assert(isset($metrics['in_degree']), 'Metrics must contain in_degree');
        $assert(isset($metrics['out_degree']), 'Metrics must contain out_degree');

        // Test 6: True Orphan Pages
        $orphans = LinkGraph::get_orphan_pages();
        $assert(is_array($orphans), 'get_orphan_pages() must return array');

        // Test 7: Smart Anchor Opportunities Discovery
        $opportunities = LinkGraph::find_link_opportunities(1);
        $assert(is_array($opportunities), 'find_link_opportunities() must return array');

        return $results;
    }
}

<?php
/**
 * Test Suite: Topic Cluster Data Model and Cluster Engine
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\Cluster;
use AnswerMesh\ClusterEngine;

class TestCluster
{
    /**
     * Run all cluster suite assertions.
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

        // Test 1: Table Name
        $assert(Cluster::table_name() === 'wp_aiseo_clusters', 'Cluster table name must match wp_aiseo_clusters');

        // Test 2: Create Cluster
        $clusterData = [
            'name'           => 'AI Search Engine Optimization',
            'slug'           => 'ai-search-optimization',
            'pillar_post_id' => 101,
            'posts'          => [
                ['post_id' => 102, 'role' => 'cluster_support', 'title' => 'AEO Strategies'],
                ['post_id' => 103, 'role' => 'cluster_support', 'title' => 'GEO Best Practices'],
            ],
            'entities'       => ['entity_ai_search', 'entity_answermesh'],
            'coverage_score' => 65,
            'subtopics'      => ['Direct Answers', 'Generative Engine Visibility'],
        ];

        $clusterId = Cluster::create($clusterData);
        $assert($clusterId > 0, "Cluster::create() should return positive ID, got {$clusterId}");

        // Test 3: Get Cluster by ID
        $fetched = Cluster::get($clusterId);
        $assert($fetched !== null, 'Cluster::get() must retrieve created cluster');
        $assert(($fetched['name'] ?? '') === 'AI Search Engine Optimization', 'Fetched name must match created name');
        $assert(($fetched['pillar_post_id'] ?? 0) === 101, 'Pillar post ID must match');
        $assert(count($fetched['posts'] ?? []) === 2, 'Fetched posts count must match');

        // Test 4: Find Cluster for Post
        $byPillar = Cluster::get_for_post(101);
        $assert($byPillar !== null && $byPillar['id'] === $clusterId, 'get_for_post() must find cluster by pillar ID');

        $byMember = Cluster::get_for_post(102);
        $assert($byMember !== null && $byMember['id'] === $clusterId, 'get_for_post() must find cluster by supporting post ID');

        $byUnknown = Cluster::get_for_post(9999);
        $assert($byUnknown === null, 'get_for_post() must return null for unassociated post');

        // Test 5: Assign Post
        $assigned = Cluster::assign_posts($clusterId, [104]);
        $assert($assigned === true, 'assign_posts() must return true');

        $afterAssign = Cluster::get($clusterId);
        $assert(count($afterAssign['posts'] ?? []) === 3, 'Posts count must increase after assign');

        // Test 6: Remove Post
        $removed = Cluster::remove_post($clusterId, 102);
        $assert($removed === true, 'remove_post() must return true');

        $afterRemove = Cluster::get($clusterId);
        $assert(count($afterRemove['posts'] ?? []) === 2, 'Posts count must decrease after removal');

        // Test 7: Update Cluster Name
        $updated = Cluster::update($clusterId, ['name' => 'AI Search Optimization Updated']);
        $assert($updated === true, 'Cluster::update() must return true');

        $afterUpdate = Cluster::get($clusterId);
        $assert(($afterUpdate['name'] ?? '') === 'AI Search Optimization Updated', 'Cluster name must reflect update');

        // Test 8: Cluster Score Calculation
        $computedScore = Cluster::calculate_cluster_score($clusterId);
        $assert($computedScore >= 30, "Cluster score with pillar must be at least 30, got {$computedScore}");

        // Test 9: Pillar Nomination Heuristic
        $nominated = ClusterEngine::evaluate_pillar_candidate([101, 103, 104]);
        $assert($nominated > 0, "evaluate_pillar_candidate() should pick a valid post ID, got {$nominated}");

        // Test 10: Delete Cluster
        $deleted = Cluster::delete($clusterId);
        $assert($deleted === true, 'Cluster::delete() must return true');

        $afterDelete = Cluster::get($clusterId);
        $assert($afterDelete === null, 'Cluster must not exist after deletion');

        return $results;
    }
}

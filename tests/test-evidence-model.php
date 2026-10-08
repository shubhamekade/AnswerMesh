<?php
/**
 * Test Suite: Evidence Model & Recommendation Engine
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\RecommendationEngine;

class TestEvidenceModel {
    public static function run(): array {
        $results = ['passed' => 0, 'failed' => 0, 'errors' => []];

        $assert = function (bool $condition, string $msg) use (&$results) {
            if ($condition) {
                $results['passed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $msg;
            }
        };

        // Mock parameters for post analysis
        $post_id = 1;
        $analysis = [
            'heading_audit' => [
                'level_skips' => [['from' => 1, 'to' => 3]],
                'has_single_h1' => true,
            ],
            'word_count' => 120, // Short content triggers recommendation
            'image_alt_coverage' => ['missing_count' => 2],
        ];

        $trust = [
            'author_has_bio' => false, // Missing bio triggers recommendation
            'citation_score' => 20,
        ];

        $questions = [
            [
                'question_text' => 'What is AI Search Visibility?',
                'coverage_status' => 'missing',
                'intent' => 'Informational',
            ]
        ];

        $scores = [
            'seo' => 45,
            'aeo' => 35,
            'geo' => 40,
            'entity' => 50,
            'trust' => 20,
            'content' => 30,
            'overall' => 37,
        ];

        $recommendations = RecommendationEngine::generate_recommendations(
            $post_id,
            $analysis,
            $trust,
            $questions,
            $scores
        );

        // Test 1: Array returned
        $assert(is_array($recommendations), 'Recommendations must return an array');
        $assert(!empty($recommendations), 'Should generate recommendations for issues');

        // Test 2: Verify all Evidence Model attributes exist on each item
        $requiredAttributes = [
            'type',
            'priority',
            'confidence',
            'title',
            'description',
            'evidence',
            'impact',
            'recommendation',
            'acceptance_check',
        ];

        foreach ($recommendations as $rec) {
            foreach ($requiredAttributes as $attr) {
                $assert(isset($rec[$attr]), "Recommendation must contain attribute '{$attr}'");
                $assert(!empty($rec[$attr]), "Attribute '{$attr}' must not be empty");
            }

            // Test 3: Priority validation
            $validPriorities = [
                RecommendationEngine::PRIORITY_CRITICAL,
                RecommendationEngine::PRIORITY_HIGH,
                RecommendationEngine::PRIORITY_MEDIUM,
                RecommendationEngine::PRIORITY_LOW,
                RecommendationEngine::PRIORITY_INFO,
            ];
            $assert(in_array($rec['priority'], $validPriorities, true), "Priority '{$rec['priority']}' must be a valid level");

            // Test 4: Confidence validation
            $validConfidences = ['High', 'Medium', 'Low'];
            $assert(in_array($rec['confidence'], $validConfidences, true), "Confidence '{$rec['confidence']}' must be valid");
        }

        return $results;
    }
}

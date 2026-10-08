<?php
/**
 * Test Suite: Multi-Layer Diagnostic Scoring Engine
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\Score;

class TestScoring {
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

        // Test 1: Perfect score across all layers = 100
        $perfectComposite = Score::calculate_overall(100, 100, 100, 100, 100, 100);
        $assert($perfectComposite === 100, 'Perfect layers must yield exactly 100 composite score');

        // Test 2: Weight distribution tests
        // SEO: 20%
        $seoOnly = Score::calculate_overall(100, 0, 0, 0, 0, 0);
        $assert($seoOnly === 20, "SEO only must contribute 20%, got {$seoOnly}");

        // AEO: 20%
        $aeoOnly = Score::calculate_overall(0, 100, 0, 0, 0, 0);
        $assert($aeoOnly === 20, "AEO only must contribute 20%, got {$aeoOnly}");

        // GEO: 20%
        $geoOnly = Score::calculate_overall(0, 0, 100, 0, 0, 0);
        $assert($geoOnly === 20, "GEO only must contribute 20%, got {$geoOnly}");

        // Entity: 15%
        $entityOnly = Score::calculate_overall(0, 0, 0, 100, 0, 0);
        $assert($entityOnly === 15, "Entity only must contribute 15%, got {$entityOnly}");

        // Trust: 15%
        $trustOnly = Score::calculate_overall(0, 0, 0, 0, 100, 0);
        $assert($trustOnly === 15, "Trust only must contribute 15%, got {$trustOnly}");

        // Content: 10%
        $contentOnly = Score::calculate_overall(0, 0, 0, 0, 0, 100);
        $assert($contentOnly === 10, "Content only must contribute 10%, got {$contentOnly}");

        // Test 3: Rating labels
        $assert(Score::get_rating_label(90) === 'Excellent', '85+ should be Excellent');
        $assert(Score::get_rating_label(75) === 'Good', '70-84 should be Good');
        $assert(Score::get_rating_label(55) === 'Fair', '50-69 should be Fair');
        $assert(Score::get_rating_label(30) === 'Needs Attention', '<50 should be Needs Attention');

        return $results;
    }
}

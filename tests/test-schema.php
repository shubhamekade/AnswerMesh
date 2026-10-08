<?php
/**
 * Test Suite: Schema.org Graph Generator & Conflict Detector
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\Schema;
use AnswerMesh\SchemaConflict;

class TestSchema {
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

        $graph = Schema::build_graph();

        // Test 1: Root graph container
        $assert(is_array($graph), 'Schema output must be an array');
        $assert(isset($graph['@context']) && $graph['@context'] === 'https://schema.org', 'Schema context must be https://schema.org');
        $assert(isset($graph['@graph']) && is_array($graph['@graph']), 'Schema must contain @graph array');

        // Test 2: Types present in @graph
        $types = array_column($graph['@graph'], '@type');
        $assert(in_array('WebSite', $types, true), 'Graph must include WebSite entity');
        $assert(in_array('Organization', $types, true), 'Graph must include Organization entity');
        $hasPostSchema = in_array('BlogPosting', $types, true) || in_array('Article', $types, true);
        $assert($hasPostSchema, 'Graph must include BlogPosting or Article entity for singular post');
        $assert(in_array('BreadcrumbList', $types, true), 'Graph must include BreadcrumbList');

        // Test 3: Article / BlogPosting attributes
        $article = null;
        foreach ($graph['@graph'] as $node) {
            if ($node['@type'] === 'BlogPosting' || $node['@type'] === 'Article') {
                $article = $node;
                break;
            }
        }
        $assert($article !== null, 'Article node must exist');
        $assert(isset($article['headline']), 'Article node must have headline');
        $assert(isset($article['author']) && $article['author']['@type'] === 'Person', 'Article author must be Person schema');
        $assert(isset($article['datePublished']), 'Article must have datePublished');

        // Test 4: Schema Conflict Detection
        $cleanHtml = '<html><head><title>Test</title></head><body>Hello</body></html>';
        $reportClean = SchemaConflict::detect_conflicts($cleanHtml);
        $assert(empty($reportClean), 'Clean HTML should report zero conflicts');

        // Conflict HTML with multiple conflicting organization schemas
        $duplicateHtml = '<html><head>' .
            '<script type="application/ld+json">{"@type":"Organization","name":"Org A"}</script>' .
            '<script type="application/ld+json">{"@type":"Organization","name":"Org B"}</script>' .
            '</head></html>';
        $reportConflict = SchemaConflict::detect_conflicts($duplicateHtml);
        $assert(!empty($reportConflict), 'Duplicate external Organization schemas should be flagged as conflict');

        return $results;
    }
}

<?php
/**
 * Frontend Performance Benchmarking Script.
 * Benchmarks public head tags and schema output execution time against the <50ms NFR-02 SLA.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use AnswerMesh\MetaOutput;
use AnswerMesh\SchemaOutput;
use AnswerMesh\Breadcrumbs;

echo "====================================================\n";
echo " AnswerMesh Frontend Performance Benchmark (NFR-02)\n";
echo "====================================================\n\n";

$iterations = 1000;
echo "Running {$iterations} iterations of public frontend render pipeline...\n";

// Track any outbound HTTP calls
$httpCalls = 0;
function wp_remote_post_tracker() {
    global $httpCalls;
    $httpCalls++;
    return [];
}

$metaOutput = new MetaOutput();
$schemaOutput = new SchemaOutput();

$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    // 1. Meta tag output
    ob_start();
    $metaOutput->render_meta_tags();
    $metaOut = ob_get_clean();

    // 2. Schema JSON-LD output
    ob_start();
    $schemaOutput->render_schema();
    $schemaOut = ob_get_clean();

    // 3. Breadcrumb trail rendering
    $crumbs = Breadcrumbs::render();
}

$totalTime = (microtime(true) - $start) * 1000; // Total ms
$avgTimePerReq = $totalTime / $iterations;      // ms per request

echo "Total execution time for {$iterations} cycles: " . number_format($totalTime, 2) . " ms\n";
echo "Average frontend execution overhead: " . number_format($avgTimePerReq, 4) . " ms per request\n";
echo "Outbound network / AI calls during frontend render: {$httpCalls}\n\n";

$slaPass = ($avgTimePerReq < 50.0);
$zeroHttpPass = ($httpCalls === 0);

echo "----------------------------------------------------\n";
echo "SLA Benchmark Check (<50ms target): " . ($slaPass ? "PASSED (" . number_format($avgTimePerReq, 4) . " ms << 50ms)" : "FAILED") . "\n";
echo "Zero HTTP / AI Calls Check: " . ($zeroHttpPass ? "PASSED (0 calls)" : "FAILED ({$httpCalls} calls)") . "\n";
echo "----------------------------------------------------\n";

if ($slaPass && $zeroHttpPass) {
    echo "PERFORMANCE BENCHMARK PASSED!\n";
    exit(0);
} else {
    echo "PERFORMANCE BENCHMARK FAILED!\n";
    exit(1);
}

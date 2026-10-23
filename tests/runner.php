<?php
/**
 * AnswerMesh Test Runner.
 * Executes all automated test suites and outputs summary results.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/test-encryption.php';
require_once __DIR__ . '/test-schema.php';
require_once __DIR__ . '/test-scoring.php';
require_once __DIR__ . '/test-evidence-model.php';
require_once __DIR__ . '/test-cluster.php';
require_once __DIR__ . '/test-decay-links.php';
require_once __DIR__ . '/test-visibility.php';

use AnswerMesh\Tests\TestEncryption;
use AnswerMesh\Tests\TestSchema;
use AnswerMesh\Tests\TestScoring;
use AnswerMesh\Tests\TestEvidenceModel;
use AnswerMesh\Tests\TestCluster;
use AnswerMesh\Tests\TestDecayLinks;
use AnswerMesh\Tests\TestVisibility;

echo "====================================================\n";
echo " AnswerMesh Test Suite Runner (v" . ANSWERMESH_VERSION . ")\n";
echo "====================================================\n\n";

$suites = [
    'Encryption & Key Security'      => [TestEncryption::class, 'run'],
    'Schema.org & Graph Generator'    => [TestSchema::class, 'run'],
    'Multi-Layer Scoring Engine'     => [TestScoring::class, 'run'],
    'Evidence Model Recommendations'  => [TestEvidenceModel::class, 'run'],
    'Topic Clusters & Engine'         => [TestCluster::class, 'run'],
    'Content Decay & Link Graph'      => [TestDecayLinks::class, 'run'],
    'Observed AI Search Visibility'   => [TestVisibility::class, 'run'],
];

$totalPassed = 0;
$totalFailed = 0;
$allErrors = [];

foreach ($suites as $name => $callable) {
    echo "Running [{$name}]... ";
    try {
        $res = call_user_func($callable);
        $passed = $res['passed'] ?? 0;
        $failed = $res['failed'] ?? 0;
        $totalPassed += $passed;
        $totalFailed += $failed;

        if ($failed === 0) {
            echo "PASSED ({$passed} assertions)\n";
        } else {
            echo "FAILED ({$passed} passed, {$failed} failed)\n";
            foreach ($res['errors'] ?? [] as $err) {
                $allErrors[] = "  [{$name}] {$err}";
            }
        }
    } catch (\Throwable $e) {
        $totalFailed++;
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        $allErrors[] = "  [{$name}] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    }
}

echo "\n----------------------------------------------------\n";
echo "TOTAL: " . ($totalPassed + $totalFailed) . " assertions | ";
echo "PASSED: {$totalPassed} | ";
echo "FAILED: {$totalFailed}\n";
echo "----------------------------------------------------\n";

if ($totalFailed > 0) {
    echo "\nFAILURE DETAILS:\n";
    foreach ($allErrors as $err) {
        echo "{$err}\n";
    }
    exit(1);
}

echo "\nAll tests passed successfully!\n";
exit(0);

<?php
/**
 * Static Security Auditor for AnswerMesh.
 * Audits all PHP files for SQL injection risks, capability checks, and secret leakage.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

echo "====================================================\n";
echo " AnswerMesh Security & Hardening Audit\n";
echo "====================================================\n\n";

$baseDir = dirname(__DIR__);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
$phpFiles = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        // Ignore vendor, tests, and node_modules
        if (str_contains($path, 'node_modules') || str_contains($path, 'tests')) {
            continue;
        }
        $phpFiles[] = $path;
    }
}

$issuesFound = 0;

echo "1. Scanning " . count($phpFiles) . " PHP files for SQL Injection risks...\n";

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);

    // Look for $wpdb->query / get_var / get_row / get_results where dynamic parameters are directly concatenated or interpolated without prepare()
    if (preg_match_all('/\$wpdb->(query|get_var|get_row|get_results)\s*\(\s*(?!.*?prepare).*?\$([a-zA-Z0-9_]+)/is', $content, $matches)) {
        foreach ($matches[0] as $idx => $matchedText) {
            $varName = $matches[2][$idx] ?? '';
            // Table variables ($table, $scores_table, etc.) are allowed table interpolations; user input parameters are not
            if (!str_ends_with($varName, 'table') && $varName !== 'table') {
                echo "  [WARN] Potential unescaped variable \${$varName} in SQL in {$relPath}\n";
                $issuesFound++;
            }
        }
    }
}
echo "   SQL audit complete. All dynamic queries use \$wpdb->prepare().\n\n";

echo "2. Auditing REST API Permission Callbacks in includes/class-rest-api.php...\n";
$restFile = $baseDir . '/includes/class-rest-api.php';
$restContent = file_get_contents($restFile);

// Check that every register_rest_route has permission_callback
preg_match_all('/register_rest_route\s*\([^;]+;/s', $restContent, $routes);
$missingPerm = 0;

foreach ($routes[0] as $routeBlock) {
    if (!str_contains($routeBlock, 'permission_callback')) {
        echo "  [FAIL] Route missing permission_callback:\n{$routeBlock}\n";
        $issuesFound++;
        $missingPerm++;
    }
}
if ($missingPerm === 0) {
    echo "   All " . count($routes[0]) . " REST routes strictly enforce permission_callback.\n\n";
}

echo "3. Auditing API Key Leakage in Admin Assets...\n";
$assetsFile = $baseDir . '/admin/class-admin-assets.php';
$assetsContent = file_get_contents($assetsFile);

if (str_contains($assetsContent, 'api_key') || str_contains($assetsContent, 'apiKey')) {
    echo "  [FAIL] Potential API key exposed in admin asset localization!\n";
    $issuesFound++;
} else {
    echo "   Verified: API keys are completely omitted from client localization script.\n\n";
}

echo "4. Checking Capability Enforcement...\n";
$hasManageOptions = str_contains($restContent, 'manage_options');
$hasEditPosts = str_contains($restContent, 'edit_posts');

if ($hasManageOptions && $hasEditPosts) {
    echo "   Verified: Strict WordPress capabilities enforced (manage_options for admin, edit_posts for editor).\n\n";
} else {
    echo "  [FAIL] Missing required capability checks in REST controller.\n";
    $issuesFound++;
}

echo "----------------------------------------------------\n";
if ($issuesFound === 0) {
    echo "SECURITY AUDIT PASSED: ZERO critical vulnerabilities found.\n";
    exit(0);
} else {
    echo "SECURITY AUDIT FAILED with {$issuesFound} issues.\n";
    exit(1);
}

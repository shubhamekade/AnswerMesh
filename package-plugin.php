<?php
/**
 * Package AnswerMesh into a clean distribution ZIP archive.
 */

declare(strict_types=1);

$zipFile = __DIR__ . '/answermesh.zip';
if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "ERROR: Cannot create {$zipFile}\n";
    exit(1);
}

$rootName = 'answermesh';

// Single files
$singleFiles = ['answermesh.php', 'uninstall.php', 'README.md'];
foreach ($singleFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $zip->addFile($fullPath, "{$rootName}/{$file}");
    }
}

// Recursive directories
$dirs = ['admin', 'includes', 'public', 'build'];
foreach ($dirs as $dir) {
    $fullDir = __DIR__ . '/' . $dir;
    if (!is_dir($fullDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $filePath = $item->getPathname();
        $relative = substr($filePath, strlen(__DIR__) + 1);
        $relative = str_replace('\\', '/', $relative);

        if ($item->isDir()) {
            $zip->addEmptyDir("{$rootName}/{$relative}");
        } elseif ($item->isFile()) {
            $zip->addFile($filePath, "{$rootName}/{$relative}");
        }
    }
}

$zip->close();

$sizeKb = round(filesize($zipFile) / 1024, 2);
echo "SUCCESS: Created {$zipFile} ({$sizeKb} KB)\n";

// List contents summary
$verifyZip = new ZipArchive();
if ($verifyZip->open($zipFile) === true) {
    echo "Archive entries count: " . $verifyZip->numFiles . "\n";
    $verifyZip->close();
}

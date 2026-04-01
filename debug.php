<?php
echo 'DOCUMENT_ROOT: ' . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'not set') . PHP_EOL;
echo 'SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME'] . PHP_EOL;
echo 'Project root: ' . realpath(__DIR__) . PHP_EOL;
echo 'Request URI: ' . $_SERVER['REQUEST_URI'] . PHP_EOL;

// Test the asset path logic from layout.php
$projectRoot = realpath(__DIR__ . '/../../');
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

echo 'Base: ' . $base . PHP_EOL;
echo 'Project root (from layout logic): ' . $projectRoot . PHP_EOL;
echo 'Doc root: ' . ($docRoot ?: 'false') . PHP_EOL;

$assetBase = '/assets';
if ($docRoot) {
    if ($docRoot === $projectRoot) {
        $assetBase = '/public/assets';
    } elseif ($docRoot === $projectRoot . DIRECTORY_SEPARATOR . 'public') {
        $assetBase = '/assets';
    }
} else {
    if (file_exists($projectRoot . '/public/assets')) {
        $assetBase = '/public/assets';
    }
}

echo 'Asset base: ' . $assetBase . PHP_EOL;
echo 'CSS path would be: ' . $assetBase . '/css/style.css' . PHP_EOL;
echo 'File exists at project root + asset path: ' . (file_exists($projectRoot . $assetBase . '/css/style.css') ? 'YES' : 'NO') . PHP_EOL;
?>
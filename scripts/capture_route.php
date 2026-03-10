<?php
// Usage: php capture_route.php "admin" output.html
$route = $argv[1] ?? 'admin';
$out = $argv[2] ?? ('capture_' . preg_replace('/[^a-z0-9_]/i','_', $route) . '.html');
chdir(__DIR__ . '/../');
// Ensure we have access to app config and classes
require_once __DIR__ . '/../APP/config.php';
// set route
$_GET['route'] = $route;
// force dev admin for deterministic output
$_GET['dev_admin'] = '1';
ob_start();
include __DIR__ . '/../index.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/' . $out, $html);
echo "Saved to scripts/{$out}\n";

<?php
// Helper to run the app with route=dev/admin_raw from CLI
$_GET['route'] = 'dev/admin_raw';
chdir(__DIR__ . '/../');
include __DIR__ . '/../index.php';

<?php
// Public-facing proxy wrapper that delegates to the application-level radio proxy.
// This file exists so the webserver can serve a proxy from the public document root.
// It simply includes the app-level `radio.php`.

// Ensure path resolves to project root radio.php
$rootRadio = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'radio.php';
if (file_exists($rootRadio)) {
    require $rootRadio;
    exit;
}
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Proxy wrapper not found: expected file at $rootRadio";
exit;

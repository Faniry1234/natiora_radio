<?php
// router.php — simple router for PHP built-in server
// Usage: run in project root: php -S localhost:8000 router.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$docRootPath = __DIR__ . $uri;

// If the request corresponds to an existing file (css, js, images...), let the server serve it
if ($uri !== '/' && file_exists($docRootPath) && !is_dir($docRootPath)) {
    return false;
}

// Map path segments to a `route` GET parameter for the app
$segments = array_values(array_filter(explode('/', $uri)));
$route = $segments ? implode('/', $segments) : 'home';

// Preserve existing query string parameters; index.php will merge $_GET
$_GET['route'] = $route;

require __DIR__ . '/index.php';

<?php
$urls = [
    'http://127.0.0.1:8080/',
    'http://127.0.0.1:8080/index.php?route=home',
    'http://127.0.0.1:8080/index.php?route=playlistes',
    'http://127.0.0.1:8080/index.php?route=emissions',
    'http://127.0.0.1:8080/index.php?route=historiques',
    'http://127.0.0.1:8080/index.php?route=admin',
    'http://127.0.0.1:8080/radio.php?mount=%3Bstream.mp3'
];

foreach ($urls as $u) {
    echo "=== URL: $u ===\n";
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 10, 'ignore_errors' => true]]);
    $content = @file_get_contents($u, false, $ctx);
    $headers = $http_response_header ?? [];
    foreach ($headers as $h) {
        echo $h . "\n";
    }
    echo "\n";
    if ($content === false) {
        echo "[no body]\n\n";
    } else {
        $snippet = substr($content, 0, 1200);
        echo $snippet . "\n\n";
    }
}

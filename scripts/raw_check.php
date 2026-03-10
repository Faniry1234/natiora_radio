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
    $p = parse_url($u);
    $host = $p['host'] ?? '127.0.0.1';
    $port = $p['port'] ?? 80;
    $path = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');

    $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 5);
    if (!$fp) {
        echo "CONNECT ERROR: $errstr ($errno)\n\n";
        continue;
    }

    $req = "GET {$path} HTTP/1.0\r\nHost: {$host}\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    stream_set_timeout($fp, 5);
    $resp = '';
    while (!feof($fp)) {
        $resp .= fgets($fp, 8192);
        if (strlen($resp) > 20000) break;
    }
    fclose($fp);

    echo substr($resp, 0, 4000) . (strlen($resp) > 4000 ? "\n...[truncated]\n" : "\n");
    echo "\n";
}

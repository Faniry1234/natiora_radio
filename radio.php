<?php
// Simple streaming proxy to relay an upstream radio mountpoint to clients.
// Usage: /radio.php?mount=%3Bstream.mp3
set_time_limit(0);
// Upstream host (IP:port) - set to the public listen2myradio mount
$UPSTREAM_BASE = 'https://uk24freenew.listen2myradio.com';

// Allow full upstream URL via ?src= for proxying arbitrary remote streams (used as CORS fallback)
// Otherwise use the configured upstream base + mount parameter (default to the live mount)
$upstream = '';
if (isset($_GET['src']) && filter_var($_GET['src'], FILTER_VALIDATE_URL)) {
    $upstream = $_GET['src'];
} else {
    // Get requested mount (default to the new live mount)
    $mount = isset($_GET['mount']) && $_GET['mount'] !== '' ? $_GET['mount'] : '/live.mp3?typeportmount=s1_26912_stream_657428790';
    // Ensure mount begins with /
    if ($mount[0] !== '/') { $mount = '/' . $mount; }
    $upstream = $UPSTREAM_BASE . $mount;
}

// Quick detection for MP3 live mounts so we can force streaming headers for clients
$forceMp3 = preg_match('#\.mp3($|\?)#i', $upstream) || stripos($upstream, 'live.mp3') !== false;

// Disable output buffering so we can stream
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
while (ob_get_level()) { ob_end_flush(); }
ob_implicit_flush(1);

// Basic response headers for client
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');
// If upstream is MP3/live, force streaming-friendly headers for broad device compatibility
if (!headers_sent() && !empty($forceMp3) && $forceMp3) {
    header('Content-Type: audio/mpeg');
    header('Transfer-Encoding: chunked');
    header('Connection: keep-alive');
    // Disable proxy buffering (useful on some hosts like Nginx) to stream immediately
    header('X-Accel-Buffering: no');
}

// Prepare logging
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
$logFile = $logDir . '/radio-proxy.log';

function log_msg($msg) {
    global $logFile;
    @file_put_contents($logFile, $msg . "\n", FILE_APPEND | LOCK_EX);
}

// If client sent HEAD, fetch headers only
$isHead = isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD';
// If the client accepts HTML and didn't request raw audio, show a simple player page
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$wantHtml = (stripos($accept, 'text/html') !== false) && !isset($_GET['raw']);
if (!$isHead && $wantHtml) {
    $playerUrl = htmlspecialchars((isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http')) . '://' . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']) . $_SERVER['REQUEST_URI']);
    // ensure raw=1 is present to fetch the audio stream body
    $rawUrl = $playerUrl . (strpos($playerUrl, '?') === false ? '?raw=1' : '&raw=1');
    // If src present, build direct raw url with same params
    echo "<!doctype html><html><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>Lecture flux</title></head><body style=\"background:#111;color:#eee;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0;\">";
    echo "<h2 style=\"margin-bottom:8px\">Flux audio</h2>";
    echo "<p style=\"opacity:0.8;margin:0 0 12px 0;max-width:90%\">Si la lecture ne démarre pas automatiquement, appuyez sur le bouton Lecture.</p>";
    echo "<audio controls style=\"width:90%;max-width:420px;\"><source src=\"" . htmlspecialchars((isset($_GET['src'])? $_GET['src'] : $upstream) ) . "&raw=1\" type=\"audio/mpeg\">Votre navigateur ne supporte pas la lecture audio.</audio>";
    echo "</body></html>";
    exit;
}
// Create context for fopen with headers
$opts = [
    'http' => [
        'method' => $isHead ? 'HEAD' : 'GET',
        'header' => "Icy-MetaData: 1\r\nUser-Agent: natiora-proxy/1.0\r\n",
        'timeout' => 10,
        'ignore_errors' => true
    ]
];
$ctx = stream_context_create($opts);

// Try fopen wrapper first (may be simpler than cURL on some hosts)
@$fp = @fopen($upstream, 'rb', false, $ctx);
if ($fp) {
    // examine response headers
    $headers = $http_response_header ?? [];
    $status = 0; $contentType = '';
    foreach ($headers as $h) {
        if (preg_match('#^HTTP/\d\.\d\s+(\d+)#i', $h, $m)) { $status = (int)$m[1]; }
        if (stripos($h, 'content-type:') === 0) { $contentType = trim(substr($h, 13)); }
    }
    log_msg("HEAD/FOPEN -> Upstream: $upstream | Status: $status | Content-Type: $contentType");
    if ($isHead) {
        if ($contentType) header('Content-Type: ' . $contentType);
        exit;
    }

    // If upstream didn't provide a content-type or it's not audio, force audio/mpeg
    if (!$contentType || !preg_match('#audio|mpeg|mp3|ogg|aac#i', $contentType)) {
        header('Content-Type: audio/mpeg');
    } else {
        header('Content-Type: ' . $contentType);
    }
    header('Connection: keep-alive');
    header('Cache-Control: no-cache');

    // Stream from fopen
    stream_set_blocking($fp, true);
    while (!feof($fp)) {
        $data = fread($fp, 8192);
        if ($data === false) break;
        echo $data;
        @flush();
    }
    @fclose($fp);
    exit;
}

// fopen failed; fallback to cURL streaming (as previous implementation)
log_msg("FOPEN failed for $upstream, trying cURL fallback");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $upstream);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
if (defined('CURL_HTTP_VERSION_1_0')) {
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
}
curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Icy-MetaData: 1', 'User-Agent: natiora-proxy/1.0' ]);

$contentTypeFromUpstream = null;
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$contentTypeFromUpstream) {
    $h = trim($header);
    if ($h === '') return strlen($header);
    if (stripos($h, 'content-type:') === 0) {
        $contentTypeFromUpstream = trim(substr($h, 13));
        header('Content-Type: ' . $contentTypeFromUpstream, true);
    } elseif (preg_match('#^(Content-Length|icy-metaint|icy-br|icy-name):#i', $h)) {
        header($h, false);
    }
    return strlen($header);
});

curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo $data; flush(); return strlen($data);
});

$ok = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

log_msg("cURL result for $upstream | err=" . ($err ?: '(none)') . " | http=" . intval($info['http_code'] ?? 0));

if ($err) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Proxy error: ' . $err;
    exit;
}

if (isset($info['http_code']) && $info['http_code'] >= 400) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Upstream returned HTTP ' . intval($info['http_code']);
    exit;
}

exit;

<?php
// Simple streaming proxy to relay an upstream radio mountpoint to clients.
// Usage: /radio.php?mount=%3Bstream.mp3
set_time_limit(0);
// Prevent PHP from printing warnings or compressing output which would corrupt audio stream
@ini_set('display_errors', '0');
@error_reporting(0);
@ini_set('zlib.output_compression', '0');
@ignore_user_abort(true);
// Upstream host (IP:port) - set to the public listen2myradio mount
$UPSTREAM_BASE = 'http://p.onlineradiobox.com/mg/natiora/player/?cs=mg.natiora&played=1';
// Otherwise use the configured upstream base + mount parameter (default to the live mount)
$upstream = '';
if (isset($_GET['src'])) {
    $src = $_GET['src'];
    if (filter_var($src, FILTER_VALIDATE_URL) || strpos($src, '/') === 0) {
        $upstream = $src;
    }
}
if (!$upstream) {
    // Get requested mount (default to the new live mount)
    $mount = isset($_GET['mount']) && $_GET['mount'] !== '' ? $_GET['mount'] : '/live.mp3?typeportmount=s1_26912_stream_657428790';
    // Ensure mount begins with /
    if ($mount[0] !== '/') { $mount = '/' . $mount; }
    $upstream = $UPSTREAM_BASE . $mount;
}

// Handle local files
if (strpos($upstream, '/') === 0) {
    $localPath = realpath(__DIR__ . '/public' . $upstream);
    if ($localPath && file_exists($localPath) && strpos($localPath, realpath(__DIR__ . '/public') . DIRECTORY_SEPARATOR) === 0) {
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        $mime = 'application/octet-stream';
        if ($ext === 'mp3') $mime = 'audio/mpeg';
        elseif ($ext === 'wav') $mime = 'audio/wav';
        elseif ($ext === 'ogg') $mime = 'audio/ogg';
        elseif ($ext === 'm4a') $mime = 'audio/mp4';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($localPath));
        readfile($localPath);
        exit;
    } else {
        http_response_code(404);
        exit;
    }
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
        // The built-in PHP dev server (php -S) doesn't reliably support chunked responses
        // and can produce ERR_INVALID_CHUNKED_ENCODING in browsers. Avoid sending
        // Transfer-Encoding when running under the CLI server. Production (Apache/Nginx)
        // can still use chunked encoding.
        if (php_sapi_name() !== 'cli-server') {
            header('Transfer-Encoding: chunked');
        }
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
// Client Range header (may be null). For live mounts (MP3) forwarding Range
// often breaks the upstream (some stream providers don't support Range);
// avoid forwarding Range for live MP3 mounts.
$clientRange = $_SERVER['HTTP_RANGE'] ?? null;
$forwardRange = $clientRange && !$forceMp3;
// Create context for fopen with headers (include Range if client requested partial content)
$headers = "Icy-MetaData: 1\r\nUser-Agent: natiora-proxy/1.0\r\n";
if ($forwardRange) { $headers .= 'Range: ' . $clientRange . "\r\n"; }
$opts = [
    'http' => [
        'method' => $isHead ? 'HEAD' : 'GET',
        'header' => $headers,
        'timeout' => 10,
        'ignore_errors' => true
    ]
];
$ctx = stream_context_create($opts);

// Quick HEAD-check helper: ensure upstream returns an audio Content-Type
function upstream_is_audio($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'natiora-proxy/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
    $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0);
    curl_close($ch);
    // treat audio/*, application/octet-stream, m3u8 playlist types as acceptable
    if ($code >= 200 && $code < 300) {
        if (preg_match('#^(audio/|application/octet-stream)#i', $ct)) return true;
        if (preg_match('#m3u8|mpegurl|vnd\.apple\.mpegurl|mpegurl#i', $ct)) return true;
    }
    return false;
}

// If upstream clearly returns HTML or non-audio, abort early with 502
if (!upstream_is_audio($upstream)) {
    log_msg("UPSTREAM NOT AUDIO -> $upstream");
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Upstream stream did not return audio content. Please verify the stream URL or provider.\n";
    exit;
}

// Prefer cURL for HTTPS upstreams (some providers require TLS and streaming
// behavior that fopen wrappers don't handle reliably). Use fopen only for
// non-HTTPS upstreams when available.
$useFopen = (stripos($upstream, 'https://') !== 0) && function_exists('fopen');
$fp = null;
if ($useFopen) {
    @$fp = @fopen($upstream, 'rb', false, $ctx);
}
// Try fopen wrapper first when allowed
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
        // forward other useful headers from upstream for HEAD requests
        foreach ($headers as $h) {
            if (stripos($h, 'content-range:') === 0 || stripos($h, 'accept-ranges:') === 0 || stripos($h, 'content-length:') === 0 || preg_match('#^icy-#i', $h)) {
                header($h, false);
            }
        }
        if ($status) { http_response_code($status); }
        exit;
    }

    // Propagate upstream status and useful headers (Content-Range, Accept-Ranges, Content-Length, icy-*)
    if ($status) { http_response_code($status); }
    foreach ($headers as $h) {
        if (stripos($h, 'content-range:') === 0 || stripos($h, 'accept-ranges:') === 0 || stripos($h, 'content-length:') === 0 || preg_match('#^icy-#i', $h)) {
            header($h, false);
        }
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
    log_msg("FOPEN not used or failed for $upstream, trying cURL fallback");

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
 $curlHeaders = [ 'Icy-MetaData: 1', 'User-Agent: natiora-proxy/1.0' ];
 if ($forwardRange) { $curlHeaders[] = 'Range: ' . $clientRange; }
 curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

$contentTypeFromUpstream = null;
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$contentTypeFromUpstream) {
    $h = trim($header);
    if ($h === '') return strlen($header);
    // Propagate status code from upstream (e.g., 206 Partial Content)
    if (preg_match('#^HTTP/\d\.\d\s+(\d+)#i', $h, $m)) {
        http_response_code((int)$m[1]);
        return strlen($header);
    }
    if (stripos($h, 'content-type:') === 0) {
        $contentTypeFromUpstream = trim(substr($h, 13));
        header('Content-Type: ' . $contentTypeFromUpstream, true);
    } elseif (preg_match('#^(Content-Length|Content-Range|Accept-Ranges|icy-metaint|icy-br|icy-name):#i', $h)) {
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

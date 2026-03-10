<?php
// Simple streaming proxy to relay an upstream radio mountpoint to clients.
// Usage: /radio.php?mount=%3Bstream.mp3
set_time_limit(0);
// Upstream host (IP:port) - set to local stream for testing
$UPSTREAM_BASE = 'http://192.168.1.102:8000';

// Get requested mount (default to ";stream.mp3")
$mount = isset($_GET['mount']) && $_GET['mount'] !== '' ? $_GET['mount'] : '/;stream.mp3';
// Ensure mount begins with /
if ($mount[0] !== '/') { $mount = '/' . $mount; }

$upstream = $UPSTREAM_BASE . $mount;

// Disable output buffering so we can stream
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
while (ob_get_level()) { ob_end_flush(); }
ob_implicit_flush(1);

// Basic response headers for client
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $upstream);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_NOPROGRESS, false);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
// Prefer HTTP/1.0 for compatibility with Icecast/Shoutcast (ICY) responses
if (defined('CURL_HTTP_VERSION_1_0')) {
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
}
// Request ICY metadata if available
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Icy-MetaData: 1',
    'User-Agent: natiora-proxy/1.0'
]);

// Forward selected headers from upstream to client
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
    $h = trim($header);
    if ($h === '') return strlen($header);
    // Forward content-type, icy headers and content-length
    if (preg_match('#^(Content-Type|Content-Length|icy-metaint|icy-br|icy-name):#i', $h)) {
        header($h, false);
    }
    return strlen($header);
});

// Stream body directly to client
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo $data;
    flush();
    return strlen($data);
});

$ok = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($err) {
    // If curl reports an HTTP/0.9 response or other transport issue, try a raw fopen fallback
    if (stripos($err, 'HTTP/0.9') !== false || stripos($err, 'Received HTTP/0.9') !== false) {
        // Some shoutcast/icecast servers reply with ICY/HTTP/0.9 headers. Try a raw TCP socket fallback.
        $u = parse_url($upstream);
        $host = $u['host'] ?? '';
        $port = $u['port'] ?? 80;
        $path = (isset($u['path']) ? $u['path'] : '/') . (isset($u['query']) ? '?' . $u['query'] : '');
        $errno = 0; $errstr = '';
        $sock = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 5);
        if ($sock) {
            stream_set_timeout($sock, 5);
            $req = "GET {$path} HTTP/1.0\r\n";
            $req .= "Host: {$host}\r\n";
            $req .= "Icy-MetaData: 1\r\n";
            $req .= "User-Agent: natiora-proxy/1.0\r\n";
            $req .= "Connection: close\r\n\r\n";
            fwrite($sock, $req);
            // Read and forward headers until end of headers
            $headers = '';
            while (!feof($sock)) {
                $line = fgets($sock);
                if ($line === false) break;
                $headers .= $line;
                if (rtrim($line) === '') break;
            }
            // Send appropriate headers to client
            header('Content-Type: audio/mpeg');
            header('Access-Control-Allow-Origin: *');
            // Stream the rest of the socket to client
            while (!feof($sock)) {
                $data = fread($sock, 8192);
                if ($data === false || $data === '') break;
                echo $data;
                @flush();
            }
            fclose($sock);
            exit;
        }
        // if socket fallback failed, continue to return the curl error below
    }
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Proxy error: ' . $err;
    exit;
}

// If upstream returned a non-success HTTP code, relay a 502
if (isset($info['http_code']) && $info['http_code'] >= 400) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Upstream returned HTTP ' . intval($info['http_code']);
    exit;
}

exit;

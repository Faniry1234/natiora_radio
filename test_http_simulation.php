<?php
// Simulate HTTP request to test static file serving
$_SERVER['REQUEST_URI'] = '/assets/videos/TUTO BOY COMMENT FAIRE UN MONTAGE PHOTO SUR PHOTOSHOP  %5Bflyer%5D.mp4';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Include the static file handler logic from index.php
$reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Request URI: {$_SERVER['REQUEST_URI']}\n";
echo "Parsed path: $reqPath\n";

if ($reqPath && $reqPath !== '/' && !preg_match('/\.(php|htaccess)$/i', $reqPath)) {
    $candidate = realpath(__DIR__ . '/PUBLIC' . $reqPath);
    echo "Candidate path: $candidate\n";
    echo "File exists: " . (file_exists($candidate) ? 'YES' : 'NO') . "\n";
    echo "Is file: " . (is_file($candidate) ? 'YES' : 'NO') . "\n";
    echo "Starts with PUBLIC: " . (strpos($candidate, realpath(__DIR__ . '/PUBLIC') . DIRECTORY_SEPARATOR) === 0 ? 'YES' : 'NO') . "\n";

    if ($candidate && is_file($candidate) && strpos($candidate, realpath(__DIR__ . '/PUBLIC') . DIRECTORY_SEPARATOR) === 0) {
        $mime = function_exists('mime_content_type') ? mime_content_type($candidate) : 'application/octet-stream';
        echo "MIME type: $mime\n";
        echo "File size: " . filesize($candidate) . " bytes\n";
        echo "WOULD SERVE FILE\n";
        exit;
    } else {
        echo "WOULD NOT SERVE FILE\n";
    }
} else {
    echo "Path matches exclusion pattern or is root\n";
}
?>
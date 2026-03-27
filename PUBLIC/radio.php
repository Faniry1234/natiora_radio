<?php
set_time_limit(0); // Désactive la limite de temps pour le streaming
// radio.php
header('Content-Type: audio/mpeg');
header('Access-Control-Allow-Origin: *'); // Autorise la lecture cross-browser

// Prefer explicit `src` query param, otherwise fall back to environment variable STREAM_URL
$stream_url = $_GET['src'] ?? '';
if (empty($stream_url)) {
    $stream_url = getenv('STREAM_URL') ?: '';
}

if (empty($stream_url)) {
    die("Aucun flux spécifié.");
}

// Nettoyage de l'URL pour éviter les failles
$stream_url = filter_var($stream_url, FILTER_SANITIZE_URL);

// On ouvre le flux distant et on le retransmet bit par bit
$handle = fopen($stream_url, "rb");

if ($handle) {
    // Désactive la compression qui pourrait bloquer le flux
    if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
    @ini_set('zlib.output_compression', 0);

    while (!feof($handle) && connection_status() == 0) {
        echo fread($handle, 8192);
        flush();
        ob_flush(); // Ajout de ob_flush pour vider tous les types de buffers
    }
    fclose($handle);
} else {
    header("HTTP/1.1 502 Bad Gateway");
    echo "Impossible de se connecter au flux source.";
}
?>
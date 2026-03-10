<?php
$root = __DIR__ . '/../PUBLIC/assets/';
$images = $root . 'images/';
$videos = $root . 'videos/';
$audios = $root . 'audios/';

if (!is_dir($audios)) mkdir($audios, 0755, true);

function makeCopies($dir) {
    $files = glob($dir . '*');
    foreach ($files as $f) {
        if (!is_file($f)) continue;
        $base = basename($f);
        $encoded = rawurlencode($base);
        $encodedPath = $dir . $encoded;
        if ($encoded !== $base && !file_exists($encodedPath)) {
            copy($f, $encodedPath);
            echo "Created copy: $encodedPath\n";
        }
        // also replace brackets [] with %5B %5D explicitly
        $brEncoded = str_replace(['[',']'], ['%5B','%5D'], $base);
        if ($brEncoded !== $base) {
            $bp = $dir . $brEncoded;
            if (!file_exists($bp)) {
                copy($f, $bp);
                echo "Created bracket-encoded copy: $bp\n";
            }
        }
    }
}

if (is_dir($images)) makeCopies($images);
if (is_dir($videos)) makeCopies($videos);

echo "Ensured audios directory exists: $audios\n";

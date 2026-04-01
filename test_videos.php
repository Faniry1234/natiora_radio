<?php
function getVideosList($assetBase) {
    $videosDir = __DIR__ . '/PUBLIC/assets/videos';
    $videos = [];
    if (is_dir($videosDir)) {
        $files = scandir($videosDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                $videos[] = [
                    'filename' => $file,
                    'title' => urldecode(pathinfo($file, PATHINFO_FILENAME)),
                    'url' => $assetBase . '/videos/' . rawurlencode($file)
                ];
            }
        }
    }
    return $videos;
}
$videos = getVideosList('/public/assets');
echo 'Nombre de vidéos: ' . count($videos) . PHP_EOL;
if (!empty($videos)) {
    echo 'La condition !empty($videos) est vraie' . PHP_EOL;
} else {
    echo 'La condition !empty($videos) est fausse' . PHP_EOL;
}
?>
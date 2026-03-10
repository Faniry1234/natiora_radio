<?php
// Simple image optimization script (GD-based).
// Usage: php scripts/optimize_images.php
// It will read PUBLIC/assets/images/oi.jpg and create oi-small.jpg (max width 1200)
// and oi-small.webp (if PHP supports imagewebp).

$root = __DIR__ . '/../PUBLIC/assets/images';
$src = $root . '/oi.jpg';
$smallJpeg = $root . '/oi-small.jpg';
$smallWebp = $root . '/oi-small.webp';

if (!file_exists($src)) {
    echo "Source image not found: $src\n";
    exit(1);
}

list($w, $h, $type) = getimagesize($src);
if (!$w || !$h) { echo "Invalid image\n"; exit(1); }

$maxW = 1200;
if ($w <= $maxW) {
    echo "Source already small enough (width={$w}). Copying to oi-small.jpg\n";
    copy($src, $smallJpeg);
    if (function_exists('imagewebp')) {
        $im = imagecreatefromjpeg($src);
        imagewebp($im, $smallWebp, 80);
        imagedestroy($im);
        echo "Created oi-small.webp\n";
    }
    echo "Created oi-small.jpg\n";
    exit(0);
}

$ratio = $h / $w;
$newW = $maxW;
$newH = (int)round($newW * $ratio);

$srcIm = imagecreatefromjpeg($src);
$dst = imagecreatetruecolor($newW, $newH);
imagecopyresampled($dst, $srcIm, 0,0,0,0, $newW, $newH, $w, $h);

// save JPEG
imagejpeg($dst, $smallJpeg, 78);
echo "Created $smallJpeg\n";

// save WEBP when available
if (function_exists('imagewebp')) {
    imagewebp($dst, $smallWebp, 78);
    echo "Created $smallWebp\n";
}

imagedestroy($srcIm);
imagedestroy($dst);

echo "Done.\n";

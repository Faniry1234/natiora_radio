$php_dev_flag = getenv('DEV_ADMIN');
if (!($php_dev_flag !== false && in_array(strtolower($php_dev_flag), ['1','true','yes']))) {
    echo "Dev scripts disabled. Set DEV_ADMIN=1 to enable.\n";
    exit(1);
}

<?php
$root = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, ['php','html','js','css'])) continue;
    $content = file_get_contents($f->getPathname());
    if (strpos($content, '/public/assets/') === false) continue;
    // find all occurrences using a regex that captures the asset path
    if (preg_match_all('/\/public\/assets\/[^
\s"\'\<\>\(\)]+/', $content, $m)) {
        foreach ($m[0] as $ref) {
            $files[$ref][] = str_replace($root, '', $f->getPathname());
        }
    }
}

$missing = [];
foreach ($files as $ref => $locs) {
    $path = $root . ltrim($ref, '/\\');
    if (!file_exists($path)) $missing[$ref] = $locs;
}

if (empty($missing)) {
    echo "No missing /public/assets references found.\n";
    exit(0);
}

echo "Missing assets referenced in views/code:\n\n";
foreach ($missing as $ref => $locs) {
    echo $ref . "\n  referenced in:\n";
    foreach ($locs as $l) echo "    - $l\n";
    echo "\n";
}

<?php
require_once __DIR__ . '/../APP/config.php';
$e = new Emissions();
$p = new Playlists();
$allE = $e->getAll();
$nonempty = [];
foreach ($allE as $k => $v) {
    if (!empty($v)) $nonempty[] = $k;
}
echo "Days: " . implode(',', $nonempty) . PHP_EOL;
echo "Total emissions: " . array_sum(array_map('count', $allE)) . PHP_EOL;
echo "Playlists: " . count($p->getAll()) . PHP_EOL;

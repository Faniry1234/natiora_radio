<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Load app config which also requires Database and model files
require_once __DIR__ . '/../APP/config.php';

$playModel = new Playlists();
$emModel = new Emissions();

$inputDate = trim($_GET['date'] ?? '');
$scopeRaw = $_GET['scope'] ?? 'week';
$scope = in_array($scopeRaw, ['day','week']) ? $scopeRaw : 'week';
$typeRaw = $_GET['type'] ?? 'all';
$type = in_array($typeRaw, ['playlists','emissions','all']) ? $typeRaw : 'all';

if ($inputDate) {
    $refTs = strtotime($inputDate);
    if ($refTs === false) $refTs = time();
} else {
    $refTs = time();
}

function weekRangeFromTs($ts) {
    $monday = strtotime('monday this week', $ts);
    $sunday = strtotime('sunday this week', $ts);
    return [$monday, $sunday];
}

if (empty($inputDate)) {
    $refTs = strtotime('-1 week');
}
list($weekStart, $weekEnd) = weekRangeFromTs($refTs);

if ($scope === 'day') {
    $dayStart = strtotime(date('Y-m-d', $refTs) . ' 00:00:00');
    $dayEnd = strtotime(date('Y-m-d', $refTs) . ' 23:59:59');
}

$playlists = $playModel->getAll();
$filteredPlaylists = [];
foreach ($playlists as $p) {
    $created = isset($p['created_at']) ? strtotime($p['created_at']) : false;
    if ($created === false) continue;
    if ($scope === 'day') {
        if ($created >= $dayStart && $created <= $dayEnd) $filteredPlaylists[] = $p;
    } else {
        if ($created >= $weekStart && $created <= $weekEnd) $filteredPlaylists[] = $p;
    }
}

$emissionsByDay = $emModel->getAll();
$selectedEmissions = [];
if ($scope === 'day') {
    $engToFr = ['monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi','thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche'];
    $fr = $engToFr[strtolower(date('l', $refTs))] ?? '';
    $selectedEmissions = $emissionsByDay[$fr] ?? [];
} else {
    for ($d = $weekStart; $d <= $weekEnd; $d = strtotime('+1 day', $d)) {
        $eng = strtolower(date('l', $d));
        $engToFr = ['monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi','thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche'];
        $fr = $engToFr[$eng] ?? '';
        $items = $emissionsByDay[$fr] ?? [];
        foreach ($items as $it) {
            $it['_date'] = date('Y-m-d', $d);
            $selectedEmissions[] = $it;
        }
    }
}

echo json_encode([
    'ok' => true,
    'type' => $type,
    'playlists' => array_values($filteredPlaylists),
    'emissions' => array_values($selectedEmissions),
]);

exit(0);

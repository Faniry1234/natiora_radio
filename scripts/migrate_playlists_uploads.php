<?php
/**
 * Migrate DATA/playlists.php: replace plain song titles with matching files
 * found under public/uploads/ when possible.
 */
require_once __DIR__ . '/../APP/config.php';

$phpFile = __DIR__ . '/../DATA/playlists.php';
if (!file_exists($phpFile)) {
    echo "No playlists file at DATA/playlists.php\n";
    exit(1);
}
$playlists = include $phpFile;
if (!is_array($playlists)) {
    echo "Playlists file did not return an array\n";
    exit(1);
}

$uploadsDir = realpath(__DIR__ . '/../public/uploads');
$uploadsMap = [];
if ($uploadsDir && is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $uploadsDir . DIRECTORY_SEPARATOR . $f;
        if (!is_file($full)) continue;
        $key = strtolower(preg_replace('/[^a-z0-9]+/i','', pathinfo($f, PATHINFO_FILENAME)));
        $uploadsMap[$key] = '/uploads/' . $f;
    }
}
if (empty($uploadsMap)) {
    echo "No uploaded files found in public/uploads/ -- nothing to migrate.\n";
    exit(0);
}

$tryMatchUpload = function($s) use ($uploadsMap) {
    $s2 = strtolower(trim(preg_replace('/[^a-z0-9]+/i','', $s)));
    if ($s2 === '') return null;
    if (isset($uploadsMap[$s2])) return $uploadsMap[$s2];
    foreach ($uploadsMap as $k => $p) {
        if (strpos($k, $s2) !== false || strpos($s2, $k) !== false) return $p;
    }
    return null;
};

$changed = 0;
foreach ($playlists as $pi => $pl) {
    if (!is_array($pl)) continue;
    $songs = is_array($pl['songs'] ?? []) ? $pl['songs'] : [];
    foreach ($songs as $si => $s) {
        if (!is_string($s) || $s === '') continue;
        $t = trim($s);
        if (preg_match('#^https?://#i', $t)) continue;
        if (strpos($t, '/') === 0) continue;
        $match = $tryMatchUpload($t);
        if ($match) {
            $playlists[$pi]['songs'][$si] = $match;
            $changed++;
            echo "Mapped \"$t\" -> $match\n";
        }
    }
}

if ($changed === 0) {
    echo "No songs mapped.\n";
    exit(0);
}

$out = "<?php\nreturn " . var_export($playlists, true) . ";\n";
if (file_put_contents($phpFile, $out, LOCK_EX) === false) {
    echo "Failed to write playlists file.\n";
    exit(1);
}

echo "Migration complete. $changed entries updated.\n";

// Attempt to sync into DB same as save_playlists does
try {
    $db = Database::getInstance();
    try { $db->init(); } catch (Throwable $e) { /* ignore */ }
    $pdo = $db->getConnection();
    if ($pdo instanceof PDO) {
        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM playlist_songs");
        $pdo->exec("DELETE FROM playlists");
        $stmtPl = $pdo->prepare("INSERT INTO playlists (title, description, cover, created_at) VALUES (:title,:description,:cover,:created_at)");
        $stmtSong = $pdo->prepare("INSERT INTO playlist_songs (playlist_id, song_title, position) VALUES (:playlist_id, :song_title, :position)");
        foreach ($playlists as $pl) {
            $title = $pl['title'] ?? '';
            $desc = $pl['desc'] ?? ($pl['description'] ?? null);
            $cover = $pl['cover'] ?? null;
            $created_at = $pl['created_at'] ?? null;
            $stmtPl->execute([':title'=>$title, ':description'=>$desc, ':cover'=>$cover, ':created_at'=>$created_at]);
            $newId = $pdo->lastInsertId();
            $songs = is_array($pl['songs'] ?? []) ? $pl['songs'] : [];
            $pos = 1;
            foreach ($songs as $s) {
                $stmtSong->execute([':playlist_id'=>$newId, ':song_title'=>$s, ':position'=>$pos]);
                $pos++;
            }
        }
        $pdo->commit();
        echo "Database synchronized.\n";
    }
} catch (Throwable $ex) {
    if (isset($pdo) && $pdo instanceof PDO) { try { $pdo->rollBack(); } catch(Throwable $_) {} }
    echo "DB sync failed: " . $ex->getMessage() . "\n";
}

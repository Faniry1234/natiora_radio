<?php
// Script to initialize a demo SQLite database with sample emissions and playlists
require_once __DIR__ . '/../APP/config.php';

// Ensure Database instance created
$dbInstance = Database::getInstance();
if (!$dbInstance->isPdo()) {
    echo "PDO not available. Cannot create demo DB.\n";
    exit(1);
}
$pdo = $dbInstance->getConnection();
try {
    // Create emissions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS emissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        day TEXT NOT NULL,
        time TEXT,
        title TEXT NOT NULL,
        presenter TEXT,
        duration TEXT,
        level TEXT,
        category TEXT,
        src TEXT,
        description TEXT
    )");

    // Create playlists and playlist_songs
    $pdo->exec("CREATE TABLE IF NOT EXISTS playlists (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        cover TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS playlist_songs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        playlist_id INTEGER NOT NULL,
        song_title TEXT NOT NULL,
        position INTEGER NOT NULL,
        FOREIGN KEY(playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
    )");

    // Insert sample emissions only if table empty
    $count = $pdo->query("SELECT COUNT(*) FROM emissions")->fetchColumn();
    if ($count == 0) {
        $em = [
            ['lundi','08:00','Morning Show','Alice','60 min','Débutant','Talk','', 'Le réveil musical'],
            ['mardi','14:00','Beat Time','DJ Max','120 min','Intermédiaire','Electro','', 'Sélection électro du moment'],
            ['mercredi','18:30','Culture+', 'Paul','45 min','Avancé','Culture','', 'Invités et débats']
        ];
        $stmt = $pdo->prepare("INSERT INTO emissions (day, time, title, presenter, duration, level, category, src, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($em as $r) $stmt->execute($r);
        echo "Inserted sample emissions.\n";
    } else {
        echo "Emissions table already has data.\n";
    }

    // Insert sample playlists only if none
    $countp = $pdo->query("SELECT COUNT(*) FROM playlists")->fetchColumn();
    if ($countp == 0) {
        $pdo->exec("INSERT INTO playlists (title, description, cover) VALUES ('Chill Vibes','Playlist détente','')");
        $pid = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO playlist_songs (playlist_id, song_title, position) VALUES (?, ?, ?)");
        $stmt->execute([$pid, 'Ambient Track 1', 1]);
        $stmt->execute([$pid, 'Acoustic Mood', 2]);
        echo "Inserted sample playlist.\n";
    } else {
        echo "Playlists table already has data.\n";
    }

    echo "Demo DB initialization complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

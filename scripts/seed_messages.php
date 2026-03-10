<?php
require_once __DIR__ . '/../APP/config.php';
try {
    $db = Database::getInstance();
    $db->init();
    $pdo = $db->getConnection();
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, is_read, created_at) VALUES (?, ?, ?, ?, 0, ?)');
    $samples = [
        [0,1,'Message test 1','Bonjour, ceci est un message de test.', $now],
        [2,1,'Question','J\'ai une question sur la playlist.', $now],
        [3,1,'Suggestion','Suggestion: ajouter une nouvelle émission le vendredi.', $now],
        [0,1,'Feedback','Super contenu aujourd\'hui!', $now],
        [4,1,'Invitation','Seriez-vous disponible pour une interview ?', $now]
    ];
    foreach ($samples as $s) {
        $stmt->execute($s);
        echo "Inserted id: " . $pdo->lastInsertId() . "\n";
    }
    echo "Seeding done.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

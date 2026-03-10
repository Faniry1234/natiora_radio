<?php
require_once __DIR__ . '/../APP/config.php';
require_once __DIR__ . '/../APP/MODEL/Database.php';
try {
    $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, is_read, created_at) VALUES (?, ?, ?, ?, 0, datetime("now"))');
    $stmt->execute([4, 3, 'Test to admin', 'Message automatique de test']);
    echo "Inserted message id: " . $pdo->lastInsertId() . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../APP/config.php';
require_once __DIR__ . '/../APP/MODEL/Database.php';

try {
    $db = Database::getInstance();
    $db->init();
    $pdo = $db->getConnection();
    $stmt = $pdo->query('SELECT id, sender_id, recipient_id, subject, body, is_read, created_at FROM messages ORDER BY created_at DESC LIMIT 50');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($rows) . " messages:\n";
    foreach ($rows as $r) {
        echo "[{$r['id']}] from={$r['sender_id']} to={$r['recipient_id']} read={$r['is_read']} at={$r['created_at']} subject=" . substr($r['subject'],0,60) . "\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

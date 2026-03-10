<?php
// Debug script: print last 50 messages from DB as JSON
require_once __DIR__ . '/../APP/config.php';
try {
    $db = Database::getInstance();
    $db->init();
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare('SELECT * FROM messages ORDER BY id DESC LIMIT 50');
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'count' => count($rows), 'rows' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

<?php
require_once __DIR__ . '/../APP/config.php';
require_once __DIR__ . '/../APP/MODEL/Database.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
    $stmt = $pdo->query('SELECT id, email, name, role, created_at FROM users ORDER BY id ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok'=>true,'count'=>count($rows),'rows'=>$rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}


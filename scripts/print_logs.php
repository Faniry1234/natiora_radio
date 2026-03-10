<?php
require __DIR__ . '/../APP/MODEL/Database.php';
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $sql = "SELECT id,title,source,played_at FROM played_logs ORDER BY played_at DESC LIMIT 5";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "RESULTS:\n";
    if (!$rows) { echo "(no rows)\n"; }
    print_r($rows);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

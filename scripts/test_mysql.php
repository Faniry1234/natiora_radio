<?php
require_once __DIR__ . '/../APP/config.php';
try {
    // Ensure Database class is loaded
    $db = Database::getInstance();
    // Try to initialise schema (will create missing tables if using MySQL and user has privileges)
    $db->init();
    $pdo = $db->getConnection();
    if ($pdo instanceof PDO) {
        echo "Connected to database using driver: " . (defined('DB_DRIVER') ? DB_DRIVER : 'unknown') . "\n";
        $stmt = $pdo->query("SELECT VERSION() as v");
        $v = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Server version: " . ($v['v'] ?? 'unknown') . "\n";
        // Check messages count
        try {
            $cstmt = $pdo->query('SELECT COUNT(*) as c FROM messages');
            $count = $cstmt->fetch(PDO::FETCH_ASSOC);
            echo "Messages in DB: " . ($count['c'] ?? '0') . "\n";
        } catch (Throwable $e) {
            echo "Unable to read messages table: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Database connection is not a PDO instance.\n";
    }
} catch (Throwable $e) {
    echo "Connection test FAILED: " . $e->getMessage() . "\n";
}

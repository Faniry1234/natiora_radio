<?php
// Simple importer: executes DELETE/INSERT statements from DATA/rn.sql against configured SQLite DB
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../APP/config.php';

echo "Using DB_PATH=" . (defined('DB_PATH') ? DB_PATH : 'undefined') . "\n";

$db = Database::getInstance()->getConnection();
$db->beginTransaction();
try {
    $sqlFile = __DIR__ . '/../DATA/rn.sql';
    if (!file_exists($sqlFile)) {
        throw new RuntimeException("SQL file not found: $sqlFile");
    }
    $content = file_get_contents($sqlFile);
    // Normalize MySQL-style escaped single quotes (\') to SQL standard ('') for SQLite
    $content = str_replace("\\'", "''", $content);
    // Remove backticks
    $content = str_replace('`', '', $content);
    // Remove C-style comments
    $content = preg_replace('#/\*.*?\*/#s', '', $content);

    // Find all DELETE FROM ...; and INSERT INTO ...; statements (multiline safe)
    preg_match_all('/(?:DELETE\s+FROM[\s\S]*?;)|(?:INSERT\s+INTO[\s\S]*?;)/i', $content, $matches);
    $statements = $matches[0] ?? [];
    $count = 0;
    foreach ($statements as $stmt) {
        $s = trim($stmt);
        if ($s === '') continue;
        // SQLite doesn't support ALTER TABLE ... AUTO_INCREMENT or ALTER TABLE ... = 1; skip residual ALTER lines
        if (preg_match('/ALTER\s+TABLE/i', $s)) continue;
        // Execute statement (remove trailing semicolon)
        $exec = rtrim($s, "\r\n; ");
        $db->exec($exec);
        $count++;
    }
    $db->commit();
    echo "Imported $count statements into SQLite DB.\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "Error during import: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";

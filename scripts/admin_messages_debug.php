<?php
require_once __DIR__ . '/../APP/config.php';
session_start();
header('Content-Type: text/html; charset=utf-8');
try {
    $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
    $stmt = $pdo->prepare('SELECT * FROM messages ORDER BY created_at DESC LIMIT 200');
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo "<h2>Error reading messages:</h2>".htmlspecialchars($e->getMessage());
    exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Debug Messages</title>
<style>body{font-family:Arial;margin:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#f4f4f4}</style>
</head><body>
<h1>Messages Debug</h1>
<p>Session user_id: <strong><?php echo htmlspecialchars($_SESSION['user_id'] ?? '(none)'); ?></strong>
 - user_role: <strong><?php echo htmlspecialchars($_SESSION['user_role'] ?? '(none)'); ?></strong></p>
<p>To test as admin open: <a href="/index.php?dev_admin=1">/index.php?dev_admin=1</a> then <a href="/index.php?route=admin/messages">Admin → Messages</a></p>
<h2>Messages table (last <?php echo count($rows); ?>)</h2>
<table>
<thead><tr><th>id</th><th>sender_id</th><th>sender_email</th><th>recipient_id</th><th>subject</th><th>body</th><th>is_read</th><th>created_at</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['id']); ?></td>
<td><?php echo htmlspecialchars($r['sender_id']); ?></td>
<td><?php echo htmlspecialchars($r['sender_email'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($r['recipient_id']); ?></td>
<td><?php echo htmlspecialchars($r['subject']); ?></td>
<td><?php echo nl2br(htmlspecialchars($r['body'])); ?></td>
<td><?php echo htmlspecialchars($r['is_read']); ?></td>
<td><?php echo htmlspecialchars($r['created_at']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>
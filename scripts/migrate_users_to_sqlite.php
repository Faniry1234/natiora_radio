<?php
// Migrate users from DATA/users.json into SQLite DB defined by APP/config.php
require_once __DIR__ . '/../APP/config.php';
$db = Database::getInstance()->getConnection();
$jsonPath = __DIR__ . '/../DATA/users.json';
if (!file_exists($jsonPath)) {
    echo "No users.json found, nothing to migrate.\n";
    exit(0);
}
$users = json_decode(file_get_contents($jsonPath), true);
if (!is_array($users) || empty($users)) {
    echo "users.json empty or invalid.\n";
    exit(0);
}
$inserted = 0;
foreach ($users as $u) {
    if (empty($u['email']) || empty($u['password'])) continue;
    // skip if already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $stmt->execute([':email' => $u['email']]);
    if ($stmt->fetch()) continue;
    $stmt = $db->prepare('INSERT INTO users (email,password,name,role,created_at,bio,avatar,phone) VALUES (:email,:password,:name,:role,:created_at,:bio,:avatar,:phone)');
    $stmt->execute([
        ':email' => $u['email'],
        ':password' => $u['password'],
        ':name' => $u['name'] ?? null,
        ':role' => $u['role'] ?? 'user',
        ':created_at' => $u['created_at'] ?? date('Y-m-d H:i:s'),
        ':bio' => $u['bio'] ?? '',
        ':avatar' => $u['avatar'] ?? '',
        ':phone' => $u['phone'] ?? ''
    ]);
    $inserted++;
}
echo "Migration complete. Inserted: $inserted users.\n";
// Optionally remove the JSON file (commented out for safety)
// unlink($jsonPath);
?>
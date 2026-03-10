<?php
// Script to set admin email and password in DATA/users.json
$path = __DIR__ . '/../DATA/users.json';
if (!file_exists($path)) {
    echo "users.json introuvable: $path\n";
    exit(1);
}
$users = json_decode(file_get_contents($path), true);
if (!is_array($users)) $users = [];
$updated = false;
foreach ($users as &$u) {
    if (isset($u['id']) && (int)$u['id'] === 1) {
        $u['email'] = 'admin@natiora.com';
        $u['password'] = password_hash('radio2026', PASSWORD_DEFAULT);
        $u['name'] = $u['name'] ?? 'Admin';
        $u['role'] = 'admin';
        $u['created_at'] = $u['created_at'] ?? date('Y-m-d H:i:s');
        $updated = true;
        break;
    }
}
if (!$updated) {
    $nextId = 1;
    foreach ($users as $u) if (isset($u['id']) && (int)$u['id'] >= $nextId) $nextId = (int)$u['id'] + 1;
    $users[] = [
        'id' => $nextId,
        'email' => 'admin@natiora.com',
        'name' => 'Admin',
        'role' => 'admin',
        'password' => password_hash('radio2026', PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ];
}
file_put_contents($path, json_encode($users, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "Admin updated/created successfully.\n";
?>
<?php
// Load .env file (project root) if present to populate environment variables
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        $val = trim($val, "\"' ");
        putenv("$key=$val");
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}

// Database configuration
// Resolve DB_DRIVER: prefer explicit .env value; otherwise try MySQL (if credentials present and connection succeeds),
// and fall back to sqlite so app remains usable locally.
$envDriver = getenv('DB_DRIVER');
if ($envDriver !== false && $envDriver !== '') {
    define('DB_DRIVER', $envDriver);
} else {
    $testHost = getenv('DB_HOST') ?: '127.0.0.1';
    $testPort = getenv('DB_PORT') ?: '3306';
    $testUser = getenv('DB_USER') ?: '';
    $testPass = getenv('DB_PASS') ?: '';
    $canMysql = false;
    if ($testUser !== '') {
        try {
            $testDsn = "mysql:host={$testHost};port={$testPort};charset=utf8mb4";
            $tmp = new PDO($testDsn, $testUser, $testPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]);
            $canMysql = true;
            // close
            $tmp = null;
        } catch (Throwable $e) {
            $canMysql = false;
        }
    }
    define('DB_DRIVER', $canMysql ? 'mysql' : 'sqlite');
}

// SQLite default path (used when DB_DRIVER = 'sqlite'). Accept relative path from project root.
$defaultSqlite = __DIR__ . '/../DATA/database.sqlite';
$envDbPath = getenv('DB_PATH') ?: '';
if ($envDbPath) {
    // If path looks relative, make absolute relative to project root
    if (preg_match('#^[A-Za-z]:\\|^/#', $envDbPath) === 0 && strpos($envDbPath, DIRECTORY_SEPARATOR) !== 0) {
        $envDbPath = realpath(__DIR__ . '/../' . $envDbPath) ?: __DIR__ . '/../' . $envDbPath;
    }
    define('DB_PATH', $envDbPath);
} else {
    define('DB_PATH', $defaultSqlite);
}

// MySQL configuration (used when DB_DRIVER = 'mysql')
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'natiora_radio');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Ensure DATA dir exists for sqlite/json and ensure sqlite file exists (empty) if using sqlite
if (!file_exists(__DIR__ . '/../DATA')) {
    mkdir(__DIR__ . '/../DATA', 0755, true);
}
if (defined('DB_PATH') && DB_DRIVER === 'sqlite') {
    $dir = dirname(DB_PATH);
    if (!file_exists($dir)) mkdir($dir, 0755, true);
    if (!file_exists(DB_PATH)) {
        // Create an empty sqlite file so PDO can open it on hosts that require file to exist
        touch(DB_PATH);
    }
}

// Include required model/controller files with robust checks
$required = [
    'MODEL/Database.php',
    'MODEL/User.php',
    'MODEL/Emissions.php',
    'MODEL/Playlists.php',
    'CONTROLLER/AuthController.php',
    'CONTROLLER/AdminController.php'
];

foreach ($required as $rel) {
    $path = __DIR__ . '/' . $rel;
    if (file_exists($path)) {
        require_once $path;
    } else {
        echo "<h2>Fichier manquant</h2>\n";
        echo "<p>Le fichier requis <strong>" . htmlspecialchars($rel) . "</strong> est introuvable dans <code>" . htmlspecialchars(__DIR__) . "</code>.</p>\n";
        echo "<p>Vérifiez que tous les fichiers dans <code>APP/MODEL</code> et <code>APP/CONTROLLER</code> sont présents.</p>\n";
        echo "<p>Fichier attendu: <code>" . htmlspecialchars($path) . "</code></p>";
        exit;
    }
}

// Initialize DB and tables
try {
    Database::getInstance()->init();
} catch (\Throwable $e) {
    $msg = $e->getMessage();
    echo "<h2>Erreur de configuration PHP</h2>";
    echo "<p>$msg</p>";
    if (stripos($msg, 'pdo_mysql') !== false || DB_DRIVER === 'mysql') {
        echo "<p>Pour MySQL : assurez-vous que l'extension <code>pdo_mysql</code> est activée et que les constantes DB_HOST/DB_USER/DB_PASS/DB_NAME sont correctement configurées.</p>";
    } else {
        echo "<p>Sur Windows, ouvrez votre fichier php.ini et activez les extensions suivantes (décommentez si nécessaire) :<br>";
        echo "<code>extension=pdo_sqlite</code> et <code>extension=sqlite3</code></p>";
    }
    echo "<p>Puis redémarrez votre serveur web (Apache/IIS) ou PHP-FPM.</p>";
    exit;
}

// Secret used to sign "remember me" cookies. Override with AUTH_SECRET in .env for production.
if (!defined('AUTH_SECRET')) {
    define('AUTH_SECRET', getenv('AUTH_SECRET') ?: 'please_change_this_secret');
}

// Cookie name used for "remember me" functionality
if (!defined('AUTH_COOKIE_NAME')) {
    define('AUTH_COOKIE_NAME', 'remember');
}

// Common directory constants
if (!defined('DATA_DIR')) {
    define('DATA_DIR', realpath(__DIR__ . '/../DATA') ?: (__DIR__ . '/../DATA'));
}
if (!defined('PUBLIC_DIR')) {
    define('PUBLIC_DIR', realpath(__DIR__ . '/../public') ?: (__DIR__ . '/../public'));
}

// Development / debug flags: set via .env or environment for local dev. Defaults to false in production.
if (!defined('DEV_ADMIN')) {
    $devAdminEnv = getenv('DEV_ADMIN');
    $isDevAdmin = false;
    if ($devAdminEnv !== false) {
        $lower = strtolower($devAdminEnv);
        $isDevAdmin = ($lower === '1' || $lower === 'true' || $lower === 'yes');
    }
    define('DEV_ADMIN', $isDevAdmin);
}

if (!defined('DEBUG_ADMIN')) {
    $debugEnv = getenv('DEBUG_ADMIN');
    $isDebug = false;
    if ($debugEnv !== false) {
        $lower = strtolower($debugEnv);
        $isDebug = ($lower === '1' || $lower === 'true' || $lower === 'yes');
    }
    define('DEBUG_ADMIN', $isDebug);
}

// Base URL used to generate absolute links. Can be overridden by BASE_URL in .env for production.
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase && $envBase !== '') {
        define('BASE_URL', rtrim($envBase, '/'));
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $computed = rtrim($scheme . '://' . $host . ($script === '/' ? '' : $script), '/');
        define('BASE_URL', $computed);
    }
}

// Helper functions: use Database singleton so config doesn't create a second PDO instance
function insertHistorique(?int $userId, string $action, ?string $details = null): int {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $sql = "INSERT INTO user_actions (user_id, action, details) VALUES (:user_id, :action, :details)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':action'  => $action,
        ':details' => $details,
    ]);
    return (int)$pdo->lastInsertId();
}

function insertMessage(?int $senderId, ?int $recipientId, ?string $subject, string $body): int {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $sql = "INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (:sender_id, :recipient_id, :subject, :body)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sender_id' => $senderId,
        ':recipient_id' => $recipientId,
        ':subject' => $subject,
        ':body'    => $body,
    ]);
    return (int)$pdo->lastInsertId();
}

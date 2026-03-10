<?php
// Request timing to help diagnose slow page loads
define('REQUEST_START', microtime(true));
session_start();

require_once __DIR__ . '/APP/config.php';

// Development helper: allow temporarily forcing an admin session by adding ?dev_admin=1
if (isset($_GET['dev_admin']) && $_GET['dev_admin'] == '1') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    // Create a lightweight fake admin session for local testing (do not enable in production)
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Dev Admin';
    $_SESSION['user_email'] = 'dev@local';
    $_SESSION['user_role'] = 'admin';
}

// NOTE: Automatic local admin session creation disabled to allow logout to persist.
// Use the explicit `?dev_admin=1` query parameter above to force a temporary
// admin session during development when needed.

$auth = new AuthController();

$route = $_GET['route'] ?? 'home';
switch($route){
    // Controller actions (POST handlers)
    case 'auth/login_post':
        $auth->login();
        break;
    case 'auth/register_post':
        $auth->register();
        break;
    case 'auth/logout':
        $auth->logout();
        break;
    case 'auth/updateProfile':
        $auth->updateProfile();
        break;
    case 'auth/changePassword':
        $auth->changePassword();
        break;

    // Admin Routes
    case 'admin':
        $admin = new AdminController();
        $admin->dashboard();
        break;
    case 'admin/emissions':
        $admin = new AdminController();
        $admin->manageEmissions();
        break;
    case 'admin/playlists':
        $admin = new AdminController();
        $admin->managePlaylists();
        break;
    case 'admin/messages':
        $admin = new AdminController();
        $admin->manageMessages();
        break;
    case 'admin/historiques':
        $admin = new AdminController();
        $admin->manageHistoriques();
        break;

    // Diagnostic route: show admin/session/DB state (safe for local debugging)
    case 'check/admin':
        header('Content-Type: text/plain; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) session_start();
        echo "== SESSION ==\n";
        echo "user_id: " . ($_SESSION['user_id'] ?? '(none)') . "\n";
        echo "user_role: " . ($_SESSION['user_role'] ?? '(none)') . "\n";
        echo "user_name: " . ($_SESSION['user_name'] ?? '(none)') . "\n\n";

        echo "== DATABASE ==\n";
        try {
            $db = Database::getInstance();
            echo "isPdo: " . ($db->isPdo() ? 'yes' : 'no') . "\n";
            $conn = $db->getConnection();
            echo "connection_class: " . (is_object($conn) ? get_class($conn) : 'none') . "\n\n";
        } catch (Throwable $e) {
            echo "DB init error: " . $e->getMessage() . "\n\n";
        }

        echo "== FILE FALLBACKS ==\n";
        $dataDir = __DIR__ . '/DATA/';
        echo "emissions.php: " . (file_exists($dataDir . 'emissions.php') ? 'present' : 'missing') . "\n";
        echo "playlists.php: " . (file_exists($dataDir . 'playlists.php') ? 'present' : 'missing') . "\n";
        echo "users.php: " . (file_exists($dataDir . 'users.php') ? 'present' : 'missing') . "\n\n";

        echo "== MODELS SAMPLE ==\n";
        try {
            $em = new Emissions();
            $pl = new Playlists();
            $us = new User();
            $e = $em->getAll();
            $p = $pl->getAll();
            $u = $us->getAll();
            echo "emissions days: " . count(array_filter($e)) . " (total items: " . array_reduce($e, function($carry,$d){ return $carry + count($d); }, 0) . ")\n";
            echo "playlists: " . count($p) . "\n";
            echo "users: " . count($u) . "\n";
        } catch (Throwable $ex) {
            echo "Model error: " . $ex->getMessage() . "\n";
        }
        echo "\nDiagnostic complete.\n";
        exit;

    // Dev helper: return raw HTML produced by AdminController::dashboard() (captured)
    case 'dev/admin_raw':
        ob_start();
        $admin = new AdminController();
        // call dashboard which normally echoes the page
        $admin->dashboard();
        $html = ob_get_clean();
        header('Content-Type: text/plain; charset=utf-8');
        echo $html;
        exit;

    // API: log a playback event (called by the client player)
    case 'api/log_play':
        // Accept JSON body or form-encoded POST
        $input = file_get_contents('php://input');
        $data = [];
        if ($input) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) $data = $decoded;
        }
        // Fallback to $_POST fields
        $title = trim($data['title'] ?? $_POST['title'] ?? '');
        $artist = trim($data['artist'] ?? $_POST['artist'] ?? '');
        $source = trim($data['source'] ?? $_POST['source'] ?? ($_POST['stream'] ?? ''));
        $duration = isset($data['duration']) ? (int)$data['duration'] : (isset($_POST['duration']) ? (int)$_POST['duration'] : null);
        $played_at = $data['played_at'] ?? $_POST['played_at'] ?? date('c');
        $user_id = $_SESSION['user_id'] ?? null;

        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getInstance();
            // Ensure tables exist
            try { $db->init(); } catch (Throwable $e) { /* ignore init errors */ }
            $pdo = $db->getConnection();
            if ($pdo instanceof PDO) {
                $sql = "INSERT INTO played_logs (title, artist, source, duration, user_id, played_at) VALUES (:title, :artist, :source, :duration, :user_id, :played_at)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':artist' => $artist,
                    ':source' => $source,
                    ':duration' => $duration,
                    ':user_id' => $user_id,
                    ':played_at' => date('Y-m-d H:i:s', strtotime($played_at))
                ]);
                echo json_encode(['ok' => true, 'insert_id' => $pdo->lastInsertId()]);
                exit;
            }
        } catch (Throwable $ex) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
            exit;
        }
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'DB unavailable']);
        exit;

    // API: proxy a replay source to avoid CORS or inaccessible direct URLs
    case 'api/proxy_replay':
        // Accept GET param 'src' or 'file'
        $src = trim($_GET['src'] ?? $_GET['file'] ?? '');
        if (!$src) {
            http_response_code(400);
            echo 'Missing src parameter';
            exit;
        }

        // Allow local files under PUBLIC/ or DATA/ or proxy remote http(s) URLs
        $root = realpath(__DIR__);
        header('Access-Control-Allow-Origin: *');
        header('X-Proxy-Server: natiora-proxy');

        // remote URL
        if (preg_match('#^https?://#i', $src)) {
            // stream remote URL (best effort)
            $ctx = stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: natiora-proxy/1.0\r\n"]]);
            $fh = @fopen($src, 'rb', false, $ctx);
            if (!$fh) { http_response_code(404); echo 'Remote resource not available'; exit; }
            // try to extract content-type from response headers
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $h) {
                    if (stripos($h, 'content-type:') === 0) {
                        header($h);
                        break;
                    }
                }
            }
            // stream in chunks
            while (!feof($fh)) { echo fread($fh, 8192); flush(); }
            fclose($fh);
            exit;
        }

        // sanitize local path: disallow traversal and require PUBLIC/ or DATA/
        $srcClean = ltrim($src, "\/\\");
        $allowedDirs = [realpath(__DIR__ . '/PUBLIC'), realpath(__DIR__ . '/DATA')];
        $candidate = realpath(__DIR__ . '/' . $srcClean);
        if (!$candidate) { http_response_code(404); echo 'File not found'; exit; }
        $ok = false;
        foreach ($allowedDirs as $d) { if ($d && strpos($candidate, $d) === 0) { $ok = true; break; } }
        if (!$ok) { http_response_code(403); echo 'Access denied'; exit; }

        // serve file
        $mime = function_exists('mime_content_type') ? mime_content_type($candidate) : 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($candidate));
        // Stream the file
        $fp = fopen($candidate, 'rb');
        if ($fp) {
            while (!feof($fp)) { echo fread($fp, 8192); flush(); }
            fclose($fp);
            exit;
        }
        http_response_code(500);
        echo 'Unable to read file';
        exit;

    // Admin: upload media file (multipart form). Returns JSON {ok,path}
    case 'admin/upload_media':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method']); exit; }
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok'=>false,'error'=>'No file uploaded']); exit;
        }
        $u = $_FILES['file'];
        $uploadsDir = __DIR__ . '/PUBLIC/uploads';
        if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0755, true);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($u['name']));
        $target = $uploadsDir . '/' . uniqid('media_') . '_' . $name;
        if (!move_uploaded_file($u['tmp_name'], $target)) { echo json_encode(['ok'=>false,'error'=>'Move failed']); exit; }
        // Return web-path relative to project root
        $webPath = '/PUBLIC/uploads/' . basename($target);
        echo json_encode(['ok'=>true,'path'=>$webPath]); exit;

    // Admin: save playlists data to DATA/playlists.php (accepts JSON body)
    case 'admin/save_playlists':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method']); exit; }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        header('Content-Type: application/json; charset=utf-8');
        if (!is_array($data)) { echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }
        $out = "<?php\nreturn " . var_export($data, true) . ";\n";
        $file = __DIR__ . '/DATA/playlists.php';
        if (file_put_contents($file, $out, LOCK_EX) === false) { echo json_encode(['ok'=>false,'error'=>'Write failed']); exit; }
        echo json_encode(['ok'=>true]); exit;

    // Admin: save emissions data to DATA/emissions.php (accepts JSON body)
    case 'admin/save_emissions':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method']); exit; }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        header('Content-Type: application/json; charset=utf-8');
        if (!is_array($data)) { echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }
        $out = "<?php\nreturn " . var_export($data, true) . ";\n";
        $file = __DIR__ . '/DATA/emissions.php';
        if (file_put_contents($file, $out, LOCK_EX) === false) { echo json_encode(['ok'=>false,'error'=>'Write failed']); exit; }
        echo json_encode(['ok'=>true]); exit;

    // API: messages - send and list
    case 'api/messages/send':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method']); exit; }
        header('Content-Type: application/json; charset=utf-8');
        // allow anonymous sends: sender may be null (store 0)
        $sender = $_SESSION['user_id'] ?? 0;

        // Support both form-encoded and raw JSON bodies
        $raw = file_get_contents('php://input');
        $json = $raw ? json_decode($raw, true) : null;

        $recipient = null;
        if ($json && isset($json['recipient_id'])) $recipient = (int)$json['recipient_id'];
        elseif (isset($_POST['recipient_id'])) $recipient = (int)$_POST['recipient_id'];
        elseif (isset($_GET['recipient_id'])) $recipient = (int)$_GET['recipient_id'];

        // default recipient to first admin user (id=1 fallback) if not provided
        try {
            $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
            if (empty($recipient)) {
                // try to find any user with role 'admin'
                $adminId = null;
                try {
                    if (!class_exists('User')) require_once __DIR__ . '/APP/MODEL/User.php';
                    $um = new User();
                    $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($admins)) $adminId = (int)$admins[0];
                } catch (Throwable $e) { /* ignore, fallback to 1 */ }
                $recipient = $adminId ?: 1;
            }

            $subject = '';
            $body = '';
            if ($json) {
                $subject = trim($json['subject'] ?? '');
                $body = trim($json['body'] ?? '');
            } else {
                $subject = trim($_POST['subject'] ?? $_GET['subject'] ?? '');
                $body = trim($_POST['body'] ?? $_GET['body'] ?? '');
            }

            if ($body === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'missing_body']); exit; }

            // basic validation: recipient must be positive integer
            if (!is_int($recipient) || $recipient <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid_recipient']); exit; }

            // accept optional sender_email and context fields
            $sender_email = '';
            $context_type = '';
            $context_id = null;
            if ($json) {
                $sender_email = trim($json['sender_email'] ?? '');
                $context_type = trim($json['context_type'] ?? '');
                $context_id = isset($json['context_id']) ? (int)$json['context_id'] : null;
            } else {
                $sender_email = trim($_POST['sender_email'] ?? $_GET['sender_email'] ?? '');
                $context_type = trim($_POST['context_type'] ?? $_GET['context_type'] ?? '');
                $context_id = isset($_POST['context_id']) ? (int)$_POST['context_id'] : (isset($_GET['context_id']) ? (int)$_GET['context_id'] : null);
            }

            // Try inserting with extended columns; if column missing, attempt to add and retry
            try {
                $stmt = $pdo->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, sender_email, context_type, context_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$sender, $recipient, $subject, $body, $sender_email, $context_type, $context_id]);
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                // detect missing column error for sqlite or mysql
                if (stripos($msg, 'no such column') !== false || stripos($msg, 'Unknown column') !== false) {
                    try {
                        // attempt to add missing columns depending on driver
                        if (strpos(strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)), 'sqlite') !== false) {
                            $pdo->exec("ALTER TABLE messages ADD COLUMN sender_email TEXT");
                            $pdo->exec("ALTER TABLE messages ADD COLUMN context_type TEXT");
                            $pdo->exec("ALTER TABLE messages ADD COLUMN context_id INTEGER");
                        } else {
                            $pdo->exec("ALTER TABLE messages ADD COLUMN sender_email VARCHAR(255) NULL");
                            $pdo->exec("ALTER TABLE messages ADD COLUMN context_type VARCHAR(100) NULL");
                            $pdo->exec("ALTER TABLE messages ADD COLUMN context_id INT NULL");
                        }
                        // retry insert
                        $stmt = $pdo->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, sender_email, context_type, context_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$sender, $recipient, $subject, $body, $sender_email, $context_type, $context_id]);
                    } catch (Throwable $e2) {
                        throw $e2;
                    }
                } else {
                    throw $e;
                }
            }
            echo json_encode(['ok'=>true, 'id' => $pdo->lastInsertId()]); exit;
            echo json_encode(['ok'=>true, 'id' => $pdo->lastInsertId()]); exit;
        } catch (Throwable $ex) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$ex->getMessage()]); exit; }

    case 'api/messages/list':
        header('Content-Type: application/json; charset=utf-8');
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthenticated']); exit; }
        try {
            $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
            $stmt = $pdo->prepare('SELECT * FROM messages WHERE recipient_id = ? OR sender_id = ? ORDER BY created_at DESC LIMIT 200');
            $stmt->execute([$uid, $uid]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // enrich with user info when possible
            $userIds = [];
            foreach ($rows as $r) {
                $userIds[(int)$r['sender_id']] = true;
                $userIds[(int)$r['recipient_id']] = true;
            }
            $users = [];
            if (!empty($userIds)) {
                // load User model if available
                if (!class_exists('User')) require_once __DIR__ . '/APP/MODEL/User.php';
                $um = new User();
                foreach (array_keys($userIds) as $id) {
                    if ($id <= 0) continue;
                    $u = $um->findById($id);
                    if ($u) $users[$id] = $u;
                }
            }
            // attach names/emails/avatars
            foreach ($rows as &$r) {
                $sid = (int)($r['sender_id'] ?? 0);
                $rid = (int)($r['recipient_id'] ?? 0);
                $r['sender_name'] = $users[$sid]['name'] ?? null;
                $r['sender_email'] = $users[$sid]['email'] ?? null;
                $r['sender_avatar'] = $users[$sid]['avatar'] ?? null;
                $r['recipient_name'] = $users[$rid]['name'] ?? null;
                $r['recipient_email'] = $users[$rid]['email'] ?? null;
                $r['recipient_avatar'] = $users[$rid]['avatar'] ?? null;
            }
            echo json_encode($rows); exit;
        } catch (Throwable $ex) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$ex->getMessage()]); exit; }

    case 'api/messages/unread_count':
        header('Content-Type: application/json; charset=utf-8');
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthenticated']); exit; }
        try {
            $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
            $stmt = $pdo->prepare('SELECT COUNT(*) as c FROM messages WHERE recipient_id = ? AND is_read = 0');
            $stmt->execute([$uid]);
            $c = (int)$stmt->fetchColumn();
            echo json_encode(['ok'=>true,'count'=>$c]); exit;
        } catch (Throwable $ex) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$ex->getMessage()]); exit; }

    case 'api/messages/mark_read':
        // POST or GET with id
        $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthenticated']); exit; }
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'missing_id']); exit; }
        try {
            $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
            $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?');
            $stmt->execute([$id, $uid]);
            echo json_encode(['ok'=>true,'updated' => $stmt->rowCount()]); exit;
        } catch (Throwable $ex) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$ex->getMessage()]); exit; }

    // Views rendered through layout
    case 'auth/login':
        $view = 'auth/login.php';
        include __DIR__ . '/VIEW/layout/layout.php';
        break;
    case 'auth/register':
        $view = 'auth/register.php';
        include __DIR__ . '/VIEW/layout/layout.php';
        break;
    case 'auth/profile':
        $auth->profile();
        break;
    case 'playlistes':
        $view = 'home/playlistes.php';
        include __DIR__ . '/VIEW/layout/layout.php';
        break;
    case 'emissions':
        $view = 'home/emissions.php';
        include __DIR__ . '/VIEW/layout/layout.php';
        break;
    case 'historiques':
        $view = 'home/historiques.php';
        include __DIR__ . '/VIEW/layout/layout.php';
        break;
    case 'home':
    default:
        $view = 'home/index.php';
        include __DIR__ . '/VIEW/layout/layout.php';
        break;}

    // Log request duration for debugging performance problems
    $elapsed = microtime(true) - REQUEST_START;
    $logLine = date('c') . "\troute={$route}\telapsed=" . round($elapsed*1000,2) . "ms\n";
    @file_put_contents(__DIR__ . '/DATA/perf.log', $logLine, FILE_APPEND | LOCK_EX);
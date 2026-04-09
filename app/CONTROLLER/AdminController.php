<?php

namespace App\Controller;

if (!class_exists('\Database')) require_once __DIR__ . '/../MODEL/Database.php';
if (!class_exists('\User')) require_once __DIR__ . '/../MODEL/User.php';
if (!class_exists('\Emissions')) require_once __DIR__ . '/../MODEL/Emissions.php';
if (!class_exists('\Playlists')) require_once __DIR__ . '/../MODEL/Playlists.php';

class AdminController {
    private $base;
    private $emissions;
    private $playlists;
    private $user;

    public function __construct($base) {
        $this->base = $base;
        $this->emissions = new \Emissions();
        $this->playlists = new \Playlists();
        $this->user = new \User();
    }

    public function dashboard(){
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Check if user is admin
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ' . $this->base . '/index.php?route=auth/login');
            exit;
        }

        // Get recent data for dashboard
        $users = $this->user->getAll() ?? [];
        $playlists = $this->playlists->getAll() ?? [];
        $emissions = $this->emissions->getAll() ?? [];

        // Flatten emissions for 'recent' list
        $recent_emissions = [];
        foreach ($emissions as $day => $items) {
            foreach ($items as $it) { $it['_day'] = $day; $recent_emissions[] = $it; }
        }
        $recent_emissions = array_slice($recent_emissions, 0, 8);
        $recent_playlists = array_slice($playlists, 0, 6);

        // Debug info if in dev mode
        if (defined('DEV_ADMIN') && DEV_ADMIN) {
            echo "<div style='background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;font-family:monospace;font-size:12px;'>";
            echo "DEV MODE: Admin Dashboard Debug<br>";
            echo "Users count: " . count($users) . " &nbsp;|&nbsp; Playlists count: " . count($playlists) . " &nbsp;|&nbsp; Emissions days: " . count($emissions) . "<br>";
            echo "Session user_id: " . ($_SESSION['user_id'] ?? '(none)') . " &nbsp;|&nbsp; user_role: " . ($_SESSION['user_role'] ?? '(none)') . "<br>";
            echo "</div>";
        }

        $base = $this->base;
        // Expose a debug flag to views when running locally or when dev_admin is set
        $debugMode = (php_sapi_name() === 'cli-server') || (defined('DEV_ADMIN') && DEV_ADMIN) || (defined('DEBUG_ADMIN') && DEBUG_ADMIN);
        include __DIR__ . '/../../VIEW/admin/dashboard.php';
    }

    public function getEmissions(){
        return $this->emissions->getAll();
    }

    public function getPlaylists(){
        return $this->playlists->getAll();
    }

    public function manageEmissions(){
        // Handle videos management
        if (isset($_GET['view']) && $_GET['view'] === 'videos') {
            $this->manageGeneralVideos();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add') {
                $this->addEmission();
            } elseif ($action === 'edit') {
                $this->editEmission();
            } elseif ($action === 'delete') {
                $this->deleteEmission();
            }

            // Après traitement POST, rediriger pour éviter resoumission
            $day = urlencode($_POST['day'] ?? $_GET['day'] ?? 'lundi');
                header('Location: ' . $this->base . '/index.php?route=admin/emissions&day=' . $day);
            exit;
        }

        $emissions = $this->getEmissions();
        // Provide auxiliary variables expected by the view
        $users = $this->user->getAll() ?? [];
        $recent_playlists = array_slice($this->playlists->getAll() ?? [], 0, 6);
        // flatten emissions for 'recent' list
        $recent_emissions = [];
        foreach ($emissions as $day => $items) {
            foreach ($items as $it) { $it['_day'] = $day; $recent_emissions[] = $it; }
        }
        $recent_emissions = array_slice($recent_emissions, 0, 8);
        $base = $this->base;
        $debugMode = (php_sapi_name() === 'cli-server') || (defined('DEV_ADMIN') && DEV_ADMIN) || (defined('DEBUG_ADMIN') && DEBUG_ADMIN);
        include __DIR__ . '/../../VIEW/admin/emissions.php';
    }

    private function manageGeneralVideos(){
        if (session_status() === PHP_SESSION_NONE) session_start();

        $videosDir = __DIR__ . '/../../PUBLIC/assets/videos';
        $videos = [];
        $message = '';
        $messageType = '';

        // Ensure videos directory exists
        if (!is_dir($videosDir)) {
            mkdir($videosDir, 0755, true);
        }

        // Handle video upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
            $file = $_FILES['video'];

            // Validate file
            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
                $maxSize = 100 * 1024 * 1024; // 100MB

                if (!in_array($file['type'], $allowedTypes)) {
                    $message = 'Type de fichier non autorisé. Seuls les MP4, AVI, MOV et WMV sont acceptés.';
                    $messageType = 'error';
                } elseif ($file['size'] > $maxSize) {
                    $message = 'Fichier trop volumineux. Taille maximum: 100MB.';
                    $messageType = 'error';
                } else {
                    // Generate safe filename
                    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $safeName = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $originalName);
                    $safeName = str_replace(' ', '_', $safeName);
                    $targetFile = $videosDir . '/' . $safeName . '.' . $extension;

                    // Avoid filename conflicts
                    $counter = 1;
                    while (file_exists($targetFile)) {
                        $targetFile = $videosDir . '/' . $safeName . '_' . $counter . '.' . $extension;
                        $counter++;
                    }

                    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                        $message = 'Vidéo uploadée avec succès!';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de l\'upload du fichier.';
                        $messageType = 'error';
                    }
                }
            } else {
                $message = 'Erreur lors de l\'upload: ' . $file['error'];
                $messageType = 'error';
            }
        }

        // Handle video deletion
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_video'])) {
            $videoToDelete = basename($_POST['delete_video']);
            $filePath = $videosDir . '/' . $videoToDelete;

            if (file_exists($filePath) && unlink($filePath)) {
                $message = 'Vidéo supprimée avec succès!';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de la suppression de la vidéo.';
                $messageType = 'error';
            }
        }

        // Get list of videos
        if (is_dir($videosDir)) {
            $files = scandir($videosDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                    $filePath = $videosDir . '/' . $file;
                    $videos[] = [
                        'filename' => $file,
                        'title' => urldecode(pathinfo($file, PATHINFO_FILENAME)),
                        'size' => filesize($filePath),
                        'date' => date('Y-m-d H:i:s', filemtime($filePath))
                    ];
                }
            }
        }

        // Variables for the view
        $base = $this->base;
        $debugMode = (php_sapi_name() === 'cli-server') || (defined('DEV_ADMIN') && DEV_ADMIN) || (defined('DEBUG_ADMIN') && DEBUG_ADMIN);
        // Variables spécifiques aux vidéos
        $videos = $videos;
        $message = $message;
        $messageType = $messageType;
        include __DIR__ . '/../../VIEW/admin/emissions.php';
    }

    private function addEmission(){
        $day = $_POST['day'] ?? '';
        $title = htmlspecialchars($_POST['title'] ?? '');
        $presenter = htmlspecialchars($_POST['presenter'] ?? '');
        $duration = htmlspecialchars($_POST['duration'] ?? '');
        $level = htmlspecialchars($_POST['level'] ?? '');
        $category = htmlspecialchars($_POST['category'] ?? '');
        $time = htmlspecialchars($_POST['time'] ?? '');
        $desc = htmlspecialchars($_POST['desc'] ?? '');
        
        // Gérer la source vidéo
        $src = '';
        $videoType = $_POST['video-type'] ?? 'url';
        
        if ($videoType === 'file' && !empty($_FILES['src-file']['name'])) {
            // Upload le fichier vidéo
            $uploadDir = __DIR__ . '/../../public/assets/videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $file = $_FILES['src-file'];
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filepath = $uploadDir . time() . '_' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $src = '/assets/videos/' . basename($filepath);
            }
        } else {
            // Utiliser l'URL
            $src = htmlspecialchars($_POST['src-url'] ?? '');
        }

        if (!$day || !$title) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Jour et titre obligatoires.'];
            return;
        }

        $emission = [
            'time' => $time,
            'title' => $title,
            'presenter' => $presenter,
            'duration' => $duration,
            'level' => $level,
            'category' => $category,
            'desc' => $desc,
            'src' => $src
        ];

        $this->emissions->add($day, $emission);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Émission ajoutée avec succès.'];
        // Log action
        if (!empty($_SESSION['user_id'])) {
            $this->user->addAction($_SESSION['user_id'], 'emission_add', "Ajout: {$title} ({$day} {$time})");
        }
    }

    private function editEmission(){
        $day = $_POST['day'] ?? '';
        $index = (int)($_POST['index'] ?? -1);
        $title = htmlspecialchars($_POST['title'] ?? '');
        $presenter = htmlspecialchars($_POST['presenter'] ?? '');
        $duration = htmlspecialchars($_POST['duration'] ?? '');
        $level = htmlspecialchars($_POST['level'] ?? '');
        $category = htmlspecialchars($_POST['category'] ?? '');
        $time = htmlspecialchars($_POST['time'] ?? '');
        $desc = htmlspecialchars($_POST['desc'] ?? '');
        
        // Gérer la source vidéo
        $src = '';
        $videoType = $_POST['video-type'] ?? 'url';
        
        if ($videoType === 'file' && !empty($_FILES['src-file']['name'])) {
            // Upload le fichier vidéo
            $uploadDir = __DIR__ . '/../../public/assets/videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $file = $_FILES['src-file'];
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filepath = $uploadDir . time() . '_' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $src = '/assets/videos/' . basename($filepath);
            }
        } else {
            // Utiliser l'URL
            $src = htmlspecialchars($_POST['src-url'] ?? '');
        }

        $emission = [
            'time' => $time,
            'title' => $title,
            'presenter' => $presenter,
            'duration' => $duration,
            'level' => $level,
            'category' => $category,
            'desc' => $desc,
            'src' => $src
        ];

        if ($this->emissions->update($day, $index, $emission)) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Émission modifiée avec succès.'];
            if (!empty($_SESSION['user_id'])) {
                $this->user->addAction($_SESSION['user_id'], 'emission_edit', "Modification: {$title} ({$day} index {$index})");
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Émission non trouvée.'];
        }
    }

    private function deleteEmission(){
        $day = $_POST['day'] ?? '';
        $index = (int)($_POST['index'] ?? -1);
        
        if ($this->emissions->delete($day, $index)) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Émission supprimée avec succès.'];
            if (!empty($_SESSION['user_id'])) {
                $this->user->addAction($_SESSION['user_id'], 'emission_delete', "Suppression: {$day} index {$index}");
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Émission non trouvée.'];
        }
    }

    private function saveEmissions($data){
        // Emissions are persisted via the Emissions model (MySQL). This method
        // is kept for compatibility but intentionally does nothing to avoid
        // writing JSON files in the admin area.
        return false;
    }

    public function managePlaylists(){
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add') {
                $this->addPlaylist();
            } elseif ($action === 'edit') {
                $this->editPlaylist();
            } elseif ($action === 'delete') {
                $this->deletePlaylist();
            }

            // Redirect after POST to avoid form resubmission
            header('Location: ' . $this->base . '/index.php?route=admin/playlists');
            exit;
        }

        $playlists = $this->getPlaylists();
        // Provide auxiliary variables for the view
        $users = $this->user->getAll() ?? [];
        $recent_playlists = array_slice($playlists ?? [], 0, 6);
        $emissions = $this->emissions->getAll();
        $recent_emissions = [];
        foreach ($emissions as $day => $items) {
            foreach ($items as $it) { $it['_day'] = $day; $recent_emissions[] = $it; }
        }
        $recent_emissions = array_slice($recent_emissions, 0, 8);
        $base = $this->base;
        $debugMode = (php_sapi_name() === 'cli-server') || (defined('DEV_ADMIN') && DEV_ADMIN) || (defined('DEBUG_ADMIN') && DEBUG_ADMIN);
        include __DIR__ . '/../../VIEW/admin/playlists.php';
    }

    public function manageTeam(){
        // Only admin-accessed: GET shows editor, POST saves responsables to DATA/responsables.php
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $raw = file_get_contents('php://input');
            $data = null;
            $error = null;

            // Accept JSON body or form field 'data'
            if ($raw !== false && $raw !== '') {
                $data = json_decode($raw, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    $error = 'JSON decode error: ' . json_last_error_msg();
                }
            }

            if ((!is_array($data) || $data === null) && isset($_POST['data'])) {
                $try = json_decode($_POST['data'], true);
                if (is_array($try)) {
                    $data = $try;
                    $error = null;
                }
            }

            header('Content-Type: application/json; charset=utf-8');
            if (!is_array($data)) {
                if (!$error) {
                    $error = 'Invalid payload';
                }
                echo json_encode(['ok' => false, 'error' => $error]);
                exit;
            }

            $file = __DIR__ . '/../../DATA/responsables.php';
            $dir = dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $out = "<?php\nreturn " . var_export($data, true) . ";\n";
            if (file_put_contents($file, $out, LOCK_EX) === false) {
                $last = error_get_last();
                $msg = $last['message'] ?? 'Write failed';
                echo json_encode(['ok' => false, 'error' => 'Write failed: ' . $msg]);
                exit;
            }

            echo json_encode(['ok' => true]);
            exit;
        }

        $dataFile = __DIR__ . '/../../DATA/responsables.php';
        $responsables = [];
        if (file_exists($dataFile)) {
            $tmp = include $dataFile;
            if (is_array($tmp)) $responsables = $tmp;
        }
        $base = $this->base;
        $debugMode = (php_sapi_name() === 'cli-server') || (defined('DEV_ADMIN') && DEV_ADMIN) || (defined('DEBUG_ADMIN') && DEBUG_ADMIN);
        include __DIR__ . '/../../VIEW/admin/team.php';
    }

    public function uploadImage(){
        header('Content-Type: application/json; charset=utf-8');

        // Check if user is admin
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
            exit;
        }

        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Aucun fichier uploadé']);
            exit;
        }

        $file = $_FILES['image'];

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.']);
            exit;
        }

        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux. Maximum 2MB.']);
            exit;
        }

        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../PUBLIC/assets/images/responsables/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'responsable_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Return the public path
            $publicPath = '/assets/images/responsables/' . $filename;
            echo json_encode(['success' => true, 'path' => $publicPath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde du fichier']);
        }
        exit;
    }

    public function manageMessages(){
        // Simple admin inbox page
        // Ensure recent datasets are available for dashboard-like sidebar
        $allUsers = $this->user->getAll() ?? [];
        $allPlaylists = $this->playlists->getAll() ?? [];
        $allEmissions = $this->emissions->getAll() ?? [];
        $base = $this->base;
        $debugMode = (php_sapi_name() === 'cli-server') || (defined('DEV_ADMIN') && DEV_ADMIN) || (defined('DEBUG_ADMIN') && DEBUG_ADMIN);
        include __DIR__ . '/../../VIEW/admin/messages.php';
    }

    private function addPlaylist(){
        $title = htmlspecialchars($_POST['title'] ?? '');
        $desc = htmlspecialchars($_POST['desc'] ?? '');
        $cover = htmlspecialchars($_POST['cover'] ?? '');
        $songs = explode(',', $_POST['songs'] ?? '');
        $songs = array_map('trim', $songs);
        $songs = array_filter($songs);

        // Handle uploaded audio files
        if (!empty($_FILES['songs_files']) && is_array($_FILES['songs_files']['name'])) {
            $uploadDir = __DIR__ . '/../../public/assets/audios/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            foreach ($_FILES['songs_files']['name'] as $idx => $name) {
                if (empty($name)) continue;
                $tmp = $_FILES['songs_files']['tmp_name'][$idx];
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                $destName = time() . '_' . $safe;
                $destPath = $uploadDir . $destName;
                if (move_uploaded_file($tmp, $destPath)) {
                    // store web-accessible path (no /public prefix)
                    $songs[] = '/assets/audios/' . $destName;
                }
            }
        }

        if (!$title) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Titre obligatoire.'];
            return;
        }

        // If admin provided a target day, compute a created_at timestamp matching that weekday
        $created_at = null;
        $chosenDay = trim(strtolower($_POST['day'] ?? ''));
        $frToEng = ['lundi'=>'Monday','mardi'=>'Tuesday','mercredi'=>'Wednesday','jeudi'=>'Thursday','vendredi'=>'Friday','samedi'=>'Saturday','dimanche'=>'Sunday'];
        if ($chosenDay && isset($frToEng[$chosenDay])) {
            $eng = $frToEng[$chosenDay];
            $todayEng = date('l');
            if (strcasecmp($todayEng, $eng) === 0) {
                $created_at = date('Y-m-d H:i:s');
            } else {
                $created_at = date('Y-m-d H:i:s', strtotime('next ' . $eng));
            }
        }

        $playlist = [
            'title' => $title,
            'desc' => $desc,
            'cover' => $cover,
            'songs' => $songs
        ];

        // allow updating the assigned day (created_at) from edit form
        $created_at = null;
        $chosenDay = trim(strtolower($_POST['day'] ?? ''));
        $frToEng = ['lundi'=>'Monday','mardi'=>'Tuesday','mercredi'=>'Wednesday','jeudi'=>'Thursday','vendredi'=>'Friday','samedi'=>'Saturday','dimanche'=>'Sunday'];
        if ($chosenDay && isset($frToEng[$chosenDay])) {
            $eng = $frToEng[$chosenDay];
            $todayEng = date('l');
            if (strcasecmp($todayEng, $eng) === 0) {
                $created_at = date('Y-m-d H:i:s');
            } else {
                $created_at = date('Y-m-d H:i:s', strtotime('next ' . $eng));
            }
        }
        if ($created_at) $playlist['created_at'] = $created_at;
        if ($created_at) $playlist['created_at'] = $created_at;

        $this->playlists->add($playlist);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Playlist ajoutée avec succès.'];
        if (!empty($_SESSION['user_id'])) {
            $this->user->addAction($_SESSION['user_id'], 'playlist_add', "Ajout playlist: {$title}");
        }
    }

    private function editPlaylist(){
        $id = (int)($_POST['id'] ?? -1);
        $title = htmlspecialchars($_POST['title'] ?? '');
        $desc = htmlspecialchars($_POST['desc'] ?? '');
        $cover = htmlspecialchars($_POST['cover'] ?? '');
        $songs = explode(',', $_POST['songs'] ?? '');
        $songs = array_map('trim', $songs);
        $songs = array_filter($songs);

        // Handle uploaded audio files
        if (!empty($_FILES['songs_files']) && is_array($_FILES['songs_files']['name'])) {
            $uploadDir = __DIR__ . '/../../public/assets/audios/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            foreach ($_FILES['songs_files']['name'] as $idx => $name) {
                if (empty($name)) continue;
                $tmp = $_FILES['songs_files']['tmp_name'][$idx];
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                $destName = time() . '_' . $safe;
                $destPath = $uploadDir . $destName;
                if (move_uploaded_file($tmp, $destPath)) {
                    $songs[] = '/assets/audios/' . $destName;
                }
            }
        }

        $playlist = [
            'title' => $title,
            'desc' => $desc,
            'cover' => $cover,
            'songs' => $songs
        ];

        if ($this->playlists->update($id, $playlist)) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Playlist modifiée avec succès.'];
            if (!empty($_SESSION['user_id'])) {
                $this->user->addAction($_SESSION['user_id'], 'playlist_edit', "Edition playlist id: {$id}");
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Playlist non trouvée.'];
        }
    }

    private function deletePlaylist(){
        $id = (int)($_POST['id'] ?? -1);
        
        if ($this->playlists->delete($id)) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Playlist supprimée avec succès.'];
            if (!empty($_SESSION['user_id'])) {
                $this->user->addAction($_SESSION['user_id'], 'playlist_delete', "Suppression playlist id: {$id}");
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Playlist non trouvée.'];
        }
    }

    public function manageHistoriques(){
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = (int)($_GET['per_page'] ?? 50);
        if ($per_page < 5) $per_page = 5;
        if ($per_page > 500) $per_page = 500;

        $res = $this->getHistoriques($page, $per_page);
        $historiques = $res['rows'] ?? [];
        $hist_total = (int)($res['total'] ?? count($historiques));

        $base = $this->base;
        $debugMode = (php_sapi_name() === 'cli-server') || (defined('DEV_ADMIN') && DEV_ADMIN) || (defined('DEBUG_ADMIN') && DEBUG_ADMIN);
        include __DIR__ . '/../../VIEW/admin/historiques.php';
    }

    private function getHistoriques($page = 1, $per_page = 50){
        $dbInstance = Database::getInstance();

        // Only support database-backed historiques for admin.
        if (!$dbInstance->isPdo()) {
            error_log('AdminController::getHistoriques requires a PDO connection.');
            return ['rows' => [], 'total' => 0];
        }

        // Try to get from database
        if ($dbInstance->isPdo()) {
            try {
                $pdo = $dbInstance->getConnection();
                $filterUser = trim($_GET['user'] ?? '');
                $filterDate = trim($_GET['date'] ?? '');
                $startDate = trim($_GET['start_date'] ?? '');
                $endDate = trim($_GET['end_date'] ?? '');

                $where = " WHERE 1=1";
                $params = [];

                if ($filterUser) {
                    $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
                    $params[] = "%$filterUser%";
                    $params[] = "%$filterUser%";
                }

                if ($filterDate) {
                    $where .= " AND DATE(pl.played_at) = ?";
                    $params[] = $filterDate;
                } else {
                    if ($startDate && $endDate) {
                        $where .= " AND DATE(pl.played_at) BETWEEN ? AND ?";
                        $params[] = $startDate;
                        $params[] = $endDate;
                    } elseif ($startDate) {
                        $where .= " AND DATE(pl.played_at) >= ?";
                        $params[] = $startDate;
                    } elseif ($endDate) {
                        $where .= " AND DATE(pl.played_at) <= ?";
                        $params[] = $endDate;
                    }
                }

                // total count
                $countQuery = "SELECT COUNT(*) as cnt FROM played_logs pl LEFT JOIN users u ON pl.user_id = u.id" . $where;
                $countStmt = $pdo->prepare($countQuery);
                $countStmt->execute($params);
                $total = (int)$countStmt->fetchColumn(0);

                $offset = max(0, ($page - 1) * $per_page);
                $query = "SELECT pl.*, u.name as user_name, u.email 
                          FROM played_logs pl 
                          LEFT JOIN users u ON pl.user_id = u.id " . $where . " ORDER BY pl.played_at DESC LIMIT :limit OFFSET :offset";

                $stmt = $pdo->prepare($query);
                $i = 1;
                foreach ($params as $p) { $stmt->bindValue($i, $p); $i++; }
                $stmt->bindValue(':limit', (int)$per_page, \PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                return ['rows' => $rows, 'total' => $total];
            } catch (\Exception $e) {
                error_log("Database error: " . $e->getMessage());
                return ['rows' => [], 'total' => 0];
            }
        }
        return ['rows' => [], 'total' => 0];
    }


}


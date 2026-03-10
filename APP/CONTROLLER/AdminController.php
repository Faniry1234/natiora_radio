<?php
class AdminController {
    private $user;
    private $emissions;
    private $playlists;
    private $base;

    public function __construct(){
        if(!class_exists('Database')) require_once __DIR__ . '/../MODEL/Database.php';
        if(!class_exists('User')) require_once __DIR__ . '/../MODEL/User.php';
        if(!class_exists('Emissions')) require_once __DIR__ . '/../MODEL/Emissions.php';
        if(!class_exists('Playlists')) require_once __DIR__ . '/../MODEL/Playlists.php';
        
        $this->user = new User();
        $this->emissions = new Emissions();
        $this->playlists = new Playlists();
        // Compute base URL (works if app is in a subfolder)
        $this->base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($this->base === '/' || $this->base === '\\') $this->base = '';

        // If PDO is not available, keep admin accessible but warn the user.
        // Models will return empty datasets when no DB connection exists.
        if (!Database::getInstance()->isPdo()) {
            if (!isset($_SESSION)) session_start();
            // If PHP fallback data files exist, present a non-blocking info message instead of an error
            $dataDir = __DIR__ . '/../../DATA/';
            $hasFallback = file_exists($dataDir . 'emissions.php') && file_exists($dataDir . 'playlists.php') && file_exists($dataDir . 'users.php');
            if ($hasFallback) {
                $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Base de données non disponible — mode local activé (données PHP utilisées).'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Base de données non disponible. Certaines fonctionnalités peuvent être limitées.'];
            }
            // Do not redirect/exit: allow admin pages to render using fallbacks.
        }

        $this->checkAdmin();
    }

    private function checkAdmin(){
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Developer bypass: controlled via DEV_ADMIN constant (set in APP/config.php or .env)
        // When DEV_ADMIN is true the application will create a lightweight admin session
        // for local development. This must be explicitly enabled in the environment.
        if (defined('DEV_ADMIN') && DEV_ADMIN) {
            $_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
            $_SESSION['user_name'] = $_SESSION['user_name'] ?? 'Dev Admin';
            $_SESSION['user_email'] = $_SESSION['user_email'] ?? 'dev@local';
            $_SESSION['user_role'] = 'admin';
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Accès refusé. Connexion requise.'];
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : $this->base) . '/index.php?route=auth/login');
            exit;
        }

        // If the session already marks the user as admin, trust it (useful for local/dev flows)
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            return;
        }

        $user = $this->user->findById($_SESSION['user_id']);
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Accès refusé. Droits administrateur requis.'];
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : $this->base) . '/index.php');
            exit;
        }
    }

    public function dashboard(){
        // Handle user management actions from dashboard
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['user_action'])) {
            $ua = $_POST['user_action'];
            $uid = (int)($_POST['user_id'] ?? 0);
            if ($uid > 0) {
                if ($ua === 'promote') {
                    $this->user->update($uid, ['role' => 'admin']);
                    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Utilisateur promu administrateur.'];
                } elseif ($ua === 'demote') {
                    $this->user->update($uid, ['role' => 'user']);
                    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Utilisateur rétrogradé.'];
                } elseif ($ua === 'delete') {
                    if ($uid == ($_SESSION['user_id'] ?? 0)) {
                        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Vous ne pouvez pas supprimer votre propre compte.'];
                    } else {
                        $this->user->delete($uid);
                        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Utilisateur supprimé.'];
                    }
                }
            }
            header('Location: ' . $this->base . '/index.php?route=admin');
            exit;
        }

        $allUsers = $this->user->getAll() ?? [];
        $allEmissions = $this->emissions->getAll() ?? [];
        $allPlaylists = $this->playlists->getAll() ?? [];

        // If models returned empty datasets (DB unavailable or model issue),
        // try to load PHP fallback files directly from DATA/ so admin shows content.
        $dataDir = __DIR__ . '/../../DATA/';
        if (empty($allEmissions) || (is_array($allEmissions) && array_reduce($allEmissions, function($c,$d){return $c + (is_array($d)?count($d):0);}, 0) === 0)) {
            if (file_exists($dataDir . 'emissions.php')) {
                $raw = include $dataDir . 'emissions.php';
                $tmp = ['lundi'=>[],'mardi'=>[],'mercredi'=>[],'jeudi'=>[],'vendredi'=>[],'samedi'=>[],'dimanche'=>[]];
                if (is_array($raw)) {
                    foreach ($raw as $em) {
                        $day = $em['day'] ?? 'autres';
                        if (!isset($tmp[$day])) $tmp[$day] = [];
                        $tmp[$day][] = $em;
                    }
                }
                $allEmissions = $tmp;
            }
        }

        if (empty($allPlaylists) && file_exists($dataDir . 'playlists.php')) {
            $raw = include $dataDir . 'playlists.php';
            if (is_array($raw)) $allPlaylists = $raw;
        }

        if (empty($allUsers) && file_exists($dataDir . 'users.php')) {
            $raw = include $dataDir . 'users.php';
            if (is_array($raw)) $allUsers = $raw;
        }
        
        // Compter correctement toutes les émissions (regroupées par jour)
        $totalEmissions = 0;
        foreach ($allEmissions as $day => $emissions) {
            $totalEmissions += count($emissions ?? []);
        }
        
        $stats = [
            'total_users' => count($allUsers),
            'total_emissions' => $totalEmissions,
            'total_playlists' => count($allPlaylists)
        ];
        // Préparer quelques éléments récents pour le dashboard
        $recent_playlists = array_slice($allPlaylists, 0, 6);

        // Aplatir les émissions par jour pour obtenir un tableau plat trié par heure/date
        $recent_emissions = [];
        foreach ($allEmissions as $day => $items) {
            foreach ($items as $it) {
                $it['_day'] = $day;
                $recent_emissions[] = $it;
            }
        }
        // Ne pas re-trier par `strtotime` ici : le modèle `Emissions::getAll()`
        // renvoie déjà les jours dans l'ordre souhaité (lundi..dimanche) et
        // les émissions par jour sont ordonnées par heure. On conserve cet
        // ordre lors de l'affichage dans le dashboard.
        $recent_emissions = array_slice($recent_emissions, 0, 8);

        $users = $allUsers;
        // If debug flag present (controlled by DEBUG_ADMIN or DEV_ADMIN), emit a small diagnostic block above the dashboard
        if ((defined('DEBUG_ADMIN') && DEBUG_ADMIN) || (defined('DEV_ADMIN') && DEV_ADMIN)) {
            echo "<div style='padding:12px;margin:12px;background:#fff;border:1px solid #ddd;max-width:1200px;'>";
            echo "<strong>DEBUG ADMIN</strong><br>";
            echo "Users count: " . count($allUsers) . " &nbsp;|&nbsp; Playlists: " . count($allPlaylists) . " &nbsp;|&nbsp; Emission days: " . count(array_filter($allEmissions)) . "<br>";
            echo "DATA files present: ";
            $dataDir = __DIR__ . '/../../DATA/';
            echo "emissions.php=" . (file_exists($dataDir . 'emissions.php') ? 'yes' : 'no') . ", ";
            echo "playlists.php=" . (file_exists($dataDir . 'playlists.php') ? 'yes' : 'no') . ", ";
            echo "users.php=" . (file_exists($dataDir . 'users.php') ? 'yes' : 'no') . "<br>";
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
            $uploadDir = __DIR__ . '/../../PUBLIC/assets/videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $file = $_FILES['src-file'];
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filepath = $uploadDir . time() . '_' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $src = '/PUBLIC/assets/videos/' . basename($filepath);
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
            $uploadDir = __DIR__ . '/../../PUBLIC/assets/videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $file = $_FILES['src-file'];
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filepath = $uploadDir . time() . '_' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $src = '/PUBLIC/assets/videos/' . basename($filepath);
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
            $uploadDir = __DIR__ . '/../../PUBLIC/assets/audios/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            foreach ($_FILES['songs_files']['name'] as $idx => $name) {
                if (empty($name)) continue;
                $tmp = $_FILES['songs_files']['tmp_name'][$idx];
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                $destName = time() . '_' . $safe;
                $destPath = $uploadDir . $destName;
                if (move_uploaded_file($tmp, $destPath)) {
                    // store web-accessible path
                    $songs[] = '/PUBLIC/assets/audios/' . $destName;
                }
            }
        }

        if (!$title) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Titre obligatoire.'];
            return;
        }

        $playlist = [
            'title' => $title,
            'desc' => $desc,
            'cover' => $cover,
            'songs' => $songs
        ];

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
            $uploadDir = __DIR__ . '/../../PUBLIC/assets/audios/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            foreach ($_FILES['songs_files']['name'] as $idx => $name) {
                if (empty($name)) continue;
                $tmp = $_FILES['songs_files']['tmp_name'][$idx];
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                $destName = time() . '_' . $safe;
                $destPath = $uploadDir . $destName;
                if (move_uploaded_file($tmp, $destPath)) {
                    $songs[] = '/PUBLIC/assets/audios/' . $destName;
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


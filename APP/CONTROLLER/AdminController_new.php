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
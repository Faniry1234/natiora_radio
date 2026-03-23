<?php
class AuthController {
    private $userModel;
    private $base;

    public function __construct(){
        if(!class_exists('Database')) require_once __DIR__ . '/../MODEL/Database.php';
        if(!class_exists('User')) require_once __DIR__ . '/../MODEL/User.php';
        $this->userModel = new User();
        // compute base URL (works if app is in subfolder)
        $this->base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($this->base === '/' || $this->base === '\\') $this->base = '';
    }

    public function showLogin(){
        $loginError = null;
        if (!empty($_SESSION['flash']) && (!isset($_SESSION['flash_type']) || $_SESSION['flash_type'] === 'error')) {
            $loginError = $_SESSION['flash'];
            // keep flash for layout to display
        }
        $view = 'auth/login.php';
        include __DIR__ . '/../../VIEW/layout/layout.php';
    }

    public function showRegister(){
        $registerError = null;
        $registerSuccess = null;
        if (!empty($_SESSION['flash'])) {
            if (!empty($_SESSION['flash_type']) && $_SESSION['flash_type'] === 'success') {
                $registerSuccess = $_SESSION['flash'];
            } else {
                $registerError = $_SESSION['flash'];
            }
        }
        $view = 'auth/register.php';
        include __DIR__ . '/../../VIEW/layout/layout.php';
    }

    public function login(){
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        // debug logging removed

        // Basic presence check
        if (empty($email) || empty($password)) {
            $_SESSION['flash'] = 'Email et mot de passe requis';
            $_SESSION['flash_type'] = 'error';
            $_SESSION['open_login_modal'] = true;
            $ref = $_SERVER['HTTP_REFERER'] ?? ($this->base . '/index.php?route=home');
            header('Location: ' . $ref);
            exit;
        }

        $user = $this->userModel->findByEmail($email);
        $pw_ok = false;
        if ($user && !empty($user['password'])) {
            $pw_ok = password_verify($password, $user['password']);
        }

        if($user && $pw_ok){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'] ?? '';
            $_SESSION['user_name'] = $user['name'] ?? '';
            $_SESSION['user_avatar'] = $user['avatar'] ?? '';
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            
            $this->userModel->addAction($user['id'], 'LOGIN', 'Connexion réussie');
            
            $_SESSION['flash'] = 'Bienvenue ' . $user['name'];
            $_SESSION['flash_type'] = 'success';
            // Si l'utilisateur demande "se souvenir de moi", créer un cookie signé durable
            if (!empty($_POST['remember'])) {
                $secret = defined('AUTH_SECRET') ? AUTH_SECRET : '';
                $signature = hash_hmac('sha256', $user['id'] . '|' . ($user['password'] ?? ''), $secret);
                $payload = base64_encode($user['id'] . '|' . $signature);
                // cookie valable 30 jours, HTTP only
                if (PHP_VERSION_ID >= 70300) {
                    setcookie(AUTH_COOKIE_NAME, $payload, [
                        'expires' => time() + 30*24*3600,
                        'path' => '/',
                        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                } else {
                    setcookie(AUTH_COOKIE_NAME, $payload, time() + 30*24*3600, '/; samesite=Lax', '', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), true);
                }
            }
            
            // Rediriger l'admin vers le dashboard
            if (($user['role'] ?? 'user') === 'admin') {
                header('Location: ' . $this->base . '/index.php?route=admin');
            } else {
                header('Location: ' . $this->base . '/index.php?route=auth/profile');
            }
            exit;
        } else {
            $_SESSION['flash'] = 'Identifiants invalides';
            $_SESSION['flash_type'] = 'error';
            $_SESSION['open_login_modal'] = true;
            $ref = $_SERVER['HTTP_REFERER'] ?? ($this->base . '/index.php?route=home');
            header('Location: ' . $ref);
            exit;
        }
    }

    public function register(){
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $name = trim($_POST['name'] ?? '');

        if (empty($email) || empty($password) || empty($password2) || empty($name)) {
            $_SESSION['flash'] = 'Veuillez remplir tous les champs';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $this->base . '/index.php?route=auth/register');
            exit;
        }
        
        if (strlen($password) < 6) {
            $_SESSION['flash'] = 'Le mot de passe doit contenir au moins 6 caractères';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $this->base . '/index.php?route=auth/register');
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = 'Email invalide';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $this->base . '/index.php?route=auth/register');
            exit;
        }

        if ($password !== $password2) {
            $_SESSION['flash'] = 'Les mots de passe ne correspondent pas';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $this->base . '/index.php?route=auth/register');
            exit;
        }
        
        if ($this->userModel->findByEmail($email)){
            $_SESSION['flash'] = 'Email déjà utilisé';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $this->base . '/index.php?route=auth/register');
            exit;
        }

        $ok = $this->userModel->create($email, $password, $name);
        if ($ok) {
            $_SESSION['flash'] = 'Compte créé avec succès. Connectez-vous!';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . $this->base . '/index.php?route=auth/login');
            exit;
        } else {
            $_SESSION['flash'] = 'Erreur lors de la création du compte';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $this->base . '/index.php?route=auth/register');
            exit;
        }
    }

    public function isLoggedIn(){
        return !empty($_SESSION['user_id']);
    }

    public function logout(){
        // Log the logout attempt for debugging
        $method = $_SERVER['REQUEST_METHOD'] ?? '(none)';
        $uri = $_SERVER['REQUEST_URI'] ?? '(none)';
        error_log("AuthController::logout called via {$method} {$uri} -- session_user_id=" . ($_SESSION['user_id'] ?? '(none)'));
        if ($this->isLoggedIn()) {
            $this->userModel->addAction($_SESSION['user_id'], 'LOGOUT', 'Déconnexion');
        }
        // Supprimer le cookie "remember" si présent
        if (defined('AUTH_COOKIE_NAME') && !empty($_COOKIE[AUTH_COOKIE_NAME])) {
            setcookie(AUTH_COOKIE_NAME, '', time() - 3600, '/');
            unset($_COOKIE[AUTH_COOKIE_NAME]);
        }
        session_unset();
        session_destroy();
        error_log("AuthController::logout completed, redirecting to " . ($this->base . '/index.php?route=home'));
        header('Location: ' . $this->base . '/index.php?route=home');
        exit;
    }

    public function profile(){
        if (!$this->isLoggedIn()) {
            $_SESSION['flash'] = 'Veuillez vous connecter';
            $_SESSION['flash_type'] = 'error';
            $_SESSION['open_login_modal'] = true;
            header('Location: /index.php?route=home');
            exit;
        }
        
        $user = $this->userModel->findById($_SESSION['user_id']);
        $history = $this->userModel->getHistory($_SESSION['user_id'], 50);
        
        $view = 'auth/profile.php';
        include __DIR__ . '/../../VIEW/layout/layout.php';
    }

    public function updateProfile(){
        if (!$this->isLoggedIn()) {
            $_SESSION['flash'] = 'Non autorisé';
            $_SESSION['flash_type'] = 'error';
            header('Location: /index.php?route=home');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'bio' => trim($_POST['bio'] ?? '')
        ];

        if (empty($data['name'])) {
            $_SESSION['flash'] = 'Le nom ne peut pas être vide';
            $_SESSION['flash_type'] = 'error';
        } else {
            if ($this->userModel->update($userId, $data)) {
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['flash'] = 'Profil mis à jour avec succès';
                $_SESSION['flash_type'] = 'success';
                $this->userModel->addAction($userId, 'PROFILE_UPDATE', 'Mise à jour du profil');
            } else {
                $_SESSION['flash'] = 'Erreur lors de la mise à jour';
                $_SESSION['flash_type'] = 'error';
            }
        }

        header('Location: ' . $this->base . '/index.php?route=auth/profile');
        exit;
    }

    public function changePassword(){
        if (!$this->isLoggedIn()) {
            $_SESSION['flash'] = 'Non autorisé';
            $_SESSION['flash_type'] = 'error';
            header('Location: /index.php?route=home');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = $this->userModel->findById($userId);
        
        if (!password_verify($oldPassword, $user['password'])) {
            $_SESSION['flash'] = 'L\'ancien mot de passe est incorrect';
            $_SESSION['flash_type'] = 'error';
        } else if (empty($newPassword) || strlen($newPassword) < 6) {
            $_SESSION['flash'] = 'Le nouveau mot de passe doit faire au moins 6 caractères';
            $_SESSION['flash_type'] = 'error';
        } else if ($newPassword !== $confirmPassword) {
            $_SESSION['flash'] = 'Les mots de passe ne correspondent pas';
            $_SESSION['flash_type'] = 'error';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($this->userModel->setPassword($userId, $hash)) {
                $_SESSION['flash'] = 'Mot de passe changé avec succès';
                $_SESSION['flash_type'] = 'success';
                $this->userModel->addAction($userId, 'PASSWORD_CHANGE', 'Changement de mot de passe');
            } else {
                $_SESSION['flash'] = 'Erreur lors du changement de mot de passe';
                $_SESSION['flash_type'] = 'error';
            }
        }

        header('Location: ' . $this->base . '/index.php?route=auth/profile');
        exit;
    }
}

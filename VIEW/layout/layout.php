<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Tentative restauration de session via cookie "remember" si disponible
        if (empty($_SESSION['user_id']) && defined('AUTH_COOKIE_NAME') && !empty($_COOKIE[AUTH_COOKIE_NAME])) {
    $c = base64_decode($_COOKIE['remember']);
            $c = base64_decode($_COOKIE[AUTH_COOKIE_NAME]);
    if ($c && strpos($c, '|') !== false) {
        list($uid, $sig) = explode('|', $c, 2);
        if (ctype_digit((string)$uid)) {
                    if (!class_exists('User')) require_once __DIR__ . '/../../APP/MODEL/User.php';
                    $um = new User();
                    $user = $um->findById($uid);

            if ($user && !empty($user['password'])) {
                $secret = defined('AUTH_SECRET') ? AUTH_SECRET : '';
                $expected = hash_hmac('sha256', $user['id'] . '|' . ($user['password'] ?? ''), $secret);
                if (hash_equals($expected, $sig)) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'] ?? '';
                    $_SESSION['user_name'] = $user['name'] ?? '';
                    $_SESSION['user_avatar'] = $user['avatar'] ?? '';
                    $_SESSION['user_role'] = $user['role'] ?? 'user';
                } else {
                        setcookie(AUTH_COOKIE_NAME, '', time() - 3600, '/');
                }
            } else {
                    setcookie(AUTH_COOKIE_NAME, '', time() - 3600, '/');
            }
        }
    }
}

// Base URL for assets and internal links (works in subfolders)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'Natiora_Radio_98.2' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $base; ?>/assets/css/style.css" rel="stylesheet">
    <!-- Explicit favicon to avoid default /favicon.ico 404 -->
    <link rel="icon" type="image/png" href="<?php echo $base; ?>/assets/images/acceuil.jpg">
</head>
<body<?php if (defined('DEV_ADMIN') && DEV_ADMIN) echo ' class="dev-admin"'; ?>>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Créer une instance du contrôleur pour vérifier si l'utilisateur est connecté
    $isLoggedIn = !empty($_SESSION['user_id']);
    $userName = $_SESSION['user_name'] ?? '';
    $userEmail = $_SESSION['user_email'] ?? '';
    $userAvatar = $_SESSION['user_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=random';
    ?>

    <!-- Header -->
    <header>
        <div class="header-inner">
            <div class="brand">
                <img src="<?php echo $base; ?>/assets/images/LOGO%20RADIO.jpg" alt="Natiora Logo" class="logo">
                <h1>Natiora Radio <span>98.2</span></h1>
            </div>
            <nav class="main-nav" aria-label="Main navigation">
                <a href="<?php echo $base; ?>/index.php?route=home" class="nav-btn">Accueil</a>
                <a href="<?php echo $base; ?>/index.php?route=playlistes" class="nav-btn">Playlists</a>
                <a href="<?php echo $base; ?>/index.php?route=historiques" class="nav-btn">Historique</a>
                <a href="<?php echo $base; ?>/index.php?route=emissions" class="nav-btn">Émissions</a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="<?php echo $base; ?>/index.php?route=admin/messages" class="nav-btn" id="adminMessagesNav">
                        Messages <span id="adminMessagesNavBadge" style="display:inline-block;margin-left:6px;background:#e74c3c;color:#fff;border-radius:12px;padding:2px 7px;font-size:0.8em;vertical-align:middle;display:none;">0</span>
                    </a>
                <?php endif; ?>
                <!-- Theme toggle -->
                <button id="themeToggle" title="Basculer thème" class="nav-btn" style="margin-left:8px;display:flex;align-items:center;gap:8px;">
                    <i id="themeToggleIcon" class="fas fa-moon"></i>
                    <span id="themeToggleText" style="font-weight:600;font-size:0.95em">Sombre</span>
                </button>
                
                <?php if ($isLoggedIn): ?>
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu(event)">
                            <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Avatar" class="user-avatar-small">
                            <span><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="<?php echo $base; ?>/index.php?route=auth/profile" class="dropdown-item">
                                <i class="fas fa-user-circle"></i> Mon profil
                            </a>
                            <?php 
                            $userRole = $_SESSION['user_role'] ?? 'user';
                            if ($userRole === 'admin'): 
                            ?>
                                            <a href="<?php echo $base; ?>/index.php?route=admin" class="dropdown-item">
                                                <i class="fas fa-crown"></i> Tableau de bord
                                            </a>
                                            <a href="<?php echo $base; ?>/index.php?route=admin/messages" class="dropdown-item" id="adminMessagesLink">
                                                <i class="fas fa-envelope"></i> Messages <span id="adminMessagesBadge" style="display:inline-block;margin-left:8px;background:#e74c3c;color:#fff;border-radius:12px;padding:2px 8px;font-size:0.8em;vertical-align:middle;display:none;">0</span>
                                            </a>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo $base; ?>/index.php?route=auth/logout" style="margin:0;">
                                <button type="submit" class="dropdown-item logout" style="background:none;border:none;padding:12px 16px;text-align:left;width:100%;cursor:pointer;">
                                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="#" class="nav-btn open-login">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php
    // page-specific hero image selection (prefer premium _premium.svg when present)
    $routeName = $_GET['route'] ?? 'home';
    $map = [
        'home' => 'hero_home',
        'playlistes' => 'hero_playlist',
        'emissions' => 'hero_emission',
        'historiques' => 'hero_history',
        'auth/profile' => 'hero_profile',
        'admin' => 'hero_admin'
    ];
    $key = $map[$routeName] ?? $map['home'];
    // prefer premium file if it exists
    $candidatePremium = __DIR__ . '/../../public/assets/images/' . $key . '_premium.svg';
    $candidateNormal = __DIR__ . '/../../public/assets/images/' . $key . '.svg';
    if (file_exists($candidatePremium)) {
        $hero = $base . '/assets/images/' . $key . '_premium.svg';
    } elseif (file_exists($candidateNormal)) {
        $hero = $base . '/assets/images/' . $key . '.svg';
    } else {
        // fallbacks to older JPGs if no SVG found
        $fallbacks = [
            'home' => $base . '/assets/images/acceuil.jpg',
            'playlistes' => $base . '/assets/images/playliste1.jpg',
            'emissions' => $base . '/assets/images/LOGO%20RADIO.jpg',
            'historiques' => $base . '/assets/images/LOGO%20VAO.jpg',
            'auth/profile' => $base . '/assets/images/LOGO%20VAO.jpg',
            'admin' => $base . '/assets/images/LOGO%20RADIO.jpg'
        ];
        $hero = $fallbacks[$routeName] ?? $fallbacks['home'];
    }
    ?>
    <div class="page-hero" style="background-image: url('<?php echo htmlspecialchars($hero); ?>');"></div>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash'])): 
        // Support both string flash and array flash(['type'=>'...', 'msg'=>'...'])
        $flashType = $_SESSION['flash_type'] ?? 'success';
        $flashMsg = '';
        if (is_array($_SESSION['flash'])) {
            $flashType = $_SESSION['flash']['type'] ?? $flashType;
            $flashMsg = $_SESSION['flash']['msg'] ?? '';
        } else {
            $flashMsg = (string)$_SESSION['flash'];
        }
    ?>
        <?php
            $cssClass = 'success';
            $icon = 'check-circle';
            if ($flashType === 'error') { $cssClass = 'error'; $icon = 'exclamation-circle'; }
            elseif ($flashType === 'info') { $cssClass = 'info'; $icon = 'info-circle'; }
        ?>
        <div class="alert alert-<?php echo $cssClass; ?>" style="margin: 20px auto; max-width: 1200px; width: 90%;">
            <i class="fas fa-<?php echo $icon; ?>"></i>
            <?php echo htmlspecialchars($flashMsg); ?>
        </div>
        <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?php 
        if (!empty($view) && file_exists(__DIR__ . '/../' . $view)) {
            include __DIR__ . '/../' . $view;
        } else {
            echo '<div style="text-align: center; padding: 40px; color: #f5576c;"><h2>Vue introuvable</h2></div>';
        }
        ?>
    </main>

    <!-- Footer -->
    <footer>
        <p>© 2026 Natiora Radio 98.2 - created by Faniry Rabearisoa
           
        </p>
    </footer>

    <!-- Login Modal -->
    <?php include __DIR__ . '/../auth/login_modal.php'; ?>

    <style>
        .user-menu {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 20px;
            background: rgba(0,255,231,0.08);
            color: #00ffe7;
            border: 1px solid rgba(0,255,231,0.12);
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .user-btn:hover {
            background: #00ffe7;
            color: #000;
        }

        .user-avatar-small {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            min-width: 200px;
            z-index: 1001;
            margin-top: 8px;
        }

        .dropdown-menu.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f9f9f9;
            color: #667eea;
        }

        .dropdown-item.logout {
            color: #f5576c;
        }

        .dropdown-item.logout:hover {
            background: #ffe0e0;
            color: #f5576c;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            .user-btn span {
                display: none;
            }
        }
    </style>
    <style>
        .page-hero{ height:180px; background-size:cover; background-position:center; border-bottom:1px solid rgba(0,0,0,0.06); filter:brightness(.9); }
        body.theme-dark .page-hero{ filter:brightness(.6); }
        @media (max-width:900px){ .page-hero{ height:120px } }
    </style>

    <script>
        // Application base path for JS (useful when app runs in a subfolder)
        window.APP_BASE = '<?php echo $base; ?>';
            // Theme: read saved preference or system preference and apply class to <body>
        (function(){
            const toggle = document.getElementById('themeToggle');
            const icon = document.getElementById('themeToggleIcon');
            const label = document.getElementById('themeToggleText');
            const apply = (mode)=>{
                document.body.classList.remove('theme-light','theme-dark');
                document.body.classList.add(mode==='light' ? 'theme-light' : 'theme-dark');
                if (icon) icon.className = 'fas ' + (mode==='light' ? 'fa-sun' : 'fa-moon');
                if (label) label.textContent = (mode==='light' ? 'Clair' : 'Sombre');
                if (toggle) toggle.title = mode==='light' ? 'Passer en sombre' : 'Passer en clair';
            };
            // initial
            let saved = null;
            try{ saved = localStorage.getItem('natiora_theme'); }catch(e){}
            if (!saved) {
                const prefers = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
                saved = prefers;
            }
            apply(saved);
            if (toggle) toggle.addEventListener('click', function(ev){
                ev.preventDefault();
                const current = document.body.classList.contains('theme-light') ? 'light' : 'dark';
                const next = current === 'light' ? 'dark' : 'light';
                try{ localStorage.setItem('natiora_theme', next); }catch(e){}
                apply(next);
            });
        })();
        function toggleUserMenu(event) {
            event.preventDefault();
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const userBtn = event.target.closest('.user-btn');
            if (!userBtn && dropdown) {
                dropdown.classList.remove('show');
            }
        });

        // Admin messages badge: poll unread count and update badge (dropdown + nav)
        window.updateMessagesBadge = async function(){
            try{
                const res = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/unread_count', { credentials: 'same-origin' });
                if (!res.ok) return;
                const j = await res.json();
                if (j && j.ok) {
                    const n = parseInt(j.count || 0, 10);
                    const badge1 = document.getElementById('adminMessagesBadge');
                    const badge2 = document.getElementById('adminMessagesNavBadge');
                    if (badge1) { if (n>0) { badge1.style.display='inline-block'; badge1.textContent=n; } else { badge1.style.display='none'; } }
                    if (badge2) { if (n>0) { badge2.style.display='inline-block'; badge2.textContent=n; } else { badge2.style.display='none'; } }
                }
            }catch(err){ /* ignore network errors */ }
        };

        // Start polling if user is admin
        (function(){
            var role = '<?php echo $_SESSION['user_role'] ?? 'user'; ?>';
            if (role === 'admin') {
                updateMessagesBadge();
                setInterval(updateMessagesBadge, 15000);
            }
        })();
    </script>
    <style>
        /* Theme variables: .theme-dark and .theme-light on <body> control colors */
        body.theme-dark { --bg:#0b0f14; --card:#0f1724; --muted:#94a3b8; --accent:#6c63ff; --accent-2:#00c2a8; --danger:#ff6b6b; --text:#e6eef6; }
        body.theme-light { --bg:#ffffff; --card:#ffffff; --muted:#64748b; --accent:#4f46e5; --accent-2:#06b6d4; --danger:#ef4444; --text:#0b1220; }
        body { background: var(--bg); color: var(--text); }
        header .header-inner { background: transparent; }
        .brand h1, .brand h1 span { color: var(--text); }
        .main-nav .nav-btn { color: var(--text); }
        .main-nav .nav-btn:hover { background: rgba(255,255,255,0.03); border-radius:8px; }
        .dropdown-menu { background: var(--card); color: var(--text); border: 1px solid rgba(0,0,0,0.04); }
        .dropdown-item { color: var(--text); border-bottom: 1px solid rgba(0,0,0,0.04); }
        .dropdown-item:hover { background: rgba(0,0,0,0.02); color: var(--text); }
        .user-btn { background: rgba(255,255,255,0.02); color: var(--text); border:1px solid rgba(0,0,0,0.04); }
        .alert { background: rgba(255,255,255,0.02); color: var(--text); border-left: none; }
        .alert.alert-error { background: linear-gradient(90deg, rgba(231,76,60,0.12), rgba(231,76,60,0.04)); color: #ffd6d6; }
        .alert.alert-success { background: linear-gradient(90deg, rgba(0,200,150,0.06), rgba(0,200,150,0.02)); color: #d9ffef; }
        main { min-height: 60vh; }
        footer { color: var(--muted); }
    </style>
</body>
</html>
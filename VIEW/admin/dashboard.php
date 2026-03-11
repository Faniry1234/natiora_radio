<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Base URL for assets and internal links (works in subfolders)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

$current_user_name = $_SESSION['user_name'] ?? 'Admin';
$current_user_avatar = $_SESSION['user_avatar'] ?? 'https://ui-avatars.com/api/?name=Admin&background=f5576c';
?>
<?php
// Debug visible only when controlled by DEBUG_ADMIN or DEV_ADMIN constants
if ((defined('DEBUG_ADMIN') && DEBUG_ADMIN) || (defined('DEV_ADMIN') && DEV_ADMIN)) {
    $sess_id = $_SESSION['user_id'] ?? '(none)';
    $sess_role = $_SESSION['user_role'] ?? '(none)';
    $u_count = is_array($users) ? count($users) : 0;
    $e_count = isset($stats['total_emissions']) ? intval($stats['total_emissions']) : 0;
    $p_count = isset($stats['total_playlists']) ? intval($stats['total_playlists']) : (is_array($recent_playlists) ? count($recent_playlists) : 0);
    echo "<div style='background:#e2e3e5;color:#383d41;padding:10px;border:1px solid #d6d8db;margin:12px;border-radius:6px;max-width:1200px;'>DEBUG VIEW — session: {$sess_id}/{$sess_role} — users: {$u_count} — emissions: {$e_count} — playlists: {$p_count}</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tableau de Bord</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/public/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
            font-size: 16px;
            line-height: 1.5;
        }

        .admin-wrapper {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            width: 100%;
        }

        /* SIDEBAR */
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px 20px;
            /* use sticky instead of fixed so the sidebar participates in grid layout */
            position: sticky;
            top: 0;
            height: 100vh;
            width: 260px;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.08);
            z-index: 1000;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4em;
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-logo i {
            font-size: 1.6em;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding-left: 20px;
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }

        .user-card img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 8px;
        }

        .user-card p {
            font-size: 0.9em;
            margin: 0;
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .footer-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: rgba(245, 87, 108, 0.3);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-align: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            width: 100%;
        }

        .logout-link:hover {
            background: rgba(245, 87, 108, 0.5);
        }

        /* MAIN CONTENT */
        .admin-main {
            /* rely on grid columns instead of manual margin */
            margin-left: 0;
            padding: 30px;
            width: 100%;
            box-sizing: border-box;
        }

        .admin-topbar {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .admin-topbar h1 {
            color: #333;
            font-size: 1.8em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-actions {
            display: flex;
            gap: 10px;
        }

        .topbar-actions a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .topbar-actions a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* FLASH MESSAGE */
        .flash-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideInUp 0.4s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flash-message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .flash-message.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff, #f8f9ff);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            opacity: 0.1;
            border-radius: 50%;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        :root{
            --brand-1: #2b2d42; /* deep slate */
            --brand-2: #8d99ae; /* soft gray */
            --accent-1: #ef476f; /* rose */
            --accent-2: #ffd166; /* warm gold */
            --muted: #6c7b8a;
        }

        .stat-card.users {
            border-color: var(--brand-2);
        }

        .stat-card.users::before {
            background: var(--brand-2);
        }

        .stat-card.emissions {
            border-color: var(--accent-1);
        }

        .stat-card.emissions::before {
            background: var(--accent-1);
        }

        .stat-card.playlists {
            border-color: var(--accent-2);
        }

        .stat-card.playlists::before {
            background: var(--accent-2);
        }

        .stat-card.emissions { border-left-color: var(--accent-1); }
        .stat-card.playlists { border-left-color: var(--accent-2); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.95em;
            margin: 0;
            font-weight: 600;
        }

        .stat-card .icon {
            font-size: 2em;
            color: #667eea;
        }

        .stat-card.emissions .icon {
            color: #f5576c;
        }

        .stat-card.playlists .icon {
            color: #764ba2;
        }

        .stat-number {
            font-size: 3.2em;
            font-weight: 800;
            background: linear-gradient(90deg, #2b2d42, #ef476f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            display: block;
            color: #4b2f5a;
        }

        .stat-description {
            color: #666;
            font-size: 0.95em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* MENU CARDS */
        .admin-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            align-items: start;
        }

        .menu-card {
            background: linear-gradient(135deg, #ffffff, #f6f6f8);
            border-radius: 15px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.35s ease;
            cursor: pointer;
            box-shadow: 0 8px 26px rgba(39, 44, 59, 0.06);
            border: 1px solid rgba(43,45,66,0.04);
            position: relative;
            overflow: hidden;
        }

        .menu-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(245, 87, 108, 0.1));
            border-radius: 50%;
            transition: all 0.6s ease;
        }

        .menu-card:hover::before {
            top: -20%;
            right: -20%;
        }

        .menu-card:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            transform: translateY(-10px);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .menu-card .icon {
            font-size: 4em;
            background: linear-gradient(135deg, #667eea, #764ba2, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            display: block;
            position: relative;
            z-index: 1;
        }

        .menu-card h3 {
            color: #333;
            margin: 0 0 15px 0;
            font-size: 1.6em;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .menu-card p {
            color: #334155;
            margin: 0 0 20px 0;
            line-height: 1.6;
            font-size: 0.98em;
            position: relative;
            z-index: 1;
        }

        .menu-card a {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }

        .menu-card a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            .admin-wrapper {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 15px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            }

            .admin-main {
                margin-left: 0;
                padding: 15px;
            }

            .admin-topbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .topbar-actions {
                flex-wrap: wrap;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Dark theme overrides for admin area */
        :root {
            --bg-1: #0b1020; /* page background */
            --bg-2: #0f1724; /* panels */
            --panel-border: rgba(255,255,255,0.04);
            --muted-text: #9aa4b2;
            --text: #e6eef8;
            --accent: #7c5cff;
            --card-grad: linear-gradient(135deg,#0d1220,#121826);
        }

        body {
            background: var(--bg-1);
            color: var(--text);
        }

        .admin-sidebar {
            background: linear-gradient(180deg,#0b1222,#0d1626);
            color: var(--text);
            box-shadow: none;
        }

        .sidebar-menu a { color: var(--muted-text); }
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.03); color: var(--text); }

        .admin-topbar {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--panel-border);
            box-shadow: 0 2px 10px rgba(2,6,23,0.6);
        }

        .panel, .menu-card, .stat-card, .panel-list, .user-table, .panel-empty {
            background: var(--bg-2);
            color: var(--text);
            border: 1px solid var(--panel-border);
        }

        .stat-card { background: var(--card-grad); }

        .stat-card h3, .stat-description, .meta { color: var(--muted-text); }
        .stat-number { -webkit-text-fill-color: unset; color: var(--text); }

        .panel-header { border-bottom: 1px solid rgba(255,255,255,0.03); }

        .panel-item { border-bottom: 1px solid rgba(255,255,255,0.02); }

        .action-btn, .topbar-actions a, .topbar-actions button { background: linear-gradient(135deg,var(--accent),#3b82f6); color: white; }

        .user-card { background: rgba(255,255,255,0.03); }

        .panel-empty { color: var(--muted-text); }

        /* ensure scrollbar is subtle on dark background */
        #dash-messages-items::-webkit-scrollbar { height: 8px; width: 8px; }
        #dash-messages-items::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); border-radius: 6px; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-crown"></i>
            <span>NATIORA</span>
        </div>

        <ul class="sidebar-menu">
            <li><a href="<?php echo $base; ?>/index.php?route=admin" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/emissions"><i class="fas fa-play-circle"></i> Émissions</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/playlists"><i class="fas fa-music"></i> Playlistes</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/historiques"><i class="fas fa-history"></i> Historiques</a></li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-card">
                <img src="<?php echo htmlspecialchars($current_user_avatar); ?>" alt="Avatar">
                <p><strong><?php echo htmlspecialchars($current_user_name); ?></strong></p>
                <small style="color: rgba(255, 255, 255, 0.7);">Administrateur</small>
            </div>
            <a href="<?php echo $base; ?>/index.php?route=home" class="footer-link">
                <i class="fas fa-home"></i> Accueil
            </a>
            <a href="<?php echo $base; ?>/index.php?route=auth/profile" class="footer-link">
                <i class="fas fa-user"></i> Mon Profil
            </a>
            <form method="POST" action="<?php echo $base; ?>/index.php?route=auth/logout" style="display: block; margin-top: 10px;">
                <button type="submit" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main">
        <?php
        // Inline fallback & debug: if controller didn't provide data, try loading DATA files here
        $debugMode = (isset($_GET['debug_admin']) && $_GET['debug_admin'] == '1') || (isset($_GET['dev_admin']) && $_GET['dev_admin'] == '1');
        $dataDir = __DIR__ . '/../../DATA/';
        if (empty($stats)) {
            $stats = ['total_users'=>0,'total_emissions'=>0,'total_playlists'=>0];
        }
        if (empty($recent_emissions)) {
            if (file_exists($dataDir . 'emissions.php')) {
                $raw = include $dataDir . 'emissions.php';
                $recent_emissions = is_array($raw) ? array_slice($raw,0,8) : [];
                $stats['total_emissions'] = is_array($raw) ? count($raw) : 0;
            }
        }
        if (empty($recent_playlists)) {
            if (file_exists($dataDir . 'playlists.php')) {
                $raw = include $dataDir . 'playlists.php';
                $recent_playlists = is_array($raw) ? array_slice($raw,0,6) : [];
                $stats['total_playlists'] = is_array($raw) ? count($raw) : 0;
            }
        }
        if (empty($users)) {
            if (file_exists($dataDir . 'users.php')) {
                $raw = include $dataDir . 'users.php';
                $users = is_array($raw) ? $raw : [];
                $stats['total_users'] = is_array($raw) ? count($raw) : 0;
            }
        }

        if ($debugMode) {
            echo "<div style='max-width:1200px;margin:12px;padding:12px;background:#fff;border:1px solid #ccc;'>";
            echo "<strong>VIEW DEBUG</strong> — users=" . count($users) . ", playlists=" . count($recent_playlists) . ", recent_emissions=" . count($recent_emissions);
            echo "</div>";

            // Visible diagnostics: dump the raw arrays so we can verify content server-side
            echo "<div style='max-width:1200px;margin:12px;padding:12px;background:#f7f7f7;border:1px solid #ddd;border-radius:6px;color:#111;'>";
            echo "<strong>RAW DATA DUMP (temporary)</strong><pre style='white-space:pre-wrap;font-size:13px;color:#222;'>";
            echo "stats: " . htmlspecialchars(json_encode($stats ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "\n\n";
            echo "recent_emissions: " . htmlspecialchars(json_encode($recent_emissions ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "\n\n";
            echo "recent_playlists: " . htmlspecialchars(json_encode($recent_playlists ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "\n\n";
            echo "users: " . htmlspecialchars(json_encode($users ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "</pre></div>";
        }
        ?>
        <!-- TOPBAR -->
        <div class="admin-topbar">
                <h1><i class="fas fa-chart-line"></i> Tableau de Bord</h1>
            <div class="topbar-right">
                <a href="<?php echo $base; ?>/index.php?route=home" class="action-btn"><i class="fas fa-globe"></i> Voir le site</a>
                <form method="POST" action="<?php echo $base; ?>/index.php?route=auth/logout" style="display:inline-block; margin:0;">
                    <button type="submit" class="logout-top" style="background:transparent;border:none;color:#fff;padding:10px 14px;border-radius:6px;cursor:pointer;"><i class="fas fa-sign-out-alt"></i> Déconnexion</button>
                </form>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash'])):
            $flashRaw = $_SESSION['flash'];
            if (is_array($flashRaw)) {
                $flashType = $flashRaw['type'] ?? 'info';
                $flashMsg = $flashRaw['msg'] ?? '';
            } else {
                $flashType = 'info';
                $flashMsg = (string)$flashRaw;
            }
        ?>
            <div class="flash-message <?php echo htmlspecialchars($flashType); ?>">
                <i class="fas fa-<?php echo ($flashType === 'success') ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flashMsg); ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- STATS SECTION -->
        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-card-header">
                    <h3>Utilisateurs</h3>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-number"><?php echo $stats['total_users'] ?? 0; ?></div>
                <div class="stat-description">Utilisateurs actifs</div>
            </div>

            <div class="stat-card emissions">
                <div class="stat-card-header">
                    <h3>Émissions</h3>
                    <div class="icon"><i class="fas fa-play-circle"></i></div>
                </div>
                <div class="stat-number"><?php echo $stats['total_emissions'] ?? 0; ?></div>
                <div class="stat-description">Émissions programmées</div>
            </div>

            <div class="stat-card playlists">
                <div class="stat-card-header">
                    <h3>Playlistes</h3>
                    <div class="icon"><i class="fas fa-music"></i></div>
                </div>
                <div class="stat-number"><?php echo $stats['total_playlists'] ?? 0; ?></div>
                <div class="stat-description">Playlistes disponibles</div>
            </div>
        </div>
        <!-- MANAGEMENT SECTION -->
        <h2 style="color: #333; margin: 40px 0 25px 0; font-size: 1.5em;">Gestion du contenu</h2>
        <div class="admin-menu">
            <div class="menu-card">
                <div class="icon"><i class="fas fa-video"></i></div>
                <h3>Émissions</h3>
                <p>Créer, modifier et supprimer les émissions de la programmation hebdomadaire</p>
                <a href="<?php echo $base; ?>/index.php?route=admin/emissions"><i class="fas fa-arrow-right"></i> Gérer</a>
            </div>

            <div class="menu-card">
                <div class="icon"><i class="fas fa-compact-disc"></i></div>
                <h3>Playlistes</h3>
                <p>Organiser et gérer les playlistes de contenu musical et vidéo</p>
                <a href="<?php echo $base; ?>/index.php?route=admin/playlists"><i class="fas fa-arrow-right"></i> Gérer</a>
            </div>

            <div class="menu-card">
                <div class="icon"><i class="fas fa-history"></i></div>
                <h3>Historiques</h3>
                <p>Consulter l'historique des actions des utilisateurs</p>
                <a href="<?php echo $base; ?>/index.php?route=admin/historiques"><i class="fas fa-arrow-right"></i> Consulter</a>
            </div>
        </div>

        <!-- RECENT / QUICK MANAGEMENT -->
        <div class="admin-panels">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">Émissions récentes</div>
                    <div></div>
                </div>
                <?php if (!empty($recent_emissions)): ?>
                    <div class="panel-list">
                        <?php foreach ($recent_emissions as $em): ?>
                            <div class="panel-item">
                                <div>
                                    <div style="font-weight:700;"><i class="fas fa-play-circle"></i> <?php echo htmlspecialchars($em['title'] ?? $em['name'] ?? 'N/A'); ?></div>
                                    <div class="meta"><strong><?php echo htmlspecialchars($em['_day']); ?></strong> • <?php echo htmlspecialchars($em['time'] ?? ''); ?> — <?php echo htmlspecialchars($em['presenter'] ?? ''); ?></div>
                                </div>
                                <div>
                                    <a href="<?php echo $base; ?>/index.php?route=admin/emissions&day=<?php echo urlencode($em['_day']); ?>" class="action-btn">Gérer</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="panel-empty"><i class="fas fa-inbox"></i> Aucune émission récente</div>
                <?php endif; ?>
            </div>

            <div>
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">Playlistes récentes</div>
                        <div></div>
                    </div>
                    <?php if (!empty($recent_playlists)): ?>
                        <div class="panel-list">
                        <?php foreach ($recent_playlists as $pl): ?>
                            <div class="panel-item">
                                <div>
                                    <div style="font-weight:700;"><i class="fas fa-compact-disc"></i> <?php echo htmlspecialchars($pl['title'] ?? 'Untitled'); ?></div>
                                    <div class="meta"><?php echo htmlspecialchars(mb_substr($pl['desc'] ?? '', 0, 80)); ?></div>
                                </div>
                                <div>
                                    <a href="<?php echo $base; ?>/index.php?route=admin/playlists" class="action-btn">Gérer</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="panel-empty"><i class="fas fa-inbox"></i> Aucune playlist récente</div>
                    <?php endif; ?>
                </div>

                <div class="panel" style="margin-top:12px;">
                    <div class="panel-header">
                        <div class="panel-title">Actions rapides</div>
                        <div></div>
                    </div>
                    <div class="actions">
                                        <a href="<?php echo $base; ?>/index.php?route=admin/emissions" class="action-btn"> <i class="fas fa-play-circle"></i> Gérer les émissions</a>
                        <a href="<?php echo $base; ?>/index.php?route=admin/playlists" class="action-btn"> <i class="fas fa-music"></i> Gérer les playlistes</a>
                        <a href="<?php echo $base; ?>/index.php?route=admin/historiques" class="action-btn"> <i class="fas fa-history"></i> Voir les historiques</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- QUICK MESSAGES WIDGET -->
        <div style="margin-top:30px;">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">Messages rapides</div>
                    <div></div>
                </div>
                <div style="display:flex;gap:18px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:320px;max-width:640px;">
                        <div id="dash-messages-list" style="border:1px solid #eef2f6;border-radius:10px;overflow:hidden;background:#fff;">
                            <div style="padding:12px;border-bottom:1px solid #f5f7fa;display:flex;justify-content:space-between;align-items:center;">
                                <strong>Derniers messages</strong>
                                <button id="dash-refresh-messages" class="btn-ghost">Rafraîchir</button>
                            </div>
                            <div id="dash-messages-items" style="max-height:260px;overflow:auto;padding:8px 10px;"></div>
                        </div>
                    </div>

                    <div style="width:360px;min-width:260px;">
                        <div style="border:1px solid #eef2f6;border-radius:10px;padding:12px;background:#fff;">
                            <div id="dash-message-empty" style="color:#666;text-align:center;padding:30px;">Sélectionnez un message pour répondre</div>
                            <div id="dash-message-detail" style="display:none;">
                                <h4 id="dash-detail-subject" style="margin:0 0 6px 0"></h4>
                                <div id="dash-detail-meta" style="color:#888;font-size:0.9em;margin-bottom:8px"></div>
                                <div id="dash-detail-body" style="white-space:pre-wrap;color:#333;margin-bottom:12px"></div>
                                <form id="dash-reply-form">
                                    <input type="hidden" id="dash-reply-recipient" name="recipient_id" value="">
                                    <div style="margin-bottom:8px"><textarea id="dash-reply-body" name="body" rows="4" placeholder="Votre réponse" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;"></textarea></div>
                                    <div style="display:flex;gap:8px;justify-content:flex-end;"><button type="submit" class="topbar-actions" style="padding:8px 12px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:8px;border:none;cursor:pointer;">Envoyer</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- USERS PANEL -->
        <div style="margin-top:30px;">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">Utilisateurs</div>
                    <div></div>
                </div>
                <?php if (empty($users)): ?>
                    <div class="panel-empty"><i class="fas fa-user-friends"></i> Aucun utilisateur</div>
                <?php else: ?>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom / Email</th>
                                <th>Rôle</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($u['avatar'] ?? ''); ?>" alt="avatar" class="user-avatar"></td>
                                <td><strong><?php echo htmlspecialchars($u['name'] ?? $u['email']); ?></strong><br><small style="color:#666;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></small></td>
                                <td><span class="role-badge"><?php echo htmlspecialchars($u['role'] ?? 'user'); ?></span></td>
                                <td style="text-align:right;">
                                    <form method="POST" style="display:inline-block; margin-left:6px;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <?php if (($u['role'] ?? 'user') !== 'admin'): ?>
                                            <input type="hidden" name="user_action" value="promote">
                                            <button class="btn-small btn-promote" type="submit" title="Promouvoir"><i class="fas fa-user-shield"></i></button>
                                        <?php else: ?>
                                            <?php if (($u['id'] ?? 0) !== ($_SESSION['user_id'] ?? 0)): ?>
                                                <input type="hidden" name="user_action" value="demote">
                                                <button class="btn-small btn-demote" type="submit" title="Rétrograder"><i class="fas fa-user-alt-slash"></i></button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </form>
                                    <?php if (($u['id'] ?? 0) !== ($_SESSION['user_id'] ?? 0)): ?>
                                    <form method="POST" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="user_action" value="delete">
                                        <button class="btn-small btn-delete" type="submit" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    (function(){
        const base = '<?php echo isset($base) ? $base : ""; ?>';
        const listEl = document.getElementById('dash-messages-items');
        const refreshBtn = document.getElementById('dash-refresh-messages');
        const emptyEl = document.getElementById('dash-message-empty');
        const detailEl = document.getElementById('dash-message-detail');
        const detailSubject = document.getElementById('dash-detail-subject');
        const detailMeta = document.getElementById('dash-detail-meta');
        const detailBody = document.getElementById('dash-detail-body');
        const replyForm = document.getElementById('dash-reply-form');
        const replyRecipient = document.getElementById('dash-reply-recipient');
        const replyBody = document.getElementById('dash-reply-body');

        function apiPath(route){
            return base + '/index.php?route=' + route;
        }

        async function loadMessages(){
            if(!listEl) return;
            listEl.innerHTML = '<div style="padding:12px;color:#666">Chargement...</div>';
            try{
                const res = await fetch(apiPath('api/messages/list'));
                const data = await res.json();
                renderList(data);
            }catch(err){
                listEl.innerHTML = '<div style="padding:12px;color:#900">Erreur de chargement</div>';
            }
        }

        function renderList(items){
            if(!items || items.length===0){
                listEl.innerHTML = '<div style="padding:16px;color:#666">Aucun message.</div>';
                return;
            }
            listEl.innerHTML = '';
            items.slice(0,8).forEach(msg=>{
                const item = document.createElement('div');
                item.style.padding = '10px';
                item.style.borderBottom = '1px solid #f5f7fa';
                item.style.cursor = 'pointer';
                if(!msg.read) item.style.background = '#f7f9fc';
                item.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center"><div><strong>'+(msg.sender_name||'Anonyme')+'</strong> <span style="color:#888;font-size:0.9em">'+(msg.created_at||'')+'</span><div style="color:#333;margin-top:6px">'+escapeHtml(truncate(msg.body,120))+'</div></div><div style="margin-left:8px;color:#888;font-size:0.9em">'+(msg.read? 'Lu' : '<span style="color:#b33">Nouveau</span>')+'</div></div>';
                item.addEventListener('click',()=>{ showDetail(msg); markRead(msg.id); });
                listEl.appendChild(item);
            });
        }

        function truncate(s,n){ return s && s.length>n ? s.slice(0,n-1)+'…' : s; }
        function escapeHtml(unsafe){ return unsafe? unsafe.replace(/[&<>"']/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#039;"}[m];}) : ''; }

        function showDetail(msg){
            if(!emptyEl || !detailEl) return;
            emptyEl.style.display = 'none';
            detailEl.style.display = 'block';
            detailSubject.textContent = 'Message de ' + (msg.sender_name || 'Anonyme');
            detailMeta.textContent = (msg.sender_email? msg.sender_email + ' • ' : '') + (msg.created_at || '');
            detailBody.textContent = msg.body || '';
            replyRecipient.value = msg.sender_id || '';
            replyBody.value = '';
        }

        async function markRead(id){
            try{ await fetch(apiPath('api/messages/mark_read'), {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})});
                if(window.updateMessagesBadge) window.updateMessagesBadge();
                loadMessages();
            }catch(e){console.warn(e)}
        }

        if(replyForm){
            replyForm.addEventListener('submit', async function(ev){
                ev.preventDefault();
                const recipient = replyRecipient.value;
                const body = replyBody.value.trim();
                if(!recipient || !body) return alert('Veuillez saisir un message.');
                try{
                    const res = await fetch(apiPath('api/messages/send'), {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({recipient_id:recipient,body:body})});
                    const ok = await res.json();
                    if(ok && (ok.success || ok.id)){
                        replyBody.value='';
                        loadMessages();
                        if(window.updateMessagesBadge) window.updateMessagesBadge();
                        alert('Réponse envoyée');
                    }else alert('Erreur lors de l\'envoi');
                }catch(err){console.error(err);alert('Erreur réseau');}
            });
        }

        if(refreshBtn) refreshBtn.addEventListener('click',loadMessages);
        loadMessages();
    })();
</script>

<script>
    // Set active menu item based on current URL
    document.addEventListener('DOMContentLoaded', function() {
        const currentRoute = new URLSearchParams(window.location.search).get('route') || 'admin';
        const menuLinks = document.querySelectorAll('.sidebar-menu a');
        
        menuLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(currentRoute)) {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>

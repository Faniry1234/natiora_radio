<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Base URL for assets and internal links (works in subfolders)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Historiques</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/public/assets/css/style.css">
    <style>
        .admin-main { margin-left:260px; padding:30px; }
        .hist-table { width:100%; border-collapse:collapse; }
        .hist-table th, .hist-table td { padding:10px; text-align:left; border-bottom:1px solid #eee; }
        .filter-row { display:flex; gap:8px; align-items:center; margin-bottom:12px; }
        .btn-clear { background:#f44336;color:#fff;padding:8px 12px;border-radius:6px;border:none; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <!-- SIDEBAR (copied from dashboard for consistency) -->
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-crown"></i>
            <span>NATIORA</span>
        </div>

        <ul class="sidebar-menu">
            <li><a href="<?php echo $base; ?>/index.php?route=admin"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/emissions"><i class="fas fa-play-circle"></i> Émissions</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/playlists"><i class="fas fa-music"></i> Playlistes</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/historiques" class="active"><i class="fas fa-history"></i> Historiques</a></li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-card">
                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? 'https://ui-avatars.com/api/?name=Admin&background=f5576c'); ?>" alt="Avatar">
                <p><strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong></p>
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
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-history"></i> Historiques</h1>
            <a href="<?php echo $base; ?>/index.php?route=admin" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>

        <div class="form-section">
            <h2 class="section-title"><i class="fas fa-filter"></i> Filtres</h2>
            <form method="GET" action="<?php echo $base; ?>/index.php?route=admin/historiques">
                <div class="filter-row">
                    <input type="text" name="user" value="<?php echo htmlspecialchars($_GET['user'] ?? ''); ?>" placeholder="Utilisateur (nom ou email)" style="padding:8px;border-radius:6px;border:1px solid #ddd;">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>" style="padding:8px;border-radius:6px;border:1px solid #ddd;">
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>" style="padding:8px;border-radius:6px;border:1px solid #ddd;">
                    <select name="per_page" style="padding:8px;border-radius:6px;border:1px solid #ddd;">
                        <?php $pp = (int)($_GET['per_page'] ?? 50); ?>
                        <option value="25" <?php if($pp===25) echo 'selected'; ?>>25</option>
                        <option value="50" <?php if($pp===50) echo 'selected'; ?>>50</option>
                        <option value="100" <?php if($pp===100) echo 'selected'; ?>>100</option>
                        <option value="200" <?php if($pp===200) echo 'selected'; ?>>200</option>
                    </select>
                    <button type="submit" class="btn-submit">Filtrer</button>
                </div>
            </form>
        </div>

        <div class="list-section" style="margin-top:16px;">
            <h2 class="section-title"><i class="fas fa-list"></i> Lectures récentes
                <small style="font-weight:400;color:#666;">(regroupées par date)</small>
            </h2>
            <?php if (empty($historiques)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Aucun historique de lecture</p>
                </div>
            <?php else: ?>
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Titre</th>
                            <th>Artiste</th>
                            <th>Action</th>
                            <th>Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historiques as $h): 
                            $played_at = $h['played_at'] ?? ($h['created_at'] ?? '');
                            $source = $h['source'] ?? '';
                            $can_replay = false;
                            if (!empty($source) && !empty($played_at)) {
                                $ts = strtotime($played_at);
                                if ($ts !== false && (time() - $ts) <= 48 * 3600) $can_replay = true;
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($played_at); ?></td>
                                <td><?php echo htmlspecialchars($h['title'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($h['artist'] ?? ''); ?></td>
                                <td>
                                    <?php if ($can_replay): ?>
                                        <button class="replay-btn" data-src="<?php echo htmlspecialchars($source); ?>">🔁 Réécouter</button>
                                    <?php else: ?>
                                        <span style="color:#666;">Réécoute indisponible (48h)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($h['user_name'] ?? $h['email'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;margin-top:12px;">
                    <div style="color:#666;">Affichage <?php echo count($historiques); ?> sur <?php echo htmlspecialchars($hist_total ?? 0); ?> entrées</div>
                    <div>
                        <?php
                            $current = max(1, (int)($page ?? ($_GET['page'] ?? 1)));
                            $per_page = (int)($per_page ?? ($_GET['per_page'] ?? 50));
                            $total_pages = max(1, (int)ceil(($hist_total ?? 0)/$per_page));
                            $qp = $_GET;
                            $baseUrl = $base . '/index.php?route=admin/historiques';
                        ?>
                        <?php if ($current > 1): $qp['page']=$current-1; ?>
                            <a href="<?php echo $baseUrl . '&' . http_build_query($qp); ?>" class="btn-clear">&laquo; Précédent</a>
                        <?php endif; ?>
                        <span style="margin:0 8px;">Page <?php echo $current; ?> / <?php echo $total_pages; ?></span>
                        <?php if ($current < $total_pages): $qp['page']=$current+1; ?>
                            <a href="<?php echo $baseUrl . '&' . http_build_query($qp); ?>" class="btn-submit">Suivant &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>

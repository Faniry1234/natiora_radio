<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Base URL for assets and internal links (works in subfolders)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

// Défensive: s'assurer que la variable fournie par le contrôleur est bien un tableau
if (!isset($playlists) || !is_array($playlists)) {
    $playlists = [];
}
// Debug visible only when controlled by DEBUG_ADMIN or DEV_ADMIN constants
if ((defined('DEBUG_ADMIN') && DEBUG_ADMIN) || (defined('DEV_ADMIN') && DEV_ADMIN)) {
    $sess_id = $_SESSION['user_id'] ?? '(none)';
    $sess_role = $_SESSION['user_role'] ?? '(none)';
    $p_count = is_array($playlists) ? count($playlists) : 0;
    echo "<div style='background:#d1ecf1;color:#0c5460;padding:10px;border:1px solid #bee5eb;margin:12px;border-radius:6px;max-width:1200px;'>DEBUG VIEW — session: {$sess_id}/{$sess_role} — playlists: {$p_count}</div>";
    // Visible diagnostic dump for troubleshooting
    echo "<div style='max-width:1200px;margin:12px;padding:12px;background:#f7f7f7;border:1px solid #ddd;border-radius:6px;color:#111;'>";
    echo "<strong>RAW DATA DUMP (temporary)</strong><pre style='white-space:pre-wrap;font-size:13px;color:#222;'>";
    echo "playlists_count: " . htmlspecialchars(json_encode(count($playlists))) . "\n\n";
    echo "playlists_preview: " . htmlspecialchars(json_encode(array_slice($playlists,0,6), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre></div>";
}

$songs_list = [];
foreach ($playlists as $pl) {
    if (!is_array($pl)) continue;
    foreach ($pl['songs'] ?? [] as $song) {
        $songs_list[$song] = true;
    }
}
$songs_list = array_keys($songs_list);

// Regroupement des playlists par jour de création (si disponible)
$days = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];
$playlists_by_day = array_fill_keys($days, []);
$playlists_by_day['autres'] = [];

$engToFr = [
    'Monday' => 'lundi', 'Tuesday' => 'mardi', 'Wednesday' => 'mercredi', 'Thursday' => 'jeudi', 'Friday' => 'vendredi', 'Saturday' => 'samedi', 'Sunday' => 'dimanche'
];

foreach ($playlists as $pl) {
    if (!is_array($pl)) {
        // Skip unexpected entries (defensive)
        continue;
    }
    if (!empty($pl['created_at'])) {
        $eng = date('l', strtotime($pl['created_at']));
        $fr = $engToFr[$eng] ?? 'autres';
        if (in_array($fr, $days)) {
            $playlists_by_day[$fr][] = $pl;
            continue;
        }
    }
    $playlists_by_day['autres'][] = $pl;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion Playlistes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/public/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            // Upload helper for playlist items
            window.uploadForPlaylist = function(btn) {
                var card = btn.closest('.playlist-card');
                if (!card) return alert('Carte introuvable');
                var inp = document.createElement('input'); inp.type = 'file'; inp.accept = 'audio/*';
                inp.onchange = function(){
                    var f = inp.files[0];
                    if (!f) return;
                    var fd = new FormData(); fd.append('file', f);
                    fetch(BASE_URL + '/index.php?route=admin/upload_media', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(j){
                        if (j && j.ok) {
                            // store path on card
                            card.dataset.src = j.path;
                            // append audio preview
                            var preview = card.querySelector('.playlist-audio-preview');
                            if (!preview) { preview = document.createElement('div'); preview.className = 'playlist-audio-preview'; card.querySelector('.playlist-info').appendChild(preview); }
                            preview.innerHTML = '<audio controls style="width:100%; max-width:320px;"><source src="' + j.path + '"></audio>';
                            alert('Fichier téléversé: ' + j.path);
                        } else {
                            alert('Upload failed: ' + (j && j.error ? j.error : 'unknown'));
                        }
                    }).catch(function(e){ alert('Upload error'); console.error(e); });
                };
                inp.click();
            };

            window.saveAllPlaylists = function(){
                var cards = document.querySelectorAll('.playlist-card');
                var payload = [];
                cards.forEach(function(c){
                    var id = c.getAttribute('data-id') || null;
                    var title = c.getAttribute('data-title') || '';
                    var desc = c.getAttribute('data-desc') || '';
                    var cover = c.getAttribute('data-cover') || '';
                    var songs = [];
                    var songsAttr = c.getAttribute('data-songs') || '';
                    if (songsAttr) songs = songsAttr.split('||');
                    var src = c.dataset.src || '';
                    payload.push({ id: id ? parseInt(id,10) : null, title: title, desc: desc, cover: cover, songs: songs, created_at: null, src: src });
                });
                fetch(BASE_URL + '/index.php?route=admin/save_playlists', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify(payload) }).then(r=>r.json()).then(j=>{ if (j && j.ok) { alert('Playlists enregistrées'); location.reload(); } else { alert('Save failed'); console.error(j); } }).catch(e=>{ alert('Save error'); console.error(e); });
            };
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

        .admin-sidebar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px 20px;
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

        .admin-main {
            margin-left: 0;
            padding: 30px;
            width: 100%;
            box-sizing: border-box;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .admin-header h1 {
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8em;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        /* TABS */
        .tabs-container {
            background: white;
            border-radius: 10px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 2px solid #e0e0e0;
            padding: 0;
            margin: 0;
        }

        .tab-btn {
            flex: 1;
            min-width: 100px;
            padding: 15px 20px;
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            text-transform: capitalize;
            font-size: 0.95em;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
        }

        .form-section, .list-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            color: #333;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            transition: all 0.3s ease;
            font-size: 0.95em;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .songs-list {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            max-height: 180px;
            overflow-y: auto;
            background: #f9f9f9;
        }

        .song-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            margin-bottom: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .song-item:hover {
            background: #f5f5f5;
        }

        .song-item input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            cursor: pointer;
        }

        .song-item label {
            margin: 0;
            flex: 1;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .playlist-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .playlist-card:hover {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            border-color: #667eea;
        }

        .playlist-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 15px;
            margin-bottom: 10px;
        }

        .playlist-info {
            flex: 1;
        }

        .playlist-title {
            color: #333;
            font-weight: 600;
            margin: 0;
            font-size: 1.05em;
        }

        .playlist-desc {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0;
        }

        .playlist-stats {
            display: flex;
            gap: 15px;
            margin-top: 8px;
            font-size: 0.85em;
        }

        .stat {
            color: #667eea;
            font-weight: 500;
        }

        .stat i {
            margin-right: 5px;
        }

        .playlist-actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.85em;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
        }

        .btn-edit:hover {
            background: #ffb300;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #da190b;
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 2.5em;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        .flash-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInUp 0.4s ease-out;
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

        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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
            }

            .admin-main {
                margin-left: 0;
                padding: 15px;
            }
        }
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
            <li><a href="<?php echo $base; ?>/index.php?route=admin"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/emissions"><i class="fas fa-play-circle"></i> Émissions</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/playlists" class="active"><i class="fas fa-music"></i> Playlistes</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/historiques"><i class="fas fa-history"></i> Historiques</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main">
        <!-- HEADER -->
        <div class="admin-header">
            <h1><i class="fas fa-music"></i> Gestion des Playlistes</h1>
            <a href="<?php echo $base; ?>/index.php?route=admin" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>

        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash-message <?php echo $_SESSION['flash']['type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['flash']['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $_SESSION['flash']['msg']; ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-btn <?php echo !isset($_GET['view']) ? 'active' : ''; ?>" onclick="switchView('manage')">
                    <i class="fas fa-tools"></i> Gérer
                </button>
                <button class="tab-btn <?php echo isset($_GET['view']) && $_GET['view'] === 'list' ? 'active' : ''; ?>" onclick="switchView('list')">
                    <i class="fas fa-eye"></i> Voir tout
                </button>
            </div>
        </div>

        <?php if (!isset($_GET['view']) || $_GET['view'] !== 'list'): // MODE GESTION ?>

        <!-- CONTENT GRID -->
        <div class="content-grid">
            <!-- FORM SECTION -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-plus-circle"></i> Nouvelle Playlist</h2>
                <form method="POST" action="<?php echo $base; ?>/index.php?route=admin/playlists" id="playlist-form" enctype="multipart/form-data" onsubmit="return prepareSongsForSubmit();">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="form-id" value="">

                    <div class="form-group">
                        <label>Titre de la playlist <span style="color: red;">*</span></label>
                        <input type="text" name="title" placeholder="Ex: Best Of 2025" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="desc" placeholder="Description de votre playlist"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Image de couverture (URL)</label>
                        <input type="text" name="cover" placeholder="https://ui-avatars.com/api/?name=...">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-list"></i> Sélectionner les émissions</label>
                        <div class="songs-list" id="songs-list">
                            <?php foreach ($songs_list as $song): ?>
                                <div class="song-item">
                                    <input type="checkbox" data-song="<?php echo htmlspecialchars($song); ?>" id="song_<?php echo md5($song); ?>">
                                    <label for="song_<?php echo md5($song); ?>"><?php echo htmlspecialchars($song); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="songs" id="form-songs" value="">
                    </div>

                    <div class="form-group">
                        <label>OU téléverser des fichiers audio (MP3)</label>
                        <input type="file" name="songs_files[]" id="songs-files" accept="audio/*" multiple>
                        <small style="color:#666; display:block; margin-top:6px;">Formats acceptés : .mp3, .wav, .ogg. Les fichiers seront déplacés vers le dossier public.</small>
                    </div>

                    <div class="form-group">
                        <label><small style="color: #999;">Ou entrer les émissions séparées par des virgules</small></label>
                        <textarea name="songs_custom" id="songs-custom" placeholder="Ex: Affiche, Lumière, Flyer" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Créer Playlist</button>
                </form>
            </div>

            <!-- LIST SECTION -->
            <div class="list-section">
                <h2 class="section-title"><i class="fas fa-list-ul"></i> Playlistes existantes</h2>
                <div style="margin-top:10px;margin-bottom:14px;">
                    <button class="btn-submit" type="button" onclick="saveAllPlaylists()">💾 Enregistrer les modifications</button>
                </div>

                <?php 
                $hasAny = false;
                foreach ($playlists_by_day as $grp) { if (!empty($grp)) { $hasAny = true; break; } }
                if (!$hasAny): 
                ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Aucune playlist</p>
                    </div>
                <?php else: ?>
                    <style>
                        .day-group { margin-bottom: 20px; }
                        .day-group-title { background: #667eea; color: white; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; font-weight: 600; display:flex; gap:8px; align-items:center; }
                        .playlists-day-list { display: flex; flex-direction: column; gap: 10px; }
                    </style>

                    <?php foreach ($days as $day): ?>
                        <?php if (empty($playlists_by_day[$day])) continue; ?>
                        <div class="day-group">
                            <div class="day-group-title"><i class="fas fa-calendar-day"></i> <?php echo ucfirst($day); ?></div>
                            <div class="playlists-day-list">
                                <?php foreach ($playlists_by_day[$day] as $pl): ?>
                                    <div class="playlist-card" data-id="<?php echo $pl['id']; ?>" data-title="<?php echo htmlspecialchars($pl['title']); ?>" data-desc="<?php echo htmlspecialchars($pl['desc'] ?? $pl['description'] ?? ''); ?>" data-cover="<?php echo htmlspecialchars($pl['cover'] ?? ''); ?>" data-songs="<?php echo htmlspecialchars(implode('||', $pl['songs'] ?? [])); ?>">
                                        <div class="playlist-header">
                                            <div class="playlist-info">
                                                <h3 class="playlist-title">
                                                    <i class="fas fa-compact-disc"></i> <?php echo htmlspecialchars($pl['title']); ?>
                                                </h3>
                                                <p class="playlist-desc"><?php echo htmlspecialchars($pl['desc'] ?? $pl['description'] ?? ''); ?></p>
                                                <div class="playlist-stats">
                                                    <span class="stat"><i class="fas fa-music"></i> <?php echo count($pl['songs'] ?? []); ?> élément<?php echo count($pl['songs'] ?? []) > 1 ? 's' : ''; ?></span>
                                                    <span class="stat"><i class="fas fa-calendar"></i> <?php echo isset($pl['created_at']) ? date('d/m/Y', strtotime($pl['created_at'])) : 'N/A'; ?></span>
                                                </div>
                                            </div>
                                            <div class="playlist-actions">
                                                <button class="btn-action btn-edit" type="button" onclick="editPlaylist(<?php echo htmlspecialchars($pl['id']); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action" type="button" title="Associer un fichier audio" onclick="uploadForPlaylist(this)">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette playlist ?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $pl['id']; ?>">
                                                    <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!empty($playlists_by_day['autres'])): ?>
                        <div class="day-group">
                            <div class="day-group-title"><i class="fas fa-folder-open"></i> Autres</div>
                            <div class="playlists-day-list">
                                <?php foreach ($playlists_by_day['autres'] as $pl): ?>
                                    <div class="playlist-card" data-id="<?php echo $pl['id']; ?>" data-title="<?php echo htmlspecialchars($pl['title']); ?>" data-desc="<?php echo htmlspecialchars($pl['desc'] ?? $pl['description'] ?? ''); ?>" data-cover="<?php echo htmlspecialchars($pl['cover'] ?? ''); ?>" data-songs="<?php echo htmlspecialchars(implode('||', $pl['songs'] ?? [])); ?>">
                                        <div class="playlist-header">
                                            <div class="playlist-info">
                                                <h3 class="playlist-title">
                                                    <i class="fas fa-compact-disc"></i> <?php echo htmlspecialchars($pl['title']); ?>
                                                </h3>
                                                <p class="playlist-desc"><?php echo htmlspecialchars($pl['desc'] ?? $pl['description'] ?? ''); ?></p>
                                                <div class="playlist-stats">
                                                    <span class="stat"><i class="fas fa-music"></i> <?php echo count($pl['songs'] ?? []); ?> émission<?php echo count($pl['songs'] ?? []) > 1 ? 's' : ''; ?></span>
                                                    <span class="stat"><i class="fas fa-calendar"></i> <?php echo isset($pl['created_at']) ? date('d/m/Y', strtotime($pl['created_at'])) : 'N/A'; ?></span>
                                                </div>
                                            </div>
                                            <div class="playlist-actions">
                                                <button class="btn-action btn-edit" type="button" onclick="editPlaylist(<?php echo htmlspecialchars($pl['id']); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette playlist ?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $pl['id']; ?>">
                                                    <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
        <?php endif; // FIN MODE GESTION ?>

        <?php if (isset($_GET['view']) && $_GET['view'] === 'list'): // MODE VUE ?>
        <!-- ALL PLAYLISTS VIEW -->
        <div style="width: 100%;">
            <div class="list-section">
                <h2 class="section-title"><i class="fas fa-music"></i> Toutes les Playlistes</h2>
                
                <?php 
                if (empty($playlists)): 
                ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Aucune playlist</p>
                    </div>
                <?php else: ?>
                    <style>
                        .playlists-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                            gap: 20px;
                        }

                        .playlist-card-full {
                            background: white;
                            border-radius: 10px;
                            overflow: hidden;
                            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
                            transition: all 0.3s ease;
                            display: flex;
                            flex-direction: column;
                        }

                        .playlist-card-full:hover {
                            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
                            transform: translateY(-4px);
                        }

                        .playlist-cover {
                            width: 100%;
                            height: 200px;
                            background: linear-gradient(135deg, #667eea, #764ba2);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-size: 3em;
                            overflow: hidden;
                        }

                        .playlist-cover img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                        }

                        .playlist-content {
                            padding: 16px;
                            flex-grow: 1;
                            display: flex;
                            flex-direction: column;
                        }

                        .playlist-title-view {
                            color: #333;
                            font-weight: 700;
                            font-size: 1.1em;
                            margin-bottom: 8px;
                        }

                        .playlist-desc-view {
                            color: #666;
                            font-size: 0.9em;
                            margin-bottom: 12px;
                            line-height: 1.4;
                            flex-grow: 1;
                        }

                        .playlist-footer {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding-top: 12px;
                            border-top: 1px solid #eee;
                        }

                        .playlist-stats-view {
                            display: flex;
                            gap: 12px;
                            font-size: 0.85em;
                        }

                        .stat-view {
                            color: #667eea;
                            font-weight: 500;
                            display: flex;
                            align-items: center;
                            gap: 5px;
                        }

                        .songs-preview {
                            margin-top: 12px;
                            padding-top: 12px;
                            border-top: 1px solid #eee;
                        }

                        .songs-preview-title {
                            color: #333;
                            font-weight: 600;
                            font-size: 0.85em;
                            margin-bottom: 8px;
                        }

                        .songs-preview-list {
                            display: flex;
                            flex-direction: column;
                            gap: 4px;
                        }

                        .song-preview-item {
                            color: #666;
                            font-size: 0.8em;
                            padding: 4px 0;
                            padding-left: 16px;
                            position: relative;
                        }

                        .song-preview-item:before {
                            content: '♪';
                            position: absolute;
                            left: 0;
                            color: #667eea;
                        }
                    </style>

                    <div class="playlists-grid">
                        <?php foreach ($playlists as $pl): ?>
                            <div class="playlist-card-full">
                                <div class="playlist-cover">
                                    <?php if (!empty($pl['cover'])): ?>
                                        <img src="<?php echo htmlspecialchars($pl['cover']); ?>" alt="<?php echo htmlspecialchars($pl['title']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-compact-disc"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="playlist-content">
                                    <h3 class="playlist-title-view">
                                        <i class="fas fa-music"></i> <?php echo htmlspecialchars($pl['title']); ?>
                                    </h3>
                                    <?php if (!empty($pl['desc'] || $pl['description'])): ?>
                                        <p class="playlist-desc-view">
                                            <?php echo htmlspecialchars($pl['desc'] ?? $pl['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($pl['songs'])): ?>
                                        <div class="songs-preview">
                                            <div class="songs-preview-title">Éléments (<?php echo count($pl['songs']); ?>)</div>
                                            <div class="songs-preview-list">
                                                <?php foreach (array_slice($pl['songs'], 0, 3) as $song): ?>
                                                    <?php if (preg_match('/\.(mp3|wav|ogg)$/i', $song) || strpos($song, '/public/assets/audios/') === 0): ?>
                                                        <div class="song-preview-item" title="<?php echo htmlspecialchars($song); ?>">
                                                            <audio controls style="width:100%; max-width:240px;">
                                                                <source src="<?php echo $base . $song; ?>" type="audio/mpeg">
                                                                Votre navigateur ne supporte pas l'audio.
                                                            </audio>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="song-preview-item" title="<?php echo htmlspecialchars($song); ?>">
                                                            <?php echo htmlspecialchars(mb_substr($song, 0, 35)); ?>
                                                            <?php if (mb_strlen($song) > 35) echo '...'; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (count($pl['songs']) > 3): ?>
                                                    <div class="song-preview-item" style="color: #667eea; font-weight: 600;">
                                                        +<?php echo count($pl['songs']) - 3; ?> autre<?php echo count($pl['songs']) - 3 > 1 ? 's' : ''; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="playlist-footer">
                                        <div class="playlist-stats-view">
                                            <span class="stat-view"><i class="fas fa-music"></i> <?php echo count($pl['songs'] ?? []); ?></span>
                                            <span class="stat-view"><i class="fas fa-calendar"></i> <?php echo isset($pl['created_at']) ? date('d/m/Y', strtotime($pl['created_at'])) : 'N/A'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; // FIN MODE VUE ?>
    </main>
</div>

<script>
    const BASE_URL = '<?php echo $base; ?>';
    function switchView(view) {
        if (view === 'list') {
            window.location.href = BASE_URL + '/index.php?route=admin/playlists&view=list';
        } else {
            window.location.href = BASE_URL + '/index.php?route=admin/playlists';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const currentRoute = new URLSearchParams(window.location.search).get('route') || 'admin';
        const menuLinks = document.querySelectorAll('.sidebar-menu a');
        menuLinks.forEach(link => {
            link.classList.remove('active');
            try {
                if (link.getAttribute('href') && link.getAttribute('href').includes(currentRoute)) {
                    link.classList.add('active');
                }
            } catch (e) { /* ignore malformed hrefs */ }
        });
    });
</script>
    <script>
        // Collect selected songs and custom songs into hidden field before submit
        function prepareSongsForSubmit(){
            const checked = Array.from(document.querySelectorAll('#songs-list input[type=checkbox]:checked')).map(cb => cb.getAttribute('data-song'));
            const scEl = document.getElementById('songs-custom');
            const custom = ((scEl && scEl.value) || '').split(',').map(s=>s.trim()).filter(Boolean);
            const all = checked.concat(custom);
            document.getElementById('form-songs').value = all.join(',');
            return true; // allow submit
        }

        // Fill the form for editing a playlist
        function editPlaylist(id){
                // Find playlist card by data-id attribute
                let found = document.querySelector('.playlist-card[data-id="' + id + '"]');

                // If not found on this page, show message
                if (!found) {
                    alert('Playlist non trouvée sur cette page. Utilisez la vue complète pour éditer.');
                    return;
                }

                // Read data attributes from the card
                const title = found.getAttribute('data-title') || '';
                const desc = found.getAttribute('data-desc') || '';
                const cover = found.getAttribute('data-cover') || '';
                const songsAttr = found.getAttribute('data-songs') || '';
                const songs = songsAttr ? songsAttr.split('||').map(s=>s.trim()).filter(Boolean) : [];

                document.getElementById('form-action').value = 'edit';
                document.getElementById('form-id').value = id;
                document.querySelector('input[name="title"]').value = title;
                document.querySelector('textarea[name="desc"]').value = desc;
                document.querySelector('input[name="cover"]').value = cover;

                // Uncheck all song checkboxes then check those present in playlist
                document.querySelectorAll('#songs-list input[type=checkbox]').forEach(cb => cb.checked = false);
                document.querySelectorAll('#songs-list input[type=checkbox]').forEach(cb => {
                    const s = cb.getAttribute('data-song');
                    if (songs.includes(s)) cb.checked = true;
                });
                document.getElementById('songs-custom').value = '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>

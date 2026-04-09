<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

if (!isset($playlists) || !is_array($playlists)) {
    $playlists = [];
}

$days = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];
$playlists_by_day = array_fill_keys($days, []);
$playlists_by_day['autres'] = [];

foreach ($playlists as $pl) {
    if (!is_array($pl)) continue;
    $day = strtolower(trim($pl['day'] ?? ''));
    if (!in_array($day, $days)) {
        if (!empty($pl['created_at'])) {
            $engToFr = ['Monday'=>'lundi','Tuesday'=>'mardi','Wednesday'=>'mercredi','Thursday'=>'jeudi','Friday'=>'vendredi','Saturday'=>'samedi','Sunday'=>'dimanche'];
            $eng = date('l', strtotime($pl['created_at']));
            $day = $engToFr[$eng] ?? 'autres';
        } else {
            $day = 'autres';
        }
    }
    $playlists_by_day[$day][] = $pl;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion Playlistes</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase); ?>/fontawesome/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; color: #1f2937; font-size: 16px; }
        .admin-wrapper { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .admin-sidebar { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px 20px; position: sticky; top: 0; height: 100vh; width: 260px; overflow-y: auto; z-index: 1000; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; font-size: 1.4em; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: rgba(255,255,255,0.8); text-decoration: none; border-radius: 6px; transition: all 0.3s; font-weight: 500; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.15); color: white; padding-left: 20px; }
        .admin-main { padding: 30px; width: 100%; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .admin-header h1 { color: #333; margin: 0; display: flex; align-items: center; gap: 12px; font-size: 1.8em; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; transition: all 0.3s; font-weight: 600; }
        .back-btn:hover { background: #764ba2; transform: translateY(-2px); }
        .content-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; }
        .form-section, .list-section { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .section-title { color: #333; margin: 0 0 20px; display: flex; align-items: center; gap: 10px; font-size: 1.3em; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #333; font-weight: 600; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95em; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .songs-list { border: 1px solid #ddd; border-radius: 6px; padding: 10px; max-height: 200px; overflow-y: auto; background: #f9f9f9; }
        .song-item { display: flex; align-items: center; padding: 10px; background: white; margin-bottom: 5px; border-radius: 4px; }
        .song-item input[type="checkbox"] { width: auto; margin-right: 10px; cursor: pointer; }
        .song-item label { margin: 0; flex: 1; cursor: pointer; font-weight: 500; }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.3); }
        .playlist-card { background: white; border-radius: 8px; padding: 15px; margin-bottom: 12px; border: 1px solid #e0e0e0; transition: all 0.3s; }
        .playlist-card:hover { box-shadow: 0 4px 12px rgba(102,126,234,0.15); border-color: #667eea; }
        .playlist-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; margin-bottom: 10px; }
        .playlist-info { flex: 1; }
        .playlist-title { color: #333; font-weight: 600; margin: 0; font-size: 1.05em; }
        .playlist-desc { color: #666; font-size: 0.9em; margin: 5px 0; }
        .playlist-stats { display: flex; gap: 15px; margin-top: 8px; font-size: 0.85em; }
        .stat { color: #667eea; font-weight: 500; }
        .playlist-actions { display: flex; gap: 8px; }
        .btn-action { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s; font-size: 0.85em; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-edit:hover { background: #ffb300; }
        .btn-delete { background: #f44336; color: white; }
        .btn-delete:hover { background: #da190b; }
        .btn-add-audio { background: #2196F3; color: white; }
        .btn-add-audio:hover { background: #1976D2; }
        .empty-state { text-align: center; padding: 30px 20px; color: #999; }
        .day-group { margin-bottom: 20px; }
        .day-group-title { background: #667eea; color: white; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; font-weight: 600; display: flex; gap: 8px; align-items: center; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 10px; padding: 30px; max-width: 600px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; color: #333; }
        .modal-close { background: none; border: none; font-size: 1.5em; cursor: pointer; color: #666; }
        @media (max-width: 900px) { .content-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-logo"><i class="fas fa-crown"></i><span>NATIORA</span></div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo $base; ?>/index.php?route=admin"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/emissions"><i class="fas fa-play-circle"></i> Émissions</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/playlists" class="active"><i class="fas fa-music"></i> Playlistes</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/historiques"><i class="fas fa-history"></i> Historiques</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-music"></i> Gestion des Playlistes</h1>
            <a href="<?php echo $base; ?>/index.php?route=admin" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>

        <div class="content-grid">
            <!-- FORMULAIRE AJOUT -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-plus-circle"></i> Nouvelle Playlist</h2>
                <form method="POST" action="<?php echo $base; ?>/index.php?route=admin/playlists" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">

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
                        <input type="text" name="cover" placeholder="https://example.com/image.jpg">
                    </div>

                    <div class="form-group">
                        <label>Assigner à un jour</label>
                        <select name="day">
                            <option value="">(Aucun — utilisera la date de création)</option>
                            <option value="lundi">Lundi</option>
                            <option value="mardi">Mardi</option>
                            <option value="mercredi">Mercredi</option>
                            <option value="jeudi">Jeudi</option>
                            <option value="vendredi">Vendredi</option>
                            <option value="samedi">Samedi</option>
                            <option value="dimanche">Dimanche</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Créer Playlist</button>
                </form>
            </div>

            <!-- LISTE DES PLAYLISTS -->
            <div class="list-section">
                <h2 class="section-title"><i class="fas fa-list-ul"></i> Playlistes existantes</h2>

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
                    <?php foreach ($days as $day): ?>
                        <?php if (empty($playlists_by_day[$day])) continue; ?>
                        <div class="day-group">
                            <div class="day-group-title"><i class="fas fa-calendar-day"></i> <?php echo ucfirst($day); ?></div>
                            <?php foreach ($playlists_by_day[$day] as $pl): ?>
                                <div class="playlist-card" data-id="<?php echo $pl['id']; ?>">
                                    <div class="playlist-header">
                                        <div class="playlist-info">
                                            <h3 class="playlist-title"><i class="fas fa-compact-disc"></i> <?php echo htmlspecialchars($pl['title']); ?></h3>
                                            <p class="playlist-desc"><?php echo htmlspecialchars($pl['desc'] ?? ''); ?></p>
                                            <div class="playlist-stats">
                                                <span class="stat"><i class="fas fa-music"></i> <?php echo count($pl['songs'] ?? []); ?> piste(s)</span>
                                                <span class="stat"><i class="fas fa-calendar"></i> <?php echo isset($pl['created_at']) ? date('d/m/Y', strtotime($pl['created_at'])) : 'N/A'; ?></span>
                                            </div>
                                        </div>
                                        <div class="playlist-actions">
                                            <button class="btn-action btn-add-audio" onclick="openAddAudioModal(<?php echo $pl['id']; ?>, <?php echo htmlspecialchars(json_encode($pl['songs'] ?? [])); ?>)">
                                                <i class="fas fa-music"></i> Ajouter Audio
                                            </button>
                                            <button class="btn-action btn-edit" onclick="editPlaylist(<?php echo $pl['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette playlist ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $pl['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; max-height: 120px; overflow-y: auto;">
                                        <?php if (!empty($pl['songs'])): ?>
                                            <strong>Pistes:</strong>
                                            <ul style="margin: 5px 0; padding-left: 20px;">
                                                <?php foreach ($pl['songs'] as $song): ?>
                                                    <li style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($song); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <em style="color: #999;">Aucune piste</em>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- MODAL AJOUT AUDIO -->
<div class="modal" id="addAudioModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Ajouter une Audio</h2>
            <button class="modal-close" onclick="closeAddAudioModal()">&times;</button>
        </div>
        <form method="POST" action="<?php echo $base; ?>/index.php?route=admin/playlists" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_audio">
            <input type="hidden" name="playlist_id" id="modal_playlist_id">

            <div class="form-group">
                <label>Source Audio (URL ou Fichier)</label>
                <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Téléversez un fichier local ou entrez une URL HTTPS</p>
                <input type="file" name="audio_file" accept="audio/*" id="modal_audio_file">
                <p style="text-align: center; color: #999; margin: 10px 0;">OU</p>
                <input type="text" name="audio_url" id="modal_audio_url" placeholder="https://example.com/audio.mp3">
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Ajouter l'Audio</button>
            </div>
        </form>
    </div>
</div>

<script>
    const assetBase = '<?php echo htmlspecialchars($assetBase ?? '/assets'); ?>';

    function openAddAudioModal(playlistId, songs) {
        document.getElementById('modal_playlist_id').value = playlistId;
        document.getElementById('modal_audio_file').value = '';
        document.getElementById('modal_audio_url').value = '';
        document.getElementById('addAudioModal').classList.add('show');
    }

    function closeAddAudioModal() {
        document.getElementById('addAudioModal').classList.remove('show');
    }

    function editPlaylist(playlistId) {
        // Redirection vers formulaire d'édition ou modal
        alert('Édition de playlist #' + playlistId);
    }

    window.onclick = function(event) {
        const modal = document.getElementById('addAudioModal');
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    }
</script>
</body>
</html>
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
                            // also append uploaded path into the card's data-songs so it will be saved with the playlist
                            try {
                                var ds = card.getAttribute('data-songs') || '';
                                if (!ds) ds = j.path;
                                else ds = ds + '||' + j.path;
                                card.setAttribute('data-songs', ds);
                            } catch(e){}
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

            window.addUrlToPlaylist = function(btn){
                var card = btn.closest('.playlist-card');
                if (!card) return alert('Carte introuvable');
                var url = prompt("Entrez l'URL publique du fichier audio (https://...)");
                if (!url) return;
                if (!/^https:\/\//i.test(url)) { alert('URL invalide — doit commencer par https://'); return; }
                try {
                    var ds = card.getAttribute('data-songs') || '';
                    if (!ds) ds = url; else ds = ds + '||' + url;
                    card.setAttribute('data-songs', ds);
                } catch(e){}
                var preview = card.querySelector('.playlist-audio-preview');
                if (!preview) { preview = document.createElement('div'); preview.className = 'playlist-audio-preview'; card.querySelector('.playlist-info').appendChild(preview); }
                preview.innerHTML = '<audio controls style="width:100%; max-width:320px;"><source src="' + url + '"></audio>';
                alert('URL ajoutée: ' + url);
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
                // Client-side validation: each song must be an absolute URL or an uploaded file under /uploads/
                function isValidSong(s){
                    if (!s || typeof s !== 'string') return false;
                    s = s.trim();
                    if (/^https:\/\//i.test(s)) return true;
                    if (s.indexOf('/uploads/') === 0) return true;
                    if (s.indexOf('/public/') === 0) return true;
                    // allow root-relative paths to public
                    if (/^\/.+\.(mp3|m4a|ogg|wav|mp4)(\?|$)/i.test(s)) return true;
                    return false;
                }

                var invalid = [];
                payload.forEach(function(pl){
                    (pl.songs || []).forEach(function(s){ if (!isValidSong(s)) invalid.push({playlist: pl.title || '(untitled)', song: s}); });
                });
                if (invalid.length) {
                    var msg = 'La sauvegarde a été annulée. Pistes non valides détectées:\n';
                    invalid.slice(0,10).forEach(function(it){ msg += '\n- ' + (it.playlist || '') + ': ' + it.song; });
                    if (invalid.length > 10) msg += '\n... et ' + (invalid.length-10) + ' autres';
                    alert(msg);
                    return;
                }

                fetch(BASE_URL + '/index.php?route=admin/save_playlists', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify(payload) }).then(r=>r.json()).then(j=>{ if (j && j.ok) { alert('Playlists enregistrées'); location.reload(); } else { alert('Save failed: ' + (j && j.error ? j.error : 'unknown')); console.error(j); } }).catch(e=>{ alert('Save error'); console.error(e); });
            };
        }
    </style>

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

        .back-btn:hover { background: #764ba2; transform: translateY(-2px); }
        .content-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; }
        .form-section, .list-section { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .section-title { color: #333; margin: 0 0 20px; display: flex; align-items: center; gap: 10px; font-size: 1.3em; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #333; font-weight: 600; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95em; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .songs-list { border: 1px solid #ddd; border-radius: 6px; padding: 10px; max-height: 200px; overflow-y: auto; background: #f9f9f9; }
        .song-item { display: flex; align-items: center; padding: 10px; background: white; margin-bottom: 5px; border-radius: 4px; }
        .song-item input[type="checkbox"] { width: auto; margin-right: 10px; cursor: pointer; }
        .song-item label { margin: 0; flex: 1; cursor: pointer; font-weight: 500; }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.3); }
        .playlist-card { background: white; border-radius: 8px; padding: 15px; margin-bottom: 12px; border: 1px solid #e0e0e0; transition: all 0.3s; }
        .playlist-card:hover { box-shadow: 0 4px 12px rgba(102,126,234,0.15); border-color: #667eea; }
        .playlist-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; margin-bottom: 10px; }
        .playlist-info { flex: 1; }
        .playlist-title { color: #333; font-weight: 600; margin: 0; font-size: 1.05em; }
        .playlist-desc { color: #666; font-size: 0.9em; margin: 5px 0; }
        .playlist-stats { display: flex; gap: 15px; margin-top: 8px; font-size: 0.85em; }
        .stat { color: #667eea; font-weight: 500; }
        .playlist-actions { display: flex; gap: 8px; }
        .btn-action { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s; font-size: 0.85em; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-edit:hover { background: #ffb300; }
        .btn-delete { background: #f44336; color: white; }
        .btn-delete:hover { background: #da190b; }
        .btn-add-audio { background: #2196F3; color: white; }
        .btn-add-audio:hover { background: #1976D2; }
        .empty-state { text-align: center; padding: 30px 20px; color: #999; }
        .day-group { margin-bottom: 20px; }
        .day-group-title { background: #667eea; color: white; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; font-weight: 600; display: flex; gap: 8px; align-items: center; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 10px; padding: 30px; max-width: 600px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; color: #333; }
        .modal-close { background: none; border: none; font-size: 1.5em; cursor: pointer; color: #666; }
        @media (max-width: 900px) { .content-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .admin-wrapper { grid-template-columns: 1fr; } .admin-sidebar { position: relative; width: 100%; height: auto; padding: 15px; } .admin-main { padding: 15px; } }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-logo"><i class="fas fa-crown"></i><span>NATIORA</span></div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo $base; ?>/index.php?route=admin"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/emissions"><i class="fas fa-play-circle"></i> Émissions</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/playlists" class="active"><i class="fas fa-music"></i> Playlistes</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/historiques"><i class="fas fa-history"></i> Historiques</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-music"></i> Gestion des Playlistes</h1>
            <a href="<?php echo $base; ?>/index.php?route=admin" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>

        <div class="content-grid">
            <!-- FORMULAIRE AJOUT -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-plus-circle"></i> Nouvelle Playlist</h2>
                <form method="POST" action="<?php echo $base; ?>/index.php?route=admin/playlists" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">

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
                        <input type="text" name="cover" placeholder="https://example.com/image.jpg">
                    </div>

                    <div class="form-group">
                        <label>Assigner à un jour</label>
                        <select name="day">
                            <option value="">(Aucun — utilisera la date de création)</option>
                            <option value="lundi">Lundi</option>
                            <option value="mardi">Mardi</option>
                            <option value="mercredi">Mercredi</option>
                            <option value="jeudi">Jeudi</option>
                            <option value="vendredi">Vendredi</option>
                            <option value="samedi">Samedi</option>
                            <option value="dimanche">Dimanche</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Créer Playlist</button>
                </form>
            </div>

                    <!-- LISTE DES PLAYLISTS -->
            <div class="list-section">
                <h2 class="section-title"><i class="fas fa-list-ul"></i> Playlistes existantes</h2>

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
                    <?php foreach ($days as $day): ?>
                        <?php if (empty($playlists_by_day[$day])) continue; ?>
                        <div class="day-group">
                            <div class="day-group-title"><i class="fas fa-calendar-day"></i> <?php echo ucfirst($day); ?></div>
                            <?php foreach ($playlists_by_day[$day] as $pl): ?>
                                <div class="playlist-card" data-id="<?php echo $pl['id']; ?>">
                                    <div class="playlist-header">
                                        <div class="playlist-info">
                                            <h3 class="playlist-title"><i class="fas fa-compact-disc"></i> <?php echo htmlspecialchars($pl['title']); ?></h3>
                                            <p class="playlist-desc"><?php echo htmlspecialchars($pl['desc'] ?? ''); ?></p>
                                            <div class="playlist-stats">
                                                <span class="stat"><i class="fas fa-music"></i> <?php echo count($pl['songs'] ?? []); ?> piste(s)</span>
                                                <span class="stat"><i class="fas fa-calendar"></i> <?php echo isset($pl['created_at']) ? date('d/m/Y', strtotime($pl['created_at'])) : 'N/A'; ?></span>
                                            </div>
                                        </div>
                                        <div class="playlist-actions">
                                            <button class="btn-action btn-add-audio" onclick="openAddAudioModal(<?php echo $pl['id']; ?>, <?php echo htmlspecialchars(json_encode($pl['songs'] ?? [])); ?>)">
                                                <i class="fas fa-music"></i> Ajouter Audio
                                            </button>
                                            <button class="btn-action btn-edit" onclick="editPlaylist(<?php echo $pl['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette playlist ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $pl['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; max-height: 120px; overflow-y: auto;">
                                        <?php if (!empty($pl['songs'])): ?>
                                            <strong>Pistes:</strong>
                                            <ul style="margin: 5px 0; padding-left: 20px;">
                                                <?php foreach ($pl['songs'] as $song): ?>
                                                    <li style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($song); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <em style="color: #999;">Aucune piste</em>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- MODAL AJOUT AUDIO -->
<div class="modal" id="addAudioModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Ajouter une Audio</h2>
            <button class="modal-close" onclick="closeAddAudioModal()">&times;</button>
        </div>
        <form method="POST" action="<?php echo $base; ?>/index.php?route=admin/playlists" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_audio">
            <input type="hidden" name="playlist_id" id="modal_playlist_id">

            <div class="form-group">
                <label>Source Audio (URL ou Fichier)</label>
                <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Téléversez un fichier local ou entrez une URL HTTPS</p>
                <input type="file" name="audio_file" accept="audio/*" id="modal_audio_file">
                <p style="text-align: center; color: #999; margin: 10px 0;">OU</p>
                <input type="text" name="audio_url" id="modal_audio_url" placeholder="https://example.com/audio.mp3">
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Ajouter l'Audio</button>
            </div>
        </form>
    </div>
</div>

<script>
    const assetBase = '<?php echo htmlspecialchars($assetBase ?? '/assets'); ?>';

    function openAddAudioModal(playlistId, songs) {
        document.getElementById('modal_playlist_id').value = playlistId;
        document.getElementById('modal_audio_file').value = '';
        document.getElementById('modal_audio_url').value = '';
        document.getElementById('addAudioModal').classList.add('show');
    }

    function closeAddAudioModal() {
        document.getElementById('addAudioModal').classList.remove('show');
    }

    function editPlaylist(playlistId) {
        // Redirection vers formulaire d'édition ou modal
        alert('Édition de playlist #' + playlistId);
    }

    window.onclick = function(event) {
        const modal = document.getElementById('addAudioModal');
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    }
</script>
</body>
</html>

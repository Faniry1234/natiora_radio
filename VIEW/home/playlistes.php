<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!class_exists('Playlists')) require_once __DIR__ . '/../../APP/MODEL/Playlists.php';

$playlistModel = new Playlists();
$playlists = $playlistModel->getAll();
$days = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];
$playlists_by_day = array_fill_keys($days, []);
$playlists_by_day['autres'] = [];

foreach ($playlists as $playlist) {
    $day = strtolower(trim($playlist['day'] ?? 'autres'));
    if (!in_array($day, $days, true)) $day = 'autres';
    $playlists_by_day[$day][] = $playlist;
}
?>
<style>
.playlists-section { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
.playlists-section h2 { font-size: 2.5rem; color: #fff; margin-bottom: 12px; font-weight: 700; }
.playlists-section > p { font-size: 1.1rem; color: rgba(255,255,255,0.8); margin-bottom: 40px; max-width: 700px; line-height: 1.6; }
.day-tabs-container { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 40px; }
.day-btn { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); color: #fff; padding: 10px 18px; border-radius: 50px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; font-size: 0.95rem; }
.day-btn:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.2); }
.day-btn.active { background: linear-gradient(135deg, #2f80ed 0%, #1e5cdb 100%); border-color: #2f80ed; }
.playlists-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.playlist-card { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); border-radius: 16px; padding: 24px; transition: all 0.3s ease; cursor: pointer; }
.playlist-card:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25); transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
.playlist-card-header { display: flex; gap: 16px; margin-bottom: 16px; align-items: flex-start; }
.playlist-cover { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; background: rgba(255,255,255,0.05); }
.playlist-info { flex: 1; }
.playlist-info h3 { color: #fff; font-size: 1.2rem; margin-bottom: 4px; line-height: 1.3; }
.playlist-info .meta { display: flex; gap: 8px; font-size: 0.85rem; color: rgba(255,255,255,0.7); margin-bottom: 8px; }
.meta-badge { background: rgba(47,128,237,0.2); padding: 4px 8px; border-radius: 6px; color: #7bb3ff; }
.playlist-desc { color: rgba(255,255,255,0.75); font-size: 0.9rem; line-height: 1.4; margin-bottom: 16px; }
.playlist-stats { display: flex; gap: 16px; margin-bottom: 16px; }
.stat { display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.8); font-size: 0.9rem; }
.stat i { color: #2f80ed; }
.play-btn { width: 100%; background: linear-gradient(135deg, #2f80ed 0%, #1e5cdb 100%); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
.play-btn:hover { transform: scale(1.02); box-shadow: 0 10px 25px rgba(47,128,237,0.3); }
.play-btn.disabled { opacity: 0.5; cursor: not-allowed; }
.player-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 3000; align-items: center; justify-content: center; padding: 20px; }
.player-modal.active { display: flex; }
.player-content { background: rgba(12,17,34,0.95); border-radius: 20px; padding: 30px; max-width: 500px; width: 100%; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
.player-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.player-header h2 { color: #fff; margin: 0; font-size: 1.5rem; }
.close-btn { background: none; border: none; color: #fff; font-size: 1.8rem; cursor: pointer; opacity: 0.7; transition: opacity 0.2s; }
.close-btn:hover { opacity: 1; }
.audio-player { width: 100%; margin: 20px 0; }
.queue-list { max-height: 300px; overflow-y: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; margin-top: 20px; }
.queue-item { padding: 10px 0; color: rgba(255,255,255,0.8); font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: color 0.2s; }
.queue-item:hover { color: #2f80ed; }
.queue-item.current { color: #2f80ed; font-weight: 600; }
.empty-state { text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.6); }
.empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; }
@media (max-width: 768px) {
  .playlists-grid { grid-template-columns: 1fr; }
  .playlists-section h2 { font-size: 1.8rem; }
  .playlist-card-header { flex-direction: column; }
  .playlist-cover { width: 100%; height: 200px; }
}
</style>

<section id="playlist" class="playlist-hero" style="background-image: linear-gradient(180deg, rgba(6,12,34,0.35), rgba(6,12,34,0.65)), url('<?php echo htmlspecialchars($heroImage); ?>');">
    <div class="hero-content">
        <h2 style="margin:0 0 8px;font-size:2rem;">🎶 Playlists</h2>
        <p style="margin:0 0 12px;opacity:0.95;max-width:640px;">Toutes les playlists par jour de la semaine, avec une lecture par piste et un mode file d’attente pour écouter toute la sélection.</p>
    </div>

    <div class="day-tabs" role="tablist" aria-label="Jours de la semaine">
        <button class="day-btn" data-day="lundi">Lundi</button>
        <button class="day-btn" data-day="mardi">Mardi</button>
        <button class="day-btn" data-day="mercredi">Mercredi</button>
        <button class="day-btn" data-day="jeudi">Jeudi</button>
        <button class="day-btn" data-day="vendredi">Vendredi</button>
        <button class="day-btn" data-day="samedi">Samedi</button>
        <button class="day-btn" data-day="dimanche">Dimanche</button>
        <?php if (!empty($playlists_by_day['autres'])): ?>
            <button class="day-btn" data-day="autres">Autres</button>
        <?php endif; ?>
    </div>

    <div class="playlist-container">
        <div class="playlist-header">
            <h3 id="playlist-title">Playlistes du jour</h3>
            <p id="playlist-summary" style="opacity:0.9;max-width:720px;">Sélectionnez un jour pour afficher les playlists et les pistes disponibles.</p>
        </div>
        <div id="playlistFeedback" class="playlist-feedback"></div>
        <div id="playlistActions" class="playlist-actions"></div>
        <ul id="playlistList" class="playlist-list" aria-live="polite"></ul>
        <audio id="playlist-player" controls preload="metadata" style="display:none;width:100%;margin-top:18px;border-radius:14px;overflow:hidden;background:#000;"></audio>
        <div id="playlistStatus" class="playlist-status" aria-live="polite"></div>
    </div>

    <script>
        (function(){
            const playlists = <?php echo json_encode($playlists_by_day, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;
            const assetBase = '<?php echo htmlspecialchars($assetBase ?? '/assets'); ?>';
            const daysOrder = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
            const todayIndex = new Date().getDay();
            const todayName = daysOrder[todayIndex];

            const tabs = document.querySelectorAll('.day-btn');
            const listEl = document.getElementById('playlistList');
            const titleEl = document.getElementById('playlist-title');
            const summaryEl = document.getElementById('playlist-summary');
            const feedbackEl = document.getElementById('playlistFeedback');
            const actionsEl = document.getElementById('playlistActions');
            const statusEl = document.getElementById('playlistStatus');
            const player = document.getElementById('playlist-player');

            let queue = [];
            let currentIndex = -1;

            function isPlayableUrl(src) {
                return typeof src === 'string' && src.trim() !== '' && /^(?:https?:\/\/|\/|data:)/i.test(src);
            }

            function resolveTrackSrc(src) {
                if (!src || typeof src !== 'string') return '';
                src = src.trim();
                if (/^https?:\/\//i.test(src) || /^\/\//.test(src) || src.startsWith('data:')) {
                    return src;
                }
                if (src.startsWith('/public/')) {
                    return src.replace(/^\/public/, '');
                }
                if (/^\/(assets|uploads)\//.test(src) || /^\//.test(src)) {
                    return src;
                }
                if (/\.(mp3|m4a|ogg|wav|aac|flac|mp4|webm|mov)$/i.test(src)) {
                    return assetBase + '/audios/' + encodeURI(src);
                }
                return '';
            }

            function formatTitle(src) {
                if (!src) return '';
                try {
                    let name = src.replace(/.*[\\/]/, '');
                    name = name.replace(/\.[^.]+$/, '');
                    name = decodeURIComponent(name).replace(/[_\-]+/g, ' ').trim();
                    return name || src;
                } catch (e) {
                    return src;
                }
            }

            function setStatus(text) {
                if (statusEl) statusEl.textContent = text;
            }

            function playQueue(index) {
                if (!Array.isArray(queue) || queue.length === 0) {
                    setStatus('Aucune piste en file.');
                    player.style.display = 'none';
                    return;
                }
                currentIndex = typeof index === 'number' ? index : 0;
                const src = queue[currentIndex];
                if (!isPlayableUrl(src)) {
                    setStatus('Piste non jouable.');
                    return;
                }
                player.src = src;
                player.style.display = 'block';
                player.load();
                player.play().catch(() => setStatus('Interaction requise pour démarrer la lecture.'));
                setStatus('Lecture de: ' + formatTitle(src));
            }

            function buildControls(day) {
                actionsEl.innerHTML = '';
                const items = playlists[day] || [];
                const playableItems = items.filter(item => Array.isArray(item.songs) && item.songs.some(song => isPlayableUrl(resolveTrackSrc(song))));
                if (!playableItems.length) return;
                const playAll = document.createElement('button');
                playAll.className = 'play-playlist-btn';
                playAll.type = 'button';
                playAll.textContent = 'Écouter toutes les playlists jouables';
                playAll.addEventListener('click', function(){
                    const allTracks = [];
                    playableItems.forEach(item => {
                        item.songs.forEach(song => {
                            const src = resolveTrackSrc(song);
                            if (isPlayableUrl(src)) allTracks.push(src);
                        });
                    });
                    queue = allTracks;
                    if (queue.length) playQueue(0);
                });
                actionsEl.appendChild(playAll);
            }

            function render(day) {
                listEl.innerHTML = '';
                const items = playlists[day] || [];
                titleEl.textContent = 'Playlistes — ' + day.charAt(0).toUpperCase() + day.slice(1);
                summaryEl.textContent = items.length ? 'Liste de ' + items.length + ' playlist' + (items.length > 1 ? 's' : '') + ' pour ce jour.' : 'Aucune playlist disponible pour ce jour.';
                feedbackEl.textContent = '';
                queue = [];
                currentIndex = -1;
                player.style.display = 'none';
                player.src = '';

                if (!items.length) {
                    listEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i> Aucune playlist disponible pour ce jour.</div>';
                    actionsEl.innerHTML = '';
                    return;
                }

                items.forEach(item => {
                    const trackUrls = Array.isArray(item.songs) ? item.songs.map(resolveTrackSrc) : [];
                    const playableCount = trackUrls.filter(isPlayableUrl).length;
                    const li = document.createElement('li');
                    li.className = 'audio-item';
                    const coverUrl = item.cover ? resolveTrackSrc(item.cover) : '';
                    li.innerHTML = `
                        <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                            <div style="flex:1;min-width:240px;">
                                <div class="emission-meta"><span><i class="fas fa-music"></i> ${playableCount} piste(s) jouables</span><span><i class="fas fa-calendar-day"></i> ${item.created_at ?? 'Date non définie'}</span></div>
                                <h4>${item.title ? item.title : 'Playlist sans titre'}</h4>
                                <div class="audio-item-description" style="opacity:0.87;line-height:1.5;margin-top:8px;">${item.desc ? item.desc : 'Aucune description fournie.'}</div>
                            </div>
                            ${coverUrl ? `<img class="cover-image" src="${coverUrl}" alt="Cover de ${item.title}">` : ''}
                        </div>
                        <div class="emission-actions"></div>
                        <div class="emission-details"></div>
                    `;

                    const actionsContainer = li.querySelector('.emission-actions');
                    const detailsContainer = li.querySelector('.emission-details');

                    const playBtn = document.createElement('button');
                    playBtn.type = 'button';
                    playBtn.className = 'play-playlist-btn';
                    playBtn.textContent = 'Écouter cette playlist';
                    playBtn.disabled = playableCount === 0;
                    if (playableCount === 0) {
                        playBtn.style.background = 'rgba(255,255,255,0.12)';
                        playBtn.style.cursor = 'not-allowed';
                        playBtn.title = 'Aucune piste jouable trouvée';
                    }
                    playBtn.addEventListener('click', function(){
                        if (playableCount === 0) return;
                        queue = trackUrls.filter(isPlayableUrl);
                        playQueue(0);
                    });
                    actionsContainer.appendChild(playBtn);

                    const trackList = document.createElement('ol');
                    trackList.className = 'track-list';

                    if (Array.isArray(item.songs) && item.songs.length > 0) {
                        item.songs.forEach(song => {
                            const url = resolveTrackSrc(song);
                            const liTrack = document.createElement('li');
                            const trackBtn = document.createElement('button');
                            trackBtn.type = 'button';
                            trackBtn.textContent = '▶';
                            if (!isPlayableUrl(url)) {
                                trackBtn.className = 'disabled';
                                trackBtn.disabled = true;
                            }
                            trackBtn.addEventListener('click', function(){
                                if (!isPlayableUrl(url)) return;
                                queue = [url];
                                playQueue(0);
                            });
                            const trackTitle = document.createElement('span');
                            trackTitle.className = 'track-title';
                            trackTitle.innerHTML = '<strong>' + formatTitle(song) + '</strong>' + (isPlayableUrl(url) ? '' : '<br><small style="opacity:.75;">Source introuvable / non jouable</small>');
                            liTrack.appendChild(trackBtn);
                            liTrack.appendChild(trackTitle);
                            trackList.appendChild(liTrack);
                        });
                    } else {
                        const message = document.createElement('div');
                        message.className = 'empty-state';
                        message.textContent = 'Aucune piste définie pour cette playlist.';
                        detailsContainer.appendChild(message);
                    }

                    detailsContainer.appendChild(trackList);
                    listEl.appendChild(li);
                });

                buildControls(day);
            }

            tabs.forEach(tab => {
                if (tab.dataset.day === todayName) tab.classList.add('active');
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    render(tab.dataset.day);
                });
            });

            player.addEventListener('ended', function () {
                if (queue.length > 0 && currentIndex + 1 < queue.length) {
                    playQueue(currentIndex + 1);
                } else {
                    setStatus('Lecture terminée.');
                }
            });

            player.addEventListener('play', function () { setStatus('Lecture en cours'); });
            player.addEventListener('pause', function () { setStatus('Lecture en pause'); });
            player.addEventListener('error', function () { setStatus('Erreur de lecture ; vérifiez la source.'); });

            render(todayName);
        })();
    </script>
</section>

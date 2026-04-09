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
    if (!in_array($day, $days, true)) {
        $day = 'autres';
    }
    $playlists_by_day[$day][] = $playlist;
}

$heroImage = isset($assetBase) ? $assetBase . '/images/playliste1.jpg' : '/assets/images/playliste1.jpg';
?>
<style>
    .playlist-hero {
        padding: 28px 20px;
        border-radius: 14px;
        color: #fff;
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center center;
        box-shadow: 0 12px 36px rgba(16,24,40,0.12);
        position: relative;
        overflow: hidden;
    }
    .playlist-hero::after {
        content: '';
        position: absolute; inset: 0; z-index: 0;
        background: linear-gradient(180deg, rgba(6,12,34,0.35) 0%, rgba(6,12,34,0.65) 100%);
        pointer-events: none;
    }
    .playlist-hero .hero-content { position: relative; z-index: 1; }
    .playlist-container { margin-top: 18px; }
    .audio-item { background: rgba(255,255,255,0.06); padding: 18px; border-radius: 14px; margin-bottom: 18px; color: #fff; }
    .audio-item h4 { margin: 0 0 10px; }
    .audio-item .emission-meta { display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0; }
    .audio-item .emission-meta span { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.08); padding: 6px 10px; border-radius: 999px; font-size: 0.92rem; }
    .audio-item .emission-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
    .audio-item .emission-details { margin-top: 8px; }
    .audio-item .track-list { list-style: none; margin: 8px 0 0; padding: 0; }
    .audio-item .track-list li { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 12px; background: rgba(255,255,255,0.04); margin-bottom: 8px; }
    .audio-item .track-list .track-title { flex: 1; color: rgba(255,255,255,0.92); }
    .audio-item .track-list button { min-width: 40px; padding: 8px 12px; border-radius: 999px; border: none; background: rgba(0,255,231,0.15); color: #e6eef6; cursor: pointer; }
    .audio-item .track-list button.disabled { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.5); cursor: not-allowed; }
    .play-playlist-btn { cursor: pointer; border: none; padding: 10px 16px; border-radius: 999px; background: #2f80ed; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
    .play-playlist-btn:hover { background: #2563eb; }
    .play-playlist-btn.disabled { background: rgba(255,255,255,0.12); cursor: not-allowed; }
    .cover-image { width: 120px; height: 120px; object-fit: cover; border-radius: 16px; flex-shrink: 0; }
    .day-tabs { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 8px; }
    .day-tabs .day-btn { border: none; background: rgba(255,255,255,0.08); color: #fff; padding: 10px 14px; border-radius: 999px; cursor: pointer; transition: transform 120ms ease, background 120ms ease; }
    .day-tabs .day-btn:hover { transform: translateY(-1px); }
    .day-tabs .day-btn.active { background: rgba(255,255,255,0.18); }
    .empty-state { color: rgba(255,255,255,0.85); padding: 30px; text-align: center; border: 1px dashed rgba(255,255,255,0.12); border-radius: 16px; }
    .playlist-status { margin-top: 18px; font-size: 0.95rem; color: rgba(255,255,255,0.85); }
    .playlist-actions { margin: 18px 0; display: flex; flex-wrap: wrap; gap: 10px; }
    .playlist-feedback { margin-bottom: 12px; color: rgba(255,255,255,0.9); }
    @media (max-width: 900px) { .audio-item { padding: 16px; } .cover-image { width: 100%; height: auto; } }
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

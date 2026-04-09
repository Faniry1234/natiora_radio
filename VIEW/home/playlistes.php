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
    .audio-item .track-list li { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; background: rgba(255,255,255,0.04); margin-bottom: 8px; }
    .audio-item .track-list .track-title { flex: 1; color: rgba(255,255,255,0.92); }
    .audio-item .track-list button { min-width: 36px; }
    .play-playlist-btn { cursor: pointer; border: none; padding: 10px 14px; border-radius: 999px; background: #2f80ed; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
    .play-playlist-btn:hover { background: #2563eb; }
    .cover-image { width: 120px; height: 120px; object-fit: cover; border-radius: 16px; flex-shrink: 0; }
    .day-tabs { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 8px; }
    .day-tabs .day-btn { border: none; background: rgba(255,255,255,0.08); color: #fff; padding: 10px 14px; border-radius: 999px; cursor: pointer; }
    .day-tabs .day-btn.active { background: rgba(255,255,255,0.16); }
    .empty-state { color: rgba(255,255,255,0.85); padding: 30px; text-align: center; border: 1px dashed rgba(255,255,255,0.12); border-radius: 16px; }
    @media (max-width: 900px) { .audio-item { padding: 16px; } .cover-image { width: 100%; height: auto; } }
</style>

<section id="playlist" class="playlist-hero" style="background-image: linear-gradient(180deg, rgba(6,12,34,0.35), rgba(6,12,34,0.65)), url('<?php echo htmlspecialchars($heroImage); ?>');">
    <div class="hero-content">
        <h2 style="margin:0 0 8px;font-size:2rem;">🎶 Playlists</h2>
        <p style="margin:0 0 12px;opacity:0.95;max-width:640px;">Retrouvez les meilleures sélections audio avec tous les détails et une lecture stable grâce à un backend de données normalisées.</p>
    </div>

    <div class="day-tabs" role="tablist" aria-label="Jours de la semaine">
        <button class="day-btn" data-day="lundi">Lundi</button>
        <button class="day-btn" data-day="mardi">Mardi</button>
        <button class="day-btn" data-day="mercredi">Mercredi</button>
        <button class="day-btn" data-day="jeudi">Jeudi</button>
        <button class="day-btn" data-day="vendredi">Vendredi</button>
        <button class="day-btn" data-day="samedi">Samedi</button>
        <button class="day-btn" data-day="dimanche">Dimanche</button>
    </div>

    <div class="playlist-container">
        <h3 id="emission-title">Playlistes</h3>
        <ul id="emissionList" class="playlist-list" aria-live="polite"></ul>
    </div>

    <script>
        (function(){
            const playlists = <?php echo json_encode($playlists_by_day, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;
            const assetBase = '<?php echo htmlspecialchars($assetBase ?? '/assets'); ?>';
            const daysOrder = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
            const todayIndex = new Date().getDay();
            const todayName = daysOrder[todayIndex];

            const tabs = document.querySelectorAll('.day-btn');
            const listEl = document.getElementById('emissionList');
            const titleEl = document.getElementById('emission-title');

            function resolveTrackSrc(src) {
                if (!src) return '';
                if (/^https?:\/\//i.test(src) || src.indexOf('//') === 0) return src;
                if (src.indexOf('/public/') === 0) return src.replace(/^\/public/, '');
                if (/^\/(assets|uploads)\//.test(src) || /^\//.test(src)) return src;
                return assetBase + '/audios/' + encodeURIComponent(src);
            }

            function resolveCover(cover) {
                if (!cover) return '';
                if (/^https?:\/\//i.test(cover) || cover.indexOf('//') === 0) return cover;
                if (cover.indexOf('/public/') === 0) return cover.replace(/^\/public/, '');
                if (/^\/(assets|uploads)\//.test(cover) || /^\//.test(cover)) return cover;
                return assetBase + '/images/' + encodeURIComponent(cover);
            }

            function normalizeTitle(src) {
                if (!src) return '';
                try {
                    let path = src;
                    if (/^https?:\/\//i.test(src) || src.indexOf('//') === 0) {
                        path = new URL(src, location.href).pathname;
                    }
                    path = path.split('?')[0];
                    const parts = path.split('/').filter(Boolean);
                    let name = parts.pop() || path;
                    name = name.replace(/\.[a-z0-9]{1,6}$/i, '');
                    name = decodeURIComponent(name.replace(/[_\-]+/g, ' ').trim());
                    return name || src;
                } catch (e) {
                    return src;
                }
            }

            function render(day) {
                listEl.innerHTML = '';
                const items = playlists[day] || [];
                titleEl.textContent = 'Playlistes — ' + day.charAt(0).toUpperCase() + day.slice(1);

                if (!items.length) {
                    listEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i> Aucune playlist disponible pour ce jour.</div>';
                    return;
                }

                items.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'audio-item';
                    const coverUrl = resolveCover(item.cover || '');
                    const trackCount = Array.isArray(item.songs) ? item.songs.length : 0;
                    li.innerHTML = `
                        <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                            <div style="flex:1;min-width:220px;">
                                <div class="emission-meta"><span><i class="fas fa-music"></i> ${trackCount} piste(s)</span></div>
                                <h4>${item.title || 'Playlist sans titre'}</h4>
                                <div class="emission-meta">
                                    <span><i class="fas fa-clock"></i> ${item.created_at || 'Date non définie'}</span>
                                </div>
                            </div>
                            ${coverUrl ? `<img class="cover-image" src="${coverUrl}" alt="Cover ${item.title}">` : ''}
                        </div>
                        <div class="emission-actions"></div>
                        <div class="emission-details"><p class="desc">${item.desc || ''}</p></div>
                    `;

                    if (trackCount > 0) {
                        const actions = li.querySelector('.emission-actions');
                        const btn = document.createElement('button');
                        btn.className = 'play-playlist-btn';
                        btn.type = 'button';
                        btn.innerHTML = '<span class="btn-icon">▶</span><span class="btn-label">Écouter la playlist</span>';
                        const normalizedTracks = item.songs.map(resolveTrackSrc);
                        btn.dataset.songs = JSON.stringify(normalizedTracks);
                        actions.appendChild(btn);

                        const details = li.querySelector('.emission-details');
                        const tracksEl = document.createElement('ol');
                        tracksEl.className = 'track-list';
                        normalizedTracks.forEach((source, index) => {
                            const tLi = document.createElement('li');
                            const tBtn = document.createElement('button');
                            tBtn.type = 'button';
                            tBtn.className = 'play-item-btn';
                            tBtn.dataset.src = source;
                            tBtn.setAttribute('aria-label', 'Écouter piste ' + (index + 1));
                            tBtn.textContent = '▶';
                            const span = document.createElement('span');
                            span.className = 'track-title';
                            span.textContent = normalizeTitle(source);
                            tLi.appendChild(tBtn);
                            tLi.appendChild(span);
                            tracksEl.appendChild(tLi);
                        });
                        details.appendChild(tracksEl);
                    }

                    listEl.appendChild(li);
                });
            }

            tabs.forEach(tab => {
                if (tab.dataset.day === todayName) tab.classList.add('active');
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    render(tab.dataset.day);
                });
            });

            render(todayName);
        })();
    </script>

    <div id="history-player-container" style="margin-top:20px;"></div>
</section>

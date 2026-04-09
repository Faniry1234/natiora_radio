<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!class_exists('Emissions')) require_once __DIR__ . '/../../APP/MODEL/Emissions.php';

$emissionModel = new Emissions();
$emissions = $emissionModel->getAll();
$days = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];
$emissions_by_day = array_fill_keys($days, []);

foreach ($emissions as $day => $items) {
    $dayKey = strtolower(trim($day));
    if (!in_array($dayKey, $days, true)) {
        $dayKey = 'autres';
    }
    $emissions_by_day[$dayKey] = $items;
}

$heroImage = isset($assetBase) ? $assetBase . '/images/LOGO%20RADIO.jpg' : '/assets/images/LOGO%20RADIO.jpg';
?>
<style>
    .emissions { color: #fff; }
    .emission-card { background: rgba(255,255,255,0.06); border-radius: 18px; padding: 20px; margin-bottom: 20px; }
    .emission-card h4 { margin: 0 0 12px; }
    .emission-meta { display: flex; flex-wrap: wrap; gap: 10px; margin: 12px 0; }
    .emission-meta span { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.08); padding: 8px 12px; border-radius: 999px; font-size: 0.95rem; }
    .day-tabs { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; }
    .day-tabs .day-btn { border: none; background: rgba(255,255,255,0.08); color: #fff; padding: 10px 14px; border-radius: 999px; cursor: pointer; transition: transform 120ms ease, background 120ms ease; }
    .day-tabs .day-btn:hover { transform: translateY(-1px); }
    .day-tabs .day-btn.active { background: rgba(255,255,255,0.18); }
    .video-player-wrapper { margin-top: 16px; }
    .empty-state { color: rgba(255,255,255,0.85); padding: 28px; text-align: center; border: 1px dashed rgba(255,255,255,0.12); border-radius: 16px; }
    @media (max-width: 900px) { .video-player-wrapper video, .video-player-wrapper audio { width: 100%; } }
</style>

<section id="emissions" class="emissions">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <h2 style="margin:0;">📻 Émissions</h2>
        <p style="margin:0;max-width:720px;opacity:0.92;">Choisissez un jour pour voir les émissions, lire l’audio ou la vidéo et afficher tous les détails de chaque programme.</p>
    </div>

    <div class="day-tabs" role="tablist" aria-label="Jours de la semaine">
        <button class="day-btn" data-day="lundi">Lundi</button>
        <button class="day-btn" data-day="mardi">Mardi</button>
        <button class="day-btn" data-day="mercredi">Mercredi</button>
        <button class="day-btn" data-day="jeudi">Jeudi</button>
        <button class="day-btn" data-day="vendredi">Vendredi</button>
        <button class="day-btn" data-day="samedi">Samedi</button>
        <button class="day-btn" data-day="dimanche">Dimanche</button>
        <?php if (!empty($emissions_by_day['autres'])): ?>
            <button class="day-btn" data-day="autres">Autres</button>
        <?php endif; ?>
    </div>

    <div class="playlist-container">
        <div style="margin-top: 20px;">
            <h3 id="emission-title">Émissions du jour</h3>
            <p id="emission-summary" style="opacity:0.9;max-width:720px;">Sélectionnez un jour pour afficher les émissions programmées.</p>
        </div>
        <ul id="emissionList" class="playlist-list" aria-live="polite"></ul>
    </div>

    <script>
        (function(){
            const emissions = <?php echo json_encode($emissions_by_day, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;
            const assetBase = '<?php echo htmlspecialchars($assetBase ?? '/assets'); ?>';
            const daysOrder = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
            const todayIndex = new Date().getDay();
            const todayName = daysOrder[todayIndex];

            const tabs = document.querySelectorAll('.day-btn');
            const listEl = document.getElementById('emissionList');
            const titleEl = document.getElementById('emission-title');
            const summaryEl = document.getElementById('emission-summary');

            function resolveMediaSrc(src) {
                if (!src || typeof src !== 'string') return '';
                src = src.trim();
                if (src.indexOf('http://') === 0 || src.indexOf('https://') === 0 || src.indexOf('//') === 0) {
                    return src;
                }
                if (src.indexOf('/public/') === 0) {
                    return src.replace('/public', '');
                }
                if (src.indexOf('/assets/') === 0 || src.indexOf('/uploads/') === 0 || src.indexOf('/') === 0) {
                    return src;
                }
                return assetBase + '/videos/' + encodeURI(src);
            }

            function isVideoSource(source) {
                if (!source) return false;
                const lower = source.toLowerCase();
                return lower.endsWith('.mp4') || lower.endsWith('.webm') || lower.endsWith('.ogg') || lower.endsWith('.mov');
            }

            function render(day) {
                listEl.innerHTML = '';
                const items = emissions[day] || [];
                titleEl.textContent = 'Émissions — ' + day.charAt(0).toUpperCase() + day.slice(1);
                summaryEl.textContent = items.length ? 'Affichage de ' + items.length + ' émission' + (items.length > 1 ? 's' : '') + ' pour ce jour.' : 'Aucune émission prévue pour ce jour.';

                if (!items.length) {
                    listEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i> Aucune émission prévue pour ce jour.</div>';
                    return;
                }

                items.forEach(item => {
                    const source = resolveMediaSrc(item.src || item.audio || '');
                    const mediaHtml = source
                        ? (isVideoSource(source)
                            ? `<video controls preload="metadata" width="100%" style="border-radius: 16px; background:#000;" playsinline><source src="${source}" type="video/mp4">Votre navigateur ne supporte pas la lecture vidéo.</video>`
                            : `<audio controls preload="metadata" style="width:100%;border-radius:16px;background:#000;display:block;"><source src="${source}" type="audio/mpeg">Votre navigateur ne supporte pas la lecture audio.</audio>`)
                        : '<div class="empty-state">Aucun média disponible pour cette émission.</div>';

                    const li = document.createElement('li');
                    li.className = 'emission-card';
                    li.innerHTML = `
                        <div>
                            <div class="emission-meta"><span><i class="fas fa-clock"></i> ${item.time || 'Heure inconnue'}</span><span><i class="fas fa-user"></i> ${item.presenter || 'Présentateur inconnu'}</span></div>
                            <h4>${item.title || 'Titre non défini'}</h4>
                            <div class="emission-meta"><span><i class="fas fa-hourglass-half"></i> ${item.duration || item.length || 'Durée non définie'}</span><span><i class="fas fa-tag"></i> ${item.category || item.genre || 'Catégorie'}</span></div>
                        </div>
                        <div class="video-player-wrapper">${mediaHtml}</div>
                        <div style="margin-top:14px; color: rgba(255,255,255,0.92); line-height:1.6;">${item.description || item.desc || 'Description non disponible.'}</div>
                    `;
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
</section>

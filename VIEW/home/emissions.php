<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!class_exists('Emissions')) require_once __DIR__ . '/../../APP/MODEL/Emissions.php';

$emissionModel = new Emissions();
$emissions = $emissionModel->getAll();
$days = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];
$emissions_by_day = array_fill_keys($days, []);

foreach ($emissions as $day => $items) {
    if (!in_array($day, $days, true)) {
        continue;
    }
    $emissions_by_day[$day] = $items;
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
    .day-tabs .day-btn { border: none; background: rgba(255,255,255,0.08); color: #fff; padding: 10px 14px; border-radius: 999px; cursor: pointer; }
    .day-tabs .day-btn.active { background: rgba(255,255,255,0.18); }
    .video-player-wrapper { margin-top: 16px; }
    .empty-state { color: rgba(255,255,255,0.85); padding: 28px; text-align: center; border: 1px dashed rgba(255,255,255,0.12); border-radius: 16px; }
    @media (max-width: 900px) { .video-player-wrapper video { width: 100%; } }
</style>

<section id="emissions" class="emissions">
    <h2>📻 Émissions</h2>

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
        <h3 id="emission-title">Émissions du jour</h3>
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

            function resolveVideoUrl(src) {
                if (!src) return '';
                if (/^https?:\/\//i.test(src) || src.indexOf('//') === 0) return src;
                if (src.indexOf('/public/') === 0) return src.replace(/^\/public/, '');
                if (/^\/(assets|uploads)\//.test(src) || /^\//.test(src)) return src;
                return assetBase + '/videos/' + encodeURIComponent(src);
            }

            function render(day) {
                listEl.innerHTML = '';
                const items = emissions[day] || [];
                titleEl.textContent = 'Émissions — ' + day.charAt(0).toUpperCase() + day.slice(1);

                if (!items.length) {
                    listEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i> Aucune émission prévue pour ce jour.</div>';
                    return;
                }

                items.forEach(item => {
                    const videoUrl = resolveVideoUrl(item.src || '');
                    const li = document.createElement('li');
                    li.className = 'emission-card';
                    li.innerHTML = `
                        <div>
                            <div class="emission-meta"><span><i class="fas fa-clock"></i> ${item.time || 'Heure inconnue'}</span><span><i class="fas fa-user"></i> ${item.presenter || 'Présentateur inconnu'}</span></div>
                            <h4>${item.title || 'Titre non défini'}</h4>
                            <div class="emission-meta"><span><i class="fas fa-hourglass-half"></i> ${item.duration || 'Durée non définie'}</span><span><i class="fas fa-signal"></i> ${item.level || 'Niveau inconnu'}</span><span><i class="fas fa-tag"></i> ${item.category || 'Catégorie'}</span></div>
                        </div>
                        <div class="video-player-wrapper">
                            <video controls preload="metadata" width="100%" style="border-radius: 16px; background:#000;" playsinline>
                                <source src="${videoUrl}" type="video/mp4">
                                Votre navigateur ne supporte pas la lecture vidéo.
                            </video>
                        </div>
                        <div style="margin-top:14px;">
                            <p>${item.description || item.desc || ''}</p>
                        </div>
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

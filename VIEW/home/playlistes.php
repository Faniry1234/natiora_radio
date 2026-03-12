<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// base URL
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

// Load models
if (!class_exists('Database')) require_once __DIR__ . '/../../APP/MODEL/Database.php';
if (!class_exists('Playlists')) require_once __DIR__ . '/../../APP/MODEL/Playlists.php';

$plModel = new Playlists();
$playlists = $plModel->getAll();
// Group playlists by weekday (French) using created_at when available
$days = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];
$playlists_by_day = array_fill_keys($days, []);
$playlists_by_day['autres'] = [];

$engToFr = [
    'Monday' => 'lundi', 'Tuesday' => 'mardi', 'Wednesday' => 'mercredi',
    'Thursday' => 'jeudi', 'Friday' => 'vendredi', 'Saturday' => 'samedi', 'Sunday' => 'dimanche'
];

foreach ($playlists as $pl) {
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

<style>
    .playlist-hero{
        padding:28px 20px;
        border-radius:14px;
        color: #fff;
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center center;
        box-shadow: 0 12px 36px rgba(16,24,40,0.12);
        position: relative;
        overflow: hidden;
    }
    .playlist-hero::after{
        content: '';
        position: absolute; inset:0; z-index:0;
        background: linear-gradient(180deg, rgba(6,12,34,0.45) 0%, rgba(6,12,34,0.6) 60%);
        pointer-events: none;
    }
    .playlist-hero .hero-content{ position:relative; z-index:1; }
    .playlist-container{ margin-top:18px; }
    .playlist-list .video-item{ background: rgba(255,255,255,0.06); padding:12px; border-radius:10px; margin-bottom:12px; color:#fff }
    .day-tabs .day-btn.active{ background: rgba(255,255,255,0.12); color:#fff }
    .empty-state{ color: rgba(255,255,255,0.9); padding:24px; text-align:center }
    @media (max-width:800px){ .playlist-hero{ padding:18px } }
</style>

<?php
$bg = $base . '/public/assets/images/playliste1.jpg';
?>
<section id="playlist" class="playlist-hero" style="background-image: linear-gradient(180deg, rgba(6,12,34,0.35), rgba(6,12,34,0.6)), url('<?php echo htmlspecialchars($bg); ?>');">
        <div class="hero-content">
            <h2 style="margin:0 0 6px;font-size:1.8rem;">🎶 Playlists</h2>
            <p style="margin:0 0 12px;opacity:0.95;">Retrouvez les meilleures sélections — écoutez et réécoutez</p>
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
            const playlists = <?php
                $js = [];
                foreach ($playlists_by_day as $k => $arr) {
                    $js[$k] = [];
                    foreach ($arr as $p) {
                        $entry = [
                            'title' => $p['title'] ?? '',
                            'desc' => $p['desc'] ?? '',
                            'created_at' => $p['created_at'] ?? '',
                            'songs' => []
                        ];
                        if (!empty($p['songs']) && is_array($p['songs'])){
                            foreach ($p['songs'] as $s) {
                                $entry['songs'][] = $base . $s;
                            }
                        }
                        $entry['tracks'] = count($entry['songs']);
                        $js[$k][] = $entry;
                    }
                }
                echo json_encode($js, JSON_UNESCAPED_SLASHES|JSON_HEX_TAG);
            ?>;

            const daysOrder = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
            const todayIndex = new Date().getDay();
            const todayName = daysOrder[todayIndex];

            const tabs = document.querySelectorAll('.day-btn');
            const listEl = document.getElementById('emissionList');
            const titleEl = document.getElementById('emission-title');

            function render(day){
                listEl.innerHTML = '';
                const arr = playlists[day] || [];
                titleEl.textContent = 'Playlistes — ' + day.charAt(0).toUpperCase() + day.slice(1);
                arr.forEach(item=>{
                    const li = document.createElement('li');
                    li.className = 'video-item';
                    let audios = '';
                    if (Array.isArray(item.songs)){
                        audios = item.songs.map(s => `\n                        <audio controls style="width:100%; margin-top:8px;">\n                            <source src="${s}" type="audio/mpeg">\n                            Votre navigateur ne supporte pas l'audio.\n                        </audio>`).join('');
                    }
                    li.innerHTML = `
                        <div class="emission-header">
                            <div class="emission-time">
                                <i class="fas fa-music"></i> ${item.tracks} piste(s)
                            </div>
                            <h4>${item.title}</h4>
                            <div class="emission-meta">
                                <span class="meta-badge duration"><i class="fas fa-clock"></i> ${item.created_at || ''}</span>
                            </div>
                        </div>
                        ${audios}
                        <div class="emission-details">
                            <p class="desc">${item.desc || ''}</p>
                        </div>
                    `;
                    listEl.appendChild(li);
                });
                if(arr.length===0){
                    listEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i> Aucune playlist pour ce jour</div>';
                }
            }

            tabs.forEach(t=>{
                const d = t.dataset.day;
                if(d===todayName) t.classList.add('active');
                t.addEventListener('click', function(){
                    tabs.forEach(x=>x.classList.remove('active'));
                    t.classList.add('active');
                    render(d);
                });
            });

            render(todayName);
        })();
    </script>

</section>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// base URL
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

require_once __DIR__ . '/../../app/MODEL/Playlists.php';
require_once __DIR__ . '/../../app/MODEL/Emissions.php';

$playModel = new Playlists();
$emModel = new Emissions();

// Input handling: date (Y-m-d), scope (day|week), type (playlists|emissions)
$inputDate = trim($_GET['date'] ?? '');
$scopeRaw = $_GET['scope'] ?? 'week';
$scope = in_array($scopeRaw, ['day','week']) ? $scopeRaw : 'week';
$typeRaw = $_GET['type'] ?? 'all';
$type = in_array($typeRaw, ['playlists','emissions','all']) ? $typeRaw : 'all';

// Determine reference date: if provided use it, else use today
if ($inputDate) {
    $refTs = strtotime($inputDate);
    if ($refTs === false) $refTs = time();
} else {
    // default: last week (use today but set ref to now)
    $refTs = time();
}

// Helper: start and end of week containing refTs (Monday..Sunday)
function weekRangeFromTs($ts) {
    // ISO week: Monday is 1
    $monday = strtotime('monday this week', $ts);
    // if today is Monday and we asked for last week default, we'll subtract 7 days later in logic
    $sunday = strtotime('sunday this week', $ts);
    return [$monday, $sunday];
}

// If no date provided, we want the previous week
if (empty($inputDate)) {
    // previous week
    $refTs = strtotime('-1 week');
}
list($weekStart, $weekEnd) = weekRangeFromTs($refTs);

// For 'day' scope, set dayStart/dayEnd to the selected date
if ($scope === 'day') {
    $dayStart = strtotime(date('Y-m-d', $refTs) . ' 00:00:00');
    $dayEnd = strtotime(date('Y-m-d', $refTs) . ' 23:59:59');
}

// Fetch playlists and filter by created_at
$playlists = $playModel->getAll();
$filteredPlaylists = [];
foreach ($playlists as $p) {
    $created = isset($p['created_at']) ? strtotime($p['created_at']) : false;
    if ($created === false) continue;
    if ($scope === 'day') {
        if ($created >= $dayStart && $created <= $dayEnd) $filteredPlaylists[] = $p;
    } else {
        if ($created >= $weekStart && $created <= $weekEnd) $filteredPlaylists[] = $p;
    }
}

// Fetch emissions and map by weekday -> for week scope we expand to dates
$emissionsByDay = $emModel->getAll(); // returns keyed by french days when DB not present
$selectedEmissions = [];
if ($scope === 'day') {
    $weekdayFr = strtolower(date('l', $refTs));
    // map English day to french key used in emissions model
    $engToFr = ['monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi','thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche'];
    $fr = $engToFr[strtolower(date('l', $refTs))] ?? '';
    $selectedEmissions = $emissionsByDay[$fr] ?? [];
} else {
    // week: iterate each day from weekStart to weekEnd and attach emissions for that weekday
    for ($d = $weekStart; $d <= $weekEnd; $d = strtotime('+1 day', $d)) {
        $eng = strtolower(date('l', $d));
        $engToFr = ['monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi','thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche'];
        $fr = $engToFr[$eng] ?? '';
        $items = $emissionsByDay[$fr] ?? [];
        foreach ($items as $it) {
            $it['_date'] = date('Y-m-d', $d);
            $selectedEmissions[] = $it;
        }
    }
}

// Prepare JSON payloads for client-side rendering
$js_playlists = json_encode(array_values($filteredPlaylists), JSON_UNESCAPED_SLASHES|JSON_HEX_TAG);
$js_emissions = json_encode(array_values($selectedEmissions), JSON_UNESCAPED_SLASHES|JSON_HEX_TAG);
?>

<section id="playlist" class="playlist" style="padding:20px;">
    <h2>Historique — Playlists & Émissions</h2>

    <form id="histForm" method="get" style="margin-bottom:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <label>Date: <input type="date" name="date" value="<?php echo htmlspecialchars(date('Y-m-d', $refTs)); ?>"></label>
        <label>Portée:
            <select name="scope">
                <option value="week" <?php if($scope==='week') echo 'selected'; ?>>Semaine</option>
                <option value="day" <?php if($scope==='day') echo 'selected'; ?>>Jour</option>
            </select>
        </label>
        <label>Type:
            <select name="type">
                <option value="all" <?php if($type==='all') echo 'selected'; ?>>Tous</option>
                <option value="playlists" <?php if($type==='playlists') echo 'selected'; ?>>Playlists</option>
                <option value="emissions" <?php if($type==='emissions') echo 'selected'; ?>>Émissions</option>
            </select>
        </label>
        <button type="submit" class="btn-primary">Afficher</button>
    </form>

    <div class="playlist-container">
        <h3 id="emission-title">Affichage: <?php echo ($scope==='day' ? 'Jour' : 'Semaine') . ' — ' . date('Y-m-d', $refTs); ?></h3>

        <div id="results">
            <!-- client will render lists from JSON payloads -->
        </div>
    </div>

    <div id="history-player-container" style="display:block;margin-top:12px;">
        <div id="historyPlayerStatus" style="color:#ddd;margin-bottom:6px;"></div>
    </div>

    <script>
        (function(){
            var playlists = <?php echo $js_playlists; ?>;
            var emissions = <?php echo $js_emissions; ?>;
            var type = '<?php echo $type; ?>';
            var results = document.getElementById('results');

            // intercept form to load data via AJAX and avoid full page reload
            var histForm = document.getElementById('histForm');
            if (histForm) {
                histForm.addEventListener('submit', function(ev){
                    ev.preventDefault();
                    var form = ev.currentTarget;
                    var params = new URLSearchParams(new FormData(form));
                    var endpoint = (window.app_BASE && window.app_BASE.length) ? (window.app_BASE.replace(/\/+$/,'') + '/scripts/history_fetch.php') : 'scripts/history_fetch.php';
                    try { console.log('history fetch ->', endpoint + '?' + params.toString()); } catch(e){}
                    fetch(endpoint + '?' + params.toString(), { credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(json){
                            playlists = json.playlists || [];
                            emissions = json.emissions || [];
                            type = json.type || params.get('type') || 'all';
                            document.getElementById('emission-title').textContent = 'Affichage: ' + (params.get('scope') === 'day' ? 'Jour' : 'Semaine') + ' — ' + (params.get('date') || '');
                            render();
                        }).catch(function(err){ console.error('history fetch error', err); });
                });
            }

            function render() {
                results.innerHTML = '';
                // Reuse the same structure/classes as the dedicated pages so styling matches
                var ul = document.createElement('ul');
                ul.id = 'emissionList';
                ul.className = 'playlist-list';

                // Playlists (render as in playlistes.php)
                if ((type === 'all' || type === 'playlists') && playlists.length) {
                    playlists.forEach(function(p){
                        var li = document.createElement('li');
                        li.className = 'video-item';
                        var tracks = Array.isArray(p.songs) ? p.songs.length : 0;
                        var created = p.created_at || '';
                        var audios = '';
                        if (Array.isArray(p.songs) && p.songs.length) {
                            audios = p.songs.map(function(s){
                                var src = s;
                                try { src = (s.indexOf('/')===0) ? s : s; } catch(e){}
                                return '\n                        <audio controls style="width:100%; margin-top:8px;">\n                            <source src="' + src + '" type="audio/mpeg">\n                            Votre navigateur ne supporte pas l\'audio.\n                        </audio>';
                            }).join('');
                        }
                        li.innerHTML = '\n                        <div class="emission-header">\n                            <div class="emission-time">\n                                <i class="fas fa-music"></i> ' + tracks + ' piste(s)\n                            </div>\n                            <h4>' + (p.title||'Untitled') + '</h4>\n                            <div class="emission-meta">\n                                <span class="meta-badge duration"><i class="fas fa-clock"></i> ' + (created || '') + '</span>\n                            </div>\n                        </div>' + audios + '\n                        <div class="emission-details">\n                            <p class="desc">' + (p.desc || '') + '</p>\n                        </div>';
                        ul.appendChild(li);
                    });
                }

                // Emissions (render as in emissions.php)
                if ((type === 'all' || type === 'emissions') && emissions.length) {
                    emissions.forEach(function(e){
                        var li = document.createElement('li');
                        li.className = 'video-item';
                        var when = e._date || '';
                        var time = e.time || '';
                        var duration = e.duration || '';
                        var level = e.level || '';
                        var category = e.category || '';
                        var presenter = e.presenter || '';
                        var desc = e.description || e.desc || '';
                        var src = e.src || '';
                        var videoHtml = '';
                        if (src) {
                            var safeSrc = encodeURI(src);
                            videoHtml = '\n                        <video controls width="100%" style="max-width: 640px; border-radius: 8px;">\n                            <source src="' + safeSrc + '" type="video/mp4">\n                            Votre navigateur ne supporte pas la vidéo.\n                        </video>';
                        }
                        li.innerHTML = '\n                        <div class="emission-header">\n                            <div class="emission-time">\n                                <i class="fas fa-clock"></i> ' + time + '\n                            </div>\n                            <h4>' + (e.title||'Untitled') + '</h4>\n                            <div class="emission-meta">\n                                <span class="meta-badge duration"><i class="fas fa-hourglass-half"></i> ' + duration + '</span>\n                                <span class="meta-badge level"><i class="fas fa-signal"></i> ' + level + '</span>\n                                <span class="meta-badge category"><i class="fas fa-tag"></i> ' + category + '</span>\n                            </div>\n                        </div>' + videoHtml + '\n                        <div class="emission-details">\n                            <p class="desc">' + desc + '</p>\n                            <div class="presenter-info">\n                                <i class="fas fa-user-circle"></i>\n                                <span><strong>Présentateur:</strong> ' + presenter + '</span>\n                            </div>\n                        </div>';
                        ul.appendChild(li);
                    });
                }

                if (!ul.hasChildNodes()) {
                    results.innerHTML = '<div class="empty-state">Aucun élément trouvé pour la période demandée.</div>';
                } else {
                    results.appendChild(ul);
                }
            }
            render();
        })();
    </script>

</section>

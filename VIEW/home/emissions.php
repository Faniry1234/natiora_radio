<?php
session_start();
if (!class_exists("Emissions")) require_once __DIR__ . "/../../APP/MODEL/Emissions.php";

$emissionModel = new Emissions();
$emissions = $emissionModel->getAll();

// Scan vidéos et audios
$videoDir = __DIR__ . "/../../PUBLIC/assets/videos/";
$audioDir = __DIR__ . "/../../PUBLIC/assets/audios/";
$mediaFiles = [];

if (is_dir($videoDir)) {
    foreach (scandir($videoDir) as $file) {
        if (is_file($videoDir . $file) && preg_match("/\.(mp4|webm|mov|mkv|avi)$/i", $file)) {
            $mediaFiles[] = ["name"=>pathinfo($file,PATHINFO_FILENAME),"path"=>"/assets/videos/".urlencode($file),"type"=>"video"];
        }
    }
}
if (is_dir($audioDir)) {
    foreach (scandir($audioDir) as $file) {
        if (is_file($audioDir . $file) && preg_match("/\.(mp3|wav|aac|m4a|ogg|flac)$/i", $file)) {
            $mediaFiles[] = ["name"=>pathinfo($file,PATHINFO_FILENAME),"path"=>"/assets/audios/".urlencode($file),"type"=>"audio"];
        }
    }
}
sort($mediaFiles);

$days = ["lundi","mardi","mercredi","jeudi","vendredi","samedi","dimanche"];
$grouped = array_fill_keys($days, []);
foreach ($emissions as $emission) {
    $day = strtolower(trim($emission["day"] ?? "autres"));
    if (!in_array($day, $days)) $day = "autres";
    if (!isset($grouped[$day])) $grouped[$day] = [];
    $grouped[$day][] = $emission;
}
$today = strtolower(date("l"));
$dayMap = ["monday"=>"lundi","tuesday"=>"mardi","wednesday"=>"mercredi","thursday"=>"jeudi","friday"=>"vendredi","saturday"=>"samedi","sunday"=>"dimanche"];
$today = isset($dayMap[date("l")]) ? $dayMap[date("l")] : "lundi";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>📻 Émissions Radio</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);color:#fff;min-height:100vh}
.header{background:linear-gradient(180deg,rgba(44,83,100,.5),rgba(15,32,39,.8));padding:60px 20px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
.header h1{font-size:3.2rem;font-weight:800;margin-bottom:12px}
.container{max-width:1400px;margin:0 auto;padding:40px 20px}
.day-tabs{display:flex;gap:12px;margin-bottom:40px;flex-wrap:wrap;justify-content:center}
.day-tab{background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.15);color:#fff;padding:12px 22px;border-radius:30px;cursor:pointer;font-weight:600;transition:all .3s;font-size:1rem}
.day-tab:hover{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3);transform:translateY(-2px)}
.day-tab.active{background:linear-gradient(135deg,#4facff,#00f2fe);border-color:#4facff;box-shadow:0 8px 30px rgba(79,172,255,.3)}
.emissions-list{display:flex;flex-direction:column;gap:24px}
.emission-item{background:rgba(255,255,255,.06);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:24px;transition:all .4s;display:grid;grid-template-columns:auto 1fr;gap:24px;align-items:start}
.emission-item:hover{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);box-shadow:0 15px 40px rgba(0,0,0,.3);transform:translateX(8px)}
.emission-time{background:linear-gradient(135deg,#4facff,#00f2fe);color:#fff;padding:16px 20px;border-radius:14px;font-weight:800;text-align:center;font-size:1.3rem;min-width:100px}
.emission-content h3{font-size:1.4rem;margin-bottom:8px;font-weight:700}
.emission-content p{font-size:.95rem;opacity:.85;line-height:1.5;margin-bottom:12px}
.emission-meta{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.meta-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(79,172,255,.2);color:#7fbfff;padding:8px 14px;border-radius:20px;font-size:.85rem;font-weight:600}
.play-media-btn{background:linear-gradient(135deg,#4facff,#00f2fe);color:#fff;border:none;padding:10px 18px;border-radius:10px;font-weight:700;cursor:pointer;font-size:.95rem;transition:all .3s;display:inline-flex;align-items:center;gap:8px}
.play-media-btn:hover{transform:scale(1.05);box-shadow:0 10px 25px rgba(79,172,255,.4)}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;padding:20px}
.modal.active{display:flex}
.modal-content{background:rgba(15,32,39,.95);border-radius:20px;max-width:800px;width:100%;padding:30px;border:1px solid rgba(255,255,255,.15);box-shadow:0 30px 80px rgba(0,0,0,.6);position:relative}
.modal-close{position:absolute;top:20px;right:20px;background:0;border:none;color:#fff;font-size:1.8rem;cursor:pointer;opacity:.7;transition:opacity .2s}
.modal-close:hover{opacity:1}
.modal h2{font-size:1.6rem;margin-bottom:8px}
.modal p{opacity:.85;margin-bottom:20px;line-height:1.6}
audio{width:100%;height:50px;border-radius:10px;margin-bottom:20px}
video{width:100%;max-height:500px;border-radius:10px;margin-bottom:20px}
.empty-state{text-align:center;padding:80px 20px;opacity:.7}
.empty-state-icon{font-size:4rem;margin-bottom:20px}
@media (max-width:768px){.emission-item{grid-template-columns:1fr}.emission-time{width:100%}}
</style>
</head>
<body>
<div class="header"><h1>📻 Émissions</h1><p>Programme radio avec audio/vidéo. Fichiers: <?php echo count($mediaFiles); ?></p></div>
<div class="container">
<div class="day-tabs" id="dayTabs">
<?php foreach ($days as $day): ?><button class="day-tab <?php echo ($day === $today ? "active" : ""); ?>" data-day="<?php echo $day; ?>"><?php echo ucfirst($day); ?></button><?php endforeach; ?>
</div>
<div id="emissionsList" class="emissions-list"></div>
</div>
<div id="mediaModal" class="modal">
<div class="modal-content">
<button class="modal-close" onclick="closeModal()">✕</button>
<h2 id="modalTitle" style="margin-top:0;"></h2>
<p id="modalDesc"></p>
<div id="mediaContainer"></div>
</div>
</div>
<script>
const data = {media:<?php echo json_encode($mediaFiles); ?>,emissions:<?php echo json_encode($grouped); ?>};
let currentDay = "<?php echo $today; ?>";
(function(){
  document.querySelectorAll(".day-tab").forEach(tab=>{
    tab.addEventListener("click",function(){
      document.querySelectorAll(".day-tab").forEach(t=>t.classList.remove("active"));
      this.classList.add("active");
      currentDay = this.dataset.day;
      render();
    });
  });
  
  function render(){
    const emissions = (data.emissions[currentDay] || []).sort((a,b)=>(a.time||"").localeCompare(b.time||""));
    const container = document.getElementById("emissionsList");
    if (!emissions.length) {
      container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📻</div><p>Pas d\'émission</p></div>';
      return;
    }
    container.innerHTML = emissions.map(e=>`
      <div class="emission-item">
        <div class="emission-time">${e.time||"--:--"}</div>
        <div class="emission-content">
          <h3>${e.title||"Sans titre"}</h3>
          <p>${e.description||e.category||"Émission radio"}</p>
          <div class="emission-meta">
            <span class="meta-badge">🎙️ ${e.presenter||"Animateur"}</span>
            <span class="meta-badge">⏱️ ${e.duration||"60 min"}</span>
            <span class="meta-badge">📊 ${e.level||"Général"}</span>
            <span class="meta-badge">🎵 ${e.category||"Musique"}</span>
          </div>
          <button class="play-media-btn" onclick="playMedia(${JSON.stringify(e).replace(/"/g, '&quot;')})">▶️ Écouter</button>
        </div>
      </div>
    `).join("");
  }
  
  window.playMedia = function(emission){
    let media = data.media.find(m=>emission.title && m.name.toLowerCase().includes(emission.title.toLowerCase()));
    if (!media) media = data.media[0];
    if (!media) {alert("Aucun média"); return;}
    const modal = document.getElementById("mediaModal");
    document.getElementById("modalTitle").textContent = emission.title || "Émission";
    document.getElementById("modalDesc").textContent = emission.description || "";
    const container = document.getElementById("mediaContainer");
    if (media.type === "video") {
      container.innerHTML = '<video controls><source src="' + media.path + '" type="video/mp4"></video>';
    } else {
      container.innerHTML = '<audio controls autoplay><source src="' + media.path + '" type="audio/mpeg"></audio>';
    }
    modal.classList.add("active");
  };
  
  window.closeModal = function(){
    document.getElementById("mediaModal").classList.remove("active");
    const media = document.querySelector("#mediaContainer audio, #mediaContainer video");
    if (media) media.pause();
  };
  
  render();
})();
</script>
</body>
</html>
<?php
session_start();
if (!class_exists("Emissions")) require_once __DIR__ . "/../../APP/MODEL/Emissions.php";

$emissionModel = new Emissions();
$emissions = $emissionModel->getAll();

// Scan vidéos et audios
$videoDir = __DIR__ . "/../../PUBLIC/assets/videos/";
$audioDir = __DIR__ . "/../../PUBLIC/assets/audios/";
$mediaFiles = [];

if (is_dir($videoDir)) {
    foreach (scandir($videoDir) as $file) {
        if (is_file($videoDir . $file) && preg_match("/\.(mp4|webm|mov|mkv|avi)$/i", $file)) {
            $mediaFiles[] = ["name"=>pathinfo($file,PATHINFO_FILENAME),"path"=>"/assets/videos/".urlencode($file),"type"=>"video"];
        }
    }
}
if (is_dir($audioDir)) {
    foreach (scandir($audioDir) as $file) {
        if (is_file($audioDir . $file) && preg_match("/\.(mp3|wav|aac|m4a|ogg|flac)$/i", $file)) {
            $mediaFiles[] = ["name"=>pathinfo($file,PATHINFO_FILENAME),"path"=>"/assets/audios/".urlencode($file),"type"=>"audio"];
        }
    }
}
sort($mediaFiles);

$days = ["lundi","mardi","mercredi","jeudi","vendredi","samedi","dimanche"];
$grouped = array_fill_keys($days, []);
foreach ($emissions as $emission) {
    $day = strtolower(trim($emission["day"] ?? "autres"));
    if (!in_array($day, $days)) $day = "autres";
    if (!isset($grouped[$day])) $grouped[$day] = [];
    $grouped[$day][] = $emission;
}
$today = strtolower(date("l"));
$dayMap = ["monday"=>"lundi","tuesday"=>"mardi","wednesday"=>"mercredi","thursday"=>"jeudi","friday"=>"vendredi","saturday"=>"samedi","sunday"=>"dimanche"];
$today = isset($dayMap[date("l")]) ? $dayMap[date("l")] : "lundi";
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

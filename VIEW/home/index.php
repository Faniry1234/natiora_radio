<?php
// Compute asset base for videos
$projectRoot = realpath(__DIR__ . '/../../');
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';
$assetBase = '/assets';
if ($docRoot) {
    if ($docRoot === $projectRoot) {
        $assetBase = '/public/assets';
    } elseif ($docRoot === $projectRoot . DIRECTORY_SEPARATOR . 'public') {
        $assetBase = '/assets';
    }
} else {
    if (file_exists($projectRoot . '/public/assets')) {
        $assetBase = '/public/assets';
    }
}

// Function to get list of videos
function getVideosList($assetBase) {
    $videosDir = __DIR__ . '/../../PUBLIC/assets/videos';
    $videos = [];

    if (is_dir($videosDir)) {
        $files = scandir($videosDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                $videos[] = [
                    'filename' => $file,
                    'title' => urldecode(pathinfo($file, PATHINFO_FILENAME)),
                    'url' => $assetBase . '/videos/' . rawurlencode($file)
                ];
            }
        }
    }

    return $videos;
}

// Get videos for display
$videos = getVideosList($assetBase);
?>

<section id="home" class="player">
    <div class="home-hero">
        <div class="hero-left">
            <div class="poster" aria-hidden="false" onclick="playRadio()" style="cursor:pointer;">
                <div style="display:flex;align-items:center;gap:18px;">
                    <img src="<?php echo htmlspecialchars($assetBase); ?>/images/LOGO%20RADIO.jpg" alt="Natiora logo" class="poster-img" style="width:110px;border-radius:50%">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                        <button class="play-btn" aria-label="Play">▶</button>
                        <div class="audio-visualizer paused" aria-hidden="true">
                            <div class="bar"></div>
                            <div class="bar"></div>
                            <div class="bar"></div>
                            <div class="bar"></div>
                            <div class="bar"></div>
                        </div>
                    </div>
                </div>
            </div>
           <div class="player-wrapper" style="text-align: center; padding: 20px;">
    <!-- Manual play button: avoids autoplay restrictions on mobile -->
    <button id="manualPlayBtn" class="play-button" onclick="playRadio()">▶️ Écouter</button>

    <div id="playerStatus" style="margin-top:10px; color:var(--accent); font-weight:700">Arrêté</div>

</div>

<script>
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
        || window.matchMedia('(max-width: 768px)').matches;
}

function playRadio() {
    const streamUrl = 'http://p.onlineradiobox.com/mg/natiora/player/?cs=mg.natiora&played=1';
    if (isMobileDevice()) {
        openMobilePlayerModal(streamUrl);
    } else {
        window.open(streamUrl, 'playerPopup', 'width=400,height=300,scrollbars=no,resizable=yes,popup=yes');
    }
}

function openMobilePlayerModal(streamUrl) {
    let modal = document.getElementById('mobilePlayerModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'mobilePlayerModal';
        modal.className = 'modal mobile-player-modal';
        modal.innerHTML = `
            <div class="modal-content mobile-player-content">
                <button class="close" type="button" aria-label="Fermer">×</button>
                <h3 style="margin-top:0;color:#e6f9f6;">Écoute mobile Natiora Radio</h3>
                <p style="color:rgba(230,249,246,0.85);line-height:1.6;">Utilisez le lecteur mobile pour ouvrir notre flux directement sur votre appareil.</p>
                <button id="mobileRadioOpenBtn" class="btn-primary" type="button" style="width:100%;margin-top:16px;">Ouvrir le lecteur mobile</button>
                <p style="margin-top:12px;color:rgba(255,255,255,0.75);font-size:0.92rem;">Si le lecteur ne démarre pas automatiquement, appuyez sur le bouton ci-dessus.</p>
            </div>
        `;
        document.body.appendChild(modal);
        modal.querySelector('.close').addEventListener('click', closeMobilePlayerModal);
        modal.querySelector('#mobileRadioOpenBtn').addEventListener('click', function() {
            window.open(streamUrl, '_blank');
        });
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeMobilePlayerModal();
        });
    }
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeMobilePlayerModal() {
    const modal = document.getElementById('mobilePlayerModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
</script>

        <div class="hero-right">
            <div class="station-badge">LIVE • <strong>98.2 FM</strong></div>
            <h2 class="station-title">Natiora Radio</h2>
            <p class="station-frequency">98.2 FM — Madagascar</p>
            <p class="station-desc">Écoutez les meilleurs hits, émissions locales et tutoriels créatifs. Direct 24/7 — rejoignez la communauté.</p>
            <div class="hero-actions">
                <button id="playBtnAlt" class="btn-primary" onclick="playRadio()">▶ Écouter</button>
                <button id="openVlcBtn" class="btn-secondary">Ouvrir dans VLC</button>
                <a href="/index.php?route=playlistes" class="btn-secondary">Voir la playlist</a>
            </div>
        </div>
    </div>
</section>

<!-- Team / Responsables -->
<section id="team" style="margin-top:36px;">
    <div class="team-wrapper" style="max-width:1100px;margin:0 auto;padding:18px;">
        <h3 style="color:#e6f9f6;margin-bottom:12px;font-size:1.35rem">Nos Responsables</h3>
        <p style="color:rgba(230,249,246,0.85);margin-bottom:18px;">Contactez l'un des responsables pour assistance, partenariats ou questions générales.</p>
        <div class="team-grid">
            <?php
            $respFile = __DIR__ . '/../../DATA/responsables.php';
            $responsables = [];
            if (file_exists($respFile)) {
                $tmp = include $respFile;
                if (is_array($tmp)) $responsables = $tmp;
            }
            if (empty($responsables)) {
                $responsables = [
                    ['name'=>'Mr Hossea','role'=>'Responsable programmation','phone'=>'+261386363474','whatsapp'=>'261386363474','email'=>'hossea@natiora.mg','facebook'=>'https://facebook.com/rakoto','instagram'=>'https://instagram.com/rakoto','img'=>$assetBase . '/images/contact.jpg'],
                    ['name'=>'Mr Hossea','role'=>'Responsable communication','phone'=>'+261339876543','whatsapp'=>'261339876543','email'=>'rasoa@natiora.mg','facebook'=>'https://facebook.com/rasoa','instagram'=>'https://instagram.com/rasoa','img'=>$assetBase . '/images/contact.jpg'],
                    ['name'=>'Jeanine Mamy','role'=>'Responsable technique','phone'=>'+261327654321','whatsapp'=>'261327654321','email'=>'jeanine@natiora.mg','facebook'=>'https://facebook.com/jeanine','instagram'=>'https://instagram.com/jeanine','img'=>$assetBase . '/images/contact.jpg']
                ];
            }
            foreach ($responsables as $r):
            ?>
            <?php
                // Normalize image paths coming from DATA: convert leading /assets or /public/assets to $assetBase
                $rawImg = $r['img'] ?? '';
                $imgUrl = '';
                if ($rawImg) {
                    if (strpos($rawImg, '/public/assets/') === 0) {
                        $imgUrl = $assetBase . substr($rawImg, strlen('/public/assets'));
                    } elseif (strpos($rawImg, '/assets/') === 0) {
                        $imgUrl = $assetBase . substr($rawImg, strlen('/assets'));
                    } else {
                        $imgUrl = $rawImg;
                    }
                } else {
                    $imgUrl = $assetBase . '/images/contact.jpg';
                }
            ?>
            <div class="team-card" data-name="<?php echo htmlspecialchars($r['name'] ?? '') ?>" data-role="<?php echo htmlspecialchars($r['role'] ?? '') ?>" data-phone="<?php echo htmlspecialchars($r['phone'] ?? '') ?>" data-whatsapp="<?php echo htmlspecialchars($r['whatsapp'] ?? '') ?>" data-facebook="<?php echo htmlspecialchars($r['facebook'] ?? '') ?>" data-instagram="<?php echo htmlspecialchars($r['instagram'] ?? '') ?>" data-email="<?php echo htmlspecialchars($r['email'] ?? '') ?>" data-img="<?php echo htmlspecialchars($imgUrl) ?>">
                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Responsable" class="team-avatar">
                <div class="team-info">
                    <div class="team-name"><?php echo htmlspecialchars($r['name'] ?? ''); ?></div>
                    <div class="team-role"><?php echo htmlspecialchars($r['role'] ?? ''); ?></div>
                    <div class="team-contacts">
                        <?php if (!empty($r['phone'])): ?><a href="tel:<?php echo htmlspecialchars($r['phone']); ?>" title="Téléphone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($r['phone']); ?></a><?php endif; ?>
                        <?php if (!empty($r['whatsapp'])): ?><a href="https://wa.me/<?php echo htmlspecialchars($r['whatsapp']); ?>" target="_blank" rel="noopener" title="WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
                        <?php if (!empty($r['email'])): ?><a href="mailto:<?php echo htmlspecialchars($r['email']); ?>" title="Email"><i class="fas fa-envelope"></i></a><?php endif; ?>
                    </div>
                    <div class="team-socials">
                        <?php if (!empty($r['facebook'])): ?><a href="<?php echo htmlspecialchars($r['facebook']); ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                        <?php if (!empty($r['instagram'])): ?><a href="<?php echo htmlspecialchars($r['instagram']); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Videos Section -->
        <?php if (!empty($videos)): ?>
        <section id="videos" style="margin-top:36px;">
            <div class="videos-wrapper" style="max-width:1100px;margin:0 auto;padding:18px;">
                <h3 style="color:#e6f9f6;margin-bottom:12px;font-size:1.35rem">Tutoriels Vidéo</h3>
                <p style="color:rgba(230,249,246,0.85);margin-bottom:18px;">Découvrez nos tutoriels Photoshop et apprenez de nouvelles techniques créatives.</p>
                <div class="videos-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;">
                    <?php foreach ($videos as $video): ?>
                    <div class="video-card" style="background:rgba(255,255,255,0.05);border-radius:12px;padding:16px;border:1px solid rgba(255,255,255,0.1);">
                        <div class="video-title" style="color:#e6f9f6;font-weight:600;margin-bottom:12px;font-size:1rem;">
                            <?php echo htmlspecialchars($video['title']); ?>
                        </div>
                        <div class="video-player" style="position:relative;width:100%;height:200px;background:#000;border-radius:8px;overflow:hidden;">
                            <video
                                controls
                                preload="metadata"
                                style="width:100%;height:100%;object-fit:contain;"
                                poster="<?php echo htmlspecialchars($assetBase); ?>/images/LOGO RADIO.jpg"
                            >
                                <source src="<?php echo htmlspecialchars($video['url']); ?>" type="video/mp4">
                                Votre navigateur ne supporte pas la lecture de vidéos.
                            </video>
                        </div>
                        <div class="video-actions" style="margin-top:12px;text-align:center;">
                            <button onclick="openVideoModal('<?php echo htmlspecialchars($video['url']); ?>', '<?php echo htmlspecialchars($video['title']); ?>')" class="btn-secondary" style="display:inline-block;padding:8px 16px;background:rgba(255,255,255,0.1);color:#e6f9f6;text-decoration:none;border-radius:6px;font-size:0.9rem;border:none;cursor:pointer;">
                                <i class="fas fa-play"></i> Regarder
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Contact card placed below responsables -->
        <div style="max-width:1100px;margin:18px auto 60px;padding:0 18px;">
            <div class="contact-card" data-name="Contact central" data-phone="+261341234567" data-whatsapp="261341234567" data-email="contact@natiora.mg" data-facebook="https://facebook.com/natiora" data-instagram="https://instagram.com/natiora" data-img="<?php echo htmlspecialchars($assetBase); ?>/images/contact.jpg">
                <img src="<?php echo htmlspecialchars($assetBase); ?>/images/contact.jpg" alt="Contact Natiora" class="contact-img" style="width:120px;height:120px;border-radius:12px;object-fit:cover;">
                <div class="contact-details" style="text-align:left;">
                    <div class="contact-title">Contact central</div>
                    <div class="contact-line">Tel: <a href="tel:+261341234567">+261 34 12 34 567</a></div>
                    <div class="contact-line">
                        <a href="https://wa.me/261341234567" target="_blank" rel="noopener" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <a href="mailto:contact@natiora.mg" title="Email"><i class="fas fa-envelope"></i></a>
                    </div>
                    <div class="contact-socials" style="margin-top:8px;">
                        <a href="https://facebook.com/natiora" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://instagram.com/natiora" target="_blank" rel="noopener" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team detail modal -->
        <div id="teamModal" class="modal" aria-hidden="true">
            <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="teamModalTitle">
                <button class="close" id="teamModalClose">×</button>
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <img id="teamModalImg" src="<?php echo htmlspecialchars($assetBase); ?>/images/contact.jpg" alt="" style="width:140px;height:140px;object-fit:cover;border-radius:8px;">
                    <div style="flex:1;text-align:left;color:#e6eef6">
                        <h3 id="teamModalTitle" style="margin:0 0 6px 0;font-size:1.25rem"></h3>
                        <div id="teamModalRole" style="margin-bottom:10px;opacity:0.9"></div>
                        <div id="teamModalPhone" style="margin-bottom:8px"></div>
                        <div id="teamModalWhats" style="margin-bottom:8px"></div>
                        <div id="teamModalEmail" style="margin-bottom:8px"></div>
                        <div id="teamModalSocials" style="display:flex;gap:8px"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<style>
@media (max-width: 768px) {
    .team-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }
}
.player-modal-content {
    max-width: 600px;
    width: 90%;
}
/* Modal styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    display: none;
    z-index: 1200;
    align-items: center;
    justify-content: center;
}
.mobile-player-modal {
    padding: 16px;
}
.mobile-player-content {
    max-width: 420px;
    width: 100%;
    padding: 18px 20px;
    background: rgba(12,17,34,0.96);
    border: 1px solid rgba(255,255,255,0.08);
}
.mobile-player-content h3 {
    margin: 0 0 10px;
}
.mobile-player-content .btn-primary {
    background: #2f80ed;
    border: none;
    color: #fff;
    padding: 12px 18px;
    border-radius: 999px;
    cursor: pointer;
    font-weight: 700;
}
.mobile-player-content .btn-primary:hover {
    background: #2563eb;
}
.modal.show {
    display: flex;
}
.modal-content {
    background: var(--card);
    color: var(--text);
    padding: 20px;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    position: relative;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.modal .close {
    position: absolute;
    top: 10px;
    right: 15px;
    background: none;
    border: none;
    font-size: 1.5em;
    color: var(--text);
    cursor: pointer;
    opacity: 0.7;
}
.modal .close:hover {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Existing team modal code
    const teamModal = document.getElementById('teamModal');
    const teamCloseBtn = document.getElementById('teamModalClose');
    const teamImg = document.getElementById('teamModalImg');
    const teamTitle = document.getElementById('teamModalTitle');
    const teamRole = document.getElementById('teamModalRole');
    const teamPhone = document.getElementById('teamModalPhone');
    const teamWhats = document.getElementById('teamModalWhats');
    const teamEmail = document.getElementById('teamModalEmail');
    const teamSocials = document.getElementById('teamModalSocials');

    function openTeamModal(data){
        teamImg.src = data.img || '<?php echo htmlspecialchars($assetBase); ?>/images/contact.jpg';
        teamTitle.textContent = data.name || '';
        teamRole.textContent = data.role || '';
        teamRole.style.display = data.role ? 'block' : 'none';
        teamPhone.innerHTML = data.phone ? ('Tel: <a href="tel:'+data.phone+'" title="Téléphone"><i class="fas fa-phone"></i> '+data.phone+'</a>') : '';
        teamWhats.innerHTML = data.whatsapp ? ('<a href="https://wa.me/'+data.whatsapp+'" target="_blank" rel="noopener" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>') : '';
        teamEmail.innerHTML = data.email ? ('<a href="mailto:'+data.email+'" title="Email"><i class="fas fa-envelope"></i></a>') : '';
        teamSocials.innerHTML = '';
        if (data.facebook) { const a = document.createElement('a'); a.href = data.facebook; a.target='_blank'; a.rel='noopener'; a.innerHTML = '<i class="fab fa-facebook-f"></i>'; teamSocials.appendChild(a); }
        if (data.instagram) { const a = document.createElement('a'); a.href = data.instagram; a.target='_blank'; a.rel='noopener'; a.innerHTML = '<i class="fab fa-instagram"></i>'; teamSocials.appendChild(a); }
        teamModal.classList.add('show'); teamModal.setAttribute('aria-hidden','false');
    }
    function closeTeamModal(){ teamModal.classList.remove('show'); teamModal.setAttribute('aria-hidden','true'); }

    document.querySelectorAll('.team-card, .contact-card').forEach(function(card){
        card.addEventListener('click', function(){
            const data = {
                name: card.dataset.name,
                role: card.dataset.role || '', // for contact central, no role
                phone: card.dataset.phone,
                whatsapp: card.dataset.whatsapp,
                email: card.dataset.email,
                facebook: card.dataset.facebook,
                instagram: card.dataset.instagram,
                img: card.dataset.img
            };
            openTeamModal(data);
        });
    });

    teamCloseBtn.addEventListener('click', closeTeamModal);
    teamModal.addEventListener('click', function(e){ if (e.target === teamModal) closeTeamModal(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeTeamModal(); });
});

// Video Modal Functions
function openVideoModal(videoUrl, videoTitle) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('videoModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'videoModal';
        modal.innerHTML = `
            <div class="video-modal-overlay">
                <div class="video-modal-content">
                    <div class="video-modal-header">
                        <h3 id="videoModalTitle"></h3>
                        <button class="video-modal-close" onclick="closeVideoModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="video-modal-body">
                        <video id="videoModalPlayer" controls autoplay style="width:100%;max-height:70vh;object-fit:contain;border-radius:8px;">
                            Votre navigateur ne supporte pas la lecture de vidéos.
                        </video>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Add modal styles
        const style = document.createElement('style');
        style.textContent = `
            .video-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease;
            }
            .video-modal-content {
                background: #000;
                border-radius: 12px;
                max-width: 90vw;
                max-height: 90vh;
                width: 100%;
                position: relative;
                box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            }
            .video-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            .video-modal-header h3 {
                color: #fff;
                margin: 0;
                font-size: 1.2rem;
                font-weight: 600;
            }
            .video-modal-close {
                background: none;
                border: none;
                color: #fff;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.3s ease;
            }
            .video-modal-close:hover {
                background: rgba(255,255,255,0.1);
            }
            .video-modal-body {
                padding: 20px;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @media (max-width: 768px) {
                .video-modal-content {
                    max-width: 95vw;
                    max-height: 95vh;
                }
                .video-modal-header {
                    padding: 15px;
                }
                .video-modal-body {
                    padding: 15px;
                }
                .video-modal-header h3 {
                    font-size: 1rem;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Set video content
    document.getElementById('videoModalTitle').textContent = videoTitle;
    const videoPlayer = document.getElementById('videoModalPlayer');
    videoPlayer.src = videoUrl;
    videoPlayer.load();

    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    // Close modal when clicking overlay
    modal.addEventListener('click', function(e) {
        if (e.target === modal || e.target.classList.contains('video-modal-overlay')) {
            closeVideoModal();
        }
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVideoModal();
        }
    });
}

function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    if (modal) {
        const videoPlayer = document.getElementById('videoModalPlayer');
        videoPlayer.pause();
        videoPlayer.src = '';
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
</script>
    // Play handling is delegated to /assets/js/radio-player.js to avoid duplicate listeners



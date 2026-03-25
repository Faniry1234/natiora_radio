<section id="home" class="player">
    <div class="home-hero">
        <div class="hero-left">
            <div class="poster" aria-hidden="false">
                <div style="display:flex;align-items:center;gap:18px;">
                    <img src="<?php echo htmlspecialchars($assetBase); ?>/images/LOGO%20RADIO.jpg" alt="Natiora logo" class="poster-img" style="width:110px;border-radius:50%">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                        <button id="playBtn" class="play-btn" aria-label="Play">▶</button>
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
            <!-- Audio stream element (used as audio/video element for cross-browser) -->
            <?php $streamUrl = getenv('STREAM_URL') ?: 'https://uk24freenew.listen2myradio.com/live.mp3?typeportmount=s1_26912_stream_657428790'; ?>
           <audio id="radio" data-stream="<?php echo htmlspecialchars($streamUrl); ?>" preload="none" style="display:none"></audio>
            <div id="playerStatus" style="margin-top:10px;color:var(--accent);font-weight:700"></div>
        </div>
        <div class="hero-right">
            <div class="station-badge">LIVE • <strong>98.2 FM</strong></div>
            <h2 class="station-title">Natiora Radio</h2>
            <p class="station-frequency">98.2 FM — Madagascar</p>
            <p class="station-desc">Écoutez les meilleurs hits, émissions locales et tutoriels créatifs. Direct 24/7 — rejoignez la communauté.</p>
            <div class="hero-actions">
                <button id="playBtnAlt" class="btn-primary">▶ Écouter</button>
                <button id="openVlcBtn" class="btn-secondary">Ouvrir dans VLC</button>
                <a href="/index.php?route=playlistes" class="btn-secondary">Voir la playlist</a>
            </div>
        </div>
    </div>
</section>

<!-- External audio player script: injecte la source depuis l'attribut data-stream et gère play/pause -->
    <script src="<?php echo htmlspecialchars($assetBase); ?>/js/radio-player.js"></script>

    <!-- Optional: local preview sound for the play button (can be remote) -->
    <audio id="localSound" src="https://uk24freenew.listen2myradio.com/live.mp3?typeportmount=s1_26912_stream_657428790" preload="auto"></audio>

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
                    ['name'=>'Rakoto Andry','role'=>'Responsable programmation','phone'=>'+261341234567','whatsapp'=>'261341234567','facebook'=>'https://facebook.com/rakoto','instagram'=>'https://instagram.com/rakoto','img'=>$assetBase . '/images/contact.jpg'],
                    ['name'=>'Rasoa Lala','role'=>'Responsable communication','phone'=>'+261339876543','whatsapp'=>'261339876543','facebook'=>'https://facebook.com/rasoa','instagram'=>'https://instagram.com/rasoa','img'=>$assetBase . '/images/contact.jpg'],
                    ['name'=>'Jeanine Mamy','role'=>'Responsable technique','phone'=>'+261327654321','whatsapp'=>'261327654321','facebook'=>'https://facebook.com/jeanine','instagram'=>'https://instagram.com/jeanine','img'=>$assetBase . '/images/contact.jpg']
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
            <div class="team-card" data-name="<?php echo htmlspecialchars($r['name'] ?? '') ?>" data-role="<?php echo htmlspecialchars($r['role'] ?? '') ?>" data-phone="<?php echo htmlspecialchars($r['phone'] ?? '') ?>" data-whatsapp="<?php echo htmlspecialchars($r['whatsapp'] ?? '') ?>" data-facebook="<?php echo htmlspecialchars($r['facebook'] ?? '') ?>" data-instagram="<?php echo htmlspecialchars($r['instagram'] ?? '') ?>" data-img="<?php echo htmlspecialchars($imgUrl) ?>">
                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Responsable" class="team-avatar">
                <div class="team-info">
                    <div class="team-name"><?php echo htmlspecialchars($r['name'] ?? ''); ?></div>
                    <div class="team-role"><?php echo htmlspecialchars($r['role'] ?? ''); ?></div>
                    <div class="team-contacts">
                        <?php if (!empty($r['phone'])): ?><a href="tel:<?php echo htmlspecialchars($r['phone']); ?>"><?php echo htmlspecialchars($r['phone']); ?></a><?php endif; ?>
                        <?php if (!empty($r['whatsapp'])): ?><a href="https://wa.me/<?php echo htmlspecialchars($r['whatsapp']); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
                    </div>
                    <div class="team-socials">
                        <?php if (!empty($r['facebook'])): ?><a href="<?php echo htmlspecialchars($r['facebook']); ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                        <?php if (!empty($r['instagram'])): ?><a href="<?php echo htmlspecialchars($r['instagram']); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Contact card placed below responsables -->
        <div style="max-width:1100px;margin:18px auto 60px;padding:0 18px;">
            <div class="contact-card" style="margin-top:18px;display:flex;gap:12px;align-items:center;">
                <img src="<?php echo htmlspecialchars($assetBase); ?>/images/contact.jpg" alt="Contact Natiora" class="contact-img" style="width:120px;height:120px;border-radius:12px;object-fit:cover;">
                <div class="contact-details" style="text-align:left;">
                    <div class="contact-title">Contact central</div>
                    <div class="contact-line">Tel: <a href="tel:+261341234567">+261 34 12 34 567</a></div>
                    <div class="contact-line">WhatsApp: <a href="https://wa.me/261341234567" target="_blank" rel="noopener">+261 34 12 34 567</a></div>
                    <div class="contact-socials" style="margin-top:8px;">
                        <a href="https://facebook.com/natiora" target="_blank" rel="noopener" class="social">Facebook</a>
                        <a href="https://instagram.com/natiora" target="_blank" rel="noopener" class="social">Instagram</a>
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
                        <div id="teamModalSocials" style="display:flex;gap:8px"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const modal = document.getElementById('teamModal');
    const closeBtn = document.getElementById('teamModalClose');
    const img = document.getElementById('teamModalImg');
    const title = document.getElementById('teamModalTitle');
    const role = document.getElementById('teamModalRole');
    const phone = document.getElementById('teamModalPhone');
    const whats = document.getElementById('teamModalWhats');
    const socials = document.getElementById('teamModalSocials');

    function openModal(data){
        img.src = data.img || '<?php echo htmlspecialchars($assetBase); ?>/images/contact.jpg';
        title.textContent = data.name || '';
        role.textContent = data.role || '';
        phone.innerHTML = data.phone ? ('Tel: <a href="tel:'+data.phone+'">'+data.phone+'</a>') : '';
        whats.innerHTML = data.whatsapp ? ('WhatsApp: <a href="https://wa.me/'+data.whatsapp+'" target="_blank" rel="noopener">'+data.whatsapp+'</a>') : '';
        socials.innerHTML = '';
        if (data.facebook) { const a = document.createElement('a'); a.href = data.facebook; a.target='_blank'; a.rel='noopener'; a.innerHTML = '<i class="fab fa-facebook-f"></i>'; socials.appendChild(a); }
        if (data.instagram) { const a = document.createElement('a'); a.href = data.instagram; a.target='_blank'; a.rel='noopener'; a.innerHTML = '<i class="fab fa-instagram"></i>'; socials.appendChild(a); }
        modal.classList.add('show'); modal.setAttribute('aria-hidden','false');
    }
    function closeModal(){ modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); }

    document.querySelectorAll('.team-card').forEach(function(card){
        card.addEventListener('click', function(){
            const data = {
                name: card.dataset.name,
                role: card.dataset.role,
                phone: card.dataset.phone,
                whatsapp: card.dataset.whatsapp,
                facebook: card.dataset.facebook,
                instagram: card.dataset.instagram,
                img: card.dataset.img
            };
            openModal(data);
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });
});
</script>
    // Play handling is delegated to /assets/js/radio-player.js to avoid duplicate listeners



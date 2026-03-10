<section id="home" class="player">
    <div class="home-hero">
        <div class="hero-left">
            <div class="poster" aria-hidden="false">
                <div style="display:flex;align-items:center;gap:18px;">
                    <img src="/PUBLIC/assets/images/LOGO%20RADIO.jpg" alt="Natiora logo" class="poster-img" style="width:110px;border-radius:50%">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                        <button id="playBtnMain" class="play-btn" aria-label="Play">▶</button>
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
            <video id="radio" data-stream="http://192.168.1.102:8000" preload="none" playsinline webkit-playsinline style="display:none"></video>
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
<script src="/PUBLIC/assets/js/radio-player.js"></script>

<!-- Team / Responsables -->
<section id="team" style="margin-top:36px;">
    <div class="team-wrapper" style="max-width:1100px;margin:0 auto;padding:18px;">
        <h3 style="color:#e6f9f6;margin-bottom:12px;font-size:1.35rem">Nos Responsables</h3>
        <p style="color:rgba(230,249,246,0.85);margin-bottom:18px;">Contactez l'un des responsables pour assistance, partenariats ou questions générales.</p>
        <div class="team-grid">
            <div class="team-card" data-name="Rakoto Andry" data-role="Responsable programmation" data-phone="+261341234567" data-whatsapp="261341234567" data-facebook="https://facebook.com/rakoto" data-instagram="https://instagram.com/rakoto" data-img="/PUBLIC/assets/images/responsable1.jpg">
                <img src="/PUBLIC/assets/images/responsable1.jpg" alt="Responsable 1" class="team-avatar">
                <div class="team-info">
                    <div class="team-name">Rakoto Andry</div>
                    <div class="team-role">Responsable programmation</div>
                    <div class="team-contacts">
                        <a href="tel:+261341234567">+261 34 12 34 567</a>
                        <a href="https://wa.me/261341234567" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                    <div class="team-socials">
                        <a href="https://facebook.com/rakoto" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://instagram.com/rakoto" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>

            <div class="team-card" data-name="Rasoa Lala" data-role="Responsable communication" data-phone="+261339876543" data-whatsapp="261339876543" data-facebook="https://facebook.com/rasoa" data-instagram="https://instagram.com/rasoa" data-img="/PUBLIC/assets/images/responsable2.jpg">
                <img src="/PUBLIC/assets/images/responsable2.jpg" alt="Responsable 2" class="team-avatar">
                <div class="team-info">
                    <div class="team-name">Rasoa Lala</div>
                    <div class="team-role">Responsable communication</div>
                    <div class="team-contacts">
                        <a href="tel:+261339876543">+261 33 98 76 543</a>
                        <a href="https://wa.me/261339876543" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                    <div class="team-socials">
                        <a href="https://facebook.com/rasoa" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://instagram.com/rasoa" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>

            <div class="team-card" data-name="Jeanine Mamy" data-role="Responsable technique" data-phone="+261327654321" data-whatsapp="261327654321" data-facebook="https://facebook.com/jeanine" data-instagram="https://instagram.com/jeanine" data-img="/PUBLIC/assets/images/responsable3.jpg">
                <img src="/PUBLIC/assets/images/responsable3.jpg" alt="Responsable 3" class="team-avatar">
                <div class="team-info">
                    <div class="team-name">Jeanine Mamy</div>
                    <div class="team-role">Responsable technique</div>
                    <div class="team-contacts">
                        <a href="tel:+261327654321">+261 32 76 54 321</a>
                        <a href="https://wa.me/261327654321" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                    <div class="team-socials">
                        <a href="https://facebook.com/jeanine" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://instagram.com/jeanine" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact card placed below responsables -->
    <div style="max-width:1100px;margin:18px auto 60px;padding:0 18px;">
        <div class="contact-card" style="margin-top:18px;">
            <img src="/PUBLIC/assets/images/contact.jpg" alt="Contact Natiora" class="contact-img">
            <div class="contact-details">
                <div class="contact-title">Contact central</div>
                <div class="contact-line">Tel: <a href="tel:+261341234567">+261 34 12 34 567</a></div>
                <div class="contact-line">WhatsApp: <a href="https://wa.me/261341234567" target="_blank" rel="noopener">+261 34 12 34 567</a></div>
                <div class="contact-socials">
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
                <img id="teamModalImg" src="/PUBLIC/assets/images/contact.jpg" alt="" style="width:140px;height:140px;object-fit:cover;border-radius:8px;">
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
        img.src = data.img || '/PUBLIC/assets/images/contact.jpg';
        img.alt = data.name || 'Contact';
        title.textContent = data.name || '';
        role.textContent = data.role || '';
        phone.innerHTML = data.phone ? ('Tel: <a href="tel:'+data.phone+'">'+data.phone+'</a>') : '';
        whats.innerHTML = data.whatsapp ? ('WhatsApp: <a href="https://wa.me/'+data.whatsapp+'" target="_blank">+'+data.whatsapp+'</a>') : '';
        socials.innerHTML = '';
        if (data.facebook) {
            const a = document.createElement('a'); a.href = data.facebook; a.target = '_blank'; a.rel='noopener'; a.textContent='Facebook'; a.style.color = 'var(--accent-2)'; socials.appendChild(a);
        }
        if (data.instagram) {
            const a = document.createElement('a'); a.href = data.instagram; a.target = '_blank'; a.rel='noopener'; a.textContent='Instagram'; a.style.color = 'var(--accent-2)'; socials.appendChild(a);
        }
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

    // Play button handling: control the #radio element and toggle .playing
    const playBtn = document.getElementById('playBtnMain');
    const playBtnAlt = document.getElementById('playBtnAlt');
    const audioEl = document.getElementById('radio');
    const visual = document.querySelector('.audio-visualizer');

    function setPlaying(state){
        if(state){
            document.body.classList.add('playing');
            visual && visual.classList.remove('paused');
            if(playBtn) playBtn.textContent = '❚❚';
            if(playBtnAlt) playBtnAlt.textContent = '❚❚ Pause';
        } else {
            document.body.classList.remove('playing');
            visual && visual.classList.add('paused');
            if(playBtn) playBtn.textContent = '▶';
            if(playBtnAlt) playBtnAlt.textContent = '▶ Écouter';
        }
    }

    if(playBtn){
        playBtn.addEventListener('click', function(e){
            e.preventDefault();
            if(!audioEl) return;
            if(audioEl.paused){
                const src = audioEl.dataset.stream || audioEl.getAttribute('src');
                if(src && !audioEl.src) audioEl.src = src;
                audioEl.play().then(()=> setPlaying(true)).catch(()=> setPlaying(false));
            } else {
                audioEl.pause();
                setPlaying(false);
            }
        });
    }
    if(playBtnAlt){
        playBtnAlt.addEventListener('click', function(e){ e.preventDefault(); playBtn && playBtn.click(); });
    }

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });
});
</script>


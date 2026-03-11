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
            const emissions = {
                lundi: [
                    { 
                        time: '08:00', 
                        title: 'Concevoir une affiche', 
                        src: '/public/assets/videos/Tuto Boy  Comment concevoir une affiche sur Photoshop..mp4', 
                        desc: "Atelier complet sur la composition d'affiche et typographie.", 
                        presenter: 'Tuto Boy',
                        duration: '45 min',
                        level: 'Intermédiaire',
                        category: 'Design Graphique'
                    },
                    { 
                        time: '09:00', 
                        title: 'Montage Flyer Professionnel', 
                        src: '/public/assets/videos/TUTO BOY COMMENT FAIRE UN MONTAGE PHOTO SUR PHOTOSHOP  [flyer].mp4', 
                        desc: "Techniques rapides et efficaces pour créer un flyer promotionnel captivant et professionnel.", 
                        presenter: 'Tuto Boy',
                        duration: '35 min',
                        level: 'Débutant',
                        category: 'Design Graphique'
                    }
                ],
                mardi: [
                    { 
                        time: '10:00', 
                        title: 'Dégradé sur texte Avancé', 
                        src: '/public/assets/videos/[Tuto Boy] Comment faire un dégradé sur un texte dans Photoshop..mp4', 
                        desc: "Effets de texte modernes, color grading et techniques de dégradé pour des résultats époustouflants.", 
                        presenter: 'Tuto Boy',
                        duration: '28 min',
                        level: 'Avancé',
                        category: 'Effets Visuels'
                    },
                    { 
                        time: '11:00', 
                        title: 'Effet de lumière Réaliste', 
                        src: '/public/assets/videos/[Tuto boy] Comment faire un effet de lumière sur une image sur Photoshop.mp4', 
                        desc: "Astuces professionnelles pour simuler des éclairages réalistes, ombres et reflets lumineux.", 
                        presenter: 'Tuto Boy',
                        duration: '32 min',
                        level: 'Intermédiaire',
                        category: 'Retouche Photo'
                    }
                ],
                mercredi: [ 
                    { 
                        time: '09:30', 
                        title: 'Concevoir une affiche - Rediffusion', 
                        src: '/public/assets/videos/Tuto Boy  Comment concevoir une affiche sur Photoshop..mp4', 
                        desc: "Rediffusion : composition, hiérarchie visuelle et principes de design graphique modernes.", 
                        presenter: 'Tuto Boy',
                        duration: '45 min',
                        level: 'Intermédiaire',
                        category: 'Design Graphique'
                    } 
                ],
                jeudi: [ 
                    { 
                        time: '14:00', 
                        title: 'Montage Flyer - Spécial Événement', 
                        src: '/public/assets/videos/TUTO BOY COMMENT FAIRE UN MONTAGE PHOTO SUR PHOTOSHOP  [flyer].mp4', 
                        desc: "Tutoriel pas-à-pas pour créer des flyers promotionnels pour vos événements et activations.", 
                        presenter: 'Tuto Boy',
                        duration: '40 min',
                        level: 'Débutant',
                        category: 'Design Graphique'
                    } 
                ],
                vendredi: [ 
                    { 
                        time: '15:30', 
                        title: 'Dégradé - Techniques Avancées', 
                        src: '/public/assets/videos/[Tuto Boy] Comment faire un dégradé sur un texte dans Photoshop..mp4', 
                        desc: "Techniques avancées de dégradé, raccourcis clavier, et astuces pour gagner du temps en production.", 
                        presenter: 'Tuto Boy',
                        duration: '30 min',
                        level: 'Avancé',
                        category: 'Effets Visuels'
                    } 
                ],
                samedi: [ 
                    { 
                        time: '11:00', 
                        title: 'Effets de Lumière - Créatifs', 
                        src: '/public/assets/videos/[Tuto boy] Comment faire un effet de lumière sur une image sur Photoshop.mp4', 
                        desc: "Effets créatifs avancés pour vos images, ajout de lumière naturelle et synthétique avec réalisme.", 
                        presenter: 'Tuto Boy',
                        duration: '38 min',
                        level: 'Intermédiaire',
                        category: 'Retouche Photo'
                    } 
                ],
                dimanche: [ 
                    { 
                        time: '19:00', 
                        title: 'Best Of - Compilation Spéciale', 
                        src: '/public/assets/videos/Tuto Boy  Comment concevoir une affiche sur Photoshop..mp4', 
                        desc: "Compilation des meilleurs extraits et tutoriels de la semaine avec conseils bonus et Q&A.", 
                        presenter: 'Tuto Boy',
                        duration: '60 min',
                        level: 'Tous niveaux',
                        category: 'Spécial'
                    } 
                ]
            };

            const daysOrder = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
            const todayIndex = new Date().getDay();
            const todayName = daysOrder[todayIndex];

            const tabs = document.querySelectorAll('.day-btn');
            const listEl = document.getElementById('emissionList');
            const titleEl = document.getElementById('emission-title');

            function render(day){
                listEl.innerHTML = '';
                const arr = emissions[day] || [];
                titleEl.textContent = 'Émissions — ' + day.charAt(0).toUpperCase() + day.slice(1);
                arr.forEach(item=>{
                    const li = document.createElement('li');
                    li.className = 'video-item';
                    li.innerHTML = `
                        <div class="emission-header">
                            <div class="emission-time">
                                <i class="fas fa-clock"></i> ${item.time}
                            </div>
                            <h4>${item.title}</h4>
                            <div class="emission-meta">
                                <span class="meta-badge duration"><i class="fas fa-hourglass-half"></i> ${item.duration}</span>
                                <span class="meta-badge level"><i class="fas fa-signal"></i> ${item.level}</span>
                                <span class="meta-badge category"><i class="fas fa-tag"></i> ${item.category}</span>
                            </div>
                        </div>
                                                <video controls width="100%" style="max-width: 640px; border-radius: 8px;">
                                                    <source src="${encodeURI(item.src)}" type="video/mp4">
                                                    Votre navigateur ne supporte pas la vidéo.
                                                </video>
                        <div class="emission-details">
                            <p class="desc">${item.desc}</p>
                            <div class="presenter-info">
                                <i class="fas fa-user-circle"></i>
                                <span><strong>Présentateur:</strong> ${item.presenter}</span>
                            </div>
                        </div>
                    `;
                    listEl.appendChild(li);
                });
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



<!-- Login Modal -->
<!-- Login modal provided by layout -->

</body>
</html>

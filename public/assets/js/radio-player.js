document.addEventListener('DOMContentLoaded', function(){
    const mediaEl = document.getElementById('radio');
    let playBtn = document.getElementById('playBtn');
    let altBtn = document.getElementById('playBtnAlt');
    const openVlcBtn = document.getElementById('openVlcBtn');
    const statusEl = document.getElementById('playerStatus');
    

    function setStatus(msg){ if(statusEl) statusEl.textContent = msg; else console.log(msg); }

    // If the page uses the new simplified player (`#player`), skip legacy initialization
    if (!mediaEl && document.getElementById('player')) {
        console.info('radio-player.js: detected #player; skipping legacy player init');
        return;
    }
    function isPlaying(){
        if (mediaEl && (mediaEl.currentSrc || mediaEl.src)) return !mediaEl.paused;
        return false;
    }
    function updateButtons(){
        var playing = isPlaying();
        if (playBtn) playBtn.textContent = playing ? '⏸ Pause' : '▶ Play';
        if (altBtn) altBtn.textContent = playing ? '⏸ Pause' : '▶ Écouter';
    }

    // Unified toggle that always targets the main media element when available,
    // otherwise falls back to the local preview. Attach via `onclick` to avoid
    // duplicate event listeners capturing or being overridden elsewhere.
    function unifiedToggle() {
        try {
            if (mediaEl) {
                // If no src is set yet but a stream is configured, attach it first
                if (!(mediaEl.currentSrc || mediaEl.src) && typeof stream === 'string' && stream) {
                    try { mediaEl.src = stream; if (typeof mediaEl.load === 'function') mediaEl.load(); } catch(e){ console.warn('Could not set media src in unifiedToggle', e); }
                }

                if (mediaEl.currentSrc || mediaEl.src) {
                    if (mediaEl.paused) {
                        mediaEl.play().catch(function(err){
                            console.warn('unifiedToggle play failed', err);
                                if (err && err.name === 'NotSupportedError') {
                                    setStatus('Format non supporté — tentative via proxy');
                                    try {
                                        if (stream && !(mediaEl.dataset && mediaEl.dataset._proxied === '1')) {
                                            tryProxyFallback(stream, mediaEl);
                                        }
                                    } catch(e) { console.error('Proxy fallback error', e); }
                            } else if (err && err.name === 'NotAllowedError') {
                                setStatus('Lecture bloquée — interaction requise');
                            } else {
                                setStatus('Lecture bloquée — tentative via proxy');
                            }
                        }).finally(function(){ updateButtons(); });
                    } else {
                        mediaEl.pause();
                    }
                    updateButtons();
                    return;
                }
            }
            setStatus('Aucun lecteur disponible');
            setStatus('Aucun lecteur disponible');
        } catch (e) { console.error('unifiedToggle error', e); }
        updateButtons();
    }

    // --- Live stream initialization (optional) ---
    var stream = '';
    if (mediaEl) {
        // Prefer an explicit global `window.APP_STREAM` (set from server env),
        // otherwise fall back to the element's data-stream attribute.
        stream = (typeof window.APP_STREAM === 'string' && window.APP_STREAM) ? window.APP_STREAM : (mediaEl.getAttribute('data-stream') || '');
        mediaEl.innerHTML = '';

        // Determine whether element is video or audio
        const isVideo = mediaEl.tagName && mediaEl.tagName.toLowerCase() === 'video';
        const mimeType = isVideo ? 'video/mp4' : 'audio/mpeg';

        if (!stream){ setStatus('Aucun flux configuré (data-stream manquant)'); }
        else {
            // Attach the upstream stream URL directly to the media element.
            try {
                try { mediaEl.removeAttribute('src'); } catch(e){}
                mediaEl.innerHTML = '';
                mediaEl.crossOrigin = 'anonymous';
                mediaEl.dataset._proxied = '0';
                mediaEl.src = stream;
                try { if (typeof mediaEl.load === 'function') mediaEl.load(); } catch(e){}
                setStatus('Flux configuré (direct)');

                function togglePlay() {
                    if (!mediaEl || !mediaEl.src) { setStatus('Flux non configuré'); return; }
                    if (mediaEl.paused) {
                        var playPromise = mediaEl.play();
                        playPromise && playPromise.catch(function(err){
                            console.warn('Direct play failed', err);
                            if (err && err.name === 'NotAllowedError') setStatus('Lecture bloquée — interaction requise');
                            else if (err && err.name === 'NotSupportedError') setStatus('Format non supporté');
                            else setStatus('Impossible de lire le flux');
                            // Attempt proxy fallback if direct play fails
                            tryProxyFallback(stream, mediaEl);
                        }).finally(updateButtons);
                    } else {
                        mediaEl.pause();
                    }
                    updateButtons();
                }

                window.playLive = togglePlay;

                if (openVlcBtn) {
                    try { openVlcBtn.addEventListener('click', function(){ window.open(stream, '_blank'); setStatus('Ouverture du flux'); }); } catch(e){}
                }

                mediaEl.addEventListener('play', function(){ updateButtons(); setStatus('Lecture en cours'); });
                mediaEl.addEventListener('pause', function(){ updateButtons(); setStatus('En pause'); });
                mediaEl.addEventListener('error', function(){ setStatus('Erreur média — voir console'); });

                updateButtons();
            } catch(e) { console.error('stream attach error', e); setStatus('Erreur configuration flux'); }
        }
    }

    // No local preview fallback; buttons act on the main media element only.

    // Ensure buttons use the unified toggle handler (clear previous listeners by cloning the nodes)
    function replaceAndBind(btn, handler){
        if (!btn) return btn;
        try {
            var clone = btn.cloneNode(true);
            btn.parentNode.replaceChild(clone, btn);
            clone.onclick = handler;
            return clone;
        } catch(e){
            try { btn.onclick = handler; } catch(e2){}
            return btn;
        }
    }

    if (playBtn) playBtn = replaceAndBind(playBtn, function(e){ e.preventDefault(); unifiedToggle(); updateButtons(); });
    if (altBtn) altBtn = replaceAndBind(altBtn, function(e){ e.preventDefault(); unifiedToggle(); updateButtons(); });

    // --- History replay support ---
    // Create a shared history player (audio element) for replaying past items
    var historyPlayer = document.getElementById('historyPlayer');
    var historyContainer = document.getElementById('history-player-container');
    var historyStatusEl = document.getElementById('historyPlayerStatus');
    if (!historyPlayer) {
        historyPlayer = document.createElement('audio');
        historyPlayer.id = 'historyPlayer';
        historyPlayer.controls = true;
        historyPlayer.style.display = 'none';
        if (historyContainer) historyContainer.appendChild(historyPlayer); else document.body.appendChild(historyPlayer);
    } else {
        historyPlayer.controls = true;
    }

    function attachHistoryListeners(el) {
        try {
            el.removeEventListener && el.removeEventListener('play', null);
        } catch(e){}
        el.addEventListener('play', function(){ setHistoryStatus('Réécoute en cours'); });
        el.addEventListener('pause', function(){ setHistoryStatus('Réécoute en pause'); });
        el.addEventListener('ended', function(){ setHistoryStatus('Réécoute terminée'); });
        el.addEventListener('error', function(){ setHistoryStatus('Erreur média — voir console'); console.error(el.error); });
    }
    attachHistoryListeners(historyPlayer);

    function setHistoryStatus(msg){ if (historyStatusEl) historyStatusEl.textContent = msg; else setStatus(msg); }

    // Helper: try server proxy fallback when direct play fails
    function tryProxyFallback(src, player) {
        try {
            if (!src) return;
            var proxyUrl = '/radio.php?src=' + encodeURIComponent(src);
            player.pause();
            try { player.removeAttribute('src'); } catch(e){}
            player.innerHTML = '';
            player.crossOrigin = 'anonymous';
            player.src = proxyUrl;
            player.dataset._proxied = '1';
            try { player.load(); } catch(e){}
            player.play().then(function(){ setHistoryStatus('Lecture via proxy'); }).catch(function(err){
                console.warn('Proxy playback failed', err);
                setHistoryStatus('Erreur lecture');
            });
        } catch(e){ console.error('tryProxyFallback error', e); }
    }

    function attachProxyAndPlay(player, proxy) {
        try {
            try { player.removeAttribute('src'); } catch(e){}
            player.innerHTML = '';
            player.crossOrigin = 'anonymous';
            player.src = proxy;
            player.dataset._proxied = '1';
            try { player.load(); } catch(e){}

            // Wait for canplay or timeout then try play
            var played = false;
            function doPlay(){ if (played) return; played = true; player.play().then(function(){ setHistoryStatus('Lecture via proxy'); }).catch(function(err){
                    console.warn('Proxy playback failed', err);
                    // If browser can't play the proxied stream directly, try fetching as blob (works for regular files)
                    if (err && err.name === 'NotSupportedError') {
                        setHistoryStatus('Format non supporté — tentative via blob');
                        fetchAndPlayBlob(player, proxy).catch(function(err2){
                            console.warn('Blob fallback failed', err2);
                            setHistoryStatus('Impossible de lire (proxy)');
                            try { window.open(proxy, '_blank'); } catch(e){}
                        });
                    } else {
                        setHistoryStatus('Lecture impossible (proxy)');
                    }
                }); }
            player.addEventListener('canplay', doPlay, { once: true });
            player.addEventListener('canplaythrough', doPlay, { once: true });
            setTimeout(doPlay, 2000);
        } catch(e) { console.error('attachProxyAndPlay error', e); }
    }

    // Attach HLS (m3u8) stream via hls.js when necessary.
    function attachHlsAndPlay(player, proxy) {
        try {
            // Safari supports HLS natively in <audio>/<video>
            var isNativeHls = (player.canPlayType && player.canPlayType('application/vnd.apple.mpegurl'));
            if (isNativeHls) {
                // Use native playback
                try { player.removeAttribute('src'); } catch(e){}
                player.innerHTML = '';
                player.crossOrigin = 'anonymous';
                player.src = proxy;
                player.dataset._proxied = '1';
                try { player.load(); } catch(e){}
                player.play().then(function(){ setHistoryStatus('Lecture HLS (native)'); }).catch(function(err){ console.warn('Native HLS play failed', err); setHistoryStatus('Lecture HLS impossible'); });
                return;
            }

            // Load hls.js dynamically if not present
            function loadScript(url) {
                return new Promise(function(resolve, reject){
                    if (window.Hls) return resolve(window.Hls);
                    var s = document.createElement('script'); s.src = url; s.async = true;
                    s.onload = function(){ resolve(window.Hls); };
                    s.onerror = function(e){ reject(new Error('Failed loading script ' + url)); };
                    document.head.appendChild(s);
                });
            }

            var CDN = 'https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js';
            loadScript(CDN).then(function(HlsLib){
                var HlsClass = window.Hls || HlsLib;
                if (!HlsClass) throw new Error('hls.js not available');
                // detach any previous hls instance on this player
                try { if (player._hls && player._hls.destroy) player._hls.destroy(); } catch(e){}
                var hls = new HlsClass();
                player._hls = hls;
                hls.attachMedia(player);
                hls.on(HlsClass.Events.MEDIA_ATTACHED, function(){
                    hls.loadSource(proxy);
                    setHistoryStatus('Lecture HLS via hls.js');
                });
                hls.on(HlsClass.Events.ERROR, function(event, data){
                    console.warn('hls.js error', event, data);
                    if (data && data.fatal) {
                        try { hls.destroy(); } catch(e){}
                        setHistoryStatus('Erreur HLS');
                    }
                });
            }).catch(function(err){
                console.warn('Failed to load hls.js', err);
                // last resort: open in new tab
                try { window.open(proxy, '_blank'); } catch(e){}
                setHistoryStatus('Impossible de lire HLS (ouvrir dans un nouvel onglet)');
            });
        } catch(e) { console.error('attachHlsAndPlay error', e); }
    }

    // Fetch resource as ArrayBuffer, create blob URL and play it (useful for static audio files)
    function fetchAndPlayBlob(player, url) {
        return new Promise(function(resolve, reject){
            fetch(url, { method: 'GET', cache: 'no-cache' }).then(function(resp){
                if (!resp.ok) return reject(new Error('HTTP ' + resp.status));
                return resp.arrayBuffer();
            }).then(function(buffer){
                try {
                    var contentType = 'audio/mpeg';
                    var blob = new Blob([buffer], { type: contentType });
                    var blobUrl = URL.createObjectURL(blob);
                    try { player.removeAttribute('src'); } catch(e){}
                    player.innerHTML = '';
                    player.src = blobUrl;
                    player.dataset._proxied = '1';
                    try { player.load(); } catch(e){}
                    player.play().then(function(){ setHistoryStatus('Lecture via blob'); resolve(); }).catch(function(playErr){ reject(playErr); });
                } catch(e) { reject(e); }
            }).catch(function(err){ reject(err); });
        });
    }

    // Helper to play a given source (used by replay and play-item buttons)
    function playSource(src) {
        if (!src) { setHistoryStatus('Source introuvable'); return; }
        try {
            var abs = new URL(src, location.href).href;
            // If the same source is playing, toggle pause/play
            if (historyPlayer.src && historyPlayer.src === abs) {
                if (historyPlayer.paused) historyPlayer.play(); else historyPlayer.pause();
                return;
            }

            // If the requested source is actually the live stream used by the main player,
            // avoid playing it in the history player (it's not an archived clip).
            var mainSrc = (mediaEl && (mediaEl.currentSrc || mediaEl.src)) || '';
            if (abs === mainSrc || src === stream) {
                // toggle the main player so user hears audio (live) instead of a silent replay
                try {
                    if (mediaEl.paused) {
                        mediaEl.play().then(function(){ setHistoryStatus('Lecture du flux live (aucun enregistrement)'); }).catch(function(err){ console.warn('live play failed', err); setHistoryStatus('Impossible de lire le flux live'); });
                    } else {
                        mediaEl.pause();
                        setHistoryStatus('Flux live en pause');
                    }
                } catch(e){ console.error(e); setHistoryStatus('Erreur lecture live'); }
                return;
            }

            // Use video element for .mp4 files, otherwise audio
            var useVideo = !!src.match(/\.mp4($|\?)/i);
            var tag = historyPlayer.tagName ? historyPlayer.tagName.toLowerCase() : 'audio';

            if (useVideo && tag !== 'video') {
                var newEl = document.createElement('video');
                newEl.id = 'historyPlayer';
                newEl.controls = true;
                newEl.style.display = 'block';
                if (historyContainer) historyContainer.replaceChild(newEl, historyPlayer); else historyPlayer.parentNode.replaceChild(newEl, historyPlayer);
                historyPlayer = newEl;
                attachHistoryListeners(historyPlayer);
            } else if (!useVideo && tag !== 'audio') {
                var newEl2 = document.createElement('audio');
                newEl2.id = 'historyPlayer';
                newEl2.controls = true;
                newEl2.style.display = 'block';
                if (historyContainer) historyContainer.replaceChild(newEl2, historyPlayer); else historyPlayer.parentNode.replaceChild(newEl2, historyPlayer);
                historyPlayer = newEl2;
                attachHistoryListeners(historyPlayer);
            }

            // attach src directly and set crossOrigin
            historyPlayer.pause();
            try { historyPlayer.removeAttribute('src'); } catch(e){}
            historyPlayer.innerHTML = '';
            historyPlayer.crossOrigin = 'anonymous';
            historyPlayer.dataset._proxied = '0';
            // Instead of direct src, always use proxy for playlists
            tryProxyFallback(src, historyPlayer);
            if (historyContainer) historyContainer.style.display = 'block'; else historyPlayer.style.display = 'block';
            if (historyContainer) historyContainer.scrollIntoView({behavior:'smooth', block:'center'});
        } catch (err) { console.warn(err); setHistoryStatus('Erreur lecture'); }
    }

    // Handle clicks on replay or play-item buttons (delegated)
    document.addEventListener('click', function(e){
        var el = e.target.closest && (e.target.closest('.replay-btn') || e.target.closest('.play-item-btn') || e.target.closest('.play-playlist-btn'));
        if (!el) return;
        e.preventDefault();
        if (el.classList.contains('play-playlist-btn')) {
            // playlist play: retrieve songs list
            var songsJson = el.dataset.songs || '[]';
            var songs = [];
            try { songs = JSON.parse(songsJson); } catch(e){ songs = []; }
            if (!Array.isArray(songs) || songs.length === 0) { setHistoryStatus('Aucune piste disponible pour cette playlist'); return; }
            // toggle playlist playback
            if (!window._playlistPlayer || window._playlistPlayer.playing === false) {
                startPlaylist(songs);
                el.textContent = '■ Arrêter la playlist';
            } else {
                stopPlaylist();
                el.textContent = '▶ Écouter la playlist';
            }
            return;
        }
        var src = el.getAttribute('data-src') || '';
        playSource(src);
    });

    // Playlist playback using historyPlayer (URL) or SpeechSynthesis fallback
    function startPlaylist(songs) {
        window._playlistPlayer = { songs: songs.slice(), index: 0, playing: true, utter: null };
        setHistoryStatus('Lecture playlist');
        playNextInPlaylist();
    }
    function stopPlaylist() {
        if (!window._playlistPlayer) return;
        window._playlistPlayer.playing = false;
        // stop speech
        if (window._playlistPlayer.utter) {
            try { window.speechSynthesis.cancel(); } catch(e){}
            window._playlistPlayer.utter = null;
        }
        // stop media
        try { historyPlayer.pause(); } catch(e){}
        setHistoryStatus('Playlist arrêtée');
        window._playlistPlayer = { playing: false };
    }
    function playNextInPlaylist() {
        var pl = window._playlistPlayer;
        if (!pl || !pl.playing) return;
        if (pl.index >= pl.songs.length) { setHistoryStatus('Playlist terminée'); pl.playing = false; return; }
        var item = pl.songs[pl.index];
        // if item looks like a URL or file path, try to play as media
        var isUrl = typeof item === 'string' && (/^https?:\/\//i.test(item) || /\.(mp3|m4a|ogg|wav|mp4)(\?|$)/i.test(item));
        if (isUrl) {
            // play via existing history player and wait for ended
            var handler = function() {
                historyPlayer.removeEventListener('ended', handler);
                pl.index += 1;
                // small delay between tracks
                setTimeout(playNextInPlaylist, 300);
            };
            try {
                playSource(item);
                historyPlayer.addEventListener('ended', handler);
            } catch(e) { console.error(e); pl.index += 1; setTimeout(playNextInPlaylist, 300); }
        } else {
            // speak the title as a fallback so user hears something
            try {
                var utter = new SpeechSynthesisUtterance(item.toString());
                window._playlistPlayer.utter = utter;
                utter.onend = function(){ window._playlistPlayer.index += 1; setTimeout(playNextInPlaylist, 200); };
                utter.onerror = function(){ window._playlistPlayer.index += 1; setTimeout(playNextInPlaylist, 200); };
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utter);
                setHistoryStatus('Lecture (TTS): ' + item);
            } catch(e) {
                console.warn('TTS failed', e); window._playlistPlayer.index += 1; setTimeout(playNextInPlaylist, 200);
            }
        }
    }

    historyPlayer.addEventListener('play', function(){ setHistoryStatus('Réécoute en cours'); });
    historyPlayer.addEventListener('pause', function(){ setHistoryStatus('Réécoute en pause'); });
    historyPlayer.addEventListener('ended', function(){ setHistoryStatus('Réécoute terminée'); });
});

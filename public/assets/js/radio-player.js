document.addEventListener('DOMContentLoaded', function(){
    const mediaEl = document.getElementById('radio');
    let playBtn = document.getElementById('playBtn');
    let altBtn = document.getElementById('playBtnAlt');
    const openVlcBtn = document.getElementById('openVlcBtn');
    const statusEl = document.getElementById('playerStatus');
    const localSound = document.getElementById('localSound');

    function setStatus(msg){ if(statusEl) statusEl.textContent = msg; else console.log(msg); }
    function isPlaying(){
        if (mediaEl && mediaEl.src) return !mediaEl.paused;
        if (localSound) return !localSound.paused;
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
            // fallback to local preview
            if (localSound) {
                if (localSound.paused) localSound.play().catch(function(err){ console.warn('localSound play failed', err); setStatus('Lecture bloquée — interaction requise'); });
                else localSound.pause();
                return;
            }
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
            // Guard against mixed-content: don't load http:// sources on an https page
            if (window.location.protocol === 'https:' && /^http:\/\//i.test(stream)) {
                console.warn('Blocked insecure stream on HTTPS page:', stream);
                setStatus('Flux HTTP bloqué sur page HTTPS — ouvrez le flux dans un nouvel onglet');
                if (openVlcBtn) {
                    openVlcBtn.style.display = '';
                    try {
                        openVlcBtn.addEventListener('click', function(){ window.open(stream, '_blank'); setStatus('Ouverture du flux dans un nouvel onglet'); });
                    } catch(e) { /* ignore */ }
                }
            } else {
                // attach source and set src directly
                const src = document.createElement('source');
                src.src = stream;
                src.type = mimeType;
                mediaEl.appendChild(src);
                try { mediaEl.pause(); mediaEl.src = stream; if (typeof mediaEl.load === 'function') mediaEl.load(); } catch(e){ console.warn('Could not set media src', e); }
                setStatus('Flux configuré (' + mimeType + ')');

                let playPromise = null;
                function togglePlay(){
                    if (!mediaEl || !mediaEl.src) { setStatus('Flux non configuré'); return; }
                    if (mediaEl.paused){
                        if (playBtn) playBtn.disabled = true; if (altBtn) altBtn.disabled = true;
                        playPromise = mediaEl.play();
                        playPromise && playPromise.catch(err=>{
                            if (err && err.name === 'AbortError') return;
                            if (err && err.name === 'NotAllowedError') setStatus('Lecture bloquée — interaction requise');
                            else if (err && err.name === 'NotSupportedError') setStatus('Format non supporté');
                            else { console.error('Play failed', err); setStatus('Impossible de lire — tentative via proxy'); }

                            // Try a single proxy fallback if play failed (avoid loops)
                            try {
                                    if (mediaEl && stream && !(mediaEl.dataset && mediaEl.dataset._proxied === '1')) {
                                        tryProxyFallback(stream, mediaEl);
                                    }
                            } catch(e) { console.error('Proxy fallback error', e); }
                        }).finally(()=>{ if (playBtn) playBtn.disabled = false; if (altBtn) altBtn.disabled = false; updateButtons(); });
                    } else {
                        mediaEl.pause();
                    }
                }

                // expose togglePlay so unifiedToggle uses the same logic and avoids duplicate handlers
                window.playLive = togglePlay;

                if (openVlcBtn) openVlcBtn.addEventListener('click', function(){
                    try { window.open(stream, '_blank'); setStatus('Ouverture du flux dans un nouvel onglet'); }
                    catch(e){ console.error(e); setStatus('Impossible d\'ouvrir le flux'); }
                });

                mediaEl.addEventListener('play', function(){ updateButtons(); setStatus('Lecture en cours'); });
                // Log the playback start to the server for history (best-effort)
                mediaEl.addEventListener('play', function(){
                    try {
                        var payload = {
                            title: document.querySelector('.station-title') ? document.querySelector('.station-title').textContent.trim() : '',
                            artist: '',
                            source: stream,
                            played_at: new Date().toISOString()
                        };
                        fetch((window.APP_BASE || '') + '/index.php?route=api/log_play', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        }).then(function(res){ return res.json(); }).then(function(res){ if (!res.ok) console.warn('log_play failed', res); }).catch(function(err){ console.warn('log_play error', err); });
                    } catch(e){ console.warn('log_play exception', e); }
                });
                mediaEl.addEventListener('pause', function(){ updateButtons(); setStatus('En pause'); });
                mediaEl.addEventListener('error', function(){ setStatus('Erreur média — voir console'); });

                updateButtons();
            }
        }
    }

    // If a local preview sound exists, keep its state in sync with buttons
    if (localSound) {
        localSound.addEventListener('play', function(){ updateButtons(); setStatus('Pré-écoute en cours'); });
        localSound.addEventListener('pause', function(){ updateButtons(); setStatus('Pré-écoute en pause'); });
        localSound.addEventListener('ended', function(){ updateButtons(); setStatus('Pré-écoute terminée'); });
        localSound.addEventListener('error', function(){ updateButtons(); setStatus('Erreur pré-écoute'); console.error(localSound.error); });
    }

    // Fallback: if main media isn't configured, make play buttons toggle the localSound
    function toggleLocalPreview(){
        if (!localSound) { setStatus('Aucun son local disponible'); return; }
        if (localSound.paused) {
            localSound.currentTime = 0;
            localSound.play().catch(function(err){ console.warn('localSound play failed', err); setStatus('Lecture bloquée — interaction requise'); });
        } else {
            localSound.pause();
        }
    }
    // Attach fallback listeners only if mediaEl has no src or is not configured
    if ((!mediaEl || !mediaEl.src) && (playBtn || altBtn)) {
        if (playBtn) playBtn.addEventListener('click', function(){ toggleLocalPreview(); updateButtons(); });
        if (altBtn) altBtn.addEventListener('click', function(){ toggleLocalPreview(); updateButtons(); });
    }

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
            // Don't loop repeatedly to proxy
            if (player.dataset && player.dataset._proxied === '1') { console.warn('Already proxied, aborting fallback'); return; }
            // If the app is hosted in a subfolder, proxy expects a path relative to the project
            var proxySrc = src;
            try {
                var base = (window.APP_BASE || '') + '';
                if (base && proxySrc.indexOf(base) === 0) {
                    proxySrc = proxySrc.slice(base.length);
                }
            } catch(e) { /* ignore */ }
            // Use radio.php as a same-origin proxy endpoint
            var proxy = (window.APP_BASE || '') + '/radio.php?src=' + encodeURIComponent(proxySrc);
            console.info('Attempting proxy fallback for', src, '->', proxy);

            // Probe proxy with HEAD to verify Content-Type before attaching to <audio>
            fetch(proxy, { method: 'HEAD', cache: 'no-cache' }).then(function(headResp){
                var ct = headResp.headers.get('content-type') || '';
                if (headResp.ok && /audio\//i.test(ct) || /mpeg/i.test(ct)) {
                    // Good: attach and play via proxy
                    attachProxyAndPlay(player, proxy);
                } else {
                    console.warn('Proxy HEAD returned unexpected Content-Type:', ct, headResp.status);
                    setHistoryStatus('Proxy non audio (' + (ct||'unknown') + '). Ouverture dans un nouvel onglet.');
                    try { window.open(proxy, '_blank'); } catch(e){}
                }
            }).catch(function(err){
                console.warn('Proxy HEAD failed', err);
                // fallback: attempt to attach anyway
                attachProxyAndPlay(player, proxy);
            });
            return;
            try {
                try { player.removeAttribute('src'); } catch(e){}
                player.innerHTML = '';
                player.crossOrigin = 'anonymous';
                player.src = proxy;
                player.dataset._proxied = '1';
                try { player.load(); } catch(e){}

                // Wait for the player to be able to play before calling play(), to avoid AbortError
                var played = false;
                function doPlay(){ if (played) return; played = true; player.play().then(function(){ setHistoryStatus('Lecture via proxy'); }).catch(function(err){ console.warn('Proxy playback failed', err); setHistoryStatus('Lecture impossible (proxy)'); }); }
                player.addEventListener('canplay', doPlay, { once: true });
                player.addEventListener('canplaythrough', doPlay, { once: true });
                // Fallback timeout: try to play after 2s even if canplay didn't fire
                setTimeout(doPlay, 2000);
            } catch(e) { console.error('tryProxyFallback error', e); }
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
            historyPlayer.src = abs;
            try { historyPlayer.load(); } catch(ex){ /* ignore */ }
            if (historyContainer) historyContainer.style.display = 'block'; else historyPlayer.style.display = 'block';
            historyPlayer.play().then(function(){ setHistoryStatus('Lecture: ' + abs); }).catch(function(err){
                console.warn('history play failed, attempting proxy', err);
                setHistoryStatus('Lecture bloquée — tentative via proxy');
                tryProxyFallback(src, historyPlayer);
            });
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

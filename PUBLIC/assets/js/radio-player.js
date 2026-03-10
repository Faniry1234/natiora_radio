document.addEventListener('DOMContentLoaded', function(){
    const mediaEl = document.getElementById('radio');
    const playBtn = document.getElementById('playBtn');
    const altBtn = document.getElementById('playBtnAlt');
    const openVlcBtn = document.getElementById('openVlcBtn');
    const statusEl = document.getElementById('playerStatus');

    function setStatus(msg){ if(statusEl) statusEl.textContent = msg; else console.log(msg); }
    function isPlaying(){ return mediaEl && !mediaEl.paused; }
    function updateButtons(){ if (playBtn) playBtn.textContent = isPlaying() ? '⏸ Pause' : '▶ Play'; if (altBtn) altBtn.textContent = isPlaying() ? '⏸ Pause' : '▶ Écouter'; }

    if (!mediaEl) return;

    const stream = mediaEl.getAttribute('data-stream') || '';
    mediaEl.innerHTML = '';

    // Determine whether element is video or audio
    const isVideo = mediaEl.tagName && mediaEl.tagName.toLowerCase() === 'video';
    const mimeType = isVideo ? 'video/mp4' : 'audio/mpeg';

    if (!stream){ setStatus('Aucun flux configuré (data-stream manquant)'); return; }

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
                else { console.error('Play failed', err); setStatus('Impossible de lire — voir console'); }
            }).finally(()=>{ if (playBtn) playBtn.disabled = false; if (altBtn) altBtn.disabled = false; updateButtons(); });
        } else {
            mediaEl.pause();
        }
    }

    if (playBtn) playBtn.addEventListener('click', function(){ togglePlay(); updateButtons(); });
    if (altBtn) altBtn.addEventListener('click', function(){ togglePlay(); updateButtons(); });

    if (openVlcBtn) openVlcBtn.addEventListener('click', function(){
        // open stream in new tab (user can open in VLC from URL)
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
            var proxy = '/index.php?route=api/proxy_replay&src=' + encodeURIComponent(src);
            console.info('Attempting proxy fallback for', src, '->', proxy);
            player.pause();
            try { player.removeAttribute('src'); } catch(e){}
            player.innerHTML = '';
            player.crossOrigin = 'anonymous';
            player.src = proxy;
            player.dataset._proxied = '1';
            try { player.load(); } catch(e){}
            player.play().then(function(){ setHistoryStatus('Lecture via proxy'); }).catch(function(err){ console.warn('Proxy playback failed', err); setHistoryStatus('Lecture impossible (proxy)'); });
        } catch(e){ console.error('tryProxyFallback error', e); }
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

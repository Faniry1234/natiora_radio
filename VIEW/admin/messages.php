<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Server-side fetch: attempt to load messages directly so the admin page shows messages
$serverRenderedMessages = [];
$serverRenderError = null;
try {
    $db = Database::getInstance();
    // ensure tables exist
    $db->init();
    $pdo = $db->getConnection();
    $uid = $_SESSION['user_id'] ?? 1; // default to admin id=1 for local dev
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE recipient_id = ? OR sender_id = ? ORDER BY created_at DESC LIMIT 200');
    $stmt->execute([$uid, $uid]);
    $serverRenderedMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $serverRenderError = $e->getMessage();
}
?>
<div class="admin-messages-wrapper dark">
    <div class="admin-messages-container">
    <div class="page-header">
        <a href="<?php echo $base; ?>/index.php?route=admin" class="btn-ghost" style="margin-right:8px;padding:6px 10px;border-radius:8px;">← Retour</a>
        <img src="<?php echo $base ?? ''; ?>/PUBLIC/assets/images/LOGO%20RADIO.jpg" alt="logo" style="width:64px;height:64px;border-radius:12px;object-fit:cover;border:3px solid rgba(255,255,255,0.12);">
        <div style="flex:1;">
            <div style="font-size:1.35rem;font-weight:700;line-height:1">Boîte de réception <span style="font-size:1.1rem;margin-left:6px;">✉️💬</span></div>
            <div style="opacity:0.95;margin-top:4px;font-size:0.95rem">Messages reçus et envoyés — gérez rapidement vos conversations</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:0.85rem;opacity:0.9">Non lus</div>
            <div id="inboxUnreadCount" style="background:var(--card);padding:6px 12px;border-radius:20px;font-weight:700;margin-top:6px;min-width:64px;display:inline-block;text-align:center">0</div>
        </div>
    </div>

    <div style="display:flex;gap:20px;align-items:flex-start;">
        <div style="flex:1;">
            <div id="admin-messages-list" class="messages-list" style="border-radius:10px;overflow:hidden;">
                <div style="padding:12px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
                    <strong>Messages</strong>
                    <div>
                        <button id="refresh-admin-messages" class="btn-ghost">Rafraîchir</button>
                    </div>
                </div>
                <div id="admin-messages-list-items" style="max-height:560px;overflow:auto;">
                    <?php if ($serverRenderError): ?>
                        <div style="padding:14px;color:#c00">Erreur base de données : <?php echo htmlspecialchars($serverRenderError); ?></div>
                    <?php else: ?>
                        <?php if (empty($serverRenderedMessages)): ?>
                            <div class="messages-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                <div style="font-size:1.05rem;font-weight:600;margin-bottom:6px">Aucun message</div>
                                <div style="max-width:420px;margin:0 auto;opacity:0.8">Vous n'avez pas encore de conversations. Cliquez sur "Rafraîchir" pour forcer la vérification ou attendez les nouveaux messages.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($serverRenderedMessages as $m): ?>
                                <?php
                                    $isUnread = ($m['is_read'] == 0);
                                    $name = $m['sender_name'] ?? ('User ' . ($m['sender_id'] ?? '?'));
                                    $avatarText = $m['sender_name'] ? implode('', array_slice(array_map(function($p){return $p[0];}, explode(' ', $m['sender_name'])),0,2)) : (isset($m['sender_email']) ? strtoupper($m['sender_email'][0]) : '?');
                                    $created = $m['created_at'] ?? '';
                                ?>
                                <div class="admin-message-item<?php echo $isUnread ? ' unread' : ''; ?>" data-id="<?php echo (int)$m['id']; ?>">
                                    <div style="display:flex;gap:12px;align-items:flex-start">
                                        <div class="avatar"><?php echo htmlspecialchars($avatarText); ?></div>
                                        <div class="meta">
                                            <div class="row"><div class="subject"><?php echo htmlspecialchars($m['subject'] ?: 'Sans sujet'); ?></div><div class="time"><?php echo htmlspecialchars($created); ?></div></div>
                                            <div class="snippet"><?php echo htmlspecialchars(substr($m['body'],0,240)); ?></div>
                                                        <div style="font-size:0.8em;color:#888;margin-top:8px">From: <?php echo htmlspecialchars($name); ?> (id:<?php echo (int)($m['sender_id'] ?? 0); ?>)</div>
                                                        <?php if (!empty($m['context_type'])): ?>
                                                            <div style="font-size:0.8em;color:#777;margin-top:6px">Contexte: 
                                                                <?php
                                                                $ctx = htmlspecialchars($m['context_type']); $cid = htmlspecialchars($m['context_id'] ?? '');
                                                                $link = '#';
                                                                if ($ctx === 'playlist') $link = $base . '/index.php?route=playlistes' . ($cid ? ('#' . $cid) : '');
                                                                elseif ($ctx === 'emission') $link = $base . '/index.php?route=emissions' . ($cid ? ('#' . $cid) : '');
                                                                elseif ($ctx === 'home') $link = $base . '/index.php?route=home';
                                                                ?>
                                                                <a href="<?php echo $link; ?>" style="color:var(--muted)"><?php echo $ctx . ($cid ? (' #' . $cid) : ''); ?></a>
                                                            </div>
                                                        <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="width:420px;">
            <div id="admin-message-detail" class="message-detail" style="border-radius:10px;padding:14px;min-height:200px;">
                <div id="detail-empty" style="color:#777;text-align:center;padding:40px;">Sélectionnez un message pour voir le détail</div>
                <div id="detail-content" style="display:none;">
                    <h3 id="detail-subject" style="margin:0 0 6px 0"></h3>
                    <div id="detail-meta" style="font-size:0.9em;color:#888;margin-bottom:10px"></div>
                    <div id="detail-body" style="white-space:pre-wrap;color:#333;line-height:1.45;margin-bottom:12px"></div>
                    <div style="border-top:1px dashed #eee;padding-top:10px;">
                        <form id="admin-reply-form">
                            <input type="hidden" id="reply-recipient" name="recipient_id" value="">
                            <div style="margin-bottom:8px;"><input id="reply-subject" name="subject" placeholder="Sujet (optionnel)" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;"></div>
                            <div style="margin-bottom:8px;"><textarea id="reply-body" name="body" rows="4" placeholder="Votre réponse" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;"></textarea></div>
                            <div style="display:flex;gap:8px;">
                                <button type="submit" class="btn-save-profile">Envoyer</button>
                                <button type="button" id="mark-read-btn" class="btn-ghost">Marquer comme lu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root{
    --bg: #f6f8fb;
    --card: #ffffff;
    --muted: #8b95a3;
    --accent: #6c63ff;
    --danger: #e74c3c;
    --accent-2: #00c2a8;
}
.admin-messages-wrapper { background: linear-gradient(180deg,#f3f7fb 0%, #ffffff 30%); padding:30px 0 60px; }
.admin-messages-container{ max-width:1150px; margin:0 auto; padding:18px; }
.messages-list { border-radius:12px; overflow:hidden }
.admin-message-item { display:flex; gap:12px; align-items:flex-start; padding:14px 16px; border-bottom:1px solid rgba(255,255,255,0.06); transition:background .18s, transform .12s, box-shadow .12s; border-radius:12px; margin:10px; background: linear-gradient(rgba(255,255,255,0.72), rgba(255,255,255,0.72)); box-shadow:0 10px 30px rgba(16,24,40,0.06); backdrop-filter: blur(4px) saturate(1.02); }
.admin-message-item{ border-left:6px solid var(--admin-border, transparent); padding-left:12px }
.admin-message-item:hover{ transform: translateY(-6px); box-shadow:0 18px 50px rgba(16,24,40,0.08); }
.admin-message-item.unread{ background: linear-gradient(90deg, rgba(108,99,255,0.08), rgba(0,194,168,0.04)); }
.admin-message-item .avatar{ width:48px; height:48px; border-radius:12px; flex:0 0 48px; background:linear-gradient(135deg,var(--accent),var(--accent-2)); display:flex;align-items:center;justify-content:center;color:#fff; font-weight:700; box-shadow:0 4px 12px rgba(12,14,20,0.08); }
.admin-message-item .meta{ flex:1; min-width:0 }
.admin-message-item .meta .row{ display:flex; justify-content:space-between; align-items:center }
.admin-message-item .meta .subject{ font-weight:600; color:#0f1724; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.admin-message-item .meta .time{ font-size:0.85rem; color:var(--muted) }
.admin-message-item .meta .snippet{ color:#334155; opacity:0.9; margin-top:6px; font-size:0.95rem; max-height:48px; overflow:hidden; text-overflow:ellipsis }

.message-detail { border-radius:12px }
.message-detail #detail-empty{ color:var(--muted) }
.message-detail h3{ margin:0 0 6px 0; font-size:1.15rem; color:#0b1220 }
.message-detail #detail-meta{ color:var(--muted); font-size:0.9rem }
.message-detail #detail-body{ margin-top:12px; color:#263238 }

/* Page header */
.page-header{ display:flex;align-items:center;gap:16px;margin-bottom:18px;padding:18px;border-radius:14px;background:linear-gradient(90deg,var(--accent),var(--accent-2));color:#fff;box-shadow:0 10px 30px rgba(108,99,255,0.08); }
.page-header .btn-ghost{ background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.12); color:#fff }
.page-header img{ width:64px;height:64px;border-radius:12px;object-fit:cover;border:3px solid rgba(255,255,255,0.12); }

/* Empty state */
.messages-empty{ padding:30px; text-align:center; color:#7b8794 }
.messages-empty svg{ width:92px; height:92px; opacity:0.9; margin-bottom:12px }

.admin-message-item .snippet{ color:#334155; opacity:0.95; margin-top:6px; font-size:0.95rem; max-height:48px; overflow:hidden; text-overflow:ellipsis }

.btn-ghost{ background:transparent;border:1px solid rgba(15,23,36,0.06);padding:8px 12px;border-radius:8px;cursor:pointer;color:#0b1220 }
.btn-save-profile{ background:linear-gradient(90deg,var(--accent),#8b7bff); color:white;border:none;padding:10px 14px;border-radius:10px;cursor:pointer }
.btn-save-profile:hover{ filter:brightness(.98); }

@media (max-width:900px){
    .admin-messages-wrapper{ padding:12px }
    .admin-message-item .avatar{ width:40px;height:40px }
    .admin-message-item .meta .snippet{ display:none }
}

/* Dark theme scoped to this view */
.admin-messages-wrapper.dark{ --bg: #0f1724; --card: #0b1220; --muted: #9aa6b2; --accent: #7b61ff; --accent-2: #00c2a8; --danger: #ff6b6b; color: #e6eef6 }
.admin-messages-wrapper.dark .admin-messages-container{ background:transparent }
.admin-messages-wrapper.dark .messages-list{ background: #071026; border:1px solid rgba(255,255,255,0.04); box-shadow: 0 6px 30px rgba(2,6,23,0.6); }
.admin-messages-wrapper.dark .page-header{ background:linear-gradient(90deg, #1f1148, #03363a); box-shadow: 0 10px 30px rgba(2,6,23,0.6); }
.admin-messages-wrapper.dark .page-header .btn-ghost{ background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.06); color:#e6eef6 }
.admin-messages-wrapper.dark .admin-message-item{ border-bottom:1px solid rgba(255,255,255,0.03); }
.admin-messages-wrapper.dark .admin-message-item .meta .subject{ color:#eaf2ff }
.admin-messages-wrapper.dark .admin-message-item .meta .snippet{ color:#c6d6e6 }
.admin-messages-wrapper.dark .admin-message-item.unread{ background: linear-gradient(90deg, rgba(123,97,255,0.06), rgba(0,194,168,0.02)); }
.admin-messages-wrapper.dark .message-detail{ background: #071026; border:1px solid rgba(255,255,255,0.04); color:#e6eef6 }
.admin-messages-wrapper.dark #detail-body{ color:#d6e6f5 }
.admin-messages-wrapper.dark .messages-empty{ color:#9fb0c6 }
.admin-messages-wrapper.dark .messages-empty svg{ stroke:#9fb0c6 }

/* Chat bubble helpers */
.chat-thread{ display:flex; flex-direction:column; gap:8px }
.chat-wrap{ display:flex }
.chat-wrap.left{ justify-content:flex-start }
.chat-wrap.right{ justify-content:flex-end }
.chat-bubble{ max-width:85%; padding:10px 14px; border-radius:14px; position:relative; box-shadow:0 2px 10px rgba(12,14,20,0.06); white-space:pre-wrap }
.chat-bubble.user{ background:#f6f8fa; color:#111; border:1px solid rgba(15,23,36,0.04) }
.chat-bubble.admin{ background:linear-gradient(90deg,#667eea,#764ba2); color:#fff }
.chat-bubble .ts{ display:block; font-size:0.75em; margin-top:8px; opacity:0.8; text-align:right; color:rgba(255,255,255,0.85) }
/* tail for bubbles */
.chat-bubble::after{ content:''; position:absolute; bottom:8px; width:12px; height:12px; transform:rotate(45deg); }
.chat-wrap.left .chat-bubble::after{ left:-6px; background:#f6f8fa; border-left:1px solid rgba(15,23,36,0.04); border-bottom:1px solid rgba(15,23,36,0.04) }
.chat-wrap.right .chat-bubble::after{ right:-6px; background:linear-gradient(90deg,#667eea,#764ba2); box-shadow:none }

/* Chat animations */
@keyframes slideUpFade {
    0% { opacity: 0; transform: translateY(10px) scale(.99); }
    60% { opacity: 1; transform: translateY(-2px) scale(1.01); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes pop {
    0% { transform: scale(.96); opacity: 0; }
    60% { transform: scale(1.03); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes pulseBorder {
    0% { box-shadow: 0 0 0 0 rgba(123,97,255,0.0); }
    70% { box-shadow: 0 0 0 10px rgba(123,97,255,0.06); }
    100% { box-shadow: 0 0 0 0 rgba(123,97,255,0.0); }
}

.animate-in {
    animation: slideUpFade 360ms cubic-bezier(.2,.9,.22,1) both;
    will-change: transform, opacity;
}
.animate-pop {
    animation: pop 280ms cubic-bezier(.2,.9,.22,1) both;
}
.admin-message-item.unread { animation: pulseBorder 1200ms ease-in-out; }

/* Typing indicator */
.typing-indicator{ display:inline-flex; gap:6px; align-items:center; padding:6px 8px; border-radius:10px; background: rgba(255,255,255,0.04); }
.typing-indicator .dot{ width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.75); opacity:0.85; animation: typingBounce 1s infinite; }
.typing-indicator .dot:nth-child(2){ animation-delay: 0.12s }
.typing-indicator .dot:nth-child(3){ animation-delay: 0.24s }
@keyframes typingBounce{ 0%{ transform: translateY(0); opacity:0.5 }50%{ transform: translateY(-6px); opacity:1 }100%{ transform: translateY(0); opacity:0.5 } }

/* Decorative background photo behind each message item and detail (subtle) */
.admin-message-item{ position:relative; z-index:1 }
.admin-message-item::before{
    content:''; position:absolute; inset:0; z-index:0; border-radius:8px; pointer-events:none;
    /* Allow per-item override via --admin-bg CSS variable, fallback to default image */
    background-image: var(--admin-bg, url('/PUBLIC/assets/images/oi.jpg'));
    background-size:cover; background-position:center; background-repeat:no-repeat;
    opacity:0.12; filter:contrast(1.03) saturate(1.02);
}
/* prefer optimized image-set where supported; still allow per-item override */
@supports (background-image: image-set(url('/PUBLIC/assets/images/oi-small.webp') 1x)) {
    .admin-message-item::before{
        background-image: var(--admin-bg, image-set(url('/PUBLIC/assets/images/oi-small.webp') 1x, url('/PUBLIC/assets/images/oi-small.jpg') 1x, url('/PUBLIC/assets/images/oi.jpg') 1x));
    }
}
.admin-message-item .avatar, .admin-message-item .meta { position:relative; z-index:2 }
.admin-message-item:hover::before{ opacity:0.18 }
.admin-message-item.selected{ outline:2px solid rgba(107,91,222,0.14); box-shadow:0 8px 22px rgba(16,24,40,0.06); }

.message-detail{ position:relative; z-index:1 }
.message-detail::before{ content:''; position:absolute; inset:0; z-index:0; border-radius:10px; pointer-events:none; background-image: var(--detail-bg, url('/PUBLIC/assets/images/oi.jpg')); background-size:cover; background-position:center; background-repeat:no-repeat; opacity:0.06; filter:contrast(1.04) }
@supports (background-image: image-set(url('/PUBLIC/assets/images/oi-small.webp') 1x)) {
    .message-detail::before{ background-image: var(--detail-bg, image-set(url('/PUBLIC/assets/images/oi-small.webp') 1x, url('/PUBLIC/assets/images/oi-small.jpg') 1x, url('/PUBLIC/assets/images/oi.jpg') 1x)); }
}

/* Fallback when background image assets fail to load */
.admin-messages-wrapper.bg-missing .admin-message-item::before{ background-image: none !important; background-color: rgba(255,255,255,0.02); opacity:1 !important; filter:none !important }
.admin-messages-wrapper.bg-missing .message-detail::before{ background-image:none !important; background-color: rgba(255,255,255,0.02); opacity:1 !important; filter:none !important }
.message-detail > * { position:relative; z-index:2 }

/* Dark theme: darker overlay so text remains readable */
.admin-messages-wrapper.dark .admin-message-item::before{ opacity:0.06; filter:brightness(0.8) contrast(1.05) }
.admin-messages-wrapper.dark .message-detail::before{ opacity:0.04; filter:brightness(0.7) }

/* Scrollbar polish */
#admin-messages-list-items::-webkit-scrollbar{ width:10px }
#admin-messages-list-items::-webkit-scrollbar-thumb{ background: rgba(12,18,30,0.08); border-radius:8px }

</style>

<style>
/* Additional dark overrides for buttons and inbox badge inside this view */
.admin-messages-wrapper.dark .btn-ghost {
    background: rgba(255,255,255,0.06) !important;
    border-color: rgba(255,255,255,0.06) !important;
    color: #e6eef6 !important;
}
.admin-messages-wrapper.dark .btn-ghost:hover {
    background: rgba(255,255,255,0.10) !important;
}
.admin-messages-wrapper.dark .btn-save-profile {
    background: linear-gradient(90deg,var(--accent),#8b7bff) !important;
    color: #fff !important;
}
.admin-messages-wrapper.dark #inboxUnreadCount {
    background: rgba(255,255,255,0.08) !important;
    color: #0b1220 !important;
}
</style>

<script>
async function loadAdminMessages(){
    const itemsContainer = document.getElementById('admin-messages-list-items');
    // show a small loading notice but keep server-rendered content if present
    const hadContent = itemsContainer && itemsContainer.innerHTML.trim().length > 0;
    if (!hadContent && itemsContainer) itemsContainer.innerHTML = '<div style="padding:16px;color:#666">Chargement...</div>';
    try{
        const res = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/list', { credentials: 'same-origin' });
        if(!res.ok) {
            console.warn('messages list HTTP', res.status);
            // if unauthorized, attempt to enable local dev admin session and retry once
            if (res.status === 401) {
                try{
                    // request dev_admin flag which sets $_SESSION['user_id']=1 on the server
                    await fetch((window.APP_BASE || '') + '/index.php?dev_admin=1', { credentials: 'same-origin' });
                    // retry fetching messages
                    const retry = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/list', { credentials: 'same-origin' });
                    if (retry.ok) {
                        const data2 = await retry.json();
                        window._adminMessages = data2 || [];
                        renderAdminMessages(window._adminMessages);
                        updateInboxUnread();
                        return;
                    }
                }catch(e){ console.warn('dev_admin retry failed', e); }
                // otherwise keep server-rendered content
                return;
            }
            throw new Error('HTTP ' + res.status);
        }
        const data = await res.json();
        // debug: log basic sender info to help diagnose duplicate-sender issue
        try{
            console.log('loadAdminMessages: received', Array.isArray(data) ? data.length : typeof data);
            if (Array.isArray(data)) console.log('senders:', data.slice(0,10).map(function(m){ return { id: m.id, sender_id: m.sender_id, sender_name: m.sender_name }; }));
        }catch(e){ console && console.warn && console.warn('debug log failed', e); }
        // keep a global copy so we can build conversation threads client-side
        window._adminMessages = data || [];
        renderAdminMessages(window._adminMessages);
        // update inbox badge
        updateInboxUnread();
    }catch(err){
        console.error('Failed to load admin messages:', err);
        // leave server-rendered content intact; show non-blocking notice
        if (itemsContainer && !hadContent) itemsContainer.innerHTML = '<div style="padding:16px;color:#c00">Erreur lors du chargement</div>';
    }
}

let currentMessage = null;

function renderAdminMessages(list){
    const c = document.getElementById('admin-messages-list-items');
    try{ console.log('renderAdminMessages: list length', Array.isArray(list) ? list.length : 0); }catch(e){}
    c.innerHTML = '';
    if(!Array.isArray(list) || list.length === 0){
        c.innerHTML = '<div class="messages-empty">\n' +
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path></svg>\n' +
            '<div style="font-size:1.05rem;font-weight:600;color:#12202b;margin-bottom:6px">Aucun message</div>\n' +
            '<div style="max-width:420px;margin:0 auto;opacity:0.8">Vous n\'avez pas encore de conversations. Cliquez sur "Rafraîchir" pour forcer la vérification ou attendez les nouveaux messages.</div>\n' +
            '</div>';
        return;
    }
    list.forEach((m, idx) => {
        const el = document.createElement('div');
        el.className = 'admin-message-item' + (m.is_read == 0 ? ' unread' : '');
        // animate in with a slight stagger
        el.classList.add('animate-in');
        el.style.animationDelay = (idx * 45) + 'ms';
        // Assign a per-message background (gradient) based on sender_id so backgrounds differ
        try{
            var sid = parseInt(m.sender_id || 0, 10) || 0;
            var hue = (sid * 47) % 360;
            var g = 'linear-gradient(135deg, hsl(' + hue + ' 70% 75%), hsl(' + ((hue+34)%360) + ' 68% 60%))';
            el.style.setProperty('--admin-bg', g);
            // also set a visible border color to easily verify per-sender variation
            var borderClr = 'hsl(' + hue + ' 72% 44%)';
            el.style.setProperty('--admin-border', borderClr);
        }catch(e){}
        const name = m.sender_name || ('User ' + (m.sender_id || '?'));
        const nameWithId = name + ' (id:' + (m.sender_id || '') + ')';
        const avatarText = (m.sender_name ? m.sender_name.split(' ').map(p=>p[0]).slice(0,2).join('') : (m.sender_email ? m.sender_email[0].toUpperCase() : '?'));
        // format date
        let timeText = '';
        try{ timeText = m.created_at ? (new Date(m.created_at).toLocaleString()) : ''; }catch(e){ timeText = m.created_at || ''; }
        let ctxHtml = '';
        try{
            if (m.context_type) {
                let link = '#';
                if (m.context_type === 'playlist') link = (window.APP_BASE || '') + '/index.php?route=playlistes' + (m.context_id ? ('#' + m.context_id) : '');
                else if (m.context_type === 'emission') link = (window.APP_BASE || '') + '/index.php?route=emissions' + (m.context_id ? ('#' + m.context_id) : '');
                else if (m.context_type === 'home') link = (window.APP_BASE || '') + '/index.php?route=home';
                ctxHtml = '<div style="font-size:0.8em;color:#777;margin-top:6px">Contexte: <a href="'+link+'" style="color:var(--muted)">'+ (m.context_type + (m.context_id ? (' #' + m.context_id) : '')) + '</a></div>';
            }
        }catch(e){ console.error(e); }
        el.innerHTML = '<div style="display:flex;gap:12px;align-items:flex-start"><div class="avatar">' + avatarText + '</div><div class="meta"><div class="row"><div class="subject">' + (m.subject||'Sans sujet') + '</div><div class="time">' + timeText + '</div></div><div class="snippet">' + (m.body||'') + '</div><div style="font-size:0.8em;color:#888;margin-top:8px">From: ' + nameWithId + '</div>' + ctxHtml + '</div></div>';
        el.addEventListener('click', function(){ showMessageDetail(m, el); });
        c.appendChild(el);
    });
}

async function showMessageDetail(m, el){
    currentMessage = m;
    const empty = document.getElementById('detail-empty');
    const cont = document.getElementById('detail-content');
    empty.style.display = 'none'; cont.style.display = 'block';
    // Determine admin id from session (fallback to 1)
    const adminId = parseInt('<?php echo $_SESSION['user_id'] ?? 1; ?>',10) || 1;
    const counterpart = (parseInt(m.sender_id||0,10) === adminId) ? parseInt(m.recipient_id||0,10) : parseInt(m.sender_id||0,10);
    // Build thread between admin and counterpart
    const thread = (window._adminMessages || []).filter(x => {
        const sid = parseInt(x.sender_id||0,10);
        const rid = parseInt(x.recipient_id||0,10);
        return (sid === counterpart && rid === adminId) || (sid === adminId && rid === counterpart);
    }).sort((a,b)=> new Date(a.created_at) - new Date(b.created_at));

    // header
    document.getElementById('detail-subject').textContent = m.subject || 'Conversation';
    let createdText = m.created_at || '';
    try{ createdText = m.created_at ? (new Date(m.created_at).toLocaleString()) : createdText; }catch(e){}
    document.getElementById('detail-meta').textContent = (m.sender_email? m.sender_email + ' • ' : '') + createdText;

    // render thread as chat bubbles
    const bodyEl = document.getElementById('detail-body');
    bodyEl.style.display = 'flex';
    bodyEl.style.flexDirection = 'column';
    bodyEl.innerHTML = '';
    thread.forEach((msg, tindex) => {
        const isAdmin = (parseInt(msg.sender_id||0,10) === adminId);
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-wrap ' + (isAdmin ? 'right' : 'left');
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble ' + (isAdmin ? 'admin' : 'user');
        // pop animation for each bubble with a tiny stagger
        bubble.classList.add('animate-pop');
        bubble.style.animationDelay = (tindex * 60) + 'ms';
        const dateTxt = msg.created_at ? (new Date(msg.created_at).toLocaleString()) : '';
        const senderLabel = isAdmin ? 'Vous' : ((msg.sender_name || msg.sender_email || ('User '+msg.sender_id)) + ' (id:' + (msg.sender_id||'') + ')');
        bubble.innerHTML = '<div style="font-weight:600;margin-bottom:6px">' + senderLabel + '</div><div>' + (msg.body || '') + '</div><span class="ts">' + dateTxt + '</span>';
        wrapper.appendChild(bubble);
        bodyEl.appendChild(wrapper);
    });

    document.getElementById('reply-recipient').value = counterpart || '';
    document.getElementById('reply-subject').value = 'Re: ' + (m.subject || '');
    document.getElementById('reply-body').value = '';

    // mark as read if unread
    if (m.is_read == 0) {
        try{
            await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/mark_read', { method: 'POST', body: new URLSearchParams({ id: m.id }), credentials: 'same-origin' });
            m.is_read = 1; if (el) el.classList.remove('unread');
            if (window.updateMessagesBadge) window.updateMessagesBadge();
            updateInboxUnread();
        }catch(err){ console.error(err); }
    }
    // mark selected visually
    try{
        document.querySelectorAll('.admin-message-item').forEach(i=> i.classList.remove('selected'));
        if (el && el.classList) el.classList.add('selected');
    }catch(e){ }
}

async function updateInboxUnread(){
    try{
        const res = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/unread_count', { credentials: 'same-origin' });
        if (!res.ok) return;
        const j = await res.json();
        const el = document.getElementById('inboxUnreadCount');
        const n = parseInt(j.count || 0, 10);
        if (el) el.textContent = n;
    }catch(e){}
}

document.getElementById('admin-reply-form').addEventListener('submit', async function(ev){
    ev.preventDefault();
    const recip = document.getElementById('reply-recipient').value;
    if (!recip || parseInt(recip,10) <= 0) { alert('Impossible de répondre : destinataire introuvable.'); return; }
    const subj = document.getElementById('reply-subject').value || '';
    const body = document.getElementById('reply-body').value || '';
    if (!body.trim()) { alert('Le message est vide'); return; }
    const form = new FormData();
    form.append('recipient_id', recip);
    form.append('subject', subj);
    form.append('body', body);
    try{
        const res = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/send', { method: 'POST', body: form, credentials: 'same-origin' });
        const j = await res.json();
        if (j.ok) { alert('Réponse envoyée'); document.getElementById('reply-body').value = ''; loadAdminMessages(); }
        else alert('Erreur: ' + (j.error || 'unknown'));
    }catch(err){ console.error(err); alert('Erreur réseau'); }
});

document.getElementById('mark-read-btn').addEventListener('click', async function(){
    if (!currentMessage) return; try{ await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/mark_read', { method: 'POST', body: new URLSearchParams({ id: currentMessage.id }), credentials: 'same-origin' }); currentMessage.is_read = 1; if(window.updateMessagesBadge) window.updateMessagesBadge(); loadAdminMessages(); }catch(err){console.error(err);}    
});

document.getElementById('refresh-admin-messages').addEventListener('click', loadAdminMessages);

    loadAdminMessages();

    // Probe for background image availability and apply fallback class if missing
    function probeAdminBackgrounds() {
        try {
            var imgs = [
                '/PUBLIC/assets/images/oi-small.webp',
                '/PUBLIC/assets/images/oi-small.jpg',
                '/PUBLIC/assets/images/oi.jpg'
            ];
            var loaded = false;
            var checks = imgs.map(function(src){
                return new Promise(function(resolve){
                    var img = new Image();
                    img.onload = function(){ loaded = true; resolve(true); };
                    img.onerror = function(){ resolve(false); };
                    img.src = src + '?_=' + (new Date().getTime());
                });
            });
            Promise.all(checks).then(function(){
                if(!loaded){
                    var wrap = document.querySelector('.admin-messages-wrapper');
                    if(wrap) wrap.classList.add('bg-missing');
                }
            });
        } catch(e){ console && console.warn && console.warn('probeAdminBackgrounds failed', e); }
    }

    // run probe after a short delay to allow network fetching
    setTimeout(probeAdminBackgrounds, 600);
</script>

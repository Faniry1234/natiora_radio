<!-- Modern User Profile Page -->
<div class="profile-container-enhanced">
    <div class="profile-tabs-enhanced" style="margin-bottom:18px;">
        <button class="tab-button-enhanced active" data-tab="profile">Profil</button>
        <button class="tab-button-enhanced" data-tab="password">Sécurité</button>
        <button class="tab-button-enhanced" data-tab="history">Historique</button>
        <button class="tab-button-enhanced" data-tab="messages">Messages</button>
    </div>
    <div id="profile" class="tab-content-enhanced active">
    

    
                <div class="header-icon"><i class="fas fa-user-edit"></i></div>
                <div>
                    <h2>Modifier le profil</h2>
                    <p>Mettez à jour vos informations personnelles</p>
                </div>
            </div>
            <form method="POST" action="/index.php?route=auth/updateProfile" class="form-enhanced">
                <div class="form-group-profile">
                    <label for="profile-name">
                        <i class="fas fa-user"></i> Nom complet
                    </label>
                    <input type="text" id="profile-name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group-profile">
                    <label for="profile-phone">
                        <i class="fas fa-phone"></i> Téléphone
                    </label>
                    <input type="tel" id="profile-phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+261 XX XXX XXX">
                </div>

                <div class="form-group-profile">
                    <label for="profile-bio">
                        <i class="fas fa-quote-left"></i> Biographie
                    </label>
                    <textarea id="profile-bio" name="bio" rows="4" placeholder="Parlez un peu de vous..." maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    <small class="char-count">0/500</small>
                </div>

                <button type="submit" class="btn-save-profile">
                    <i class="fas fa-check-circle"></i> Enregistrer les modifications
                </button>
            </form>
        </div>
    </div>

    <!-- Password Tab -->
    <div id="password" class="tab-content-enhanced">
        <div class="card-enhanced">
            <div class="card-header-enhanced">
                <div class="header-icon security"><i class="fas fa-lock"></i></div>
                <div>
                    <h2>Changer le mot de passe</h2>
                    <p>Sécurisez votre compte avec un nouveau mot de passe</p>
                </div>
            </div>
            <form method="POST" action="/index.php?route=auth/changePassword" class="form-enhanced">
                <div class="form-group-profile">
                    <label for="old-password">
                        <i class="fas fa-lock"></i> Ancien mot de passe
                    </label>
                    <input type="password" id="old-password" name="old_password" required>
                </div>

                <div class="form-group-profile">
                    <label for="new-password">
                        <i class="fas fa-key"></i> Nouveau mot de passe
                    </label>
                    <input type="password" id="new-password" name="new_password" required minlength="6" placeholder="Minimum 6 caractères">
                </div>

                <div class="form-group-profile">
                    <label for="confirm-password">
                        <i class="fas fa-key"></i> Confirmer le mot de passe
                    </label>
                    <input type="password" id="confirm-password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" class="btn-change-password">
                    <i class="fas fa-check-circle"></i> Changer le mot de passe
                </button>
            </form>
        </div>
    </div>

    <!-- History Tab -->
    <div id="history" class="tab-content-enhanced">
        <div class="card-enhanced">
            <div class="card-header-enhanced">
                <div class="header-icon history"><i class="fas fa-history"></i></div>
                <div>
                    <h2>Historique des actions</h2>
                    <p>Tous les événements de votre compte</p>
                </div>
            </div>
            <?php if (!empty($history)): ?>
                <div class="history-timeline">
                    <?php foreach (array_reverse($history) as $action): ?>
                        <div class="history-item-enhanced">
                            <div class="timeline-dot">
                                <?php
                                $icon = 'circle';
                                $color = 'default';
                                switch ($action['action']) {
                                    case 'LOGIN': $icon = 'sign-in-alt'; $color = 'success'; break;
                                    case 'LOGOUT': $icon = 'sign-out-alt'; $color = 'info'; break;
                                    case 'PROFILE_UPDATE': $icon = 'user-edit'; $color = 'primary'; break;
                                    case 'PASSWORD_CHANGE': $icon = 'key'; $color = 'warning'; break;
                                }
                                ?>
                                <div class="dot-icon dot-<?php echo $color; ?>">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                            </div>
                            <div class="history-content-enhanced">
                                <div class="history-action-label">
                                    <span class="action-badge"><?php echo htmlspecialchars($action['action']); ?></span>
                                    <span class="action-details"><?php echo htmlspecialchars($action['details']); ?></span>
                                </div>
                                <div class="history-info">
                                    <small><i class="fas fa-clock"></i> <?php echo date('d/m/Y à H:i:s', strtotime($action['timestamp'])); ?></small>
                                    <span class="separator">•</span>
                                    <small><i class="fas fa-globe"></i> <?php echo htmlspecialchars($action['ip']); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-enhanced">
                    <i class="fas fa-inbox"></i>
                    <p>Aucun historique pour le moment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Messages Tab -->
    <div id="messages" class="tab-content-enhanced">
        <div class="card-enhanced card-messages">
            <div class="card-header-enhanced card-header-messages">
                <div class="messages-banner">
                    <div class="header-icon banner-icon"><i class="fas fa-envelope"></i></div>
                    <div class="banner-text">
                        <h2>Messages</h2>
                        <p>Envoyez un message à l'équipe (ou consultez vos échanges)</p>
                    </div>
                </div>
            </div>
            <div style="padding:20px; display:flex; gap:20px;">
                <div style="width:320px;">
                    <div style="font-weight:700;margin-bottom:8px">Conversations</div>
                    <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                        <div style="display:flex;gap:6px">
                            <button id="view-conversations" class="btn-ghost active-view" style="padding:6px 10px">Conversations</button>
                            <button id="view-list" class="btn-ghost" style="padding:6px 10px">Liste</button>
                        </div>
                        <div style="flex:1;display:flex;justify-content:center">
                            <input id="messages-search" placeholder="Rechercher..." style="width:60%;padding:6px 8px;border:1px solid #ddd;border-radius:8px;" />
                        </div>
                        <div style="display:flex;gap:8px">
                            <button id="mark-all-read" class="btn-ghost" title="Marquer tous les messages visibles comme lus">Marquer tout lu</button>
                            <button id="new-message-btn" class="btn-ghost" style="padding:6px 10px">Nouveau message</button>
                            <button id="refresh-messages-quick" class="btn-ghost" style="padding:6px 10px">Rafraîchir</button>
                        </div>
                    </div>
                    <div id="conversations-list" style="max-height:360px;overflow:auto;border:1px solid #eee;padding:8px;border-radius:8px;">
                        <em>Chargement...</em>
                    </div>
                    <div id="messages-list" style="display:none;max-height:360px;overflow:auto;border:1px solid #eee;padding:8px;border-radius:8px;margin-top:8px;">
                        <!-- flat list restored -->
                    </div>

                    <?php
                    // determine default recipient (first admin) for quick messages
                    $defaultRecipient = 1;
                    try {
                        if (!class_exists('Database')) require_once __DIR__ . '/../../APP/MODEL/Database.php';
                        $db = Database::getInstance(); $db->init(); $pdo = $db->getConnection();
                        $row = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_COLUMN);
                        if ($row) $defaultRecipient = (int)$row;
                    } catch (Throwable $e) { /* keep fallback */ }
                    ?>
                    <div id="quick-message" style="margin-top:10px;display:none;border:1px dashed #e6e6e6;padding:8px;border-radius:8px;background:rgba(255,255,255,0.88)">
                        <form id="message-send-form">
                                <input type="hidden" id="message-recipient" name="recipient_id" value="<?php echo htmlspecialchars($defaultRecipient); ?>">
                            <div style="margin-bottom:6px;display:flex;gap:8px;align-items:center">
                                <select id="message-context-type" name="context_type" style="padding:6px;border:1px solid #ddd;border-radius:6px;">
                                    <option value="">Aucun</option>
                                    <option value="home">Accueil</option>
                                    <option value="playlist">Playlist</option>
                                    <option value="emission">Émission</option>
                                </select>
                                <select id="message-context-id" name="context_id" style="padding:6px;border:1px solid #ddd;border-radius:6px;display:none;min-width:140px">
                                    <option value="">Choisir...</option>
                                </select>
                            </div>
                            <div style="margin-bottom:6px;"><input id="message-subject" name="subject" placeholder="Sujet (optionnel)" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;"></div>
                            <div style="margin-bottom:6px;"><textarea id="message-body" name="body" rows="3" placeholder="Écrivez un message rapide" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;"></textarea></div>
                            <div style="display:flex;gap:8px;align-items:center;"><button type="submit" class="btn-save-profile">Envoyer</button><button type="button" id="cancel-quick-message" class="btn-ghost">Annuler</button></div>
                        </form>
                    </div>
                </div>
                <div style="flex:1;">
                    <div id="conversation-header" style="font-weight:700;margin-bottom:8px">Sélectionnez une conversation</div>
                    <div id="conversation-thread" class="chat-thread" style="height:360px;overflow:auto;border:1px solid #eee;padding:12px;border-radius:8px;margin-bottom:8px;display:flex;flex-direction:column;gap:8px;">
                        <div style="color:#666;margin:auto">Aucune conversation sélectionnée</div>
                    </div>
                    <form id="conversation-send-form" style="display:none;background:rgba(255,255,255,0.88);padding:12px;border-radius:8px;">
                        <input type="hidden" id="conv-recipient" name="recipient_id" value="<?php echo htmlspecialchars($defaultRecipient); ?>">
                        <div style="margin-bottom:8px;display:flex;gap:8px;align-items:center">
                            <select id="conv-context-type" name="context_type" style="padding:6px;border:1px solid #ddd;border-radius:6px;">
                                <option value="">Aucun</option>
                                <option value="home">Accueil</option>
                                <option value="playlist">Playlist</option>
                                <option value="emission">Émission</option>
                            </select>
                            <select id="conv-context-id" name="context_id" style="padding:6px;border:1px solid #ddd;border-radius:6px;display:none;min-width:140px">
                                <option value="">Choisir...</option>
                            </select>
                        </div>
                        <div style="margin-bottom:8px;"><input id="conv-subject" name="subject" placeholder="Sujet (optionnel)" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;"></div>
                        <div style="margin-bottom:8px;"><textarea id="conv-body" name="body" rows="3" placeholder="Écrivez votre message" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;"></textarea></div>
                        <div style="display:flex;gap:8px;align-items:center;"><button type="submit" class="btn-save-profile">Envoyer</button><button type="button" id="refresh-messages" class="btn-change-password" style="background:#eee;color:#333;">Rafraîchir</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container-enhanced { max-width: 1100px; margin: 20px auto; padding: 0 20px; }
.profile-header-enhanced { position: relative; margin-bottom: 40px; border-radius: 20px; overflow: hidden; }
.profile-header-bg { position: absolute; top: 0; left: 0; right: 0; height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f5576c 100%); z-index: 1; }
.profile-header-content { position: relative; z-index: 2; background: white; padding: 60px 40px 40px; border-radius: 20px; display: flex; align-items: flex-start; gap: 30px; box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15); margin-top: -50px; }
.profile-avatar-wrapper { position: relative; }
.profile-avatar-enhanced { width: 140px; height: 140px; border-radius: 50%; border: 5px solid white; object-fit: cover; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); }
.avatar-status { position: absolute; bottom: 5px; right: 5px; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; background: #00c897; }
.avatar-status.offline { background: #999; }
.profile-info-enhanced { flex: 1; }
.profile-info-enhanced h1 { margin: 0 0 5px; font-size: 2em; color: #333; }
.profile-subtitle { margin: 0 0 15px; color: #667eea; font-weight: 600; font-size: 0.95em; }
.profile-meta { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 15px; }
.meta-item { display: flex; align-items: center; gap: 8px; color: #666; font-size: 0.95em; }
.meta-item i { color: #667eea; }
.btn-logout-profile { align-self: center; padding: 12px 24px; background: #f5576c; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3); }
.btn-logout-profile:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4); }
.profile-tabs-enhanced { display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; flex-wrap: wrap; }
.tab-button-enhanced { background: none; border: none; padding: 15px 20px; cursor: pointer; transition: all 0.3s ease; color: #666; font-weight: 600; border-bottom: 3px solid transparent; display: flex; align-items: center; gap: 10px; }
.tab-button-enhanced:hover { color: #667eea; }
.tab-button-enhanced.active { color: #667eea; border-bottom-color: #667eea; }
.active-view{ background: linear-gradient(90deg,#667eea,#764ba2); color:#fff; border-color:transparent }
.tab-icon { display: flex; align-items: center; font-size: 1.2em; }
.tab-content-enhanced { display: none; animation: slideInContent 0.3s ease; }
.tab-content-enhanced.active { display: block; }
@keyframes slideInContent { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.card-enhanced { background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); overflow: hidden; border: 1px solid #f0f0f0; }
.card-header-enhanced { background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05)); padding: 30px; display: flex; align-items: center; gap: 20px; border-bottom: 1px solid #f0f0f0; }
.header-icon { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.3em; }
.header-icon.security { background: linear-gradient(135deg, #f5576c, #f093fb); }
.header-icon.history { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.card-header-enhanced h2 { margin: 0 0 5px; color: #333; font-size: 1.4em; }
.card-header-enhanced p { margin: 0; color: #999; font-size: 0.9em; }
.form-enhanced { padding: 30px; }
.form-group-profile { margin-bottom: 25px; }
.form-group-profile label { display: flex; align-items: center; gap: 10px; font-weight: 600; margin-bottom: 10px; color: #333; font-size: 0.95em; }
.form-group-profile label i { color: #667eea; }
.form-group-profile input, .form-group-profile textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease; }
.form-group-profile input:focus, .form-group-profile textarea:focus { outline: none; border-color: #667eea; background: rgba(102, 126, 234, 0.02); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.char-count { display: block; margin-top: 5px; color: #999; font-size: 0.85em; }
.btn-save-profile, .btn-change-password { width: 100%; padding: 13px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 10px; font-size: 1.05em; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); }
.btn-save-profile:hover, .btn-change-password:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }
.history-timeline { padding: 30px; }
.history-item-enhanced { display: flex; gap: 20px; padding: 20px; margin-bottom: 15px; background: #f9f9f9; border-radius: 12px; transition: all 0.3s ease; border-left: 4px solid #667eea; }
.history-item-enhanced:hover { background: #f5f5f5; transform: translateX(5px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); }
.timeline-dot { display: flex; align-items: center; justify-content: center; min-width: 60px; }
.dot-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1em; }
.dot-success { background: linear-gradient(135deg, #00c897, #00d4a8); }
.dot-info { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.dot-primary { background: linear-gradient(135deg, #667eea, #764ba2); }
.dot-warning { background: linear-gradient(135deg, #f5576c, #f093fb); }
.history-content-enhanced { flex: 1; display: flex; flex-direction: column; justify-content: center; }
.history-action-label { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.action-badge { display: inline-block; padding: 4px 10px; background: rgba(102, 126, 234, 0.1); color: #667eea; border-radius: 20px; font-weight: 600; font-size: 0.85em; }
.action-details { color: #666; font-size: 0.95em; }
.history-info { display: flex; align-items: center; gap: 8px; color: #999; font-size: 0.85em; }
.separator { opacity: 0.5; }
.empty-state-enhanced { text-align: center; padding: 60px 40px; color: #999; }
.empty-state-enhanced i { font-size: 4em; margin-bottom: 15px; opacity: 0.4; color: #ccc; }
.empty-state-enhanced p { margin: 0; font-size: 1.05em; }
.alert-profile { margin: 0 0 25px 0; padding: 15px 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px; border-left: 4px solid; position: relative; }
.alert-profile.alert-error { background: #ffe0e0; border-left-color: #f5576c; color: #d32f2f; }
.alert-profile.alert-success { background: #e0ffe0; border-left-color: #00c897; color: #2d5f2e; }
.alert-close { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.3em; opacity: 0.7; transition: opacity 0.3s ease; }
.alert-close:hover { opacity: 1; }
@media (max-width: 768px) {
    .profile-header-content { flex-direction: column; text-align: center; align-items: center; }
    .profile-info-enhanced h1 { font-size: 1.5em; }
    .profile-meta { justify-content: center; }
    .btn-logout-profile { align-self: center; }
    .profile-tabs-enhanced { gap: 5px; }
    .tab-button-enhanced { padding: 12px 15px; font-size: 0.9em; }
    .tab-button-enhanced span { display: none; }
    .tab-button-enhanced.active span { display: inline; }
    .card-header-enhanced { flex-direction: column; text-align: center; }
}

/* Chat bubble helpers (match admin view) */
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

/* Messages theme (banner + conversation cards) */
.card-header-messages { padding: 0; overflow: visible; }
.messages-banner { display:flex; align-items:center; gap:16px; padding:20px 24px; background: linear-gradient(135deg,#6b5bde,#b76bdc); color:#fff; border-radius:8px 8px 0 0; }
.messages-banner .banner-icon { width:64px; height:64px; border-radius:50%; background: rgba(255,255,255,0.12); display:flex; align-items:center; justify-content:center; font-size:1.6em; box-shadow:0 6px 18px rgba(107,91,222,0.18); }
.messages-banner .banner-text h2{ margin:0; font-size:1.4em; }
.messages-banner .banner-text p{ margin:0; opacity:0.95; color:rgba(255,255,255,0.95); }

#conversations-list { background: linear-gradient(180deg, #fbfbff, #ffffff); border-radius:0 0 8px 8px; }
#conversations-list .conversation-item{ padding:14px; border-radius:12px; margin-bottom:12px; background: linear-gradient(rgba(255,255,255,0.72), rgba(255,255,255,0.72)); box-shadow:0 8px 25px rgba(16,24,40,0.06); border:1px solid rgba(255,255,255,0.6); cursor:pointer; transition:transform .18s ease, box-shadow .18s ease; backdrop-filter: blur(4px) saturate(1.04); }
#conversations-list .conversation-item .meta{ font-size:0.85em; color:#777; margin-top:6px; }

#messages-list .message-row{ padding:14px; border-radius:12px; margin-bottom:10px; background: linear-gradient(rgba(255,255,255,0.7), rgba(255,255,255,0.7)); box-shadow:0 8px 22px rgba(10,10,20,0.06); border:1px solid rgba(255,255,255,0.6); backdrop-filter: blur(3px) saturate(1.03); }

/* Make the messages card transparent so banner is visible */
.card-messages { background: transparent !important; border: none !important; box-shadow: none !important; }
.card-messages .card-header-messages { border-radius: 10px; padding: 0; }

/* Background image for message panels
   Save the provided photo as: PUBLIC/assets/images/messages-bg.jpg */
/* Move background image onto each card so the photo appears clearly inside items */
.card-enhanced.card-messages #conversations-list,
.card-enhanced.card-messages #messages-list,
.card-enhanced.card-messages #conversation-thread {
    background: transparent !important;
}

/* Apply image directly to items */
.card-enhanced.card-messages #conversations-list .conversation-item,
.card-enhanced.card-messages #messages-list .message-row,
.card-enhanced.card-messages #conversation-thread {
    /* Fallback first, then prefer optimized images when supported */
    background-image: url('/PUBLIC/assets/images/oi.jpg') !important;
    /* prefer image-set where supported for smaller/modern formats */
    @supports (background-image: image-set(url('/PUBLIC/assets/images/oi-small.webp') 1x)) {
        background-image: image-set(url('/PUBLIC/assets/images/oi-small.webp') 1x, url('/PUBLIC/assets/images/oi-small.jpg') 1x, url('/PUBLIC/assets/images/oi.jpg') 1x) !important;
    }
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    filter: contrast(1.06) saturate(1.05);
    background-blend-mode: overlay !important;
}

/* Keep quick message/forms slightly more opaque for readability */
.card-enhanced.card-messages #quick-message,
.card-enhanced.card-messages #conversation-send-form {
    background: rgba(255,255,255,0.98) !important;
    border: 1px solid rgba(255,255,255,0.8) !important;
    box-shadow: 0 8px 30px rgba(16,24,40,0.06) !important;
}

/* chat bubble polish */
.chat-bubble.user{ background: rgba(255,255,255,0.92); color:#111; border:1px solid rgba(15,23,36,0.06) }
.chat-bubble.admin{ background:linear-gradient(90deg,#5f6be6,#9a6be0); color:#fff }
.conversation-item:hover, #messages-list .message-row:hover { transform: translateY(-4px); box-shadow:0 14px 40px rgba(16,24,40,0.08); }

/* Fallback when background image assets fail to load */
.card-enhanced.card-messages.bg-missing #conversations-list,
.card-enhanced.card-messages.bg-missing #messages-list,
.card-enhanced.card-messages.bg-missing #conversation-thread {
    background-image: linear-gradient(180deg, rgba(245,246,250,0.98), rgba(255,255,255,0.98)) !important;
    filter: none !important;
}


</style>

<style>
/* Conversation item selected/hover styles */
.conversation-item:hover{ transform: translateY(-2px); box-shadow: 0 6px 18px rgba(16,24,40,0.06); }
.conversation-item.selected{ outline: 2px solid rgba(102,126,234,0.18); box-shadow: 0 8px 22px rgba(102,126,234,0.08); transform: translateY(-2px); }

/* Custom scrollbar for lists */
#conversations-list::-webkit-scrollbar, #messages-list::-webkit-scrollbar, #conversation-thread::-webkit-scrollbar { height:10px; width:10px }
#conversations-list::-webkit-scrollbar-thumb, #messages-list::-webkit-scrollbar-thumb, #conversation-thread::-webkit-scrollbar-thumb { background: rgba(16,24,40,0.08); border-radius:8px }
</style>

<script>
document.querySelectorAll('.tab-button-enhanced').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.tab-button-enhanced').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content-enhanced').forEach(content => content.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
    });
});
// Check background images and apply fallback class if none load
(function(){
    const imgCandidates = [
        '/PUBLIC/assets/images/oi-small.webp',
        '/PUBLIC/assets/images/oi-small.jpg',
        '/PUBLIC/assets/images/oi.jpg'
    ];
    function tryLoad(list, cb){
        if (!list || !list.length) return cb(false);
        const src = list.shift();
        const i = new Image();
        i.onload = function(){ cb(true, src); };
        i.onerror = function(){ tryLoad(list, cb); };
        i.src = src + '?_=' + Date.now();
    }
    tryLoad(imgCandidates.slice(), function(ok, succeeded){
        if (!ok){
            const root = document.querySelector('.card-enhanced.card-messages');
            if (root) root.classList.add('bg-missing');
        }
    });
})();
const bioTextarea = document.getElementById('profile-bio');
if (bioTextarea) {
    bioTextarea.addEventListener('input', function() {
        const counter = this.parentElement.querySelector('.char-count');
        if (counter) counter.textContent = this.value.length + '/500';
    });
    bioTextarea.dispatchEvent(new Event('input'));
}

// current logged user id available to scripts
const CURRENT_USER_ID = parseInt('<?php echo $_SESSION['user_id'] ?? 0; ?>',10);

// Conversation messaging: polling control and tab activation
var messagesPoll = null;
function startMessagesPoll(){ if (messagesPoll) return; messagesPoll = setInterval(loadMessages, 20000); }
function stopMessagesPoll(){ if (!messagesPoll) return; clearInterval(messagesPoll); messagesPoll = null; }

// Notification helpers: track unread message ids and show browser notification + sound
let lastUnreadIds = new Set();
function ensureNotificationPermission(){
    try{
        if (!('Notification' in window)) return;
        if (Notification.permission === 'granted') return;
        if (Notification.permission !== 'denied') Notification.requestPermission().catch(()=>{});
    }catch(e){ console.error('Notification init error', e); }
}

function playNotificationSound(){
    try{
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine'; o.frequency.value = 880; // A5
        g.gain.value = 0.05;
        o.connect(g); g.connect(ctx.destination);
        o.start();
        setTimeout(()=>{ o.stop(); try{ ctx.close(); }catch(e){} }, 220);
    }catch(e){ /* ignore audio errors */ }
}

function notifyNewMessage(m){
    try{
        const title = m.subject && m.subject.trim() ? m.subject : 'Nouveau message';
        const body = (m.body || '').slice(0,160);
        if ('Notification' in window && Notification.permission === 'granted'){
            const n = new Notification(title, { body: body, tag: 'msg-'+m.id });
            n.onclick = function(){ window.focus(); try{ window.location.href = (window.APP_BASE||'') + '/index.php?route=auth/profile'; }catch(e){} };
        }
        playNotificationSound();
    }catch(e){ console.error('notify error', e); }
}

// Ask for notification permission early
ensureNotificationPermission();

// Tab activation: load and poll when Messages tab is opened
document.querySelectorAll('.tab-button-enhanced').forEach(button => {
    button.addEventListener('click', function() {
        // previous tab click handlers manage UI; here we trigger messages behavior
        if (this.dataset.tab === 'messages') { loadMessages(); startMessagesPoll(); } else { stopMessagesPoll(); }
    });
});
// If messages tab is active on load, start polling
const _messagesPanelEl = document.getElementById('messages');
if (_messagesPanelEl && _messagesPanelEl.classList.contains('active')) { loadMessages(); startMessagesPoll(); }

// ---- Conversation UI logic (consolidated) ----
let allMessages = [];
function formatDate(ts){ try{ return ts ? new Date(ts).toLocaleString() : ''; }catch(e){return ts||'';} }
function buildConversations(msgs, meId){
    const conv = {};
    msgs.forEach(m => {
        const sid = parseInt(m.sender_id || 0,10);
        const rid = parseInt(m.recipient_id || 0,10);
        let other = (sid === meId) ? rid : sid;
        if (other === 0) other = 'anon:' + (m.sender_email || 'unknown');
        if (!conv[other]) conv[other] = [];
        conv[other].push(m);
    });
    return conv;
}

async function loadMessages(){
    const convList = document.getElementById('conversations-list');
    if (convList) convList.innerHTML = 'Chargement...';
    try{
        const res = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/list', { credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        allMessages = Array.isArray(data) ? data : (data.rows || []);
        const meId = parseInt('<?php echo $_SESSION['user_id'] ?? 0; ?>',10);
        const convs = buildConversations(allMessages, meId);
        renderConversations(convs, meId);

        // Detect new unread messages addressed to the current user and notify
        try{
            const currentUnread = new Set();
            allMessages.forEach(m => {
                const rid = parseInt(m.recipient_id || 0, 10);
                const sid = parseInt(m.sender_id || 0, 10);
                const isRead = parseInt(m.is_read || 0, 10);
                if (rid === meId && isRead === 0 && sid !== meId) {
                    currentUnread.add(String(m.id));
                }
            });
            const newIds = [];
            currentUnread.forEach(id => { if (!lastUnreadIds.has(id)) newIds.push(id); });
            if (newIds.length > 0) {
                newIds.forEach(id => {
                    const msg = allMessages.find(mm => String(mm.id) === String(id));
                    if (msg) notifyNewMessage(msg);
                });
            }
            lastUnreadIds = currentUnread;
        }catch(e){ console.error('unread detect error', e); }
    }catch(err){ if (convList) convList.innerHTML = '<div style="color:#c00">Erreur lors du chargement</div>'; console.error(err); }
}

function renderConversations(convs, meId){
    const list = document.getElementById('conversations-list');
    list.innerHTML = '';
    const _msEl = document.getElementById('messages-search');
    const q = ((_msEl && _msEl.value) || '').toLowerCase();
    const entries = Object.keys(convs).map(k=>({key:k, items: convs[k]}));
    entries.sort((a,b)=>{ const at = new Date(a.items[0].created_at || 0).getTime(); const bt = new Date(b.items[0].created_at || 0).getTime(); return bt - at; });
    if (entries.length===0) { list.innerHTML = '<div style="color:#666">Aucune conversation</div>'; return; }
    entries.forEach(ent=>{
        const items = ent.items.slice().sort((a,b)=> new Date(b.created_at)-new Date(a.created_at));
        const last = items[0];
        if (q) {
            const hay = ((last.subject||'') + ' ' + (last.body||'') + ' ' + (last.sender_name||'') + ' ' + (last.sender_email||'')).toLowerCase();
            if (!hay.includes(q)) return;
        }
        const preview = (last.body||'').slice(0,80);
        const el = document.createElement('div');
        el.className = 'conversation-item';
        const name = (last.sender_id==meId) ? ('Vous') : (last.sender_name || last.sender_email || ('User '+last.sender_id));
        el.innerHTML = '<div style="font-weight:600">' + name + '</div><div class="meta">' + preview + '</div><div style="font-size:0.8em;color:#999;margin-top:8px">' + formatDate(last.created_at) + '</div>';
        el.dataset.convKey = ent.key;
        el.addEventListener('click', function(){ openConversation(ent.key, meId); });
        list.appendChild(el);
    });
}
function renderMessagesList(list){
    const container = document.getElementById('messages-list');
    if (!container) return;
    container.innerHTML = '';
    if (!Array.isArray(list) || list.length === 0) {
        container.innerHTML = '<div style="color:#666;padding:10px">Aucun message</div>';
        return;
    }
    const _msEl2 = document.getElementById('messages-search');
    const q = ((_msEl2 && _msEl2.value) || '').toLowerCase();
    list.forEach(m => {
        if (q) {
            const hay = ((m.subject||'') + ' ' + (m.body||'') + ' ' + (m.sender_name||'') + ' ' + (m.sender_email||'')).toLowerCase();
            if (!hay.includes(q)) return;
        }
        const row = document.createElement('div');
        row.className = 'message-row';
        row.setAttribute('data-msg-id', m.id);
        const who = (parseInt(m.sender_id || 0,10) === CURRENT_USER_ID) ? 'Vous' : ((m.sender_name || m.sender_email || ('User '+(m.sender_id||''))) + ' (id:' + (m.sender_id||'') + ')');
        const date = formatDate(m.created_at);
        row.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center"><div><strong>' + (m.subject || 'Sans sujet') + '</strong><div style="font-size:0.95em;color:#444;margin-top:6px">' + (m.body||'') + '</div><div style="font-size:0.8em;color:#888;margin-top:6px">De: ' + who + ' • ' + date + '</div></div></div>';
        const actions = document.createElement('div');
        actions.style.marginTop = '8px';
        const replyBtn = document.createElement('button'); replyBtn.className = 'btn-ghost'; replyBtn.textContent = 'Répondre'; replyBtn.style.marginRight='6px';
        replyBtn.addEventListener('click', function(){
            // open quick form prefilled
            const qm = document.getElementById('quick-message'); if (qm) qm.style.display='block';
            document.getElementById('message-recipient').value = m.sender_id || 1;
            document.getElementById('message-subject').value = 'Re: ' + (m.subject || '');
            document.getElementById('message-body').focus();
        });
        const markBtn = document.createElement('button'); markBtn.className = 'btn-ghost'; markBtn.textContent = m.is_read==0 ? 'Marquer lu' : 'Marquer non-lu';
        markBtn.addEventListener('click', async function(){
            try{
                await fetch((window.APP_BASE||'') + '/index.php?route=api/messages/mark_read', { method:'POST', body: new URLSearchParams({ id: m.id }), credentials:'same-origin' });
                loadMessages();
            }catch(e){ console.error(e); }
        });
        actions.appendChild(replyBtn); actions.appendChild(markBtn);
        row.appendChild(actions);
        container.appendChild(row);
    });
}

function openConversation(key, meId){
    const conv = allMessages.filter(m=>{
        const sid = String(m.sender_id||0);
        const rid = String(m.recipient_id||0);
        let other = (sid === String(meId)) ? rid : sid;
        if (other === '0') other = 'anon:' + (m.sender_email || 'unknown');
        return other === String(key);
    }).sort((a,b)=> new Date(a.created_at)-new Date(b.created_at));
    const header = document.getElementById('conversation-header');
    const thread = document.getElementById('conversation-thread');
    thread.innerHTML = '';
    if (conv.length===0){ header.textContent = 'Conversation vide'; return; }
    const first = conv[0];
    const otherDisplay = (first.sender_id==meId) ? (first.recipient_name || ('User '+first.recipient_id)) : (first.sender_name || first.sender_email || ('User '+first.sender_id));
    header.textContent = 'Conversation — ' + otherDisplay;
    conv.forEach(m=>{
        const isMine = (parseInt(m.sender_id||0,10) === meId);
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-wrap ' + (isMine ? 'right' : 'left');
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble ' + (isMine ? 'admin' : 'user');
        const dateTxt = m.created_at ? (new Date(m.created_at).toLocaleString()) : '';
        const senderLabel = isMine ? 'Vous' : ((m.sender_name || m.sender_email || ('User '+m.sender_id)) + ' (id:' + (m.sender_id||'') + ')');
        bubble.innerHTML = '<div style="font-weight:600;margin-bottom:6px">' + senderLabel + '</div><div>' + (m.body || '') + '</div><span class="ts">' + dateTxt + '</span>';
        wrapper.appendChild(bubble);
        thread.appendChild(wrapper);
    });
    document.getElementById('conversation-send-form').style.display = 'block';
    const orig = conv[0];
    const otherId = (orig.sender_id==meId) ? orig.recipient_id : orig.sender_id;
    document.getElementById('conv-recipient').value = otherId || '1';
    // smooth scroll to bottom
    thread.scrollTo({ top: thread.scrollHeight, behavior: 'smooth' });
    // mark selected conversation visually
    try{
        document.querySelectorAll('#conversations-list .conversation-item').forEach(it=> it.classList.remove('selected'));
        const sel = document.querySelector('#conversations-list .conversation-item[data-conv-key="'+String(key)+'"]');
        if (sel) sel.classList.add('selected');
    }catch(e){/* ignore */}
}

// view toggle handlers
(function(){ const _vc = document.getElementById('view-conversations'); if (_vc) _vc.addEventListener('click', function(){
    const cl = document.getElementById('conversations-list'); if (cl) cl.style.display = 'block';
    const ml = document.getElementById('messages-list'); if (ml) ml.style.display = 'none';
    this.classList.add('active-view'); const _vl = document.getElementById('view-list'); if (_vl) _vl.classList.remove('active-view');
});
})();
(function(){ const _vl2 = document.getElementById('view-list'); if (_vl2) _vl2.addEventListener('click', function(){
    const cl2 = document.getElementById('conversations-list'); if (cl2) cl2.style.display = 'none';
    const ml2 = document.getElementById('messages-list'); if (ml2) ml2.style.display = 'block';
    this.classList.add('active-view'); const _vc2 = document.getElementById('view-conversations'); if (_vc2) _vc2.classList.remove('active-view');
    renderMessagesList(allMessages);
});
})();

// search handler
(function(){ const _ms = document.getElementById('messages-search'); if (!_ms) return; _ms.addEventListener('input', function(){
    const ml = document.getElementById('messages-list'); if (ml && ml.style.display === 'block') renderMessagesList(allMessages); else {
        const meId = parseInt('<?php echo $_SESSION['user_id'] ?? 0; ?>',10); renderConversations(buildConversations(allMessages, meId), meId);
    }
}); })();

// mark all read for visible messages
(function(){ const _mar = document.getElementById('mark-all-read'); if (!_mar) return; _mar.addEventListener('click', async function(){
    const ml = document.getElementById('messages-list');
    const visible = (ml && ml.style.display === 'block');
    let ids = [];
    if (visible) {
        // gather ids from messages-list DOM
        document.querySelectorAll('#messages-list > div').forEach(d=>{ const id = d.getAttribute('data-msg-id'); if (id) ids.push(id); });
    } else {
        // gather from filtered conversations
        const meId = parseInt('<?php echo $_SESSION['user_id'] ?? 0; ?>',10);
        const convs = buildConversations(allMessages, meId);
        Object.values(convs).forEach(arr=> arr.forEach(m=> { if (m.is_read==0) ids.push(m.id); }));
    }
    if (ids.length===0) { alert('Aucun message non lu trouvé'); return; }
    try{
        for (const id of ids) {
            await fetch((window.APP_BASE||'') + '/index.php?route=api/messages/mark_read', { method:'POST', body: new URLSearchParams({ id: id }), credentials: 'same-origin' });
        }
        alert('Marqué comme lu'); loadMessages();
    }catch(e){ console.error(e); alert('Erreur'); }
}); })();

(function(){ const _r = document.getElementById('refresh-messages'); if (_r) _r.addEventListener('click', function(){ loadMessages(); }); })();

(function(){ const _csf = document.getElementById('conversation-send-form'); if (!_csf) return; _csf.addEventListener('submit', async function(ev){
    ev.preventDefault();
    const recip = document.getElementById('conv-recipient').value || '1';
    const subj = document.getElementById('conv-subject').value || '';
    const body = document.getElementById('conv-body').value || '';
    if (!body.trim()) { alert('Entrez un message'); return; }
    const form = new FormData();
    form.append('recipient_id', recip);
    form.append('subject', subj);
    form.append('body', body);
    try{
        const res = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/send', { method: 'POST', body: form, credentials: 'same-origin' });
        const j = await res.json();
        if (j.ok) { document.getElementById('conv-body').value = ''; loadMessages(); } else { alert('Erreur: ' + (j.error || 'unknown')); }
    }catch(err){ console.error(err); alert('Erreur réseau'); }
    // after send, smooth-scroll to latest when thread visible
    setTimeout(()=>{ const thread = document.getElementById('conversation-thread'); if (thread) thread.scrollTo({ top: thread.scrollHeight, behavior: 'smooth' }); }, 300);
}); })();

// Quick new message controls
(function(){ const _n = document.getElementById('new-message-btn'); if (_n) _n.addEventListener('click', function(){ const el = document.getElementById('quick-message'); if (!el) return; el.style.display = (el.style.display === 'block') ? 'none' : 'block'; }); })();
(function(){ const _c = document.getElementById('cancel-quick-message'); if (_c) _c.addEventListener('click', function(){ const el = document.getElementById('quick-message'); if (el) el.style.display='none'; }); })();
(function(){ const _rq = document.getElementById('refresh-messages-quick'); if (_rq) _rq.addEventListener('click', function(){ loadMessages(); }); })();

(function(){ const _msf = document.getElementById('message-send-form'); if (!_msf) return; _msf.addEventListener('submit', async function(ev){
    ev.preventDefault();
    const recip = document.getElementById('message-recipient').value || '1';
    const subj = document.getElementById('message-subject').value || '';
    const body = document.getElementById('message-body').value || '';
    if (!body.trim()) { alert('Entrez un message'); return; }
    const form = new FormData();
    form.append('recipient_id', recip);
    form.append('subject', subj);
    form.append('body', body);
    // include context fields if present
    const _mct = document.getElementById('message-context-type');
    const _mcid = document.getElementById('message-context-id');
    const ctxType = (_mct && _mct.value) || '';
    const ctxId = (_mcid && _mcid.value) || '';
    if (ctxType) form.append('context_type', ctxType);
    if (ctxId) form.append('context_id', ctxId);
    try{
        const res = await fetch((window.APP_BASE || '') + '/index.php?route=api/messages/send', { method: 'POST', body: form, credentials: 'same-origin' });
        const j = await res.json();
        if (j.ok) { document.getElementById('message-subject').value=''; document.getElementById('message-body').value=''; document.getElementById('quick-message').style.display='none'; loadMessages(); alert('Message envoyé'); }
        else { alert('Erreur: ' + (j.error || 'unknown')); }
    }catch(err){ console.error(err); alert('Erreur réseau'); }
}); })();

// conversation-send-form is attached above (duplicate block removed).

// Populate context id selects with playlists and emissions from server-rendered data
function populateContextOptions(){
    // We attempt to fetch lists via simple endpoints: use embedded PHP arrays if available
    try{
        const playlists = <?php try { $pm = new Playlists(); echo json_encode($pm->getAll()); } catch(Throwable $e){ echo '[]'; } ?> || [];
        const emissions = <?php try { $em = new Emissions(); $all = $em->getAll(); $flat = []; foreach($all as $day=>$items){ foreach($items as $it){ $flat[] = $it; } } echo json_encode($flat); } catch(Throwable $e){ echo '[]'; } ?> || [];
        const playlistSelects = [document.getElementById('message-context-id'), document.getElementById('conv-context-id')];
        playlistSelects.forEach(sel=>{ if(!sel) return; sel.innerHTML = '<option value="">Choisir...</option>'; });
        // store options in data-attrs for quick re-use
        window.__contextOptions = { playlists: playlists, emissions: emissions };
    }catch(e){ console.error(e); }
}
populateContextOptions();

// when context type changes, show relevant id select
function onContextTypeChange(prefix){
    const typeEl = document.getElementById(prefix+'-context-type');
    const idEl = document.getElementById(prefix+'-context-id');
    if (!typeEl || !idEl) return;
    const v = typeEl.value;
    if (v === 'playlist'){
        idEl.style.display = 'inline-block';
        idEl.innerHTML = '<option value="">Choisir playlist...</option>' + (window.__contextOptions.playlists || []).map(p=>'<option value="'+(p.id||'')+'">'+(p.title||('Playlist '+(p.id||'')))+'</option>').join('');
    } else if (v === 'emission'){
        idEl.style.display = 'inline-block';
        idEl.innerHTML = '<option value="">Choisir émission...</option>' + (window.__contextOptions.emissions || []).map(e=>'<option value="'+(e.id||'')+'">'+(e.title||('Émission '+(e.id||'')))+'</option>').join('');
    } else {
        idEl.style.display = 'none'; idEl.value = '';
    }
}
(function(){ const __mct = document.getElementById('message-context-type'); if (__mct) __mct.addEventListener('change', function(){ onContextTypeChange('message'); }); })();
(function(){ const __cct = document.getElementById('conv-context-type'); if (__cct) __cct.addEventListener('change', function(){ onContextTypeChange('conv'); }); })();
// Robust tab click handling (delegation) to ensure tabs always switch
try{
    const tabsRoot = document.querySelector('.profile-tabs-enhanced');
    if (tabsRoot) {
        tabsRoot.addEventListener('click', function(ev){
            const btn = ev.target.closest('.tab-button-enhanced');
            if (!btn) return;
            try{
                document.querySelectorAll('.tab-button-enhanced').forEach(b=>b.classList.remove('active'));
                document.querySelectorAll('.tab-content-enhanced').forEach(c=>c.classList.remove('active'));
                btn.classList.add('active');
                const id = btn.dataset.tab;
                const panel = document.getElementById(id);
                if (panel) panel.classList.add('active');
                // if messages tab opened, start polling
                if (id === 'messages') { loadMessages(); startMessagesPoll(); } else { stopMessagesPoll(); }
            }catch(e){ console.error('tab click handler error', e); }
        });
    }
}catch(e){ console.error('tab delegation init error', e); }
</script>

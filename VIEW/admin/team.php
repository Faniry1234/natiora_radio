<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Base URL for assets and internal links (works in subfolders)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

$current_user_name = $_SESSION['user_name'] ?? 'Admin';
$current_user_avatar = $_SESSION['user_avatar'] ?? 'https://ui-avatars.com/api/?name=Admin&background=f5576c';
?>
<?php
// Debug visible only when controlled by DEBUG_ADMIN or DEV_ADMIN constants
if ((defined('DEBUG_ADMIN') && DEBUG_ADMIN) || (defined('DEV_ADMIN') && DEV_ADMIN)) {
    $sess_id = $_SESSION['user_id'] ?? '(none)';
    $sess_role = $_SESSION['user_role'] ?? '(none)';
    $count = is_array($responsables) ? count($responsables) : 0;
    echo "<div style='background:#e2e3e5;color:#383d41;padding:10px;border:1px solid #d6d8db;margin:12px;border-radius:6px;max-width:1200px;'>DEBUG VIEW — session: {$sess_id}/{$sess_role} — responsables: {$count}</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion Responsables</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/fontawesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
            font-size: 16px;
            line-height: 1.5;
        }

        .admin-wrapper {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            width: 100%;
        }

        .admin-sidebar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            width: 260px;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.08);
            z-index: 1000;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4em;
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding-left: 20px;
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }

        .user-card img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 8px;
        }

        .user-card p {
            font-size: 0.9em;
            margin: 0;
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .admin-main {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-topbar {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-topbar h1 {
            color: #333;
            font-size: 1.8em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-actions {
            display: flex;
            gap: 10px;
        }

        .topbar-actions a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .topbar-actions a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* RESPONSABLES CARDS */
        .responsables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .responsable-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .responsable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .responsable-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .responsable-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }

        .responsable-info h3 {
            color: #1f2937;
            font-size: 1.3em;
            margin: 0 0 4px 0;
            font-weight: 700;
        }

        .responsable-role {
            color: #667eea;
            font-weight: 600;
            font-size: 0.95em;
            margin: 0;
        }

        .responsable-contacts {
            margin-top: 16px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #6b7280;
        }

        .contact-item i {
            width: 16px;
            color: #667eea;
        }

        .contact-item a {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-item a:hover {
            color: #667eea;
        }

        .responsable-actions {
            display: flex;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .btn-edit {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-edit:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        /* EDITOR FORM */
        .editor-panel {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-top: 30px;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .editor-header h3 {
            color: #1f2937;
            font-size: 1.4em;
            margin: 0;
        }

        .editor-content {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 24px;
        }

        .editor-avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .editor-avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }

        .editor-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9em;
        }

        .form-group input {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* FLASH MESSAGE */
        .flash-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideInUp 0.4s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flash-message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .flash-message.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .admin-wrapper {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                display: none;
            }

            .responsables-grid {
                grid-template-columns: 1fr;
            }

            .editor-content {
                grid-template-columns: 1fr;
            }

            .editor-avatar-section {
                order: -1;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* DARK THEME OVERRIDES FOR ADMIN AREA */
        :root {
            --bg-1: #0b1020; /* page background */
            --bg-2: #0f1724; /* panels */
            --panel-border: rgba(255,255,255,0.04);
            --muted-text: #9aa4b2;
            --text: #e6eef8;
            --accent: #7c5cff;
            --card-grad: linear-gradient(135deg,#0d1220,#121826);
        }

        body {
            background: var(--bg-1);
            color: var(--text);
        }

        .admin-sidebar {
            background: linear-gradient(180deg,#0b1222,#0d1626);
            color: var(--text);
            box-shadow: none;
        }

        .sidebar-menu a { color: var(--muted-text); }
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.03); color: var(--text); }

        .admin-topbar {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--panel-border);
            box-shadow: 0 2px 10px rgba(2,6,23,0.6);
        }

        .admin-topbar h1 {
            color: var(--text);
        }

        .responsable-card, .editor-panel {
            background: var(--bg-2);
            color: var(--text);
            border: 1px solid var(--panel-border);
        }

        .responsable-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .responsable-info h3 {
            color: var(--text);
        }

        .responsable-role {
            color: var(--accent);
        }

        .contact-item {
            color: var(--muted-text);
        }

        .contact-item a {
            color: var(--muted-text);
        }

        .contact-item a:hover {
            color: var(--accent);
        }

        .btn-edit, .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: var(--muted-text);
            border: 1px solid var(--panel-border);
        }

        .btn-edit:hover, .btn-secondary:hover {
            background: rgba(255,255,255,0.08);
            color: var(--text);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        .editor-header h3 {
            color: var(--text);
        }

        .form-group label {
            color: var(--text);
        }

        .form-group input {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--panel-border);
            color: var(--text);
        }

        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(124, 92, 255, 0.1);
        }

        .form-group input::placeholder {
            color: var(--muted-text);
        }

        .flash-message.success {
            background: rgba(34, 197, 94, 0.1);
            color: #86efac;
            border-left-color: #22c55e;
        }

        .flash-message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border-left-color: #ef4444;
        }

        .empty-state {
            color: var(--muted-text);
        }

        .empty-state h3 {
            color: var(--text);
        }

        .user-card {
            background: rgba(255,255,255,0.03);
        }

        .user-card p {
            color: var(--muted-text);
        }

        .footer-link {
            color: var(--muted-text);
        }

        .footer-link:hover {
            color: var(--text);
        }

        /* ensure scrollbar is subtle on dark background */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.02);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.06);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- SIDEBAR -->
        <aside class="admin-sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-radio"></i>
                Natiora Radio
            </div>

            <ul class="sidebar-menu">
                <li><a href="<?php echo $base; ?>/index.php?route=admin"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="<?php echo $base; ?>/index.php?route=admin/emissions"><i class="fas fa-video"></i> Émissions</a></li>
                <li><a href="<?php echo $base; ?>/index.php?route=admin/playlists"><i class="fas fa-compact-disc"></i> Playlistes</a></li>
                <li><a href="<?php echo $base; ?>/index.php?route=admin/team" class="active"><i class="fas fa-users"></i> Responsables</a></li>
                <li><a href="<?php echo $base; ?>/index.php?route=admin/messages"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="<?php echo $base; ?>/index.php?route=admin/historiques"><i class="fas fa-history"></i> Historiques</a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="user-card">
                    <img src="<?php echo htmlspecialchars($current_user_avatar); ?>" alt="Avatar">
                    <p><?php echo htmlspecialchars($current_user_name); ?></p>
                </div>
                <a href="<?php echo $base; ?>/index.php?route=auth/logout" class="footer-link">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <!-- TOPBAR -->
            <div class="admin-topbar">
                <h1><i class="fas fa-users"></i> Gestion des Responsables</h1>
                <div class="topbar-actions">
                    <button id="addBtn" class="btn-primary"><i class="fas fa-plus"></i> Ajouter un responsable</button>
                    <a href="<?php echo $base; ?>/index.php?route=admin"><i class="fas fa-arrow-left"></i> Retour au dashboard</a>
                </div>
            </div>

            <!-- FLASH MESSAGE -->
            <div id="saveStatus" class="flash-message success" style="display: none;">
                <i class="fas fa-check-circle"></i>
                Modifications enregistrées avec succès !
            </div>

            <!-- RESPONSABLES GRID -->
            <div id="teamContainer" class="responsables-grid">
                <!-- Les responsables seront affichés ici via JavaScript -->
            </div>

            <!-- EDITOR FORM -->
            <div id="editor" class="editor-panel" style="display: none;">
                <div class="editor-header">
                    <h3 id="editorTitle">Nouveau responsable</h3>
                    <button id="editorCancel" class="btn-secondary"><i class="fas fa-times"></i> Annuler</button>
                </div>

                <div class="editor-content">
                    <div class="editor-avatar-section">
                        <img id="editorImgPreview" src="<?php echo htmlspecialchars($base . '/public/assets/images/contact.jpg'); ?>" alt="preview" class="editor-avatar-preview">
                        <small style="color: #6b7280; text-align: center;">Aperçu de l'image</small>
                    </div>

                    <div class="editor-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="f_name"><i class="fas fa-user"></i> Nom complet *</label>
                                <input id="f_name" placeholder="Ex: Rakoto Andry" required>
                            </div>
                            <div class="form-group">
                                <label for="f_role"><i class="fas fa-briefcase"></i> Rôle *</label>
                                <input id="f_role" placeholder="Ex: Responsable programmation" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="f_phone"><i class="fas fa-phone"></i> Téléphone</label>
                                <input id="f_phone" placeholder="Ex: +261341234567" type="tel">
                            </div>
                            <div class="form-group">
                                <label for="f_whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</label>
                                <input id="f_whatsapp" placeholder="Ex: 261341234567">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="f_email"><i class="fas fa-envelope"></i> Email</label>
                            <input id="f_email" placeholder="Ex: responsable@natiora.mg" type="email">
                        </div>

                        <div class="form-group">
                            <label for="f_img"><i class="fas fa-image"></i> Image (URL ou upload)</label>
                            <input id="f_img" placeholder="Ex: /assets/images/photo.jpg" style="margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <input type="file" id="f_img_upload" accept="image/*" style="flex: 1;">
                                <button type="button" id="uploadBtn" class="btn-secondary" style="padding: 8px 16px;"><i class="fas fa-upload"></i> Uploader</button>
                            </div>
                            <small style="color: var(--muted-text); font-size: 0.85em;">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                        </div>

                        <div class="form-actions">
                            <button id="editorSave" class="btn-primary"><i class="fas fa-save"></i> Sauvegarder</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initial data from PHP
        var responsables = <?php echo json_encode($responsables ?? [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
        var base = window.APP_BASE || '<?php echo htmlspecialchars($base); ?>';
        var editingIndex = -1;

        function renderList(){
            var container = document.getElementById('teamContainer');
            container.innerHTML = '';

            if (responsables.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>Aucun responsable défini</h3>
                        <p>Cliquez sur "Ajouter un responsable" pour commencer.</p>
                    </div>
                `;
                return;
            }

            responsables.forEach(function(r, idx){
                var card = document.createElement('div');
                card.className = 'responsable-card';

                var avatar = r.img || (base + '/public/assets/images/contact.jpg');
                var email = r.email || '';

                card.innerHTML = `
                    <div class="responsable-header">
                        <img src="${avatar}" alt="${r.name || ''}" class="responsable-avatar">
                        <div class="responsable-info">
                            <h3>${r.name || 'Nom non défini'}</h3>
                            <p class="responsable-role">${r.role || 'Rôle non défini'}</p>
                        </div>
                    </div>

                    <div class="responsable-contacts">
                        ${r.phone ? `<div class="contact-item"><i class="fas fa-phone"></i> <a href="tel:${r.phone}">${r.phone}</a></div>` : ''}
                        ${r.whatsapp ? `<div class="contact-item"><i class="fab fa-whatsapp"></i> <a href="https://wa.me/${r.whatsapp}" target="_blank" rel="noopener">WhatsApp</a></div>` : ''}
                        ${email ? `<div class="contact-item"><i class="fas fa-envelope"></i> <a href="mailto:${email}">${email}</a></div>` : ''}

                    </div>

                    <div class="responsable-actions">
                        <button class="btn-edit" onclick="openEditor(${idx})"><i class="fas fa-edit"></i> Modifier</button>
                        <button class="btn-delete" onclick="deleteResponsable(${idx})"><i class="fas fa-trash"></i> Supprimer</button>
                    </div>
                `;

                container.appendChild(card);
            });
        }

        function openEditor(idx){
            editingIndex = (typeof idx === 'number') ? idx : -1;
            var editor = document.getElementById('editor');
            var title = document.getElementById('editorTitle');
            var preview = document.getElementById('editorImgPreview');

            if (editingIndex >= 0){
                title.textContent = 'Modifier le responsable';
                var r = responsables[editingIndex] || {};
                document.getElementById('f_name').value = r.name || '';
                document.getElementById('f_role').value = r.role || '';
                document.getElementById('f_phone').value = r.phone || '';
                document.getElementById('f_whatsapp').value = r.whatsapp || '';
                document.getElementById('f_email').value = r.email || '';
                document.getElementById('f_img').value = r.img || '';
                preview.src = r.img || (base + '/public/assets/images/contact.jpg');
            } else {
                title.textContent = 'Nouveau responsable';
                document.getElementById('f_name').value = '';
                document.getElementById('f_role').value = '';
                document.getElementById('f_phone').value = '';
                document.getElementById('f_whatsapp').value = '';
                document.getElementById('f_email').value = '';
                document.getElementById('f_img').value = '';
                preview.src = base + '/public/assets/images/contact.jpg';
            }

            editor.style.display = 'block';
            editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function deleteResponsable(idx){
            if (confirm('Êtes-vous sûr de vouloir supprimer ce responsable ? Cette action est irréversible.')){
                responsables.splice(idx, 1);
                renderList();
                saveToServer();
            }
        }

        document.getElementById('addBtn').addEventListener('click', function(){ openEditor(-1); });
        document.getElementById('editorCancel').addEventListener('click', function(){
            document.getElementById('editor').style.display = 'none';
        });

        document.getElementById('f_img').addEventListener('input', function(e){
            var v = e.target.value;
            document.getElementById('editorImgPreview').src = v || (base + '/public/assets/images/contact.jpg');
        });

        // Image upload functionality
        document.getElementById('uploadBtn').addEventListener('click', function(){
            var fileInput = document.getElementById('f_img_upload');
            var file = fileInput.files[0];

            if (!file) {
                alert('Veuillez sélectionner une image à uploader.');
                return;
            }

            // Validate file type
            if (!file.type.match('image.*')) {
                alert('Veuillez sélectionner un fichier image valide.');
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('L\'image ne doit pas dépasser 2MB.');
                return;
            }

            var formData = new FormData();
            formData.append('image', file);

            // Show loading state
            var btn = document.getElementById('uploadBtn');
            var originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Upload...';
            btn.disabled = true;

            fetch((window.APP_BASE || '') + '/index.php?route=admin/upload_image', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(j => {
                if (j && j.success && j.path) {
                    document.getElementById('f_img').value = j.path;
                    document.getElementById('editorImgPreview').src = j.path;
                    alert('Image uploadée avec succès !');
                } else {
                    alert('Erreur lors de l\'upload: ' + (j && j.error ? j.error : 'Erreur inconnue'));
                }
            })
            .catch(e => {
                alert('Erreur lors de l\'upload de l\'image');
                console.error(e);
            })
            .finally(() => {
                // Reset button state
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });

        document.getElementById('editorSave').addEventListener('click', function(){
            var name = document.getElementById('f_name').value.trim();
            var role = document.getElementById('f_role').value.trim();

            if (!name) {
                alert('Le nom est obligatoire.');
                document.getElementById('f_name').focus();
                return;
            }

            if (!role) {
                alert('Le rôle est obligatoire.');
                document.getElementById('f_role').focus();
                return;
            }

            var r = {
                name: name,
                role: role,
                phone: document.getElementById('f_phone').value.trim(),
                whatsapp: document.getElementById('f_whatsapp').value.trim(),
                email: document.getElementById('f_email').value.trim(),
                img: document.getElementById('f_img').value.trim()
            };

            if (editingIndex >= 0){
                responsables[editingIndex] = r;
            } else {
                responsables.push(r);
            }

            document.getElementById('editor').style.display = 'none';
            renderList();
            saveToServer();
        });

        function saveToServer(){
            var status = document.getElementById('saveStatus');
            status.style.display = 'none';

            fetch((window.APP_BASE || '') + '/index.php?route=admin/team', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(responsables)
            })
            .then(r => r.json())
            .then(j => {
                if (j && j.ok) {
                    status.style.display = 'block';
                    setTimeout(function(){ status.style.display = 'none'; }, 3000);
                } else {
                    showError('Échec de la sauvegarde: ' + (j && j.error ? j.error : 'Erreur inconnue'));
                }
            })
            .catch(e => {
                showError('Erreur lors de la sauvegarde');
                console.error(e);
            });
        }

        function showError(message){
            var status = document.getElementById('saveStatus');
            status.className = 'flash-message error';
            status.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
            status.style.display = 'block';
            setTimeout(function(){ status.style.display = 'none'; }, 5000);
        }

        // Initial render
        renderList();
    </script>
</body>
</html>

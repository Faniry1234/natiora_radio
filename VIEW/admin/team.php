<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base = $base ?? '';
$escapedBase = htmlspecialchars($base);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin — Responsables</title>
    <link rel="stylesheet" href="<?php echo $escapedBase; ?>/public/assets/css/style.css">
    <style>
        .team-list{display:flex;flex-direction:column;gap:10px;margin-top:12px}
        .team-row{display:flex;align-items:center;gap:12px;padding:10px;border:1px solid #eee;border-radius:6px}
        .team-row img{width:64px;height:64px;object-fit:cover;border-radius:8px}
        .team-meta{flex:1}
        .team-actions{display:flex;gap:6px}
        .form-row{display:flex;gap:8px;flex-wrap:wrap}
        .form-row input{flex:1;min-width:180px}
        .small{width:140px}
    </style>
</head>
<body>
    <div style="max-width:1100px;margin:24px auto;padding:18px;background:#fff;border-radius:8px;">
        <h2>Gestion des responsables</h2>
        <p>Ajoutez, modifiez ou supprimez des responsables (nom, rôle, contacts, réseaux, image).</p>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <div>
                <button id="addBtn" class="btn-primary" type="button">Ajouter un responsable</button>
                <a href="<?php echo $escapedBase; ?>/index.php?route=admin" class="btn-ghost">Retour</a>
            </div>
            <div id="saveStatus" style="color:green;display:none">Enregistré.</div>
        </div>

        <div id="teamContainer" class="team-list"></div>

        <hr style="margin:18px 0">

        <div id="editor" style="display:none;margin-top:12px;">
            <h3 id="editorTitle">Nouveau responsable</h3>
            <div style="display:flex;gap:12px;align-items:flex-start;">
                <img id="editorImgPreview" src="<?php echo htmlspecialchars($base . '/public/assets/images/contact.jpg'); ?>" alt="preview" style="width:120px;height:120px;object-fit:cover;border-radius:8px">
                <div style="flex:1">
                    <div class="form-row">
                        <input id="f_name" placeholder="Nom" />
                        <input id="f_role" placeholder="Rôle" />
                    </div>
                    <div class="form-row" style="margin-top:8px">
                        <input id="f_phone" placeholder="Téléphone" class="small" />
                        <input id="f_whatsapp" placeholder="WhatsApp" class="small" />
                        <input id="f_facebook" placeholder="Facebook (URL)" />
                    </div>
                    <div class="form-row" style="margin-top:8px">
                        <input id="f_instagram" placeholder="Instagram (URL)" />
                        <input id="f_img" placeholder="Image (chemin ou URL)" />
                    </div>
                    <div style="margin-top:10px;display:flex;gap:8px;">
                        <button id="editorSave" class="btn-primary" type="button">Sauvegarder</button>
                        <button id="editorCancel" class="btn-ghost" type="button">Annuler</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initial data from PHP
        var responsables = <?php echo json_encode($responsables ?? [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
        var base = window.APP_BASE || '<?php echo $escapedBase; ?>';
        var editingIndex = -1;

        function renderList(){
            var container = document.getElementById('teamContainer');
            container.innerHTML = '';
            responsables.forEach(function(r, idx){
                var row = document.createElement('div'); row.className='team-row';
                var img = document.createElement('img'); img.src = r.img || (base + '/public/assets/images/contact.jpg');
                var meta = document.createElement('div'); meta.className='team-meta';
                var name = document.createElement('div'); name.style.fontWeight='700'; name.textContent = r.name || '';
                var role = document.createElement('div'); role.style.opacity='0.9'; role.textContent = r.role || '';
                var contacts = document.createElement('div'); contacts.style.marginTop='6px';
                if (r.phone) contacts.innerHTML += '<a href="tel:'+r.phone+'">'+r.phone+'</a> &nbsp;';
                if (r.whatsapp) contacts.innerHTML += '<a href="https://wa.me/'+r.whatsapp+'" target="_blank" rel="noopener">WhatsApp</a>';
                var socials = document.createElement('div'); socials.style.marginTop='6px';
                if (r.facebook) socials.innerHTML += '<a href="'+r.facebook+'" target="_blank" rel="noopener">Facebook</a> ';
                if (r.instagram) socials.innerHTML += '<a href="'+r.instagram+'" target="_blank" rel="noopener">Instagram</a>';
                meta.appendChild(name); meta.appendChild(role); meta.appendChild(contacts); meta.appendChild(socials);
                var actions = document.createElement('div'); actions.className='team-actions';
                var edit = document.createElement('button'); edit.className='btn-ghost'; edit.textContent='Modifier'; edit.addEventListener('click', function(){ openEditor(idx); });
                var del = document.createElement('button'); del.className='btn-danger'; del.textContent='Supprimer'; del.addEventListener('click', function(){ if (confirm('Supprimer ce responsable ?')){ responsables.splice(idx,1); saveToServer(); } });
                actions.appendChild(edit); actions.appendChild(del);
                row.appendChild(img); row.appendChild(meta); row.appendChild(actions);
                container.appendChild(row);
            });
            if (responsables.length===0){ container.innerHTML = '<div style="padding:12px;color:#666">Aucun responsable défini.</div>'; }
        }

        function openEditor(idx){
            editingIndex = (typeof idx === 'number') ? idx : -1;
            var editor = document.getElementById('editor');
            var title = document.getElementById('editorTitle');
            var preview = document.getElementById('editorImgPreview');
            if (editingIndex >= 0){
                title.textContent = 'Modifier responsable';
                var r = responsables[editingIndex] || {};
                document.getElementById('f_name').value = r.name||'';
                document.getElementById('f_role').value = r.role||'';
                document.getElementById('f_phone').value = r.phone||'';
                document.getElementById('f_whatsapp').value = r.whatsapp||'';
                document.getElementById('f_facebook').value = r.facebook||'';
                document.getElementById('f_instagram').value = r.instagram||'';
                document.getElementById('f_img').value = r.img||'';
                preview.src = r.img || (base + '/public/assets/images/contact.jpg');
            } else {
                title.textContent = 'Nouveau responsable';
                document.getElementById('f_name').value = '';
                document.getElementById('f_role').value = '';
                document.getElementById('f_phone').value = '';
                document.getElementById('f_whatsapp').value = '';
                document.getElementById('f_facebook').value = '';
                document.getElementById('f_instagram').value = '';
                document.getElementById('f_img').value = '';
                preview.src = base + '/public/assets/images/contact.jpg';
            }
            editor.style.display = 'block';
            window.scrollTo({ top: editor.offsetTop - 20, behavior: 'smooth' });
        }

        document.getElementById('addBtn').addEventListener('click', function(){ openEditor(-1); });
        document.getElementById('editorCancel').addEventListener('click', function(){ document.getElementById('editor').style.display='none'; });

        document.getElementById('f_img').addEventListener('input', function(e){ var v = e.target.value; document.getElementById('editorImgPreview').src = v || (base + '/public/assets/images/contact.jpg'); });

        document.getElementById('editorSave').addEventListener('click', function(){
            var r = {
                name: document.getElementById('f_name').value.trim(),
                role: document.getElementById('f_role').value.trim(),
                phone: document.getElementById('f_phone').value.trim(),
                whatsapp: document.getElementById('f_whatsapp').value.trim(),
                facebook: document.getElementById('f_facebook').value.trim(),
                instagram: document.getElementById('f_instagram').value.trim(),
                img: document.getElementById('f_img').value.trim()
            };
            if (!r.name) { alert('Le nom est requis.'); return; }
            if (editingIndex >= 0){ responsables[editingIndex] = r; } else { responsables.push(r); }
            document.getElementById('editor').style.display='none';
            renderList();
            saveToServer();
        });

        function saveToServer(){
            var status = document.getElementById('saveStatus'); status.style.display='none';
            fetch((window.APP_BASE||'') + '/index.php?route=admin/team', { method:'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(responsables) })
            .then(r=>r.json()).then(j=>{
                if (j && j.ok) { status.style.display='block'; setTimeout(function(){ status.style.display='none'; },1200); }
                else { alert('Échec sauvegarde: ' + (j && j.error ? j.error : 'unknown')); }
            }).catch(e=>{ alert('Erreur lors de la sauvegarde'); console.error(e); });
        }

        // Initial render
        renderList();
    </script>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base = $base ?? '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin — Responsables</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/public/assets/css/style.css">
    <style>textarea{width:100%;height:360px;font-family:monospace;padding:12px;}</style>
</head>
<body>
    <div style="max-width:1100px;margin:24px auto;padding:18px;background:#fff;border-radius:8px;">
        <h2>Modifier la liste des responsables</h2>
        <p>Éditez le tableau JSON ci-dessous puis cliquez sur <strong>Enregistrer</strong>. Le format est un tableau d'objets avec les clés : <code>name, role, phone, whatsapp, facebook, instagram, img</code>.</p>
        <form id="teamForm">
            <textarea id="teamData"><?php echo htmlspecialchars(json_encode($responsables, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); ?></textarea>
            <div style="margin-top:12px;display:flex;gap:8px;">
                <button id="saveBtn" class="btn-primary" type="button">Enregistrer</button>
                <a href="<?php echo $base; ?>/index.php?route=admin" class="btn-ghost">Retour</a>
            </div>
            <div id="saveResult" style="margin-top:12px;color:green;display:none"></div>
        </form>
    </div>
    <script>
        document.getElementById('saveBtn').addEventListener('click', function(){
            var txt = document.getElementById('teamData').value;
            try { var parsed = JSON.parse(txt); } catch(e){ alert('JSON invalide: ' + e.message); return; }
            fetch((window.APP_BASE||'') + '/index.php?route=admin/team', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(parsed) }).then(r=>r.json()).then(j=>{
                if (j && j.ok) { document.getElementById('saveResult').style.display='block'; document.getElementById('saveResult').textContent='Enregistré.'; setTimeout(function(){ location.reload(); },600); }
                else { alert('Save failed: ' + (j && j.error ? j.error : 'unknown')); }
            }).catch(e=>{ alert('Save error'); console.error(e); });
        });
    </script>
</body>
</html>

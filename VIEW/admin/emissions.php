<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Base URL for assets and internal links (works in subfolders)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\') $base = '';

$days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
$levels = ['Débutant', 'Intermédiaire', 'Avancé'];
$categories = ['Design Graphique', 'Effets Visuels', 'Retouche Photo', 'Montage', 'Spécial'];
$selectedDay = $_POST['day'] ?? $_GET['day'] ?? 'lundi';
// Défensive: s'assurer que la variable fournie par le contrôleur est bien un tableau
if (!isset($emissions) || !is_array($emissions)) {
    $emissions = [];
}
// Debug visible only when controlled by DEBUG_ADMIN or DEV_ADMIN constants
if ((defined('DEBUG_ADMIN') && DEBUG_ADMIN) || (defined('DEV_ADMIN') && DEV_ADMIN)) {
    $sess_id = $_SESSION['user_id'] ?? '(none)';
    $sess_role = $_SESSION['user_role'] ?? '(none)';
    $days_count = is_array($emissions) ? count(array_filter($emissions)) : 0;
    $total = 0;
    if (is_array($emissions)) { foreach ($emissions as $d) { if (is_array($d)) $total += count($d); } }
    echo "<div style='background:#fff3cd;color:#856404;padding:10px;border:1px solid #ffeeba;margin:12px;border-radius:6px;max-width:1200px;'>DEBUG VIEW — session: {$sess_id}/{$sess_role} — days with data: {$days_count} — total emissions: {$total}</div>";
        if ($debugMode) {
            echo "<div style='max-width:1200px;margin:12px;padding:12px;background:#fff;border:1px solid #ccc;'>";
            echo "<strong>VIEW DEBUG</strong> — users=" . count($users) . ", playlists=" . count($recent_playlists) . ", recent_emissions=" . count($recent_emissions);
            echo "</div>";

            echo "<div style='max-width:1200px;margin:12px;padding:12px;background:#f7f7f7;border:1px solid #ddd;border-radius:6px;color:#111;'>";
            echo "<strong>RAW DATA DUMP (temporary)</strong><pre style='white-space:pre-wrap;font-size:13px;color:#222;'>";
            echo "emissions (summary): " . htmlspecialchars(json_encode(array_map(function($d){ return is_array($d) ? count($d) : 0; }, $emissions ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "\n\n";
            echo "selectedDay: " . htmlspecialchars($selectedDay ?? '') . "\n\n";
            echo "dayEmissions sample: " . htmlspecialchars(json_encode(array_slice($emissions[$selectedDay] ?? [],0,5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "</pre></div>";
        }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion Émissions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/PUBLIC/assets/css/style.css">
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
            border-radius: 6px;
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

        .admin-main {
            margin-left: 0;
            padding: 30px;
            width: 100%;
            box-sizing: border-box;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .admin-header h1 {
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8em;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        /* TABS */
        .tabs-container {
            background: white;
            border-radius: 10px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 2px solid #e0e0e0;
            padding: 0;
            margin: 0;
        }

        .tab-btn {
            flex: 1;
            min-width: 100px;
            padding: 15px 20px;
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            text-transform: capitalize;
            font-size: 0.95em;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
        }

        .form-section, .list-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            color: #333;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .emission-card {
            background: linear-gradient(135deg, #667eea15, #f5576c15);
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .emission-card:hover {
            box-shadow: 0 4px 12px rgba(5, 248, 25, 0.15);
            transform: translateX(5px);
        }

        .emission-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 15px;
            margin-bottom: 10px;
        }

        .emission-info {
            flex: 1;
        }

        .emission-title {
            color: #333;
            font-weight: 600;
            margin: 0;
            font-size: 1.05em;
        }

        .emission-meta {
            color: #666;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .emission-badges {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 0.75em;
            color: #666;
        }

        .emission-actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.85em;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
        }

        .btn-edit:hover {
            background: #ffb300;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #da190b;
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 2.5em;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        .flash-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInUp 0.4s ease-out;
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

        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .admin-wrapper {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 15px;
            }

            .admin-main {
                margin-left: 0;
                padding: 15px;
            }

            .tabs {
                overflow-x: auto;
            }

            .tab-btn {
                min-width: 80px;
                padding: 12px 15px;
                font-size: 0.85em;
            }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-crown"></i>
            <span>NATIORA</span>
        </div>

        <ul class="sidebar-menu">
            <li><a href="<?php echo $base; ?>/index.php?route=admin"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/emissions" class="active"><i class="fas fa-play-circle"></i> Émissions</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/playlists"><i class="fas fa-music"></i> Playlistes</a></li>
            <li><a href="<?php echo $base; ?>/index.php?route=admin/historiques"><i class="fas fa-history"></i> Historiques</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main">
        <!-- HEADER -->
        <div class="admin-header">
            <h1><i class="fas fa-video"></i> Gestion des Émissions</h1>
            <a href="<?php echo $base; ?>/index.php?route=admin" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>

        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash-message <?php echo $_SESSION['flash']['type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['flash']['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $_SESSION['flash']['msg']; ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-btn <?php echo !isset($_GET['view']) ? 'active' : ''; ?>" onclick="switchView('manage')">
                    <i class="fas fa-tools"></i> Gérer
                </button>
                <button class="tab-btn <?php echo isset($_GET['view']) && $_GET['view'] === 'list' ? 'active' : ''; ?>" onclick="switchView('list')">
                    <i class="fas fa-eye"></i> Voir tout
                </button>
            </div>
        </div>

        <?php if (!isset($_GET['view']) || $_GET['view'] !== 'list'): // MODE GESTION ?>
        <!-- DAYS TABS -->
        <div class="tabs-container">
            <div class="tabs">
                <?php foreach ($days as $day): ?>
                    <button class="tab-btn <?php echo $selectedDay === $day ? 'active' : ''; ?>" onclick="switchDay('<?php echo $day; ?>')">
                        <i class="fas fa-calendar"></i> <?php echo ucfirst($day); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CONTENT GRID -->
        <div class="content-grid">
            <!-- FORM SECTION -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-plus-circle"></i> <span id="form-title-label">Nouvelle Émission</span></h2>
                <form method="POST" action="<?php echo $base; ?>/index.php?route=admin/emissions&day=<?php echo htmlspecialchars($selectedDay); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="day" id="form-day-hidden" value="<?php echo htmlspecialchars($selectedDay); ?>">
                    <input type="hidden" name="index" id="form-index" value="">

                    <div class="form-group">
                        <label>Jour</label>
                        <select name="day" id="form-day" required onchange="document.getElementById('form-day-hidden').value = this.value; document.forms[0].action = '<?php echo $base; ?>/index.php?route=admin/emissions&day=' + this.value;">
                            <?php foreach ($days as $day): ?>
                                <option value="<?php echo $day; ?>" <?php echo $selectedDay === $day ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($day); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Heure</label>
                        <input type="time" id="form-time" name="time" required>
                    </div>

                    <div class="form-group">
                        <label>Titre <span style="color: red;">*</span></label>
                        <input type="text" id="form-title" name="title" placeholder="Ex: Concevoir une affiche" required>
                    </div>

                    <div class="form-group">
                        <label>Présentateur</label>
                        <input type="text" id="form-presenter" name="presenter" placeholder="Ex: Tuto Boy">
                    </div>

                    <div class="form-group">
                        <label>Durée</label>
                        <input type="text" id="form-duration" name="duration" placeholder="Ex: 45 min">
                    </div>

                    <!-- Niveau et Catégorie retirés du formulaire d'ajout/édition (gérés ailleurs si nécessaire) -->

                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="form-desc" name="desc" placeholder="Description de l'émission"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Vidéo</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                            <div>
                                <label style="font-size: 0.85em; color: #666; display: block; margin-bottom: 6px;">
                                    <input type="radio" name="video-type" value="url" checked onchange="toggleVideoInput()"> URL (Lien internet)
                                </label>
                                <input type="text" id="src-url" name="src-url" placeholder="https://..." style="width: 100%; display: block;">
                            </div>
                            <div>
                                <label style="font-size: 0.85em; color: #666; display: block; margin-bottom: 6px;">
                                    <input type="radio" name="video-type" value="file" onchange="toggleVideoInput()"> Upload local
                                </label>
                                <input type="file" id="src-file" name="src-file" accept="video/*" style="width: 100%; display: none;">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> <span id="btn-label">Créer Émission</span></button>
                </form>
            </div>

            <!-- LIST SECTION -->
            <div class="list-section">
                <h2 class="section-title"><i class="fas fa-list"></i> Émissions du <?php echo ucfirst($selectedDay); ?></h2>
                <div style="margin-top:10px;margin-bottom:14px;">
                    <button class="btn-submit" type="button" onclick="saveAllEmissions()">💾 Enregistrer les émissions</button>
                </div>
                
                <?php 
                $dayEmissions = $emissions[$selectedDay] ?? [];
                if (empty($dayEmissions)): 
                ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Aucune émission programmée</p>
                        <small style="color: #bbb;">Créez une nouvelle émission pour ce jour</small>
                    </div>
                <?php else: ?>
                        <?php foreach ($dayEmissions as $index => $emission): ?>
                            <div class="emission-card" data-day="<?php echo htmlspecialchars($selectedDay); ?>" data-index="<?php echo $index; ?>" data-time="<?php echo htmlspecialchars($emission['time'] ?? ''); ?>" data-title="<?php echo htmlspecialchars($emission['title'] ?? $emission['name'] ?? ''); ?>" data-presenter="<?php echo htmlspecialchars($emission['presenter'] ?? ''); ?>" data-duration="<?php echo htmlspecialchars($emission['duration'] ?? ''); ?>" data-level="<?php echo htmlspecialchars($emission['level'] ?? ''); ?>" data-category="<?php echo htmlspecialchars($emission['category'] ?? ''); ?>" data-desc="<?php echo htmlspecialchars($emission['desc'] ?? $emission['description'] ?? ''); ?>" data-src="<?php echo htmlspecialchars($emission['src'] ?? ''); ?>">
                            <div class="emission-header">
                                <div class="emission-info">
                                    <h3 class="emission-title">
                                        <i class="fas fa-play"></i> <?php echo htmlspecialchars($emission['title'] ?? $emission['name'] ?? 'N/A'); ?>
                                    </h3>
                                    <div class="emission-meta">
                                        <strong><?php echo htmlspecialchars($emission['time'] ?? 'N/A'); ?></strong> • 
                                        <?php echo htmlspecialchars($emission['presenter'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="emission-badges">
                                        <?php if (!empty($emission['duration'])): ?>
                                            <span class="badge"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($emission['duration']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($emission['level'])): ?>
                                            <span class="badge"><i class="fas fa-signal"></i> <?php echo htmlspecialchars($emission['level']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($emission['category'])): ?>
                                            <span class="badge"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($emission['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                        <?php if (!empty($emission['src'])): ?>
                                            <div style="margin-top:10px;">
                                                <video controls width="100%" style="max-width:420px; border-radius:8px;">
                                                    <source src="<?php echo htmlspecialchars($emission['src']); ?>" type="video/mp4">
                                                    Votre navigateur ne supporte pas la vidéo.
                                                </video>
                                            </div>
                                        <?php endif; ?>
                                </div>
                                <div class="emission-actions">
                                    <button class="btn-action btn-edit" onclick="editEmission(<?php echo $index; ?>, '<?php echo htmlspecialchars($selectedDay); ?>')">
                                        <i class="fas fa-edit"></i> Éditer
                                    </button>
                                    <button class="btn-action" type="button" onclick="uploadForEmission(this)">
                                        <i class="fas fa-upload"></i> Associer audio
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette émission ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="day" value="<?php echo htmlspecialchars($selectedDay); ?>">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i> Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; // FIN MODE GESTION ?>

        <?php if (isset($_GET['view']) && $_GET['view'] === 'list'): // MODE VUE ?>
        <!-- ALL EMISSIONS VIEW -->
        <div class="list-section" style="max-width: 100%;">
            <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Toutes les Émissions</h2>
            
            <?php 
            $allEmissionsExist = false;
            foreach ($emissions as $day => $dayItems) {
                if (!empty($dayItems)) {
                    $allEmissionsExist = true;
                    break;
                }
            }
            
            if (!$allEmissionsExist): 
            ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Aucune émission programmée</p>
                </div>
            <?php else: ?>
                <style>
                    .day-group {
                        margin-bottom: 30px;
                    }

                    .day-group-title {
                        background: linear-gradient(135deg, #667eea, #764ba2);
                        color: white;
                        padding: 12px 16px;
                        border-radius: 6px;
                        margin-bottom: 15px;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        font-size: 1.1em;
                    }

                    .day-group-title i {
                        font-size: 1.2em;
                    }

                    .emissions-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                        gap: 15px;
                    }

                    .emission-card-full {
                        background: white;
                        border-left: 4px solid #667eea;
                        padding: 16px;
                        border-radius: 8px;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                        transition: all 0.3s ease;
                        display: flex;
                        flex-direction: column;
                    }

                    .emission-card-full:hover {
                        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.2);
                        transform: translateY(-2px);
                    }

                    .emission-time-badge {
                        display: inline-block;
                        background: #667eea;
                        color: white;
                        padding: 6px 12px;
                        border-radius: 12px;
                        font-weight: 600;
                        font-size: 0.9em;
                        margin-bottom: 10px;
                        width: fit-content;
                    }

                    .emission-title-view {
                        color: #333;
                        font-weight: 600;
                        font-size: 1.05em;
                        margin: 10px 0;
                    }

                    .emission-presenter-view {
                        color: #666;
                        font-size: 0.9em;
                        margin: 8px 0;
                    }

                    .emission-desc-view {
                        color: #777;
                        font-size: 0.85em;
                        line-height: 1.5;
                        margin: 10px 0;
                        flex-grow: 1;
                    }

                    .emission-badges-view {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                        margin-top: 12px;
                        padding-top: 12px;
                        border-top: 1px solid #eee;
                    }

                    .badge-view {
                        display: inline-block;
                        padding: 4px 10px;
                        background: #f5f5f5;
                        border: 1px solid #ddd;
                        border-radius: 12px;
                        font-size: 0.75em;
                        color: #666;
                    }
                </style>

                <?php foreach ($days as $day): ?>
                    <?php 
                    $dayEmissionsView = $emissions[$day] ?? [];
                    if (empty($dayEmissionsView)) continue;
                    ?>
                    <div class="day-group">
                        <div class="day-group-title">
                            <i class="fas fa-calendar"></i>
                            <?php echo ucfirst($day); ?>
                        </div>
                        <div class="emissions-grid">
                            <?php foreach ($dayEmissionsView as $emission): ?>
                                <div class="emission-card-full">
                                    <div class="emission-time-badge">
                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($emission['time'] ?? ''); ?>
                                    </div>
                                    <h3 class="emission-title-view">
                                        <i class="fas fa-play-circle"></i> <?php echo htmlspecialchars($emission['title'] ?? $emission['name'] ?? 'N/A'); ?>
                                    </h3>
                                    <?php if (!empty($emission['presenter'])): ?>
                                        <p class="emission-presenter-view">
                                            <strong>Présentateur:</strong> <?php echo htmlspecialchars($emission['presenter']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($emission['description'])): ?>
                                        <p class="emission-desc-view">
                                            <?php echo htmlspecialchars($emission['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="emission-badges-view">
                                        <?php if (!empty($emission['duration'])): ?>
                                            <span class="badge-view"><i class="fas fa-hourglass"></i> <?php echo htmlspecialchars($emission['duration']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($emission['level'])): ?>
                                            <span class="badge-view"><i class="fas fa-chart-line"></i> <?php echo htmlspecialchars($emission['level']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($emission['category'])): ?>
                                            <span class="badge-view"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($emission['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; // FIN MODE VUE ?>
    </main>
</div>

    <script>
    const BASE_URL = '<?php echo $base; ?>';
    function switchDay(day) {
        window.location.href = BASE_URL + '/index.php?route=admin/emissions&day=' + day;
    }

    function switchView(view) {
        if (view === 'list') {
            window.location.href = BASE_URL + '/index.php?route=admin/emissions&view=list';
        } else {
            window.location.href = BASE_URL + '/index.php?route=admin/emissions';
        }
    }

    function editEmission(index, day) {
        const emissions = document.querySelectorAll(`.emission-card[data-day="${day}"]`);
        if (!emissions[index]) return;
        
        const emission = emissions[index];
        
        // Remplir le formulaire avec les données
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-day').value = day;
        document.getElementById('form-day-hidden').value = day;
        document.getElementById('form-index').value = index;
        document.getElementById('form-time').value = emission.getAttribute('data-time') || '';
        document.getElementById('form-title').value = emission.getAttribute('data-title') || '';
        document.getElementById('form-presenter').value = emission.getAttribute('data-presenter') || '';
        document.getElementById('form-duration').value = emission.getAttribute('data-duration') || '';
        document.getElementById('form-desc').value = emission.getAttribute('data-desc') || '';
        // Remplir le champ URL video si présent et sélectionner l'option URL
        var src = emission.getAttribute('data-src') || ''; 
        var urlInput = document.getElementById('src-url');
        var fileInput = document.getElementById('src-file');
        if (urlInput) urlInput.value = src;
        if (fileInput) fileInput.value = '';
        // forcer l'affichage du champ URL
        if (document.querySelector('input[name="video-type"][value="url"]')) document.querySelector('input[name="video-type"][value="url"]').checked = true;
        toggleVideoInput();
        
        // Mettre à jour les labels
        document.getElementById('form-title-label').textContent = 'Éditer Émission';
        document.getElementById('btn-label').textContent = 'Mettre à jour';
        
        // Scroll vers le formulaire
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
        document.getElementById('form-title').focus();
    }

    function toggleVideoInput() {
        const videoType = document.querySelector('input[name="video-type"]:checked').value;
        const urlInput = document.getElementById('src-url');
        const fileInput = document.getElementById('src-file');
        
        if (videoType === 'url') {
            urlInput.style.display = 'block';
            fileInput.style.display = 'none';
            fileInput.value = '';
        } else {
            urlInput.style.display = 'none';
            fileInput.style.display = 'block';
            urlInput.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const currentRoute = new URLSearchParams(window.location.search).get('route') || 'admin';
        const menuLinks = document.querySelectorAll('.sidebar-menu a');
        menuLinks.forEach(link => {
            link.classList.remove('active');
            try {
                if (link.getAttribute('href') && link.getAttribute('href').includes(currentRoute)) {
                    link.classList.add('active');
                }
            } catch (e) { /* ignore malformed hrefs */ }
        });
        // helpers: upload for emission and save all emissions
        window.uploadForEmission = function(btn) {
            var card = btn.closest('.emission-card');
            if (!card) return alert('Carte introuvable');
            var inp = document.createElement('input'); inp.type = 'file'; inp.accept = 'audio/*,video/*';
            inp.onchange = function(){
                var f = inp.files[0]; if (!f) return;
                var fd = new FormData(); fd.append('file', f);
                fetch((window.BASE_URL||'') + '/index.php?route=admin/upload_media', { method: 'POST', body: fd }).then(r=>r.json()).then(j=>{
                    if (j && j.ok) {
                        card.dataset.src = j.path;
                        var preview = card.querySelector('.emission-media-preview');
                        if (!preview) { preview = document.createElement('div'); preview.className = 'emission-media-preview'; card.querySelector('.emission-info').appendChild(preview); }
                        preview.innerHTML = '<video controls width="100%" style="max-width:420px; border-radius:8px;"><source src="' + j.path + '"></video>';
                        alert('Fichier téléversé: ' + j.path);
                    } else { alert('Upload failed'); }
                }).catch(e=>{ alert('Upload error'); console.error(e); });
            };
            inp.click();
        };

        window.saveAllEmissions = function(){
            var cards = document.querySelectorAll('.emission-card');
            var payload = {};
            cards.forEach(function(c){
                var day = c.getAttribute('data-day') || 'lundi';
                if (!payload[day]) payload[day] = [];
                var obj = {
                    time: c.getAttribute('data-time') || '',
                    title: c.getAttribute('data-title') || '',
                    presenter: c.getAttribute('data-presenter') || '',
                    duration: c.getAttribute('data-duration') || '',
                    level: c.getAttribute('data-level') || '',
                    category: c.getAttribute('data-category') || '',
                    desc: c.getAttribute('data-desc') || '',
                    src: c.getAttribute('data-src') || c.dataset.src || ''
                };
                payload[day].push(obj);
            });
            fetch((window.BASE_URL||'') + '/index.php?route=admin/save_emissions', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) }).then(r=>r.json()).then(j=>{ if (j && j.ok) { alert('Émissions enregistrées'); location.reload(); } else { alert('Save failed'); console.error(j); } }).catch(e=>{ alert('Save error'); console.error(e); });
        };
    });
</script>
</body>
</html>

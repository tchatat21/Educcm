<?php
$page_title = 'Gestion des Plannings';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur') {
    echo "<div class='container mt-5'><div class='alert alert-danger shadow-sm'>Accès refusé.</div></div>";
    include '../includes/footer.php'; exit;
}

$message = '';
$error = '';
$selected_class_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;

// Traitement POST (Ajout/Suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        $classe_id = (int)$_POST['classe_id'];
        $matiere_id = (int)$_POST['matiere_id'];
        $enseignant_id = (int)$_POST['enseignant_id'];
        $jour = $_POST['jour_semaine'];
        $h_deb = $_POST['heure_debut'];
        $h_fin = $_POST['heure_fin'];

        if ($classe_id && $matiere_id && $enseignant_id && $jour && $h_deb && $h_fin) {
            if (strtotime($h_fin) <= strtotime($h_deb)) {
                $error = 'L\'heure de fin doit être après l\'heure de début.';
            } else {
                // Vérification conflits
                $conflict = $conn->prepare("SELECT id FROM emploi_du_temps WHERE jour_semaine = ? AND heure_debut < ? AND heure_fin > ? AND (enseignant_id = ? OR classe_id = ?)");
                $conflict->bind_param("sssii", $jour, $h_fin, $h_deb, $enseignant_id, $classe_id);
                $conflict->execute();
                if ($conflict->get_result()->num_rows > 0) {
                    $error = 'Conflit d\'horaire : L\'enseignant ou la classe est déjà occupé(e).';
                } else {
                    $ins = $conn->prepare("INSERT INTO emploi_du_temps (classe_id, matiere_id, enseignant_id, jour_semaine, heure_debut, heure_fin) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("iissss", $classe_id, $matiere_id, $enseignant_id, $jour, $h_deb, $h_fin);
                    $ins->execute();
                    $message = "Cours ajouté au planning avec succès !";
                }
            }
        }
    }
    if (isset($_POST['delete_schedule'])) {
        $id = (int)$_POST['schedule_id'];
        $conn->query("DELETE FROM emploi_du_temps WHERE id = $id");
        $message = "Cours supprimé du planning.";
    }
}

// Données pour les formulaires
$classes = $conn->query("SELECT * FROM classes ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$matieres = $conn->query("SELECT * FROM matieres ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$profs = $conn->query("SELECT id, nom, prenom FROM utilisateurs WHERE role='enseignant' ORDER BY nom")->fetch_all(MYSQLI_ASSOC);

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

// Récupérer le planning de la classe sélectionnée
$schedule = [];
if ($selected_class_id > 0) {
    $res = $conn->query("
        SELECT e.*, m.nom as matiere, u.nom as prof_nom, u.prenom as prof_prenom 
        FROM emploi_du_temps e 
        JOIN matieres m ON e.matiere_id = m.id 
        JOIN utilisateurs u ON e.enseignant_id = u.id 
        WHERE e.classe_id = $selected_class_id 
        ORDER BY heure_debut
    ");
    while($row = $res->fetch_assoc()) $schedule[$row['jour_semaine']][] = $row;
}

$selected_class_name = '';
foreach ($classes as $c) {
    if ($c['id'] == $selected_class_id) {
        $selected_class_name = $c['nom'] . ' (' . $c['niveau'] . ')';
        break;
    }
}
?>

<?php if ($selected_class_id > 0): ?>
<!-- EN-TÊTE IMPRESSION -->
<div class="print-only-header mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <div class="d-flex align-items-center">
            <img src="/G/educ.jpeg" alt="Logo" style="width: 50px; height: 50px; object-fit: contain; margin-right: 12px;">
            <div>
                <h4 class="fw-bold mb-0" style="color: #223E6F;">EDUC.CM</h4>
                <small class="text-muted">Système Intégré de Gestion Scolaire</small>
            </div>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-0">EMPLOI DU TEMPS OFFICIEL</h5>
            <div class="text-primary fw-bold">Classe : <?php echo htmlspecialchars($selected_class_name); ?></div>
            <small class="text-muted">Année Académique 2025 - 2026</small>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mb-4 align-items-center no-print">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Gestion des Plannings</h3>
        <p class="text-muted small mb-0">Configurez et imprimez l'emploi du temps hebdomadaire par classe.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <?php if ($selected_class_id > 0): ?>
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill shadow-sm px-3 me-2">
                <i class="bi bi-printer-fill me-1"></i> Imprimer
            </button>
            <button class="btn btn-primary rounded-pill shadow-sm px-4" data-bs-toggle="collapse" data-bs-target="#addEntryForm">
                <i class="bi bi-calendar-plus me-2"></i> Ajouter un cours
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success shadow-sm rounded-4 mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger shadow-sm rounded-4 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div><?php endif; ?>

<!-- Sélecteur de classe -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-light no-print">
    <div class="card-body p-4">
        <form action="pages/gestion_emplois_du_temps.php" method="GET" class="row align-items-center">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-navy">Sélectionnez une classe pour gérer son planning :</label>
                <select name="classe_id" class="form-select form-select-lg border-0 shadow-sm" onchange="this.form.submit()">
                    <option value="0">-- Choisir une classe --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $selected_class_id == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nom'].' ('.$c['niveau'].')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_class_id > 0): ?>
    <!-- Formulaire d'ajout caché -->
    <div class="collapse mb-4 no-print" id="addEntryForm">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Planifier un nouveau créneau</h5>
                <form action="pages/gestion_emplois_du_temps.php?classe_id=<?php echo $selected_class_id; ?>" method="POST" class="row g-3">
                    <input type="hidden" name="classe_id" value="<?php echo $selected_class_id; ?>">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Jour</label>
                        <select name="jour_semaine" class="form-select" required>
                            <?php foreach($jours as $j): ?><option value="<?php echo $j; ?>"><?php echo $j; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Début</label>
                        <input type="time" name="heure_debut" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fin</label>
                        <input type="time" name="heure_fin" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Matière</label>
                        <select name="matiere_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach($matieres as $m): ?><option value="<?php echo $m['id']; ?>"><?php echo $m['nom']; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Enseignant</label>
                        <select name="enseignant_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach($profs as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo $p['prenom'].' '.$p['nom']; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" name="add_schedule" class="btn btn-success px-5 rounded-pill shadow-sm">Valider l'ajout</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vue Planner Hebdomadaire -->
    <div class="timetable-container">
        <div class="row g-3">
            <?php foreach($jours as $jour): ?>
                <div class="col-md-6 col-lg-4 col-xl-2 day-column-wrapper">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden day-card">
                        <div class="day-header text-white text-center py-2 border-0" style="background-color: #223E6F;">
                            <h6 class="mb-0 fw-bold"><?php echo $jour; ?></h6>
                        </div>
                        <div class="card-body p-2 day-body bg-light">
                            <?php if (isset($schedule[$jour])): ?>
                                <?php foreach($schedule[$jour] as $cours): ?>
                                    <div class="course-box p-2 mb-2 rounded-3 border-start border-4 shadow-sm bg-white position-relative" style="border-color: #39A9C3 !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-light text-navy border small rounded-pill time-badge">
                                                <i class="bi bi-clock me-1 text-primary"></i>
                                                <?php echo date('H:i', strtotime($cours['heure_debut'])); ?> - <?php echo date('H:i', strtotime($cours['heure_fin'])); ?>
                                            </span>
                                            <form action="pages/gestion_emplois_du_temps.php?classe_id=<?php echo $selected_class_id; ?>" method="POST" onsubmit="return confirm('Supprimer ce cours ?')" class="no-print">
                                                <input type="hidden" name="schedule_id" value="<?php echo $cours['id']; ?>">
                                                <button type="submit" name="delete_schedule" class="btn btn-link text-danger p-0 border-0"><i class="bi bi-x-circle-fill"></i></button>
                                            </form>
                                        </div>
                                        <div class="course-title fw-bold text-dark"><?php echo htmlspecialchars($cours['matiere']); ?></div>
                                        <div class="course-sub small text-muted"><i class="bi bi-person me-1"></i> Prof. <?php echo htmlspecialchars($cours['prof_nom']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-day text-center py-5 opacity-25">
                                    <i class="bi bi-calendar-x fs-2"></i>
                                    <p class="small mb-0">Aucun cours</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="bi bi-calendar3-range display-1 text-muted opacity-25"></i>
        <h4 class="mt-3 fw-bold">Bienvenue dans le gestionnaire de planning</h4>
        <p class="text-muted">Veuillez sélectionner une classe ci-dessus pour afficher ou modifier son emploi du temps.</p>
    </div>
<?php endif; ?>

<style>
    .bg-navy { background-color: #223E6F; }
    .text-navy { color: #223E6F; }
    .print-only-header { display: none; }
    .time-badge { font-size: 0.75rem; }
    .course-title { font-size: 0.85rem; }
    .course-sub { font-size: 0.75rem; }

    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        body {
            background: #fff !important;
            font-size: 9pt;
            color: #000 !important;
            padding: 0 !important;
        }
        nav, footer, .no-print, .btn, .alert {
            display: none !important;
        }
        .print-only-header {
            display: block !important;
            margin-bottom: 12px !important;
        }
        .timetable-container {
            width: 100% !important;
        }
        .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            margin: 0 !important;
            gap: 6px !important;
        }
        .day-column-wrapper {
            flex: 1 1 0 !important;
            max-width: 16.66% !important;
            padding: 0 !important;
        }
        .day-card {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            box-shadow: none !important;
        }
        .day-header {
            background-color: #223E6F !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color: #fff !important;
            padding: 4px !important;
            font-size: 8.5pt !important;
        }
        .day-body {
            background-color: #f8fafc !important;
            padding: 4px !important;
        }
        .course-box {
            border: 1px solid #e2e8f0 !important;
            border-left: 3px solid #39A9C3 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 4px !important;
            margin-bottom: 4px !important;
            page-break-inside: avoid;
        }
        .time-badge { font-size: 7pt !important; font-weight: bold; }
        .course-title { font-size: 8pt !important; font-weight: bold; }
        .course-sub { font-size: 7pt !important; }
        .empty-day { padding: 15px 0 !important; }
        main, .card, .card-body {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>

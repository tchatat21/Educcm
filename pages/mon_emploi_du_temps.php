<?php
$page_title = 'Mon Emploi du Temps';
include '../includes/header.php';
require_once '../includes/db.php';

// Autoriser à la fois Élève et Enseignant
if ($_SESSION['user_role'] !== 'eleve' && $_SESSION['user_role'] !== 'enseignant') {
    die("<div class='container mt-5'><div class='alert alert-danger'>Accès réservé aux élèves et aux enseignants.</div></div>");
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$schedule = [];
$target_info = '';

if ($user_role === 'eleve') {
    // 1. Récupérer la classe de l'élève
    $res_c = $conn->query("
        SELECT c.id as classe_id, c.nom as classe_nom, c.niveau 
        FROM inscriptions i 
        JOIN classes c ON i.classe_id = c.id 
        WHERE i.eleve_id = $user_id 
        LIMIT 1
    ");

    if (!$res_c || $res_c->num_rows == 0) {
        echo "<div class='container py-5'><div class='alert alert-warning rounded-4 shadow-sm text-center py-4'>
                <i class='bi bi-exclamation-circle fs-1 d-block mb-2'></i>
                <h5 class='fw-bold'>Vous n'êtes pas encore inscrit dans une classe.</h5>
                <p class='text-muted mb-0'>Veuillez contacter l'administration de l'établissement pour votre affectation.</p>
              </div></div>";
        include '../includes/footer.php'; 
        exit;
    }

    $classe_info = $res_c->fetch_assoc();
    $classe_id = $classe_info['classe_id'];
    $target_info = "Classe : " . htmlspecialchars($classe_info['classe_nom'] . ' (' . $classe_info['niveau'] . ')');

    // Récupérer les cours de la classe
    $edt = $conn->query("
        SELECT e.*, m.nom as matiere_nom, u.nom as prof_nom, u.prenom as prof_prenom 
        FROM emploi_du_temps e
        JOIN matieres m ON e.matiere_id = m.id
        JOIN utilisateurs u ON e.enseignant_id = u.id
        WHERE e.classe_id = $classe_id
        ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), heure_debut
    ");

    while ($row = $edt->fetch_assoc()) {
        $schedule[$row['jour_semaine']][] = $row;
    }

} elseif ($user_role === 'enseignant') {
    $target_info = "Enseignant : " . htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']);

    // Récupérer tous les cours dispensés par cet enseignant
    $edt = $conn->query("
        SELECT e.*, m.nom as matiere_nom, c.nom as classe_nom 
        FROM emploi_du_temps e
        JOIN matieres m ON e.matiere_id = m.id
        JOIN classes c ON e.classe_id = c.id
        WHERE e.enseignant_id = $user_id
        ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), heure_debut
    ");

    while ($row = $edt->fetch_assoc()) {
        $schedule[$row['jour_semaine']][] = $row;
    }
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
?>

<!-- EN-TÊTE OFFICIEL POUR L'IMPRESSION SEULEMENT -->
<div class="print-only-header mb-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3">
        <div class="d-flex align-items-center">
            <img src="/G/educ.jpeg" alt="Logo" style="width: 55px; height: 55px; object-fit: contain; margin-right: 15px;">
            <div>
                <h3 class="fw-bold mb-0" style="color: #223E6F;">EDUC.CM</h3>
                <small class="text-muted">Établissement Scolaire d'Excellence</small>
            </div>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-0">EMPLOI DU TEMPS HEBDOMADAIRE</h5>
            <div class="text-primary fw-bold"><?php echo $target_info; ?></div>
            <small class="text-muted">Année Académique 2025 - 2026</small>
        </div>
    </div>
</div>

<!-- EN-TÊTE ÉCRAN -->
<div class="row mb-4 align-items-center no-print">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-0">Emploi du Temps Hebdomadaire</h3>
        <p class="text-muted small mb-0"><i class="bi bi-person-badge me-1"></i> <?php echo $target_info; ?></p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <button onclick="window.print()" class="btn btn-primary rounded-pill shadow-sm px-4">
            <i class="bi bi-printer-fill me-2"></i> Imprimer l'emploi du temps
        </button>
    </div>
</div>

<!-- GRILLE HEBDOMADAIRE MODERNE -->
<div class="timetable-container">
    <div class="row g-3">
        <?php foreach ($jours as $jour): ?>
            <div class="col-md-6 col-lg-4 col-xl-2 day-column-wrapper">
                <div class="card border-0 shadow-sm rounded-4 h-100 day-card overflow-hidden">
                    <div class="day-header text-white text-center py-2" style="background-color: #223E6F;">
                        <h6 class="mb-0 fw-bold"><?php echo $jour; ?></h6>
                    </div>
                    <div class="card-body p-2 day-body bg-light">
                        <?php if (!empty($schedule[$jour])): ?>
                            <?php foreach ($schedule[$jour] as $cours): ?>
                                <div class="course-box p-2 mb-2 bg-white rounded-3 shadow-sm border-start border-4 border-info">
                                    <div class="time-badge mb-1">
                                        <i class="bi bi-clock me-1 text-primary"></i>
                                        <strong><?php echo date('H:i', strtotime($cours['heure_debut'])); ?></strong> - <?php echo date('H:i', strtotime($cours['heure_fin'])); ?>
                                    </div>
                                    <div class="course-title fw-bold text-dark text-truncate" title="<?php echo htmlspecialchars($cours['matiere_nom']); ?>">
                                        <?php echo htmlspecialchars($cours['matiere_nom']); ?>
                                    </div>
                                    <div class="course-sub small text-muted text-truncate">
                                        <?php if ($user_role === 'eleve'): ?>
                                            <i class="bi bi-person me-1"></i> Prof. <?php echo htmlspecialchars($cours['prof_nom']); ?>
                                        <?php else: ?>
                                            <i class="bi bi-door-open me-1"></i> <?php echo htmlspecialchars($cours['classe_nom']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-day text-center py-4 text-muted opacity-50">
                                <i class="bi bi-calendar-x fs-4 d-block mb-1"></i>
                                <span class="small">Aucun cours</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .text-navy { color: #223E6F; }
    .course-box {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .course-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08) !important;
    }
    .time-badge {
        font-size: 0.75rem;
        color: #475569;
    }
    .course-title {
        font-size: 0.85rem;
        color: #0f172a;
    }
    .course-sub {
        font-size: 0.75rem;
    }
    .print-only-header {
        display: none;
    }

    /* STYLES SPÉCIFIQUES POUR L'IMPRESSION PAYSAGE */
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
        .time-badge {
            font-size: 7pt !important;
            font-weight: bold;
        }
        .course-title {
            font-size: 8pt !important;
            font-weight: bold;
        }
        .course-sub {
            font-size: 7pt !important;
        }
        .empty-day {
            padding: 15px 0 !important;
        }
        main, .card, .card-body {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>

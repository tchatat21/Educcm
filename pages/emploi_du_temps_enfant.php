<?php
$page_title = 'Emploi du Temps de mon Enfant';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'parent') {
    die("<div class='container mt-5'><div class='alert alert-danger'>Accès réservé aux parents.</div></div>");
}

$parent_id = $_SESSION['user_id'];

// 1. Récupérer tous les enfants liés
$stmt_children = $conn->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo, c.nom as classe_nom, c.id as classe_id
    FROM parents_eleves pe 
    JOIN utilisateurs u ON pe.eleve_id = u.id 
    LEFT JOIN inscriptions i ON u.id = i.eleve_id
    LEFT JOIN classes c ON i.classe_id = c.id
    WHERE pe.parent_id = ?
");
$stmt_children->bind_param("i", $parent_id);
$stmt_children->execute();
$children_res = $stmt_children->get_result();

$children = [];
while ($row = $children_res->fetch_assoc()) {
    $children[] = $row;
}
$stmt_children->close();

// Si un enfant spécifique est sélectionné
$selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (count($children) > 0 ? $children[0]['id'] : 0);

$selected_child = null;
foreach ($children as $child) {
    if ($child['id'] == $selected_child_id) {
        $selected_child = $child;
        break;
    }
}

$schedule = [];
if ($selected_child && !empty($selected_child['classe_id'])) {
    $classe_id = $selected_child['classe_id'];
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
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
?>

<?php if ($selected_child): ?>
    <!-- EN-TÊTE OFFICIEL POUR L'IMPRESSION -->
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
                <h5 class="fw-bold mb-0">EMPLOI DU TEMPS ÉLÈVE</h5>
                <div class="text-primary fw-bold"><?php echo htmlspecialchars($selected_child['prenom'] . ' ' . $selected_child['nom']); ?> (<?php echo htmlspecialchars($selected_child['classe_nom'] ?: 'N/A'); ?>)</div>
                <small class="text-muted">Année Académique 2025 - 2026</small>
            </div>
        </div>
    </div>

    <!-- EN-TÊTE ÉCRAN -->
    <div class="row mb-4 align-items-center no-print">
        <div class="col-md-6">
            <h3 class="fw-bold text-navy mb-0">Emploi du Temps de votre Enfant</h3>
            <p class="text-muted small mb-0">Consultez et imprimez le planning hebdomadaire.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2 align-items-center">
            <?php if (count($children) > 1): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle rounded-pill shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-people-fill me-1 text-primary"></i> <?php echo htmlspecialchars($selected_child['prenom'] . ' ' . $selected_child['nom']); ?>
                    </button>
                    <ul class="dropdown-menu shadow border-0 rounded-3">
                        <?php foreach ($children as $child): ?>
                            <li>
                                <a class="dropdown-item <?php echo ($child['id'] == $selected_child_id) ? 'active' : ''; ?>" href="pages/emploi_du_temps_enfant.php?child_id=<?php echo $child['id']; ?>">
                                    <?php echo htmlspecialchars($child['prenom'] . ' ' . $child['nom']); ?> (<?php echo htmlspecialchars($child['classe_nom'] ?: 'Sans classe'); ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <button onclick="window.print()" class="btn btn-primary rounded-pill shadow-sm px-4">
                <i class="bi bi-printer-fill me-2"></i> Imprimer
            </button>
        </div>
    </div>

    <!-- CARTE D'IDENTITÉ ÉLÈVE SÉLECTIONNÉ (Écran) -->
    <div class="mb-4 d-flex align-items-center bg-white p-3 rounded-4 shadow-sm no-print">
        <?php 
            $photo_name = $selected_child['photo'];
            $photo_path = __DIR__ . "/../uploads/photos/" . $photo_name;
            if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                $avatar = "/G/uploads/photos/" . $photo_name;
            } else {
                $avatar = "https://ui-avatars.com/api/?name=" . urlencode($selected_child['prenom'] . ' ' . $selected_child['nom']) . "&background=223E6F&color=fff";
            }
        ?>
        <img src="<?php echo $avatar; ?>" class="rounded-circle shadow-sm me-3" style="width: 55px; height: 55px; object-fit: cover;">
        <div>
            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($selected_child['prenom'] . ' ' . $selected_child['nom']); ?></h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">
                Classe : <?php echo htmlspecialchars($selected_child['classe_nom'] ?: 'Non inscrit'); ?>
            </span>
        </div>
    </div>

    <?php if (!empty($selected_child['classe_id'])): ?>
        <!-- GRILLE HORAIRE -->
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
                                                <i class="bi bi-person me-1"></i> Prof. <?php echo htmlspecialchars($cours['prof_nom']); ?>
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
    <?php else: ?>
        <div class="alert alert-warning mt-4 rounded-4 shadow-sm text-center py-4 no-print">
            <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
            Cet enfant n'est pas encore inscrit dans une classe pour cette année scolaire.
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
        <i class="bi bi-people display-1 text-muted opacity-25"></i>
        <h4 class="mt-3 fw-bold">Aucun enfant rattaché</h4>
        <p class="text-muted">Il semblerait qu'aucun compte élève ne soit encore rattaché à votre profil parent.<br>Veuillez contacter l'administration de l'établissement.</p>
    </div>
<?php endif; ?>

<style>
    .text-navy { color: #223E6F; }
    .course-box {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .course-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08) !important;
    }
    .time-badge { font-size: 0.75rem; color: #475569; }
    .course-title { font-size: 0.85rem; color: #0f172a; }
    .course-sub { font-size: 0.75rem; }
    .print-only-header { display: none; }

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

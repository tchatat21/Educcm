<?php
$page_title = 'Mon Emploi du Temps';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'eleve') {
    die("Accès refusé.");
}

$eleve_id = $_SESSION['user_id'];

// Récupérer la classe de l'élève
$res_c = $conn->query("SELECT classe_id FROM inscriptions WHERE eleve_id = $eleve_id LIMIT 1");
if ($res_c->num_rows == 0) {
    echo "<div class='alert alert-warning mt-4'>Vous n'êtes pas encore inscrit dans une classe.</div>";
    include '../includes/footer.php'; exit;
}
$classe_id = $res_c->fetch_assoc()['classe_id'];

// Récupérer l'emploi du temps
$edt = $conn->query("
    SELECT e.*, m.nom as matiere_nom, u.nom as prof_nom 
    FROM emploi_du_temps e
    JOIN matieres m ON e.matiere_id = m.id
    JOIN utilisateurs u ON e.enseignant_id = u.id
    WHERE e.classe_id = $classe_id
    ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), heure_debut
");

$schedule = [];
while($row = $edt->fetch_assoc()) {
    $schedule[$row['jour_semaine']][] = $row;
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Agenda Hebdomadaire</h3>
        <p class="text-muted small">Consultez votre planning de cours.</p>
    </div>
</div>

<div class="row g-3">
    <?php foreach($jours as $jour): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-navy text-white text-center py-2 border-0" style="background-color: #223E6F;">
                    <h6 class="mb-0 fw-bold"><?php echo $jour; ?></h6>
                </div>
                <div class="card-body p-3">
                    <?php if (isset($schedule[$jour])): ?>
                        <?php foreach($schedule[$jour] as $cours): ?>
                            <div class="course-slot p-3 mb-3 rounded-3 border-start border-4 border-turquoise shadow-sm bg-light">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($cours['matiere_nom']); ?></div>
                                    <span class="badge bg-white text-navy border small rounded-pill">
                                        <?php echo date('H:i', strtotime($cours['heure_debut'])); ?>
                                    </span>
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-person me-1"></i> Prof. <?php echo htmlspecialchars($cours['prof_nom']); ?>
                                </div>
                                <div class="x-small text-muted">
                                    <i class="bi bi-clock me-1"></i> <?php echo date('H:i', strtotime($cours['heure_fin'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 opacity-25">
                            <i class="bi bi-calendar-x fs-2"></i>
                            <p class="small mb-0">Pas de cours</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .bg-navy { background-color: #223E6F; }
    .border-turquoise { border-color: #39A9C3 !important; }
    .course-slot { transition: transform 0.2s; cursor: default; }
    .course-slot:hover { transform: scale(1.02); background-color: #fff !important; }
</style>

<?php include '../includes/footer.php'; ?>

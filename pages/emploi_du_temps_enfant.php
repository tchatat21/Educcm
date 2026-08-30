<?php
$page_title = 'Emploi du Temps de mon Enfant';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'parent') {
    die("Accès refusé.");
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
while($row = $children_res->fetch_assoc()) {
    $children[] = $row;
}

// Si un enfant spécifique est sélectionné
$selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (count($children) > 0 ? $children[0]['id'] : 0);

$selected_child = null;
foreach($children as $child) {
    if ($child['id'] == $selected_child_id) {
        $selected_child = $child;
        break;
    }
}

if ($selected_child && $selected_child['classe_id']) {
    $classe_id = $selected_child['classe_id'];
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
} else {
    $schedule = [];
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Emploi du Temps</h3>
        <p class="text-muted small">Consultez le planning hebdomadaire de vos enfants.</p>
    </div>
    <?php if (count($children) > 1): ?>
    <div class="col-md-6 text-md-end">
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle rounded-pill" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($selected_child['prenom'] . ' ' . $selected_child['nom']); ?>
            </button>
            <ul class="dropdown-menu shadow border-0 rounded-3">
                <?php foreach($children as $child): ?>
                    <li><a class="dropdown-item" href="?child_id=<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['prenom'] . ' ' . $child['nom']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($selected_child): ?>
    <div class="mb-4 d-flex align-items-center bg-white p-3 rounded-4 shadow-sm">
        <?php 
            $photo_name = $selected_child['photo'];
            $photo_path = "../uploads/photos/" . $photo_name;
            if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                $avatar = "/G/uploads/photos/" . $photo_name;
            } else {
                $avatar = "https://ui-avatars.com/api/?name=" . urlencode($selected_child['prenom'] . ' ' . $selected_child['nom']) . "&background=random&color=fff";
            }
        ?>
        <img src="<?php echo $avatar; ?>" class="rounded-circle shadow-sm me-3" style="width: 60px; height: 60px; object-fit: cover;">
        <div>
            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($selected_child['prenom'] . ' ' . $selected_child['nom']); ?></h5>
            <span class="badge bg-light text-dark border small"><?php echo htmlspecialchars($selected_child['classe_nom'] ?: 'N/A'); ?></span>
        </div>
    </div>

    <?php if ($selected_child['classe_id']): ?>
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
    <?php else: ?>
        <div class="alert alert-warning mt-4 rounded-4">Cet enfant n'est pas encore inscrit dans une classe.</div>
    <?php endif; ?>

<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="bi bi-people display-1 text-muted opacity-25"></i>
        <h4 class="mt-3 fw-bold">Aucun enfant lié</h4>
        <p class="text-muted">Il semblerait qu'aucun compte élève ne soit rattaché à votre profil parent.</p>
    </div>
<?php endif; ?>

<style>
    .bg-navy { background-color: #223E6F; }
    .border-turquoise { border-color: #39A9C3 !important; }
    .course-slot { transition: transform 0.2s; cursor: default; }
    .course-slot:hover { transform: scale(1.02); background-color: #fff !important; }
</style>

<?php include '../includes/footer.php'; ?>

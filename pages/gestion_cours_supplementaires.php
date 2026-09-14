<?php
$page_title = 'Mes Cours Supplémentaires';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'enseignant') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Accès réservé aux enseignants.</div></div>";
    include '../includes/footer.php';
    exit;
}

$enseignant_id = $_SESSION['user_id'];
$message = $_GET['message'] ?? '';
$error = '';

// Traitement de l'ajout
if (isset($_POST['add_course'])) {
    $classe_id = (int)$_POST['classe_id'];
    $matiere_id = (int)$_POST['matiere_id'];
    $date_heure = $_POST['date_heure'];
    $duree_minutes = (int)$_POST['duree_minutes'];

    if ($classe_id > 0 && $matiere_id > 0 && !empty($date_heure)) {
        $stmt = $conn->prepare("INSERT INTO cours_supplementaires (enseignant_id, classe_id, matiere_id, date_heure, duree_minutes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisi", $enseignant_id, $classe_id, $matiere_id, $date_heure, $duree_minutes);
        if ($stmt->execute()) {
            header("Location: gestion_cours_supplementaires.php?message=" . urlencode("Cours planifié avec succès !"));
            exit;
        } else {
            $error = 'Erreur lors de l\'ajout.';
        }
    } else {
        $error = 'Tous les champs sont obligatoires.';
    }
}

// Traitement de la suppression
if (isset($_POST['delete_course'])) {
    $cours_id = (int)$_POST['cours_id'];
    $stmt = $conn->prepare("DELETE FROM cours_supplementaires WHERE id = ? AND enseignant_id = ?");
    $stmt->bind_param("ii", $cours_id, $enseignant_id);
    if ($stmt->execute()) {
        header("Location: gestion_cours_supplementaires.php?message=" . urlencode("Cours annulé."));
        exit;
    }
}

// Récupérer les classes/matières de l'enseignant
$stmt_enseignements = $conn->prepare("SELECT DISTINCT c.id as classe_id, c.nom as classe_nom, m.id as matiere_id, m.nom as matiere_nom FROM enseignants_classes ec JOIN classes c ON ec.classe_id = c.id JOIN matieres m ON ec.matiere_id = m.id WHERE ec.enseignant_id = ? ORDER BY c.nom, m.nom");
$stmt_enseignements->bind_param("i", $enseignant_id);
$stmt_enseignements->execute();
$enseignements_result = $stmt_enseignements->get_result();

// Récupérer les cours déjà planifiés
$stmt_courses = $conn->prepare("SELECT cs.id, cs.date_heure, cs.duree_minutes, c.nom as classe_nom, m.nom as matiere_nom FROM cours_supplementaires cs JOIN classes c ON cs.classe_id = c.id JOIN matieres m ON cs.matiere_id = m.id WHERE cs.enseignant_id = ? ORDER BY cs.date_heure DESC");
$stmt_courses->bind_param("i", $enseignant_id);
$stmt_courses->execute();
$courses_result = $stmt_courses->get_result();
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Cours Supplémentaires</h3>
        <p class="text-muted small mb-0">Gérez vos séances de soutien et cours exceptionnels.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary rounded-pill shadow-sm px-4" type="button" data-bs-toggle="collapse" data-bs-target="#addCourseForm">
            <i class="bi bi-calendar-plus me-2"></i> Planifier une séance
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Formulaire de planification -->
<div class="collapse mb-4" id="addCourseForm">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="pages/gestion_cours_supplementaires.php" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Classe & Matière</label>
                    <select name="classe_id" id="sel_classe" class="form-select" required>
                        <option value="">-- Sélectionner --</option>
                        <?php while ($ens = $enseignements_result->fetch_assoc()): ?>
                            <option value="<?php echo $ens['classe_id']; ?>" data-matiere="<?php echo $ens['matiere_id']; ?>">
                                <?php echo htmlspecialchars($ens['classe_nom'] . ' - ' . $ens['matiere_nom']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="hidden" name="matiere_id" id="matiere_id_hidden">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Date et Heure</label>
                    <input type="datetime-local" name="date_heure" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Durée (min)</label>
                    <input type="number" name="duree_minutes" class="form-control" min="15" step="15" value="60" required>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" name="add_course" class="btn btn-success mt-md-4">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Liste des séances -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Séance</th>
                        <th>Classe / Matière</th>
                        <th class="text-center">Durée</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($courses_result->num_rows > 0): ?>
                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo date('d/m/Y', strtotime($course['date_heure'])); ?></div>
                                    <div class="text-muted small"><?php echo date('H:i', strtotime($course['date_heure'])); ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-navy"><?php echo htmlspecialchars($course['matiere_nom']); ?></div>
                                    <div class="badge bg-light text-dark border small"><?php echo htmlspecialchars($course['classe_nom']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info px-3"><?php echo $course['duree_minutes']; ?> min</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="pages/faire_appel_supp.php?cours_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-person-check-fill me-1"></i> Faire l'appel
                                    </a>
                                    <form action="pages/gestion_cours_supplementaires.php" method="POST" class="d-inline" onsubmit="return confirm('Annuler cette séance ?');">
                                        <input type="hidden" name="cours_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" name="delete_course" class="btn btn-sm btn-outline-danger border-0 ms-2">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Aucun cours supplémentaire planifié.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('sel_classe').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById('matiere_id_hidden').value = selectedOption.dataset.matiere || '';
});
</script>

<?php include '../includes/footer.php'; ?>

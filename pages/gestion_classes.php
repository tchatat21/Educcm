<?php
$page_title = 'Gestion des Classes';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur') {
    die("Accès refusé.");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class'])) {
        $nom = trim($_POST['nom']);
        $niveau = trim($_POST['niveau']);
        if (!empty($nom)) {
            $stmt = $conn->prepare("INSERT INTO classes (nom, niveau) VALUES (?, ?)");
            $stmt->bind_param("ss", $nom, $niveau);
            if ($stmt->execute()) $message = "Classe ajoutée !";
            else $error = "Erreur lors de l'ajout.";
        }
    }
    if (isset($_POST['delete_class'])) {
        $id = (int)$_POST['classe_id'];
        $conn->query("DELETE FROM classes WHERE id = $id");
        $message = "Classe supprimée.";
    }
}

// Requête avec comptage d'élèves
$classes_result = $conn->query("
    SELECT c.*, (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id) as nb_eleves 
    FROM classes c 
    ORDER BY niveau, nom
");
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-primary mb-0">Gestion des Classes</h3>
        <p class="text-muted small mb-0">Organisez les niveaux et effectifs de l'établissement.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary rounded-pill shadow-sm px-4" data-bs-toggle="collapse" data-bs-target="#addClassForm">
            <i class="bi bi-plus-lg me-2"></i> Nouvelle Classe
        </button>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success shadow-sm"><?php echo $message; ?></div><?php endif; ?>

<div class="collapse mb-4" id="addClassForm">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="pages/gestion_classes.php" method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nom de la classe (ex: Seconde A)</label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Niveau (ex: Lycée)</label>
                    <input type="text" name="niveau" class="form-control">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" name="add_user" class="btn btn-success mt-md-4">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="p-3 bg-light border-bottom">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="classSearch" class="form-control border-start-0" placeholder="Filtrer par nom ou niveau...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="classTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nom de la Classe</th>
                        <th>Niveau</th>
                        <th>Effectif</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $classes_result->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-navy"><?php echo htmlspecialchars($c['nom']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['niveau']); ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2"><?php echo $c['nb_eleves']; ?></span>
                                    <div class="progress w-50" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: <?php echo min($c['nb_eleves'] * 2, 100); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <form action="pages/gestion_classes.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette classe ?');">
                                    <input type="hidden" name="classe_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" name="delete_class" class="btn btn-sm btn-outline-danger border-0">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('classSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#classTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
</script>
<?php include '../includes/footer.php'; ?>

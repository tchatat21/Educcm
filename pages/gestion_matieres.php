<?php
$page_title = 'Gestion des Matières';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur') {
    die("Accès refusé.");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_matiere'])) {
        $nom = trim($_POST['nom']);
        if (!empty($nom)) {
            $stmt = $conn->prepare("INSERT INTO matieres (nom) VALUES (?)");
            $stmt->bind_param("s", $nom);
            $stmt->execute();
            $message = "Matière ajoutée !";
        }
    }
    if (isset($_POST['delete_matiere'])) {
        $id = (int)$_POST['matiere_id'];
        $conn->query("DELETE FROM matieres WHERE id = $id");
        $message = "Matière supprimée.";
    }
}

$matieres_result = $conn->query("SELECT * FROM matieres ORDER BY nom");
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Catalogue des Matières</h3>
        <p class="text-muted small mb-0">Gérez les enseignements dispensés dans l'établissement.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary rounded-pill shadow-sm" data-bs-toggle="collapse" data-bs-target="#addMatiereForm">
            <i class="bi bi-book-half me-2"></i> Nouvelle Matière
        </button>
    </div>
</div>

<div class="collapse mb-4" id="addMatiereForm">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="pages/gestion_matieres.php" method="POST" class="row g-3">
                <div class="col-md-9">
                    <label class="form-label small fw-bold">Nom de la matière (ex: Mathématiques)</label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" name="add_matiere" class="btn btn-success mt-md-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <input type="text" id="subjectSearch" class="form-control" placeholder="Rechercher une matière...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="subjectTable">
                        <thead>
                            <tr>
                                <th class="ps-4">Matière</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($m = $matieres_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary-subtle p-2 me-3 text-primary">
                                                <i class="bi bi-journal-bookmark"></i>
                                            </div>
                                            <span class="fw-bold"><?php echo htmlspecialchars($m['nom']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="pages/gestion_matieres.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette matière ?');">
                                            <input type="hidden" name="matiere_id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" name="delete_matiere" class="btn btn-sm btn-outline-danger border-0">
                                                <i class="bi bi-trash3"></i>
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
    </div>
    
    <!-- Aide visuelle sur le côté -->
    <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card bg-info-subtle border-0 rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-info-emphasis"><i class="bi bi-info-circle-fill"></i> Information</h6>
                <p class="small mb-0 text-info-emphasis opacity-75">Ces matières seront ensuite disponibles pour créer les emplois du temps et assigner les enseignants aux classes.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('subjectSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#subjectTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
</script>
<?php include '../includes/footer.php'; ?>

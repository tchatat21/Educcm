<?php
$page_title = 'Gestion des Utilisateurs';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur') {
    echo "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle-fill'></i> Accès refusé.</div>";
    include '../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// ... (Traitement POST reste identique pour la stabilite) ...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $mot_de_passe = $_POST['mot_de_passe'];
        $role = $_POST['role'];

        if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($role)) {
            $error = 'Tous les champs sont obligatoires.';
        } else {
            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nom, $prenom, $email, $hashed_password, $role);
            if ($stmt->execute()) $message = 'Utilisateur ajouté avec succès !';
            else $error = 'Erreur lors de l\'ajout (Email peut-être déjà utilisé).';
            $stmt->close();
        }
    }
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        if ($user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $message = 'Utilisateur supprimé.';
            $stmt->close();
        }
    }
}

$users_result = $conn->query("SELECT id, nom, prenom, email, role, photo FROM utilisateurs ORDER BY nom, prenom");
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Annuaire des Utilisateurs</h3>
        <p class="text-muted small mb-0">Gérez les accès et les profils de l'établissement.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary rounded-pill shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formCollapse">
            <i class="bi bi-person-plus-fill me-2"></i> Nouvel Utilisateur
        </button>
    </div>
</div>

<!-- Formulaire d'ajout (Collapse) -->
<div class="collapse mb-4" id="formCollapse">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Créer un compte</h5>
            <form action="pages/gestion_utilisateurs.php" method="POST">
                 <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nom</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Prénom</label>
                        <input type="text" class="form-control" name="prenom" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Mot de passe</label>
                        <input type="password" class="form-control" name="mot_de_passe" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Rôle assigné</label>
                        <select class="form-select" name="role" required>
                            <option value="eleve">Élève</option>
                            <option value="enseignant">Enseignant</option>
                            <option value="parent">Parent</option>
                            <option value="administrateur">Administrateur</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                     <button type="submit" name="add_user" class="btn btn-primary px-4">Enregistrer l'utilisateur</button>
                     <button type="button" class="btn btn-light" data-bs-toggle="collapse" data-bs-target="#formCollapse">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filtres et Recherche -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="userSearch" class="form-control border-start-0 ps-0" placeholder="Rechercher par nom, email ou rôle...">
        </div>
    </div>
</div>

<!-- Liste des Utilisateurs -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="userTable">
                <thead>
                    <tr>
                        <th class="ps-4">Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users_result->fetch_assoc()): 
                        $role_color = [
                            'administrateur' => 'bg-danger',
                            'enseignant' => 'bg-primary',
                            'eleve' => 'bg-success',
                            'parent' => 'bg-warning text-dark'
                        ][$user['role']];
                        
                        // Logique d'avatar améliorée
                        $photo_name = $user['photo'];
                        $photo_path = "../uploads/photos/" . $photo_name;
                        
                        if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                            $avatar_url = "/uploads/photos/" . $photo_name;
                        } else {
                            // API de secours très fiable avec les initiales
                            $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user['prenom'] . ' ' . $user['nom']) . "&background=random&color=fff&size=128";
                        }
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $avatar_url; ?>" class="rounded-circle me-3 shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold mb-0"><?php echo htmlspecialchars($user['nom'].' '.$user['prenom']); ?></div>
                                        <div class="text-muted x-small">ID: #<?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge <?php echo $role_color; ?> rounded-pill px-3"><?php echo ucfirst($user['role']); ?></span></td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <?php if ($user['role'] === 'eleve'): ?>
                                        <a href="pages/carte_scolaire.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-light border" title="Carte Scolaire">
                                            <i class="bi bi-card-image text-primary"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="pages/modifier_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-light border" title="Modifier">
                                        <i class="bi bi-pencil-square text-dark"></i>
                                    </a>
                                    <form action="pages/gestion_utilisateurs.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer définitivement ?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-light border" <?php if ($user['id'] == $_SESSION['user_id']) echo 'disabled'; ?>>
                                            <i class="bi bi-trash3-fill text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // --- RECHERCHE INSTANTANÉE ---
    document.getElementById('userSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#userTable tbody tr');
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<style>
    .text-navy { color: #223E6F; }
    .x-small { font-size: 0.75rem; }
    .btn-light:hover { background-color: #f1f5f9; border-color: #cbd5e1; }
    #userTable thead th { background-color: #f8fafc; color: #64748b; border-bottom: 1px solid #edf2f7; }
</style>

<?php
$conn->close();
include '../includes/footer.php';
?>

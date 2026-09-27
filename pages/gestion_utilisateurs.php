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

        // Seuls eleve, enseignant ou parent peuvent être créés directement
        if ($role === 'administrateur') {
            $error = "La création directe d'un administrateur est interdite. Seul un enseignant peut être promu administrateur.";
        } elseif (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($role)) {
            $error = 'Tous les champs sont obligatoires.';
        } else {
            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $qr_token = ($role === 'eleve') ? bin2hex(random_bytes(16)) : null;
            $statut_compte = 'actif'; // Les ajouts manuels par l'admin sont actifs d'office

            $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, qr_token, statut_compte) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nom, $prenom, $email, $hashed_password, $role, $qr_token, $statut_compte);
            if ($stmt->execute()) {
                $message = 'Utilisateur ajouté avec succès !';
            } else {
                $error = 'Erreur lors de l\'ajout (Email peut-être déjà utilisé).';
            }
            $stmt->close();
        }
    }

    // Validation manuelle d'un compte inscrit en attente
    if (isset($_POST['validate_account'])) {
        $user_id_val = (int)$_POST['user_id'];
        $conn->query("UPDATE utilisateurs SET statut_compte = 'actif' WHERE id = $user_id_val");

        $user_row = $conn->query("SELECT id, nom, prenom, email, role FROM utilisateurs WHERE id = $user_id_val")->fetch_assoc();
        if ($user_row) {
            $message_user = "Votre compte a été validé par l'administration. Vous pouvez maintenant vous connecter et accéder à votre espace.";
            $stmt_notif = $conn->prepare("INSERT INTO notifications (recipient_id, message, type, status, is_read) VALUES (?, ?, 'email', 'sent', 0)");
            $stmt_notif->bind_param("is", $user_id_val, $message_user);
            $stmt_notif->execute();
            $stmt_notif->close();
        }

        $message = "Le compte a été validé et activé avec succès !";
    }

    // Promotion exclusive : Enseignant -> Administrateur
    if (isset($_POST['promote_to_admin'])) {
        $user_id_prom = (int)$_POST['user_id'];
        $chk = $conn->query("SELECT role, nom, prenom FROM utilisateurs WHERE id = $user_id_prom");
        if ($chk && $row_prom = $chk->fetch_assoc()) {
            if ($row_prom['role'] === 'enseignant') {
                $conn->query("UPDATE utilisateurs SET role = 'administrateur' WHERE id = $user_id_prom");
                $message = "L'enseignant " . htmlspecialchars($row_prom['prenom'] . ' ' . $row_prom['nom']) . " a été promu Administrateur avec succès !";
            } else {
                $error = "Action non autorisée : Seul un enseignant peut être promu Administrateur.";
            }
        }
    }

    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        if ($user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $message = 'Utilisateur supprimé.';
            $stmt->close();
        }
    }
}

$users_result = $conn->query("SELECT u.id, u.nom, u.prenom, u.email, u.role, u.photo, u.justificatif, u.enfants_noms, u.statut_compte, v.validation_score, v.is_valid, v.issues
    FROM utilisateurs u
    LEFT JOIN registration_validations v ON v.user_id = u.id
    ORDER BY u.nom, u.prenom");
?>

<div class="admin-shell mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3">
                <div class="admin-icon rounded-4 shadow-sm">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-navy">Annuaire des Utilisateurs</h3>
                    <p class="text-muted small mb-0">Gérez les accès, les pièces justificatives et les validations.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary rounded-pill shadow-sm px-4" type="button" data-bs-toggle="collapse" data-bs-target="#formCollapse">
                <i class="bi bi-person-plus-fill me-2"></i> Nouvel Utilisateur
            </button>
        </div>
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
                        </select>
                        <small class="text-muted">Note : La création d'un administrateur se fait par promotion d'un enseignant existant.</small>
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
                        <th>Statut</th>
                        <th>Justificatif</th>
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
                        ][$user['role']] ?? 'bg-secondary';
                        
                        $is_pending = ($user['statut_compte'] === 'en_attente');
                        $issue_text = '';
                        if (!empty($user['issues'])) {
                            $decoded_issues = json_decode($user['issues'], true);
                            if (is_array($decoded_issues)) {
                                $issue_text = implode(' • ', $decoded_issues);
                            } else {
                                $issue_text = (string)$user['issues'];
                            }
                        }
                        
                        // Logique d'avatar améliorée
                        $photo_name = $user['photo'];
                        $photo_path = __DIR__ . "/../uploads/photos/" . $photo_name;
                        
                        if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                            $avatar_url = "uploads/view_file.php?folder=photos&file=" . rawurlencode($photo_name);
                        } else {
                            $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user['prenom'] . ' ' . $user['nom']) . "&background=random&color=fff&size=128";
                        }

                        $child_links = [];
                        if ($user['role'] === 'parent') {
                            $child_query = $conn->prepare("SELECT u.prenom, u.nom, c.nom AS classe_nom FROM parents_eleves pe JOIN utilisateurs u ON u.id = pe.eleve_id LEFT JOIN inscriptions i ON i.eleve_id = u.id LEFT JOIN classes c ON c.id = i.classe_id WHERE pe.parent_id = ? ORDER BY u.nom, u.prenom");
                            $child_query->bind_param('i', $user['id']);
                            $child_query->execute();
                            $child_result = $child_query->get_result();
                            while ($child_row = $child_result->fetch_assoc()) {
                                $child_links[] = trim($child_row['prenom'] . ' ' . $child_row['nom']) . ($child_row['classe_nom'] ? ' (' . $child_row['classe_nom'] . ')' : '');
                            }
                            $child_query->close();
                        }
                    ?>
                        <tr class="<?php echo $is_pending ? 'table-warning' : ''; ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $avatar_url; ?>" class="rounded-circle me-3 shadow-sm user-avatar">
                                    <div>
                                        <div class="fw-bold mb-0"><?php echo htmlspecialchars($user['nom'].' '.$user['prenom']); ?></div>
                                        <div class="text-muted x-small">ID: #<?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge <?php echo $role_color; ?> rounded-pill px-3"><?php echo ucfirst($user['role']); ?></span></td>
                            <td>
                                <?php if ($is_pending): ?>
                                    <span class="badge bg-warning text-dark border"><i class="bi bi-clock-history me-1"></i> En attente</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i> Actif</span>
                                <?php endif; ?>
                                <?php if (isset($user['validation_score'])): ?>
                                    <div class="small mt-1">
                                        <span class="text-muted">Bot:</span>
                                        <strong><?php echo (int)$user['validation_score']; ?>/100</strong>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($user['justificatif'])): 
                                    $justif_name = htmlspecialchars($user['justificatif']);
                                    $justif_path = "../uploads/view_file.php?folder=justificatifs&file=" . rawurlencode($user['justificatif']);
                                    $ext = strtolower(pathinfo($user['justificatif'], PATHINFO_EXTENSION));
                                    $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
                                ?>
                                    <div class="doc-preview-card">
                                        <div class="doc-preview-icon <?php echo $is_image ? 'doc-image' : 'doc-file'; ?>">
                                            <i class="bi <?php echo $is_image ? 'bi-file-earmark-image-fill' : 'bi-file-earmark-pdf-fill'; ?>"></i>
                                        </div>
                                        <div class="doc-preview-body">
                                            <span class="doc-badge <?php echo $is_image ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary'; ?> rounded-pill px-2 py-1 small fw-bold">
                                                <?php echo $is_image ? 'Image' : 'PDF'; ?>
                                            </span>
                                            <div class="doc-name mt-2"><?php echo htmlspecialchars(mb_strimwidth($user['justificatif'], 0, 28, '...')); ?></div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-2 flex-wrap">
                                        <a href="<?php echo $justif_path; ?>" target="_blank" class="btn btn-sm btn-gradient-primary rounded-pill">
                                            <i class="bi bi-eye-fill me-1"></i> Voir
                                        </a>
                                        <?php if ($is_image): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#docModal_<?php echo $user['id']; ?>">
                                                <i class="bi bi-arrows-fullscreen me-1"></i> Aperçu
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($user['enfants_noms'])): ?>
                                        <div class="x-small text-muted mt-2" title="Enfants renseignés">
                                            <i class="bi bi-people"></i> <?php echo htmlspecialchars(mb_strimwidth($user['enfants_noms'], 0, 30, '...')); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($user['role'] === 'parent' && !empty($child_links)): ?>
                                        <div class="x-small text-success mt-2" title="Enfants liés">
                                            <i class="bi bi-link-45deg"></i> <?php echo htmlspecialchars(mb_strimwidth(implode(', ', $child_links), 0, 40, '...')); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($issue_text)): ?>
                                        <div class="x-small text-danger mt-2" title="Motifs bot"><?php echo htmlspecialchars(mb_strimwidth($issue_text, 0, 60, '...')); ?></div>
                                    <?php endif; ?>

                                    <?php if ($is_image): ?>
                                        <div class="modal fade" id="docModal_<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                    <div class="modal-header border-0 bg-light">
                                                        <h5 class="modal-title fw-bold text-navy">Justificatif - <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-3 text-center">
                                                        <img src="<?php echo $justif_path; ?>" alt="Justificatif" class="img-fluid rounded-3 shadow-sm" style="max-height: 75vh;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <?php if ($is_pending): ?>
                                        <form action="pages/gestion_utilisateurs.php" method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="validate_account" class="btn btn-sm btn-success" title="Valider et Activer ce compte">
                                                <i class="bi bi-check-lg"></i> Valider
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($user['role'] === 'enseignant'): ?>
                                        <!-- Bouton pour promouvoir un enseignant en administrateur -->
                                        <form action="pages/gestion_utilisateurs.php" method="POST" class="d-inline" onsubmit="return confirm('Voulez-vous vraiment promouvoir cet enseignant au rôle d\'Administrateur ?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="promote_to_admin" class="btn btn-sm btn-outline-warning text-dark border" title="Promouvoir en Administrateur">
                                                <i class="bi bi-shield-lock-fill text-warning"></i> Promouvoir Admin
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($user['role'] === 'eleve'): ?>
                                        <a href="pages/carte_scolaire.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-light border" title="Carte Scolaire">
                                            <i class="bi bi-card-image text-primary"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="pages/modifier_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-light border" title="Modifier">
                                        <i class="bi bi-pencil-square text-dark"></i>
                                    </a>
                                    
                                    <form action="pages/gestion_utilisateurs.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
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
    body {
        background: linear-gradient(180deg, #f4f7fb 0%, #eef2ff 100%);
    }
    .text-navy { color: #223E6F; }
    .x-small { font-size: 0.75rem; }
    .btn-light:hover { background-color: #f1f5f9; border-color: #cbd5e1; }
    .admin-shell {
        background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 45%, #f5f3ff 100%);
        border: 1px solid rgba(34, 62, 111, 0.08);
        border-radius: 24px;
        padding: 22px 24px;
        box-shadow: 0 10px 30px rgba(34, 62, 111, 0.08);
    }
    .admin-icon {
        width: 52px;
        height: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #223E6F 0%, #39A9C3 100%);
        color: white;
        font-size: 1.4rem;
    }
    #userTable {
        border-collapse: separate;
        border-spacing: 0;
    }
    #userTable thead th {
        background: linear-gradient(180deg, #f8fafc 0%, #eef6ff 100%);
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    #userTable tbody tr {
        transition: all 0.2s ease;
    }
    #userTable tbody tr:hover {
        background: #f8fbff;
        transform: translateY(-1px);
    }
    .user-avatar {
        width: 42px;
        height: 42px;
        object-fit: cover;
        border: 2px solid #edf2ff;
    }
    .doc-preview-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 16px;
        background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
        border: 1px solid rgba(57, 169, 195, 0.18);
        min-width: 220px;
    }
    .doc-preview-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: white;
        flex-shrink: 0;
    }
    .doc-image { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
    .doc-file { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .doc-name {
        color: #1e293b;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.3;
        word-break: break-word;
    }
    .btn-gradient-primary {
        background: linear-gradient(135deg, #223E6F 0%, #39A9C3 100%);
        border: none;
        color: #fff;
        box-shadow: 0 8px 18px rgba(57, 169, 195, 0.25);
    }
    .btn-gradient-primary:hover {
        color: #fff;
        opacity: 0.96;
    }
</style>

<?php
$conn->close();
include '../includes/footer.php';
?>

<?php
$page_title = 'Modifier un Utilisateur';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur') {
    echo "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle-fill'></i> Accès refusé.</div>";
    include '../includes/footer.php';
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header('Location: ' . BASE_URL . 'pages/gestion_utilisateurs.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $mot_de_passe = $_POST['mot_de_passe'];
        $telephone = trim($_POST['telephone'] ?? '');

        // 1. Récupérer l'ancienne photo et l'ancien rôle
        $stmt_old = $conn->prepare("SELECT photo, role FROM utilisateurs WHERE id = ?");
        $stmt_old->bind_param("i", $user_id);
        $stmt_old->execute();
        $old_data = $stmt_old->get_result()->fetch_assoc();
        $old_photo = $old_data['photo'];
        $old_role = $old_data['role'];
        $stmt_old->close();

        // Règle stricte: seul un enseignant peut être promu administrateur
        if ($role === 'administrateur' && $old_role !== 'enseignant' && $old_role !== 'administrateur') {
            $error = "Action interdite : Seul un enseignant peut être promu au rôle d'Administrateur.";
        }

        $new_photo_name = $old_photo; // Par défaut, on garde l'ancienne

        // 2. Gestion de l'upload de la nouvelle photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (in_array($fileExtension, $allowedExtensions)) {
                // On génère un nom unique
                $new_photo_name = $user_id . '_' . time() . '.' . $fileExtension;
                $uploadFileDir = '../uploads/photos/';
                
                if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0777, true);
                
                $dest_path = $uploadFileDir . $new_photo_name;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Supprimer l'ancienne photo physique si elle existe et n'est pas l'avatar par défaut
                    if ($old_photo && $old_photo !== 'default_avatar.png' && file_exists($uploadFileDir . $old_photo)) {
                        unlink($uploadFileDir . $old_photo);
                    }
                } else {
                    $error = "Erreur lors du déplacement du fichier téléchargé.";
                }
            } else {
                $error = "Extension de fichier non autorisée (uniquement JPG, JPEG, PNG).";
            }
        }

        // 3. Mise à jour de la base de données
        if (empty($error)) {
            if (!empty($mot_de_passe)) {
                $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role = ?, mot_de_passe = ?, telephone = ?, photo = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $nom, $prenom, $email, $role, $hash, $telephone, $new_photo_name, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role = ?, telephone = ?, photo = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $nom, $prenom, $email, $role, $telephone, $new_photo_name, $user_id);
            }

            if ($stmt->execute()) {
                $message = "Utilisateur mis à jour avec succès !";
                
                // Si c'est un élève, on gère aussi son inscription
                if ($role === 'eleve') {
                    $classe_id = isset($_POST['classe_id']) ? (int)$_POST['classe_id'] : 0;
                    $conn->query("DELETE FROM inscriptions WHERE eleve_id = $user_id");
                    if ($classe_id > 0) {
                        $stmt_ins = $conn->prepare("INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire) VALUES (?, ?, '2025-2026')");
                        $stmt_ins->bind_param("ii", $user_id, $classe_id);
                        $stmt_ins->execute();
                        $stmt_ins->close();
                    }
                }
            } else {
                $error = "Erreur SQL : " . $conn->error;
            }
            $stmt->close();
        }
    }
    // Liaisons Parents/Enfants (Reste inchangé)
    elseif (isset($_POST['link_child'])) {
        $selectedClassIds = array_map('intval', $_POST['classe_ids'] ?? []);
        $selectedClassIds = array_values(array_unique(array_filter($selectedClassIds, fn($id) => $id > 0)));
        $selectedChildIds = array_map('intval', $_POST['enfant_ids'] ?? []);
        $selectedChildIds = array_values(array_unique(array_filter($selectedChildIds, fn($id) => $id > 0)));

        if (empty($selectedClassIds)) {
            $error = "Veuillez choisir au moins une classe.";
        } elseif (empty($selectedChildIds)) {
            $error = "Veuillez choisir au moins un enfant.";
        } else {
            $valid_child_ids = [];
            if (!empty($selectedChildIds)) {
                $in_clause = implode(',', array_fill(0, count($selectedChildIds), '?'));
                $types = str_repeat('i', count($selectedChildIds));
                $stmt_validate = $conn->prepare("SELECT u.id FROM utilisateurs u JOIN inscriptions i ON i.eleve_id = u.id WHERE u.role = 'eleve' AND u.id IN ($in_clause) AND i.classe_id IN (" . implode(',', $selectedClassIds) . ")");
                $stmt_validate->bind_param($types, ...$selectedChildIds);
                $stmt_validate->execute();
                $result_validate = $stmt_validate->get_result();
                while ($row = $result_validate->fetch_assoc()) {
                    $valid_child_ids[] = (int)$row['id'];
                }
                $stmt_validate->close();
            }

            if (count($valid_child_ids) !== count($selectedChildIds)) {
                $error = "Certains enfants sélectionnés ne correspondent pas aux classes choisies.";
            } else {
                foreach ($selectedChildIds as $child_id) {
                    $conn->query("INSERT IGNORE INTO parents_eleves (parent_id, eleve_id) VALUES ($user_id, $child_id)");
                }
                $message = "Les enfants ont été liés avec succès.";
            }
        }
    }
    elseif (isset($_POST['unlink_child'])) {
        $enfant_id = (int)$_POST['enfant_id'];
        $conn->query("DELETE FROM parents_eleves WHERE parent_id = $user_id AND eleve_id = $enfant_id");
        $message = "Lien retiré.";
    }
}

// 4. Re-récupérer les infos fraîches
$stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$current_classe_id = 0;
if ($user['role'] === 'eleve') {
    $res = $conn->query("SELECT classe_id FROM inscriptions WHERE eleve_id = $user_id LIMIT 1");
    if ($res->num_rows > 0) $current_classe_id = $res->fetch_assoc()['classe_id'];
}

$classes_result = $conn->query("SELECT * FROM classes ORDER BY nom");
$all_students = $conn->query("SELECT id, nom, prenom FROM utilisateurs WHERE role='eleve' ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$linked_children = $conn->query("SELECT u.id, u.nom, u.prenom FROM utilisateurs u JOIN parents_eleves pe ON u.id = pe.eleve_id WHERE pe.parent_id = $user_id")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-4">
    <?php if ($message): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">Modifier l'utilisateur : <?php echo htmlspecialchars($user['prenom'].' '.$user['nom']); ?></div>
                <div class="card-body">
                    <form action="pages/modifier_utilisateur.php?id=<?php echo $user_id; ?>" method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nom</label><input type="text" name="nom" class="form-control" value="<?php echo $user['nom']; ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Prénom</label><input type="text" name="prenom" class="form-control" value="<?php echo $user['prenom']; ?>" required></div>
                            <div class="col-md-12"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control" value="<?php echo $user['telephone']; ?>"></div>
                            <div class="col-md-6"><label class="form-label">Rôle</label>
                                <select name="role" class="form-select">
                                    <?php if ($user['role'] === 'enseignant'): ?>
                                        <option value="enseignant" selected>Enseignant</option>
                                        <option value="administrateur">Administrateur (Promouvoir)</option>
                                    <?php elseif ($user['role'] === 'administrateur'): ?>
                                        <option value="administrateur" selected>Administrateur</option>
                                        <option value="enseignant">Enseignant</option>
                                    <?php elseif ($user['role'] === 'eleve'): ?>
                                        <option value="eleve" selected>Élève</option>
                                    <?php elseif ($user['role'] === 'parent'): ?>
                                        <option value="parent" selected>Parent</option>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Seuls les enseignants peuvent être promus au rôle d'Administrateur.</small>
                            </div>
                            <div class="col-md-12"><label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label><input type="password" name="mot_de_passe" class="form-control"></div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Photo de profil / Carte</label>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($user['photo']): ?>
                                        <img src="../uploads/view_file.php?folder=photos&file=<?php echo rawurlencode($user['photo']); ?>" class="rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['nom']); ?>'">
                                    <?php endif; ?>
                                    <input type="file" name="photo" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <?php if ($user['role'] === 'eleve'): ?>
                            <div class="col-md-12">
                                <label class="form-label">Classe actuelle</label>
                                <select name="classe_id" class="form-select">
                                    <option value="0">-- Aucune --</option>
                                    <?php while($c = $classes_result->fetch_assoc()): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo $current_classe_id==$c['id']?'selected':''; ?>><?php echo $c['nom']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="col-12 mt-4">
                                <button type="submit" name="update_user" class="btn btn-success px-4">Enregistrer les modifications</button>
                                <a href="pages/gestion_utilisateurs.php" class="btn btn-outline-secondary">Annuler</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($user['role'] === 'parent'): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">Lier des enfants</div>
                <div class="card-body">
                    <form action="pages/modifier_utilisateur.php?id=<?php echo $user_id; ?>" method="POST" class="row g-2 mb-4">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Classe(s)</label>
                            <div class="border rounded-3 p-3 bg-light" id="parent_class_checkbox_wrapper">
                                <?php $classes_result2 = $conn->query("SELECT * FROM classes ORDER BY nom"); ?>
                                <?php while ($classe = $classes_result2->fetch_assoc()): ?>
                                    <label class="d-flex align-items-center gap-2 mb-2">
                                        <input type="checkbox" class="form-check-input" name="classe_ids[]" value="<?php echo $classe['id']; ?>">
                                        <span><?php echo htmlspecialchars($classe['nom'] . ' (' . $classe['niveau'] . ')'); ?></span>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                            <small class="text-muted">Cliquez simplement sur les classes des enfants.</small>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Enfants</label>
                            <div class="border rounded-3 p-3 bg-light" id="parent_child_checkbox_wrapper">
                                <div class="text-muted small">Sélectionnez d’abord une ou plusieurs classes.</div>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="link_child" class="btn btn-primary w-100">Lier</button>
                        </div>
                    </form>
                    <h6>Enfants liés :</h6>
                    <ul class="list-group">
                        <?php foreach($linked_children as $lc): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo $lc['prenom'].' '.$lc['nom']; ?>
                                <form action="pages/modifier_utilisateur.php?id=<?php echo $user_id; ?>" method="POST" onsubmit="return confirm('Retirer ce lien ?')">
                                    <input type="hidden" name="enfant_id" value="<?php echo $lc['id']; ?>">
                                    <button type="submit" name="unlink_child" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const parentClassMap = <?php
    $mapped_students = [];
    $class_map_result = $conn->prepare("SELECT DISTINCT i.classe_id, u.id, CONCAT(u.prenom, ' ', u.nom) AS label FROM utilisateurs u JOIN inscriptions i ON i.eleve_id = u.id WHERE u.role = 'eleve' AND NOT EXISTS (SELECT 1 FROM parents_eleves pe WHERE pe.eleve_id = u.id AND pe.parent_id <> ?) ORDER BY i.classe_id, u.nom, u.prenom");
    $class_map_result->bind_param('i', $user_id);
    $class_map_result->execute();
    $class_map_result = $class_map_result->get_result();
    while ($student_row = $class_map_result->fetch_assoc()) {
        $mapped_students[(string)$student_row['classe_id']][] = [
            'id' => (int)$student_row['id'],
            'label' => $student_row['label']
        ];
    }
    echo json_encode($mapped_students, JSON_UNESCAPED_UNICODE);
?>;

function updateParentChildChoices() {
    const classCheckboxes = document.querySelectorAll('input[name="classe_ids[]"]');
    const childWrapper = document.getElementById('parent_child_checkbox_wrapper');
    if (!childWrapper) return;

    const selectedClasses = Array.from(classCheckboxes)
        .filter(checkbox => checkbox.checked)
        .map(checkbox => checkbox.value);

    const children = [];
    selectedClasses.forEach(function(classId) {
        (parentClassMap[classId] || []).forEach(function(student) {
            children.push(student);
        });
    });

    childWrapper.innerHTML = '';
    if (children.length === 0) {
        childWrapper.innerHTML = '<div class="text-muted small">Aucun enfant disponible pour les classes sélectionnées.<br><span class="text-secondary">Tous les élèves de ces classes sont déjà liés à un parent.</span></div>';
        return;
    }

    const uniqueChildren = [];
    const seen = new Set();
    children.forEach(function(student) {
        if (!seen.has(String(student.id))) {
            seen.add(String(student.id));
            uniqueChildren.push(student);
        }
    });

    uniqueChildren.forEach(function(student) {
        const label = document.createElement('label');
        label.className = 'd-flex align-items-center gap-2 mb-2';
        label.innerHTML = '<input type="checkbox" class="form-check-input" name="enfant_ids[]" value="' + student.id + '"> <span>' + student.label + '</span>';
        childWrapper.appendChild(label);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const classCheckboxes = document.querySelectorAll('input[name="classe_ids[]"]');
    classCheckboxes.forEach(function(item) {
        item.addEventListener('change', updateParentChildChoices);
    });
    updateParentChildChoices();
});
</script>

<?php include '../includes/footer.php'; ?>

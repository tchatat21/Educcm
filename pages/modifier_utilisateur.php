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

        // 1. Récupérer l'ancienne photo pour la conserver si on n'en télécharge pas une nouvelle
        $stmt_old = $conn->prepare("SELECT photo FROM utilisateurs WHERE id = ?");
        $stmt_old->bind_param("i", $user_id);
        $stmt_old->execute();
        $old_photo = $stmt_old->get_result()->fetch_assoc()['photo'];
        $stmt_old->close();

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
        $enfant_id = (int)$_POST['enfant_id'];
        $conn->query("INSERT IGNORE INTO parents_eleves (parent_id, eleve_id) VALUES ($user_id, $enfant_id)");
        $message = "Enfant lié avec succès.";
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
                                    <option value="administrateur" <?php echo $user['role']=='administrateur'?'selected':''; ?>>Administrateur</option>
                                    <option value="enseignant" <?php echo $user['role']=='enseignant'?'selected':''; ?>>Enseignant</option>
                                    <option value="eleve" <?php echo $user['role']=='eleve'?'selected':''; ?>>Élève</option>
                                    <option value="parent" <?php echo $user['role']=='parent'?'selected':''; ?>>Parent</option>
                                </select>
                            </div>
                            <div class="col-md-12"><label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label><input type="password" name="mot_de_passe" class="form-control"></div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Photo de profil / Carte</label>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($user['photo']): ?>
                                        <img src="/G/uploads/photos/<?php echo $user['photo']; ?>" class="rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['nom']); ?>'">
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
                        <div class="col-md-9">
                            <select name="enfant_id" class="form-select">
                                <option value="">-- Choisir un élève --</option>
                                <?php foreach($all_students as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['prenom'].' '.$s['nom']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><button type="submit" name="link_child" class="btn btn-primary w-100">Lier</button></div>
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

<?php include '../includes/footer.php'; ?>

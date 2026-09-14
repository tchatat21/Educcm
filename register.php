<?php
// Fichier: register.php
// Inscription publique pour les Élèves, Parents et Enseignants avec téléversement de justificatifs
session_start();

// Si déjà connecté, redirection vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'includes/db.php';

$error = '';
$success = '';

// Récupération des classes disponibles pour les élèves
$classes_list = [];
$res_classes = $conn->query("SELECT id, nom, niveau FROM classes ORDER BY niveau, nom");
if ($res_classes) {
    while ($c = $res_classes->fetch_assoc()) {
        $classes_list[] = $c;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST['role'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // 1. Validation de base
    $allowed_roles = ['eleve', 'parent', 'enseignant'];
    if (!in_array($role, $allowed_roles)) {
        $error = "Veuillez sélectionner un profil valide (Élève, Parent ou Enseignant).";
    } elseif (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Veuillez renseigner tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Le format de l'adresse email est invalide.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $password_confirm) {
        $error = "Les deux mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email existe déjà
        $check_email = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();
        if ($check_email->num_rows > 0) {
            $error = "Cette adresse email est déjà utilisée. Veuillez vous connecter ou utiliser un autre email.";
            $check_email->close();
        } else {
            $check_email->close();

            // 2. Vérification de la photo d'identité / profil (obligatoire)
            $photo_name = 'default_avatar.png';
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $error = "La photo d'identité / profil est obligatoire.";
            } else {
                $photo_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed_img_exts = ['jpg', 'jpeg', 'png', 'webp'];
                $check_img = @getimagesize($_FILES['photo']['tmp_name']);

                if (!in_array($photo_ext, $allowed_img_exts) || $check_img === false) {
                    $error = "La photo doit être une image valide (JPG, PNG ou WEBP).";
                }
            }

            // 3. Vérifications spécifiques selon le rôle
            $justificatif_name = null;
            $enfants_noms = null;
            $classe_id = 0;

            if (empty($error)) {
                if ($role === 'eleve') {
                    $classe_id = (int)($_POST['classe_id'] ?? 0);
                    if ($classe_id <= 0) {
                        $error = "Veuillez sélectionner obligatoirement votre classe.";
                    } elseif (!isset($_FILES['justificatif_eleve']) || $_FILES['justificatif_eleve']['error'] !== UPLOAD_ERR_OK) {
                        $error = "Le bulletin de l'année précédente est obligatoire pour l'inscription d'un élève.";
                    } else {
                        $file_ext = strtolower(pathinfo($_FILES['justificatif_eleve']['name'], PATHINFO_EXTENSION));
                        $allowed_doc_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                        if (!in_array($file_ext, $allowed_doc_exts)) {
                            $error = "Le bulletin doit être au format image (JPG, PNG) ou document PDF.";
                        }
                    }
                } elseif ($role === 'parent') {
                    $enfants_noms = trim($_POST['enfants_noms'] ?? '');
                    if (empty($enfants_noms)) {
                        $error = "Veuillez renseigner le nom et prénom de votre ou vos enfant(s).";
                    } elseif (!isset($_FILES['justificatif_parent']) || $_FILES['justificatif_parent']['error'] !== UPLOAD_ERR_OK) {
                        $error = "La photo de votre CNI (Carte Nationale d'Identité) est obligatoire.";
                    } else {
                        $file_ext = strtolower(pathinfo($_FILES['justificatif_parent']['name'], PATHINFO_EXTENSION));
                        $allowed_doc_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                        if (!in_array($file_ext, $allowed_doc_exts)) {
                            $error = "La photo de la CNI doit être au format JPG, PNG ou PDF.";
                        }
                    }
                } elseif ($role === 'enseignant') {
                    if (!isset($_FILES['justificatif_enseignant']) || $_FILES['justificatif_enseignant']['error'] !== UPLOAD_ERR_OK) {
                        $error = "L'attestation prouvant votre statut d'enseignant est obligatoire.";
                    } else {
                        $file_ext = strtolower(pathinfo($_FILES['justificatif_enseignant']['name'], PATHINFO_EXTENSION));
                        $allowed_doc_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                        if (!in_array($file_ext, $allowed_doc_exts)) {
                            $error = "L'attestation d'enseignant doit être au format JPG, PNG ou PDF.";
                        }
                    }
                }
            }

            // 4. Traitement des fichiers et insertion en base
            if (empty($error)) {
                $upload_photos_dir = __DIR__ . '/uploads/photos/';
                $upload_justif_dir = __DIR__ . '/uploads/justificatifs/';
                if (!is_dir($upload_photos_dir)) @mkdir($upload_photos_dir, 0755, true);
                if (!is_dir($upload_justif_dir)) @mkdir($upload_justif_dir, 0755, true);

                // Sauvegarde de la photo
                $photo_filename = 'photo_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $photo_ext;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_photos_dir . $photo_filename)) {
                    $error = "Erreur lors de l'enregistrement de votre photo.";
                } else {
                    $photo_name = $photo_filename;
                }

                // Sauvegarde du justificatif
                if (empty($error)) {
                    $justif_file = null;
                    $prefix = '';
                    if ($role === 'eleve') {
                        $justif_file = $_FILES['justificatif_eleve'];
                        $prefix = 'bulletin_';
                    } elseif ($role === 'parent') {
                        $justif_file = $_FILES['justificatif_parent'];
                        $prefix = 'cni_';
                    } elseif ($role === 'enseignant') {
                        $justif_file = $_FILES['justificatif_enseignant'];
                        $prefix = 'attest_';
                    }

                    if ($justif_file && $justif_file['error'] === UPLOAD_ERR_OK) {
                        $j_ext = strtolower(pathinfo($justif_file['name'], PATHINFO_EXTENSION));
                        $justificatif_filename = $prefix . time() . '_' . bin2hex(random_bytes(5)) . '.' . $j_ext;
                        if (!move_uploaded_file($justif_file['tmp_name'], $upload_justif_dir . $justificatif_filename)) {
                            $error = "Erreur lors de l'enregistrement du document justificatif.";
                        } else {
                            $justificatif_name = $justificatif_filename;
                        }
                    }
                }

                // 5. Insertion dans la base de données
                if (empty($error)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $qr_token = ($role === 'eleve') ? bin2hex(random_bytes(16)) : null;
                    $statut_compte = 'en_attente'; // Mis en attente de vérification par l'administration

                    $stmt = $conn->prepare("
                        INSERT INTO utilisateurs 
                        (nom, prenom, email, mot_de_passe, role, photo, telephone, qr_token, justificatif, enfants_noms, statut_compte) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("sssssssssss", $nom, $prenom, $email, $hashed_password, $role, $photo_name, $telephone, $qr_token, $justificatif_name, $enfants_noms, $statut_compte);

                    if ($stmt->execute()) {
                        $new_user_id = $stmt->insert_id;
                        $stmt->close();

                        // Si c'est un élève, l'inscrire directement dans sa classe
                        if ($role === 'eleve' && $classe_id > 0) {
                            $stmt_ins = $conn->prepare("INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire) VALUES (?, ?, '2025-2026')");
                            $stmt_ins->bind_param("ii", $new_user_id, $classe_id);
                            $stmt_ins->execute();
                            $stmt_ins->close();
                        }

                        // Redirection vers login avec message de confirmation
                        header("Location: login.php?registered=1");
                        exit;
                    } else {
                        $error = "Erreur lors de l'enregistrement en base de données : " . $conn->error;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - EDUC.CM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
        body {
            background-color: #f1f5f9;
            min-height: 100vh;
            padding: 30px 15px;
        }
        .register-container {
            max-width: 820px;
            margin: 0 auto;
        }
        .role-card {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background: #fff;
            text-align: center;
        }
        .role-card:hover {
            border-color: #39A9C3;
            transform: translateY(-2px);
        }
        .btn-check:checked + .role-card {
            border-color: #223E6F;
            background-color: #f0f7ff;
            box-shadow: 0 4px 12px rgba(34, 62, 111, 0.12);
        }
        .section-header {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
        <!-- En-tête -->
        <div class="p-4 text-white text-center" style="background-color: #223E6F;">
            <img src="educ.jpeg" alt="Logo" class="rounded mb-2 shadow-sm" style="width: 60px; height: 60px; object-fit: contain; background: white; padding: 4px;">
            <h2 class="fw-bold mb-1">Inscription EDUC.CM</h2>
            <p class="mb-0 text-white-50 small">Créez votre compte Élève, Parent ou Enseignant</p>
        </div>

        <div class="card-body p-4 p-md-5 bg-white">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger shadow-sm rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" enctype="multipart/form-data" id="registerForm">
                
                <!-- 1. Sélection du Profil / Rôle -->
                <div class="mb-4">
                    <div class="section-header"><i class="bi bi-person-badge-fill me-1"></i> 1. Choisissez votre profil</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="radio" class="btn-check" name="role" id="role_eleve" value="eleve" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'eleve') ? 'checked' : ''; ?> onchange="onRoleChange()">
                            <label class="role-card d-block h-100" for="role_eleve">
                                <div class="rounded-circle bg-primary-subtle text-primary mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-backpack4-fill fs-4"></i>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">Élève</h6>
                                <p class="text-muted small mb-0">Accès aux cours et emploi du temps</p>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="radio" class="btn-check" name="role" id="role_parent" value="parent" <?php echo (isset($_POST['role']) && $_POST['role'] === 'parent') ? 'checked' : ''; ?> onchange="onRoleChange()">
                            <label class="role-card d-block h-100" for="role_parent">
                                <div class="rounded-circle bg-success-subtle text-success mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">Parent</h6>
                                <p class="text-muted small mb-0">Suivi d'assiduité de vos enfants</p>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="radio" class="btn-check" name="role" id="role_enseignant" value="enseignant" <?php echo (isset($_POST['role']) && $_POST['role'] === 'enseignant') ? 'checked' : ''; ?> onchange="onRoleChange()">
                            <label class="role-card d-block h-100" for="role_enseignant">
                                <div class="rounded-circle bg-warning-subtle text-warning mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-mortarboard-fill fs-4"></i>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">Enseignant</h6>
                                <p class="text-muted small mb-0">Pointage des appels et plannings</p>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 2. Informations Générales -->
                <div class="mb-4">
                    <div class="section-header"><i class="bi bi-info-circle-fill me-1"></i> 2. Informations Générales</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" name="nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required placeholder="Ex: Dupont">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" name="prenom" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required placeholder="Ex: Jean">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Adresse Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control rounded-3" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="exemple@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Numéro de Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control rounded-3" name="telephone" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>" required placeholder="+237 600 000 000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mot de passe <span class="text-danger">*</span> (min 6 car.)</label>
                            <input type="password" class="form-control rounded-3" name="password" required placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Confirmer le mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control rounded-3" name="password_confirm" required placeholder="••••••••">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">
                                Photo d'identité / Profil <span class="text-danger">*</span>
                                <span class="text-muted fw-normal">(Sera utilisée sur votre carte scolaire ou profil)</span>
                            </label>
                            <input type="file" class="form-control rounded-3" name="photo" accept="image/*" required>
                        </div>
                    </div>
                </div>

                <!-- 3. Section Spécifique ÉLÈVE -->
                <div id="section_eleve" class="role-specific mb-4">
                    <div class="section-header text-primary"><i class="bi bi-backpack4 me-1"></i> 3. Spécifications pour Élève</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Classe souhaitée / actuelle <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="classe_id" id="field_classe">
                                <option value="">-- Sélectionnez votre classe --</option>
                                <?php foreach ($classes_list as $cls): ?>
                                    <option value="<?php echo $cls['id']; ?>" <?php echo (isset($_POST['classe_id']) && $_POST['classe_id'] == $cls['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cls['nom'] . ' (' . $cls['niveau'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">
                                Bulletin de l'année précédente <span class="text-danger">*</span>
                                <span class="text-muted fw-normal">(JPG, PNG ou PDF)</span>
                            </label>
                            <input type="file" class="form-control rounded-3" name="justificatif_eleve" id="field_bulletin" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                    </div>
                </div>

                <!-- 4. Section Spécifique PARENT -->
                <div id="section_parent" class="role-specific mb-4 d-none">
                    <div class="section-header text-success"><i class="bi bi-people me-1"></i> 3. Spécifications pour Parent</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">
                                Photo de votre CNI (Carte Nationale d'Identité) <span class="text-danger">*</span>
                                <span class="text-muted fw-normal">(JPG, PNG ou PDF)</span>
                            </label>
                            <input type="file" class="form-control rounded-3" name="justificatif_parent" id="field_cni" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">
                                Nom(s) et Prénom(s) de votre ou vos enfant(s) <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control rounded-3" name="enfants_noms" id="field_enfants" rows="2" placeholder="Ex: Dupont Leo (Seconde A), Dupont Marie (Première B)"><?php echo htmlspecialchars($_POST['enfants_noms'] ?? ''); ?></textarea>
                            <small class="text-muted">Indiquez les noms complets et si possible la classe de vos enfants pour faciliter la liaison par l'administration.</small>
                        </div>
                    </div>
                </div>

                <!-- 5. Section Spécifique ENSEIGNANT -->
                <div id="section_enseignant" class="role-specific mb-4 d-none">
                    <div class="section-header text-warning"><i class="bi bi-mortarboard me-1"></i> 3. Spécifications pour Enseignant</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">
                                Attestation d'enseignement / Justificatif officiel <span class="text-danger">*</span>
                                <span class="text-muted fw-normal">(Diplôme, carte professionnelle ou arrêté - JPG, PNG ou PDF)</span>
                            </label>
                            <input type="file" class="form-control rounded-3" name="justificatif_enseignant" id="field_attestation" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                    </div>
                </div>

                <!-- Bouton de Soumission -->
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                        <i class="bi bi-check-circle-fill me-2"></i> Valider mon inscription
                    </button>
                </div>

                <div class="mt-4 text-center">
                    <p class="text-muted small mb-0">
                        Vous avez déjà un compte ? 
                        <a href="login.php" class="fw-bold text-decoration-none">Connectez-vous ici</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function onRoleChange() {
    const roleEleve = document.getElementById('role_eleve').checked;
    const roleParent = document.getElementById('role_parent').checked;
    const roleEnseignant = document.getElementById('role_enseignant').checked;

    const secEleve = document.getElementById('section_eleve');
    const secParent = document.getElementById('section_parent');
    const secEnseignant = document.getElementById('section_enseignant');

    const fieldClasse = document.getElementById('field_classe');
    const fieldBulletin = document.getElementById('field_bulletin');
    const fieldCni = document.getElementById('field_cni');
    const fieldEnfants = document.getElementById('field_enfants');
    const fieldAttestation = document.getElementById('field_attestation');

    if (roleEleve) {
        secEleve.classList.remove('d-none');
        secParent.classList.add('d-none');
        secEnseignant.classList.add('d-none');

        if (fieldClasse) fieldClasse.required = true;
        if (fieldBulletin) fieldBulletin.required = true;
        if (fieldCni) fieldCni.required = false;
        if (fieldEnfants) fieldEnfants.required = false;
        if (fieldAttestation) fieldAttestation.required = false;
    } else if (roleParent) {
        secEleve.classList.add('d-none');
        secParent.classList.remove('d-none');
        secEnseignant.classList.add('d-none');

        if (fieldClasse) fieldClasse.required = false;
        if (fieldBulletin) fieldBulletin.required = false;
        if (fieldCni) fieldCni.required = true;
        if (fieldEnfants) fieldEnfants.required = true;
        if (fieldAttestation) fieldAttestation.required = false;
    } else if (roleEnseignant) {
        secEleve.classList.add('d-none');
        secParent.classList.add('d-none');
        secEnseignant.classList.remove('d-none');

        if (fieldClasse) fieldClasse.required = false;
        if (fieldBulletin) fieldBulletin.required = false;
        if (fieldCni) fieldCni.required = false;
        if (fieldEnfants) fieldEnfants.required = false;
        if (fieldAttestation) fieldAttestation.required = true;
    }
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', onRoleChange);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/notifications.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// 0. Vérification d'authentification et de rôle
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'enseignant' && $_SESSION['user_role'] !== 'administrateur')) {
    die(json_encode(['success' => false, 'message' => 'Accès non autorisé. Veuillez vous connecter.']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Méthode non autorisée.']));
}

$qr_token = trim($_POST['qr_token'] ?? '');
$course_id = (int)($_POST['course_id'] ?? 0);
$type = $_POST['type'] ?? 'normal'; // 'normal' ou 'supplementaire'
$enseignant_id = (int)$_SESSION['user_id'];
$date_cours = date('Y-m-d');

if (empty($qr_token) || $course_id === 0) {
    die(json_encode(['success' => false, 'message' => 'Données incomplètes.']));
}

// 1. Identifier l'élève via son jeton QR
$stmt = $conn->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE qr_token = ? AND role = 'eleve' LIMIT 1");
$stmt->bind_param("s", $qr_token);
$stmt->execute();
$eleve = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$eleve) {
    die(json_encode(['success' => false, 'message' => 'Jeton QR invalide ou élève introuvable.']));
}

$eleve_id = (int)$eleve['id'];
$eleve_nom = $eleve['prenom'] . ' ' . $eleve['nom'];

// 2. Traitement selon le type de cours
if ($type === 'supplementaire') {
    // Récupérer la classe et matière du cours supplémentaire
    $stmt_c = $conn->prepare("SELECT cs.classe_id, m.nom as matiere_nom FROM cours_supplementaires cs JOIN matieres m ON cs.matiere_id = m.id WHERE cs.id = ?");
    $stmt_c->bind_param("i", $course_id);
    $stmt_c->execute();
    $c_info = $stmt_c->get_result()->fetch_assoc();
    $stmt_c->close();

    if (!$c_info) {
        die(json_encode(['success' => false, 'message' => 'Cours supplémentaire introuvable.']));
    }

    $target_classe_id = (int)$c_info['classe_id'];
    $matiere_nom = $c_info['matiere_nom'] . " (Suppl.)";

    // Vérifier si l'élève est inscrit dans cette classe
    $stmt_inscr = $conn->prepare("SELECT id FROM inscriptions WHERE eleve_id = ? AND classe_id = ?");
    $stmt_inscr->bind_param("ii", $eleve_id, $target_classe_id);
    $stmt_inscr->execute();
    if ($stmt_inscr->get_result()->num_rows === 0) {
        $stmt_inscr->close();
        die(json_encode(['success' => false, 'message' => "$eleve_nom n'appartient pas à la classe de ce cours."]));
    }
    $stmt_inscr->close();

    // Vérifier si déjà présent en cours suppl.
    $stmt_chk = $conn->prepare("SELECT id FROM presences_supplementaires WHERE eleve_id = ? AND cours_supp_id = ?");
    $stmt_chk->bind_param("ii", $eleve_id, $course_id);
    $stmt_chk->execute();
    if ($stmt_chk->get_result()->num_rows > 0) {
        $stmt_chk->close();
        die(json_encode(['success' => false, 'message' => "$eleve_nom est déjà scanné.", 'eleve_nom' => $eleve_nom]));
    }
    $stmt_chk->close();

    // Insérer présence suppl.
    $ins = $conn->prepare("INSERT INTO presences_supplementaires (eleve_id, cours_supp_id, statut) VALUES (?, ?, 'Présent')");
    $ins->bind_param("ii", $eleve_id, $course_id);
    $success = $ins->execute();
    $ins->close();

} else {
    // Cours Normal
    $stmt_c = $conn->prepare("SELECT edt.classe_id, m.nom as matiere_nom FROM emploi_du_temps edt JOIN matieres m ON edt.matiere_id = m.id WHERE edt.id = ?");
    $stmt_c->bind_param("i", $course_id);
    $stmt_c->execute();
    $c_info = $stmt_c->get_result()->fetch_assoc();
    $stmt_c->close();

    if (!$c_info) {
        die(json_encode(['success' => false, 'message' => 'Cours normal introuvable.']));
    }

    $target_classe_id = (int)$c_info['classe_id'];
    $matiere_nom = $c_info['matiere_nom'];

    // Vérifier si l'élève est inscrit dans cette classe
    $stmt_inscr = $conn->prepare("SELECT id FROM inscriptions WHERE eleve_id = ? AND classe_id = ?");
    $stmt_inscr->bind_param("ii", $eleve_id, $target_classe_id);
    $stmt_inscr->execute();
    if ($stmt_inscr->get_result()->num_rows === 0) {
        $stmt_inscr->close();
        die(json_encode(['success' => false, 'message' => "$eleve_nom n'appartient pas à la classe de ce cours."]));
    }
    $stmt_inscr->close();

    // Vérifier si déjà présent
    $stmt_chk = $conn->prepare("SELECT id FROM presences WHERE eleve_id = ? AND emploi_du_temps_id = ? AND date_cours = ?");
    $stmt_chk->bind_param("iis", $eleve_id, $course_id, $date_cours);
    $stmt_chk->execute();
    if ($stmt_chk->get_result()->num_rows > 0) {
        $stmt_chk->close();
        die(json_encode(['success' => false, 'message' => "$eleve_nom est déjà scanné.", 'eleve_nom' => $eleve_nom]));
    }
    $stmt_chk->close();

    $ins = $conn->prepare("INSERT INTO presences (eleve_id, emploi_du_temps_id, date_cours, statut, enregistre_par) VALUES (?, ?, ?, 'Présent', ?)");
    $ins->bind_param("iisi", $eleve_id, $course_id, $date_cours, $enseignant_id);
    $success = $ins->execute();
    $ins->close();
}

// 3. Notifier le parent si succès
if ($success) {
    notifierScanQR($conn, $eleve_id, $matiere_nom);
    echo json_encode(['success' => true, 'eleve_nom' => $eleve_nom]);
} else {
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'enregistrement."]);
}

<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/notifications.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Méthode non autorisée.']));
}

$qr_token = $_POST['qr_token'] ?? '';
$course_id = (int)($_POST['course_id'] ?? 0);
$type = $_POST['type'] ?? 'normal'; // 'normal' ou 'supplementaire'
$enseignant_id = $_SESSION['user_id'];
$date_cours = date('Y-m-d');

if (empty($qr_token) || $course_id === 0) {
    die(json_encode(['success' => false, 'message' => 'Données incomplètes.']));
}

// 1. Identifier l'élève
$stmt = $conn->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE qr_token = ? AND role = 'eleve' LIMIT 1");
$stmt->bind_param("s", $qr_token);
$stmt->execute();
$eleve = $stmt->get_result()->fetch_assoc();

if (!$eleve) {
    die(json_encode(['success' => false, 'message' => 'Token invalide ou élève introuvable.']));
}

$eleve_id = $eleve['id'];
$eleve_nom = $eleve['prenom'] . ' ' . $eleve['nom'];

// 2. Traitement selon le type de cours
if ($type === 'supplementaire') {
    // Vérifier si déjà présent en cours suppl.
    $check = $conn->query("SELECT id FROM presences_supplementaires WHERE eleve_id = $eleve_id AND cours_supp_id = $course_id");
    if ($check->num_rows > 0) {
        die(json_encode(['success' => false, 'message' => "$eleve_nom est déjà scanné.", 'eleve_nom' => $eleve_nom]));
    }

    // Insérer présence suppl.
    $ins = $conn->prepare("INSERT INTO presences_supplementaires (eleve_id, cours_supp_id, statut) VALUES (?, ?, 'Présent')");
    $ins->bind_param("ii", $eleve_id, $course_id);
    $success = $ins->execute();
    
    $matiere_nom = "Cours Supplémentaire"; // Fallback
    $stmt_m = $conn->query("SELECT m.nom FROM cours_supplementaires cs JOIN matieres m ON cs.matiere_id = m.id WHERE cs.id = $course_id");
    if($row = $stmt_m->fetch_assoc()) $matiere_nom = $row['nom'] . " (Suppl.)";

} else {
    // Cours Normal
    $check = $conn->query("SELECT id FROM presences WHERE eleve_id = $eleve_id AND emploi_du_temps_id = $course_id AND date_cours = '$date_cours'");
    if ($check->num_rows > 0) {
        die(json_encode(['success' => false, 'message' => "$eleve_nom est déjà scanné.", 'eleve_nom' => $eleve_nom]));
    }

    $ins = $conn->prepare("INSERT INTO presences (eleve_id, emploi_du_temps_id, date_cours, statut, enregistre_par) VALUES (?, ?, ?, 'Présent', ?)");
    $ins->bind_param("iisi", $eleve_id, $course_id, $date_cours, $enseignant_id);
    $success = $ins->execute();

    $matiere_nom = "Cours Normal"; // Fallback
    $stmt_m = $conn->query("SELECT m.nom FROM emploi_du_temps edt JOIN matieres m ON edt.matiere_id = m.id WHERE edt.id = $course_id");
    if($row = $stmt_m->fetch_assoc()) $matiere_nom = $row['nom'];
}

// 3. Notifier le parent si succès
if ($success) {
    notifierScanQR($conn, $eleve_id, $matiere_nom);
    echo json_encode(['success' => true, 'eleve_nom' => $eleve_nom]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement.']);
}
?>

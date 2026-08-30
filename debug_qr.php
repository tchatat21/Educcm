<?php
require_once 'includes/db.php';

echo "<h3>Diagnostic QR Code</h3>";

// 1. Vérifier si la colonne existe
$res = $conn->query("SHOW COLUMNS FROM utilisateurs LIKE 'qr_token'");
if ($res->num_rows == 0) {
    echo "❌ La colonne 'qr_token' n'existe pas. Tentative de création...<br>";
    $conn->query("ALTER TABLE utilisateurs ADD COLUMN qr_token VARCHAR(100) UNIQUE DEFAULT NULL");
} else {
    echo "✅ La colonne 'qr_token' existe.<br>";
}

// 2. Compter les utilisateurs sans token
$res = $conn->query("SELECT COUNT(*) as total FROM utilisateurs WHERE qr_token IS NULL AND role='eleve'");
$row = $res->fetch_assoc();
echo "Utilisateurs sans jeton : " . $row['total'] . "<br>";

if ($row['total'] > 0) {
    echo "Génération des jetons en cours...<br>";
    $users = $conn->query("SELECT id FROM utilisateurs WHERE qr_token IS NULL");
    while ($user = $users->fetch_assoc()) {
        $token = bin2hex(random_bytes(16));
        $stmt = $conn->prepare("UPDATE utilisateurs SET qr_token = ? WHERE id = ?");
        $stmt->bind_param("si", $token, $user['id']);
        $stmt->execute();
    }
    echo "✅ Jetons générés avec succès.<br>";
}

// 3. Afficher un exemple
$res = $conn->query("SELECT id, nom, prenom, qr_token FROM utilisateurs WHERE role='eleve' LIMIT 5");
echo "<h4>Liste des 5 premiers élèves :</h4><ul>";
while($u = $res->fetch_assoc()) {
    echo "<li>ID: " . $u['id'] . " - " . $u['nom'] . " " . $u['prenom'] . " | Token: <b>" . ($u['qr_token'] ?: 'VIDE') . "</b></li>";
}
echo "</ul>";
?>

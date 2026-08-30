<?php
require_once 'includes/db.php';

// 1. Exécuter l'ALTER TABLE (si pas déjà fait)
$sql_check = "SHOW COLUMNS FROM `utilisateurs` LIKE 'qr_token'";
$result = $conn->query($sql_check);
if ($result->num_rows == 0) {
    $sql_alter = "ALTER TABLE `utilisateurs` 
                  ADD COLUMN `photo` VARCHAR(255) DEFAULT 'default_avatar.png' AFTER `role`,
                  ADD COLUMN `telephone` VARCHAR(20) DEFAULT NULL AFTER `photo`,
                  ADD COLUMN `qr_token` VARCHAR(100) UNIQUE DEFAULT NULL AFTER `telephone` ";
    if ($conn->query($sql_alter)) {
        echo "Table utilisateurs mise à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour : " . $conn->error . "<br>";
    }
}

// 2. Générer des tokens uniques pour les utilisateurs existants
$users = $conn->query("SELECT id FROM utilisateurs WHERE qr_token IS NULL");
while ($user = $users->fetch_assoc()) {
    $token = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("UPDATE utilisateurs SET qr_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $user['id']);
    $stmt->execute();
}
echo "Jetons QR Code générés pour tous les utilisateurs.<br>";

// 3. Renommer le logo de l'établissement
$old_name = "WhatsApp Image 2026-02-18 at 17.49.38.jpeg";
$new_name = "logo_ecole.jpeg";
if (file_exists($old_name)) {
    rename($old_name, $new_name);
    echo "Logo renommé en $new_name.<br>";
} else {
    echo "Le fichier logo est introuvable sous le nom d'origine.<br>";
}

echo "<br><b>Configuration terminée !</b> Vous pouvez supprimer ce fichier.";
?>

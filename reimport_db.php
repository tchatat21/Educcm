<?php
/**
 * Script de réinitialisation de la base de données
 * Ce script supprime la base de données existante et la réimporte depuis gestion_scolaire_complete.sql
 */

// Connexion MySQL sans la base de données
$mysqli = new mysqli('127.0.0.1', 'root', '', null, 3306);

if ($mysqli->connect_error) {
    die("ERREUR: Impossible de se connecter à MySQL. " . $mysqli->connect_error);
}

echo "<h2>🔄 Réinitialisation de la base de données Educcm</h2>";

// 1. Supprimer la base de données existante
echo "<p>1️⃣ Suppression de la base de données 'gestion_scolaire'...</p>";
if ($mysqli->query("DROP DATABASE IF EXISTS `gestion_scolaire`")) {
    echo "<p style='color:green;'>✅ Base de données supprimée avec succès</p>";
} else {
    die("❌ Erreur lors de la suppression : " . $mysqli->error);
}

// 2. Lire le fichier SQL
echo "<p>2️⃣ Lecture du fichier SQL...</p>";
$sql_file = __DIR__ . '/gestion_scolaire_complete.sql';

if (!file_exists($sql_file)) {
    die("❌ Erreur : Le fichier gestion_scolaire_complete.sql n'existe pas");
}

$sql_content = file_get_contents($sql_file);

if (!$sql_content) {
    die("❌ Erreur : Impossible de lire le fichier SQL");
}

echo "<p style='color:green;'>✅ Fichier SQL chargé (" . strlen($sql_content) . " octets)</p>";

// 3. Exécuter le script SQL avec multi_query
echo "<p>3️⃣ Exécution des commandes SQL...</p>";

// Utiliser multi_query pour exécuter le script SQL complet
if ($mysqli->multi_query($sql_content)) {
    $success_count = 0;
    do {
        $success_count++;
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->next_result());
    
    echo "<p style='color:green;'>✅ Script SQL exécuté avec succès ($success_count commandes)</p>";
} else {
    echo "<p style='color:red;'>❌ Erreur lors de l'exécution : " . $mysqli->error . "</p>";
}

// 4. Vérification
echo "<p>4️⃣ Vérification...</p>";
$mysqli->select_db('gestion_scolaire');

$result = $mysqli->query("SELECT COUNT(*) as total FROM utilisateurs");
$row = $result->fetch_assoc();
echo "<p style='color:green;'>✅ Utilisateurs présents : " . $row['total'] . "</p>";

// 5. Afficher les identifiants de connexion
echo "<hr>";
echo "<h3 style='color:#223E6F;'>📋 Identifiants de connexion mis à jour :</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
echo "<tr><td><strong>Rôle</strong></td><td><strong>Email</strong></td><td><strong>Mot de passe</strong></td></tr>";
echo "<tr><td>Administrateur</td><td>admin@ecole.com</td><td>admin123</td></tr>";
echo "<tr><td>Enseignant</td><td>p.durand@ecole.com</td><td>prof123</td></tr>";
echo "<tr><td>Enseignant</td><td>s.martin@ecole.com</td><td>prof456</td></tr>";
echo "<tr><td>Élève</td><td>leo.dupont@email.com</td><td>eleve123</td></tr>";
echo "<tr><td>Élève</td><td>mia.petit@email.com</td><td>eleve456</td></tr>";
echo "<tr><td>Parent</td><td>j.dupont@email.com</td><td>parent123</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p style='color:green; font-weight:bold;'>✅ Base de données réinitialisée avec succès !</p>";
echo "<p><a href='login.php' style='color:blue; text-decoration:underline;'>➜ Aller à la page de connexion</a></p>";

$mysqli->close();
?>

<?php
// Fichier : includes/db.php
// mysql://root:dElLkGOSThJTGJgCMvgnukZrDaqlQWXn@altaria.proxy.rlwy.net:37233/gestion_scolaire
// 1. Récupération des constantes via les variables d'environnement de Railway (avec valeurs par défaut pour le local)
define('DB_SERVER', getenv('MYSQLHOST') ?: 'altaria.proxy.rlwy.net');
define('DB_USERNAME', getenv('MYSQLUSER') ?: 'root');
define('DB_PASSWORD', getenv('MYSQLPASSWORD') ?: 'dElLkGOSThJTGJgCMvgnukZrDaqlQWXn');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'gestion_scolaire');
define('DB_PORT', getenv('MYSQLPORT') ?: 37233);

// 2. Création de la connexion à la base de données avec mysqli (en incluant le port)
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, (int)DB_PORT);

// 3. Vérification de la connexion
if ($conn->connect_error) {
    // Si la connexion échoue, on arrête le script et on affiche une erreur.
    die("ERREUR : La connexion à la base de données a échoué. " . $conn->connect_error);
}

// 4. Définir le jeu de caractères en UTF-8
$conn->set_charset("utf8mb4");

// --- AUTO-RÉPARATION DE LA BASE DE DONNÉES ---
// Vérifie si la colonne is_read existe, sinon la crée
$check_col = $conn->query("SHOW COLUMNS FROM `notifications` LIKE 'is_read'");
if ($check_col && $check_col->num_rows == 0) {
    $conn->query("ALTER TABLE `notifications` ADD COLUMN `is_read` TINYINT(1) DEFAULT 0 AFTER `status` ");
}

// Création de la table settings si elle n'existe pas
$conn->query("CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Initialisation des paramètres du cachet si vide
$default_settings = [
    'school_stamp' => '',
    'stamp_top' => '-8',
    'stamp_right' => '2',
    'stamp_size' => '12'
];

foreach ($default_settings as $key => $value) {
    $check = $conn->query("SELECT setting_key FROM settings WHERE setting_key = '$key'");
    if ($check && $check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
}
// ----------------------------------------------

?>
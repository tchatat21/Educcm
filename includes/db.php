<?php
// Fichier : includes/db.php

// Fichier : includes/db.php

// 1. Fonction utilitaire pour lire les variables d'environnement de façon 100% fiable
function getEnvVar($key, $default = null)
{
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '')
        return $_SERVER[$key];
    if (isset($_ENV[$key]) && $_ENV[$key] !== '')
        return $_ENV[$key];
    $val = getenv($key);
    if ($val !== false && $val !== '')
        return $val;
    return $default;
}

// 2. Récupération des constantes (sans fallback sur localhost pour forcer l'erreur si vide)
$host = getEnvVar('MYSQLHOST');
$user = getEnvVar('MYSQLUSER');
$pass = getEnvVar('MYSQLPASSWORD');
$db = getEnvVar('MYSQLDATABASE');
$port = getEnvVar('MYSQLPORT', 3306);

// --- VÉRIFICATION DE SÉCURITÉ ---
if (empty($host)) {
    die("ERREUR CRITIQUE : PHP n'arrive toujours pas à lire les variables d'environnement sur Railway.");
}
// --------------------------------

define('DB_SERVER', $host);
define('DB_USERNAME', $user);
define('DB_PASSWORD', $pass);
define('DB_NAME', $db);
define('DB_PORT', $port);

// 3. Création de la connexion à la base de données avec mysqli
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, (int) DB_PORT);

// 4. Vérification de la connexion
if ($conn->connect_error) {
    die("ERREUR : La connexion à la base de données a échoué. " . $conn->connect_error);
}

// 5. Définir le jeu de caractères en UTF-8
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
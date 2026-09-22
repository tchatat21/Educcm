<?php
// Fichier : includes/db.php
// Ce fichier gère la connexion à la base de données MySQL.

// 1. Définition des constantes de connexion
// Railway injecte ces variables automatiquement quand le service MySQL est lié
// Noms possibles selon la version Railway : MYSQLHOST ou MYSQL_HOST
define('DB_SERVER',   getenv('MYSQLHOST')     ?: getenv('MYSQL_HOST')     ?: getenv('DB_SERVER')   ?: '127.0.0.1');
define('DB_USERNAME', getenv('MYSQLUSER')     ?: getenv('MYSQL_USER')     ?: getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('DB_PASSWORD') ?: '');
define('DB_NAME',     getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME')     ?: 'gestion_scolaire');
define('DB_PORT',     (int)(getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: 3306));

// Sur Railway, forcer une connexion TCP (jamais via socket Unix)
// mysqli traite 'localhost' comme socket Unix — utiliser 127.0.0.1 force TCP
$db_host = DB_SERVER;
if ($db_host === 'localhost') $db_host = '127.0.0.1';

// 2. Création de la connexion à la base de données avec mysqli
$conn = new mysqli($db_host, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

// 3. Vérification de la connexion
if ($conn->connect_error) {
    // Si la connexion échoue, on arrête le script et on affiche une erreur.
    // C'est une façon simple de gérer les erreurs pour un projet de débutant.
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

// Vérifie les colonnes pour les inscriptions avec justificatifs
$check_justif = $conn->query("SHOW COLUMNS FROM `utilisateurs` LIKE 'justificatif'");
if ($check_justif && $check_justif->num_rows == 0) {
    $conn->query("ALTER TABLE `utilisateurs` 
        ADD COLUMN `justificatif` VARCHAR(255) DEFAULT NULL AFTER `qr_token`,
        ADD COLUMN `enfants_noms` TEXT DEFAULT NULL AFTER `justificatif`,
        ADD COLUMN `statut_compte` VARCHAR(20) DEFAULT 'actif' AFTER `enfants_noms`");
}

// Création des dossiers d'uploads si inexistants
$justif_dir = __DIR__ . '/../uploads/justificatifs';
if (!is_dir($justif_dir)) @mkdir($justif_dir, 0755, true);
$photos_dir = __DIR__ . '/../uploads/photos';
if (!is_dir($photos_dir)) @mkdir($photos_dir, 0755, true);

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
        $stmt->close();
    }
}
// ----------------------------------------------

// Le script s'arrête ici.
?>

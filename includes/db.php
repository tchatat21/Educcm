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

// Année scolaire partagée par les inscriptions, listes et documents imprimés.
require_once __DIR__ . '/school_year.php';
require_once __DIR__ . '/school_settings.php';

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
    `setting_value` LONGTEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Les logos encodés en base64 dépassent parfois la limite du type TEXT.
$settings_value_column = $conn->query("SHOW COLUMNS FROM `settings` LIKE 'setting_value'");
if ($settings_value_column && ($settings_column = $settings_value_column->fetch_assoc()) && strtolower($settings_column['Type']) !== 'longtext') {
    $conn->query("ALTER TABLE `settings` MODIFY `setting_value` LONGTEXT NULL");
}

// Création de la table pour le suivi des validations automatisées des inscriptions
$conn->query("CREATE TABLE IF NOT EXISTS `registration_validations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role` VARCHAR(30) NOT NULL,
    `validation_score` INT NOT NULL,
    `is_valid` TINYINT(1) NOT NULL DEFAULT 0,
    `checks` JSON DEFAULT NULL,
    `issues` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_validation` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `parents_eleves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL,
    `eleve_id` INT NOT NULL,
    UNIQUE KEY `unique_parent_eleve` (`parent_id`, `eleve_id`),
    KEY `eleve_id` (`eleve_id`),
    CONSTRAINT `fk_parents_eleves_parent` FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_parents_eleves_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `parents_enfants` (
    `parent_id` INT NOT NULL,
    `enfant_id` INT NOT NULL,
    PRIMARY KEY (`parent_id`, `enfant_id`),
    KEY `enfant_id` (`enfant_id`),
    CONSTRAINT `fk_parents_enfants_parent` FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_parents_enfants_enfant` FOREIGN KEY (`enfant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Initialisation des paramètres du cachet si vide
$default_settings = [
    'school_name' => 'EDUC.CM',
    'school_logo_data' => '',
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

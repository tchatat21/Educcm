<?php
// Fichier : includes/db.php
// mysql://root:cjaBEiUlvdeWUgNYyCzYRmhcZFvIAIOe@mysql.railway.internal:3306/gestion_scolaire
// 1. Définition des constantes de connexion
define('DB_SERVER', 'localhost'); // Adresse du serveur MySQL
define('DB_USERNAME', 'root');      // Nom d'utilisateur de la base de données
define('DB_PASSWORD', 'cjaBEiUlvdeWUgNYyCzYRmhcZFvIAIOe');          // Mot de passe de la base de données
define('DB_NAME', 'gestion_scolaire'); // Nom de la base de données

// 2. Création de la connexion à la base de données avec mysqli
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

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
if ($check_col->num_rows == 0) {
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
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
}
// ----------------------------------------------

// Le script s'arrête ici.
?>

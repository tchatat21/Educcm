<?php
// Fichier : includes/db.php

// 1. Fonction utilitaire pour lire les variables d'environnement de façon 100% fiable
// Parcourt $_SERVER, $_ENV et getenv() dans cet ordre
function getEnvVar($key, $default = null)
{
    // $_SERVER peut contenir les vars d'env sur certains serveurs
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    // $_ENV nécessite variables_order="E..." dans php.ini
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    // getenv() est la méthode la plus fiable sur Railway/Nixpacks
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    return $default;
}

// 2. Tenter de parser DATABASE_URL si les variables séparées sont absentes
//    Railway peut injecter une URL de type : mysql://user:pass@host:port/dbname
$host = getEnvVar('MYSQLHOST');
$user = getEnvVar('MYSQLUSER');
$pass = getEnvVar('MYSQLPASSWORD');
$db   = getEnvVar('MYSQLDATABASE');
$port = getEnvVar('MYSQLPORT', 3306);

if (empty($host)) {
    $databaseUrl = getEnvVar('DATABASE_URL') ?: getEnvVar('MYSQL_URL') ?: getEnvVar('MYSQL_PUBLIC_URL');
    if (!empty($databaseUrl)) {
        $parsed = parse_url($databaseUrl);
        $host = $parsed['host']  ?? null;
        $user = $parsed['user']  ?? null;
        $pass = $parsed['pass']  ?? null;
        $port = $parsed['port']  ?? 3306;
        // Le nom de la base est dans le chemin, ex: /dbname → dbname
        $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : null;
    }
}

// --- VÉRIFICATION DE SÉCURITÉ ---
if (empty($host)) {
    // Diagnostic détaillé pour faciliter le débogage
    $debug  = "ERREUR : Impossible de lire les variables d'environnement Railway.\n\n";
    $debug .= "Variables attendues : MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT\n";
    $debug .= "Ou : DATABASE_URL / MYSQL_URL\n\n";
    $debug .= "Vérifiez que ces variables sont bien définies dans Railway → Variables.\n";
    $debug .= "variables_order PHP actuel : " . ini_get('variables_order') . "\n";
    die(nl2br(htmlspecialchars($debug)));
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
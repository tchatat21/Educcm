<?php
// Fichier: index.php
// Point d'entrée de l'application

// Démarrer la session pour pouvoir vérifier le statut de connexion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin de base de l'application dynamiquement
// Sur Railway : /    Sur XAMPP local : /Educcm/
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$base = ($script_dir === '/' || $script_dir === '\\') ? '/' : rtrim($script_dir, '/') . '/';
define('BASE_URL', $base);

// Si l'utilisateur est connecté (sa session existe), le rediriger vers le tableau de bord.
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
} 
// Sinon, le rediriger vers la page de connexion.
else {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Aucun contenu HTML n'est nécessaire ici, car cette page ne fait que des redirections.

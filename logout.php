<?php
// Fichier: logout.php

// 1. Démarrer la session
session_start();

// 2. Détruire toutes les variables de session
$_SESSION = array();

// 3. Détruire la session
session_destroy();

// 4. Rediriger vers la page de connexion
header("Location: login.php");
exit;
?>

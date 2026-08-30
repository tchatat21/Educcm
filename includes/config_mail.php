<?php
// Configuration SMTP pour PHPMailer
define('SMTP_HOST', 'smtp.mailtrap.io'); // Remplacez par smtp.gmail.com pour Gmail
define('SMTP_PORT', 2525);               // 587 pour Gmail (TLS)
define('SMTP_USER', 'votre_user_id');    // Votre login SMTP
define('SMTP_PASS', 'votre_password');   // Votre mot de passe (ou mdp d'application Gmail)
define('SMTP_FROM', 'ecole@votre-domaine.com');
define('SMTP_FROM_NAME', 'EDUC.CM');
?>

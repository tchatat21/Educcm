<?php
// Fichier: login.php

// 1. Démarrer la session PHP.
session_start();

// Si l'utilisateur est déjà connecté, on le redirige vers le tableau de bord.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Inclure le fichier de connexion à la base de données
require_once 'includes/db.php';

$error_message = '';

// 2. Vérifier si le formulaire a été envoyé (méthode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, nom, prenom, mot_de_passe, role, statut_compte FROM utilisateurs WHERE email = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        
        if ($stmt->execute()) {
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $nom, $prenom, $hashed_password, $role, $statut_compte);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        if ($statut_compte === 'en_attente') {
                            $error_message = "Votre compte a bien été créé mais il est actuellement en attente de validation par l'administration après examen de vos pièces justificatives.";
                        } elseif ($statut_compte === 'bloque') {
                            $error_message = "Votre compte a été suspendu par l'administration. Veuillez contacter l'école.";
                        } else {
                            session_regenerate_id(true); // Régénérer l'ID de session pour la sécurité
                            
                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_nom'] = $nom;
                            $_SESSION['user_prenom'] = $prenom;
                            $_SESSION['user_role'] = $role;
                            
                            header("Location: dashboard.php");
                            exit;
                        }
                    } else {
                        $error_message = "L'email ou le mot de passe est incorrect.";
                    }
                }
            } else {
                $error_message = "L'email ou le mot de passe est incorrect.";
            }
        } else {
            $error_message = "Oops! Quelque chose s'est mal passé. Veuillez réessayer plus tard.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EDUC.CM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-body p-4 p-sm-5 text-center">
            <img src="educ.jpeg" alt="Logo" class="mb-3" style="width: 80px; height: 80px; object-fit: contain;">
            <h1 class="card-title mb-2 h2">EDUC.CM</h1>
            <p class="card-subtitle text-muted mb-4">Veuillez vous connecter pour continuer</p>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success text-start" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <strong>Inscription enregistrée !</strong><br>
                    Votre demande a été transmise avec succès. L'administration va examiner vos pièces justificatives avant d'activer votre compte.
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nom@example.com" required>
                    <label for="email"><i class="bi bi-envelope-fill"></i> Email</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                    <label for="password"><i class="bi bi-lock-fill"></i> Mot de passe</label>
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-box-arrow-in-right"></i> Se connecter</button>
                </div>
            </form>

            <hr class="my-4">
            <div>
                <p class="text-muted small mb-2">Vous êtes un nouvel élève, parent ou enseignant ?</p>
                <a href="register.php" class="btn btn-outline-success w-100 py-2 rounded-3">
                    <i class="bi bi-person-plus-fill me-1"></i> Créer mon compte
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

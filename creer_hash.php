<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de Hash de Mot de Passe</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Générateur de Hash</h1>
        <p style="text-align:center; color: #777;">
            Utilisez cet outil pour créer un mot de passe haché à insérer dans la base de données.
        </p>

        <?php
        $hashed_password = '';
        if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['password_to_hash'])) {
            $password_to_hash = $_POST['password_to_hash'];
            // Utilisation de l'algorithme par défaut, qui est sécurisé.
            $hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);
        }
        ?>

        <form action="creer_hash.php" method="post">
            <div class="input-group">
                <label for="password_to_hash">Mot de passe à hacher :</label>
                <input type="text" id="password_to_hash" name="password_to_hash" required>
            </div>
            <button type="submit">Hacher le mot de passe</button>
        </form>

        <?php if ($hashed_password): ?>
            <div class="card" style="margin-top: 30px; word-wrap: break-word;">
                <h2>Mot de passe haché :</h2>
                <p>Copiez cette chaîne de caractères et collez-la dans la colonne `mot_de_passe` lors de la création d'un utilisateur.</p>
                <pre style="background-color: #eee; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($hashed_password); ?></pre>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 20px; text-align:center;">
            <a href="login.php">Retour à la page de connexion</a>
        </p>
    </div>
</body>
</html>

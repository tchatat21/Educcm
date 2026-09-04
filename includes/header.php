<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php'; // Toujours charger la DB en premier

define('BASE_URL', '/');

$public_pages = ['login.php', 'creer_hash.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
    $user_nom = $_SESSION['user_nom'] ?? '';
    $user_role = $_SESSION['user_role'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo BASE_URL; ?>">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Tableau de Bord'; ?> - EDUC.CM</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="css/main.css">
    <script src="js/main.js" defer></script>
</head>
<body class="bg-light">

<!-- Toast Container pour notifications en temps réel -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="liveToast" class="toast hide shadow-lg border-0 rounded-4" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header border-0 text-white rounded-top-4" style="background-color: #223E6F;">
            <i class="bi bi-bell-fill me-2"></i>
            <strong class="me-auto">Alerte Scolaire</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-white rounded-bottom-4 p-3" id="toastMessage">
            <!-- Message rempli par JS -->
        </div>
    </div>
</div>

<?php if (isset($user_id)): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="/G/educ.jpeg" alt="Logo" class="rounded me-2" style="width: 30px; height: 30px; object-fit: contain;">
            EDUC.CM
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php"><i class="bi bi-house-door-fill"></i> Tableau de Bord</a>
                </li>
                
                <?php if ($user_role === 'administrateur'): ?>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'gestion_utilisateurs.php') ? 'active' : ''; ?>" href="pages/gestion_utilisateurs.php"><i class="bi bi-people-fill"></i> Utilisateurs</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'gestion_classes.php') ? 'active' : ''; ?>" href="pages/gestion_classes.php"><i class="bi bi-door-open-fill"></i> Classes</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'gestion_matieres.php') ? 'active' : ''; ?>" href="pages/gestion_matieres.php"><i class="bi bi-book-fill"></i> Matières</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'impression_groupée.php') ? 'active' : ''; ?>" href="pages/impression_groupée.php"><i class="bi bi-printer-fill text-warning"></i> Impression Cartes</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'gestion_emplois_du_temps.php') ? 'active' : ''; ?>" href="pages/gestion_emplois_du_temps.php"><i class="bi bi-calendar-week-fill"></i> Emplois du temps</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'suivi_absences.php') ? 'active' : ''; ?>" href="pages/suivi_absences.php"><i class="bi bi-person-check-fill"></i> Suivi Absences</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'suivi_notifications.php') ? 'active' : ''; ?>" href="pages/suivi_notifications.php"><i class="bi bi-bell-fill"></i> Notifications</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'parametres_ecole.php') ? 'active' : ''; ?>" href="pages/parametres_ecole.php"><i class="bi bi-gear-fill"></i> Paramètres</a></li>
                <?php endif; ?>

                <?php if ($user_role === 'enseignant'): ?>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'faire_appel.php') ? 'active' : ''; ?>" href="pages/faire_appel.php"><i class="bi bi-check-circle-fill"></i> Faire l'appel</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'gestion_cours_supplementaires.php') ? 'active' : ''; ?>" href="pages/gestion_cours_supplementaires.php"><i class="bi bi-plus-circle-fill"></i> Cours Supplémentaires</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'suivi_absences.php') ? 'active' : ''; ?>" href="pages/suivi_absences.php"><i class="bi bi-person-check-fill"></i> Suivi Absences</a></li>
                <?php endif; ?>

                <?php if ($user_role === 'eleve'): ?>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'mes_absences.php') ? 'active' : ''; ?>" href="pages/mes_absences.php"><i class="bi bi-calendar-x-fill"></i> Mes Absences</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'mon_emploi_du_temps.php') ? 'active' : ''; ?>" href="pages/mon_emploi_du_temps.php"><i class="bi bi-calendar3"></i> Mon Emploi du temps</a></li>
                <?php endif; ?>

                <?php if ($user_role === 'parent'): 
                    // Compter les notifications non lues avec sécurité
                    $unread_count = 0;
                    try {
                        $res_notif = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE recipient_id = $user_id AND is_read = 0");
                        if ($res_notif) $unread_count = $res_notif->fetch_assoc()['unread'];
                    } catch (Exception $e) {
                        $unread_count = 0; // Sécurité si la colonne n'existe pas encore
                    }
                ?>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'absences_enfants.php') ? 'active' : ''; ?>" href="pages/absences_enfants.php"><i class="bi bi-person-rolodex"></i> Enfants</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'emploi_du_temps_enfant.php') ? 'active' : ''; ?>" href="pages/emploi_du_temps_enfant.php"><i class="bi bi-calendar3"></i> Emplois du temps</a></li>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo ($current_page == 'notifications_parent.php') ? 'active' : ''; ?>" href="pages/notifications_parent.php">
                            <i class="bi bi-bell-fill"></i> Notifications
                            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; <?php echo ($unread_count > 0) ? '' : 'display:none;'; ?>">
                                <?php echo $unread_count; ?>
                            </span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text me-3">
                Bienvenue, <?php echo htmlspecialchars($user_prenom); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="container-fluid mt-4">
    <div class="p-3 mb-4 bg-white rounded-3 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
             <h1 class="display-6"><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Tableau de Bord'; ?></h1>
             <?php if (isset($user_id)): ?>
                <div class="text-end">
                    <p class="mb-0"><strong><?php echo htmlspecialchars($user_prenom . ' ' . $user_nom); ?></strong></p>
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars(ucfirst($user_role)); ?></p>
                </div>
            <?php endif; ?>
        </div>
       
    </div>

    <!-- Le contenu de la page spécifique commence ici -->
    <div class="card">
        <div class="card-body">
</main>

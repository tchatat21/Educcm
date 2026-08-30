<?php
$page_title = 'Mes Notifications';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'parent') {
    die("Accès refusé.");
}

$parent_id = $_SESSION['user_id'];

// Marquer tout comme lu si demandé
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE recipient_id = $parent_id");
    header("Location: notifications_parent.php"); exit;
}

// Récupérer les notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE recipient_id = $parent_id ORDER BY date_creation DESC LIMIT 30");
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Centre de Notifications</h3>
        <p class="text-muted small mb-0">Suivez les alertes concernant la scolarité de vos enfants.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <form method="POST">
            <button type="submit" name="mark_all_read" class="btn btn-outline-primary rounded-pill btn-sm px-4">
                <i class="bi bi-check2-all me-2"></i> Tout marquer comme lu
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-10 mx-auto">
        <?php if ($notifications->num_rows > 0): ?>
            <div class="list-group shadow-sm rounded-4 overflow-hidden border-0">
                <?php while ($n = $notifications->fetch_assoc()): 
                    $is_unread = ($n['is_read'] == 0);
                ?>
                    <div class="list-group-item p-4 border-0 border-bottom <?php echo $is_unread ? 'bg-light-blue' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle <?php echo $is_unread ? 'bg-primary' : 'bg-secondary'; ?> p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <i class="bi <?php echo $is_unread ? 'bi-envelope-fill' : 'bi-envelope-open'; ?> small"></i>
                                </div>
                                <h6 class="mb-0 fw-bold <?php echo $is_unread ? 'text-primary' : 'text-dark'; ?>">
                                    Alerte Scolaire
                                </h6>
                            </div>
                            <small class="text-muted"><?php echo date('d/m/Y à H:i', strtotime($n['date_creation'])); ?></small>
                        </div>
                        <p class="mb-0 text-dark ms-5" style="line-height: 1.6;">
                            <?php echo nl2br($n['message']); ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash display-1 text-muted opacity-25"></i>
                <h5 class="mt-3 fw-bold text-muted">Aucune notification pour le moment.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .bg-light-blue { background-color: #f0f7ff !important; border-left: 4px solid #223E6F !important; }
    .text-navy { color: #223E6F; }
</style>

<?php include '../includes/footer.php'; ?>

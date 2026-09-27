<?php
$page_title = 'Mes messages';
include __DIR__ . '/../includes/header.php';

$user_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'La session du formulaire a expiré. Rechargez la page et réessayez.';
    } else {
        $read_stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE recipient_id = ? AND is_read = 0');
        $read_stmt->bind_param('i', $user_id);
        if ($read_stmt->execute()) {
            $message = 'Tous les messages ont été marqués comme lus.';
        } else {
            $error = 'Impossible de mettre à jour les messages.';
        }
        $read_stmt->close();
    }
}

$notifications_stmt = $conn->prepare('SELECT message, date_creation, is_read FROM notifications WHERE recipient_id = ? ORDER BY date_creation DESC LIMIT 100');
$notifications_stmt->bind_param('i', $user_id);
$notifications_stmt->execute();
$notifications = $notifications_stmt->get_result();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Boîte de réception</h2>
        <p class="text-muted mb-0">Annonces et notifications de l’établissement.</p>
    </div>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
            <i class="bi bi-check2-all me-2"></i>Tout marquer comme lu
        </button>
    </form>
</div>

<?php if ($message !== ''): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php if ($notifications->num_rows > 0): ?>
    <div class="list-group">
        <?php while ($notification = $notifications->fetch_assoc()): ?>
            <article class="list-group-item py-3 <?php echo (int)$notification['is_read'] === 0 ? 'list-group-item-primary' : ''; ?>">
                <div class="d-flex justify-content-between gap-3 mb-2">
                    <strong><?php echo (int)$notification['is_read'] === 0 ? 'Nouveau message' : 'Message'; ?></strong>
                    <time class="small text-muted" datetime="<?php echo htmlspecialchars($notification['date_creation']); ?>"><?php echo date('d/m/Y à H:i', strtotime($notification['date_creation'])); ?></time>
                </div>
                <div><?php echo nl2br(htmlspecialchars($notification['message'])); ?></div>
            </article>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox display-4 d-block mb-3" aria-hidden="true"></i>
        <p class="mb-0">Aucun message pour le moment.</p>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
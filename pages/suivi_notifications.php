<?php
$page_title = 'Suivi des Notifications';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur') {
    die("Accès refusé.");
}

$notifications = $conn->query("
    SELECT n.*, u.nom as parent_nom, u.prenom as parent_prenom 
    FROM notifications n 
    JOIN utilisateurs u ON n.recipient_id = u.id 
    ORDER BY n.date_creation DESC 
    LIMIT 50
");
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Notifications aux Parents</h3>
        <p class="text-muted small">Historique des alertes envoyées par le système.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Destinataire</th>
                        <th>Message</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($n = $notifications->fetch_assoc()): 
                        $status_badge = ($n['status'] == 'sent' || $n['status'] == 'pending') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($n['parent_prenom'].' '.$n['parent_nom']); ?></div>
                            </td>
                            <td><div class="small text-muted" style="max-width: 400px;"><?php echo $n['message']; ?></div></td>
                            <td><span class="badge bg-light text-dark border"><?php echo strtoupper($n['type']); ?></span></td>
                            <td class="small"><?php echo date('d/m H:i', strtotime($n['date_creation'])); ?></td>
                            <td class="text-end pe-4">
                                <span class="badge <?php echo $status_badge; ?> rounded-pill px-3">
                                    <i class="bi bi-check-all me-1"></i> <?php echo ucfirst($n['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

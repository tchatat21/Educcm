<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (($_SESSION['user_role'] ?? '') !== 'administrateur') {
    http_response_code(403);
    exit('Accès refusé.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$allowed_roles = ['parent', 'eleve', 'enseignant'];
if (isset($_GET['sent']) && ctype_digit((string)$_GET['sent'])) {
    $message = 'Message diffusé dans les boîtes de réception de ' . (int)$_GET['sent'] . ' utilisateur(s).';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])) {
    $token = $_POST['csrf_token'] ?? '';
    $raw_body = $_POST['message'] ?? '';
    $body = is_string($raw_body) ? trim($raw_body) : '';
    $target_role = $_POST['target_role'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'La session du formulaire a expiré. Rechargez la page et réessayez.';
    } elseif (mb_strlen($body, 'UTF-8') < 5 || mb_strlen($body, 'UTF-8') > 2000) {
        $error = 'Le message doit contenir entre 5 et 2 000 caractères.';
    } elseif ($target_role !== 'tous' && !in_array($target_role, $allowed_roles, true)) {
        $error = 'Veuillez choisir un groupe de destinataires valide.';
    } else {
        // Seuls les comptes actifs reçoivent les annonces; les administrateurs ne sont pas ciblés.
        if ($target_role === 'tous') {
            $recipient_stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE role IN ('parent', 'eleve', 'enseignant') AND (statut_compte = 'actif' OR statut_compte IS NULL)");
        } else {
            $recipient_stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE role = ? AND (statut_compte = 'actif' OR statut_compte IS NULL)");
            if ($recipient_stmt) {
                $recipient_stmt->bind_param('s', $target_role);
            }
        }

        if (!$recipient_stmt || !$recipient_stmt->execute()) {
            $error = 'Impossible de récupérer les destinataires.';
        } else {
            $recipients = $recipient_stmt->get_result();
            $insert_stmt = $conn->prepare("INSERT INTO notifications (recipient_id, message, type, status, is_read) VALUES (?, ?, 'email', 'sent', 0)");
            if (!$insert_stmt) {
                $error = 'Impossible de préparer la diffusion du message.';
            } else {
                $conn->begin_transaction();
                $recipient_id = 0;
                $insert_stmt->bind_param('is', $recipient_id, $body);
                $sent_count = 0;

                while ($recipient = $recipients->fetch_assoc()) {
                    $recipient_id = (int)$recipient['id'];
                    if (!$insert_stmt->execute()) {
                        $error = 'La diffusion a échoué; aucune notification n’a été enregistrée.';
                        break;
                    }
                    $sent_count++;
                }

                if ($error !== '') {
                    $conn->rollback();
                } else {
                    $conn->commit();
                    header('Location: diffusion_messages.php?sent=' . $sent_count);
                    exit;
                }
                $insert_stmt->close();
            }
            $recipient_stmt->close();
        }
    }
}

$page_title = 'Diffusion de messages';
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <?php if ($message !== ''): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Nouvelle annonce</h2>
                <form method="post" action="pages/diffusion_messages.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="target_role">Destinataires</label>
                        <select class="form-select" name="target_role" id="target_role" required>
                            <option value="tous">Tous les parents, élèves et enseignants</option>
                            <option value="parent">Parents</option>
                            <option value="eleve">Élèves</option>
                            <option value="enseignant">Enseignants</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="6" minlength="5" maxlength="2000" required></textarea>
                        <div class="form-text">Le message apparaîtra dans la boîte de réception des comptes actifs sélectionnés.</div>
                    </div>
                    <button class="btn btn-primary" type="submit" name="send_broadcast">
                        <i class="bi bi-send-fill me-2"></i>Diffuser le message
                    </button>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
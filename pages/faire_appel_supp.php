<?php
$page_title = 'Appel : Cours Supplémentaire';
include '../includes/header.php';
require_once '../includes/db.php';
require_once '../includes/notifications.php';

if ($_SESSION['user_role'] !== 'enseignant') {
    die("Accès refusé.");
}

$enseignant_id = $_SESSION['user_id'];
$cours_id = isset($_GET['cours_id']) ? (int)$_GET['cours_id'] : 0;
$message = $_GET['message'] ?? '';
$error = '';

if ($cours_id <= 0) {
    header('Location: pages/gestion_cours_supplementaires.php');
    exit;
}

// 1. Récupérer les détails du cours
$stmt = $conn->prepare("
    SELECT cs.id, cs.date_heure, c.id as classe_id, c.nom as classe_nom, m.nom as matiere_nom 
    FROM cours_supplementaires cs 
    JOIN classes c ON cs.classe_id = c.id 
    JOIN matieres m ON cs.matiere_id = m.id 
    WHERE cs.id = ? AND cs.enseignant_id = ?
");
$stmt->bind_param("ii", $cours_id, $enseignant_id);
$stmt->execute();
$cours = $stmt->get_result()->fetch_assoc();

if (!$cours) {
    echo "<div class='container mt-5'><div class='alert alert-danger shadow-sm'>Cours introuvable ou vous n'avez pas les droits.</div></div>";
    include '../includes/footer.php'; exit;
}

// 2. Enregistrement de l'appel
if (isset($_POST['submit_attendance'])) {
    $attendances = $_POST['attendance'] ?? [];
    
    $conn->begin_transaction();
    try {
        // Supprimer l'ancien appel s'il existe
        $conn->query("DELETE FROM presences_supplementaires WHERE cours_supp_id = $cours_id");
        
        $ins = $conn->prepare("INSERT INTO presences_supplementaires (cours_supp_id, eleve_id, statut) VALUES (?, ?, ?)");
        foreach ($attendances as $eleve_id => $statut) {
            $ins->bind_param("iis", $cours_id, $eleve_id, $statut);
            $ins->execute();
            
            // Notification aux parents (Optionnel pour cours supp, activé ici)
            if ($statut !== 'Présent') {
                notifierParent($conn, $eleve_id, $statut, $cours['matiere_nom'] . " (Cours Supplémentaire)");
            }
        }
        $conn->commit();
        header("Location: faire_appel_supp.php?cours_id=$cours_id&message=" . urlencode("Appel enregistré !"));
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Erreur : " . $e->getMessage();
    }
}

// 3. Récupérer les élèves et les présences existantes
$students = $conn->query("SELECT u.id, u.nom, u.prenom, u.photo FROM utilisateurs u JOIN inscriptions i ON u.id = i.eleve_id WHERE i.classe_id = {$cours['classe_id']} ORDER BY u.nom, u.prenom");

$existing = [];
$res_ex = $conn->query("SELECT eleve_id, statut FROM presences_supplementaires WHERE cours_supp_id = $cours_id");
while($row = $res_ex->fetch_assoc()) $existing[$row['eleve_id']] = $row['statut'];
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h3 class="fw-bold text-navy mb-0">Appel : <?php echo htmlspecialchars($cours['matiere_nom']); ?></h3>
        <p class="text-muted small mb-0">
            <i class="bi bi-door-open me-1"></i> Classe : <?php echo htmlspecialchars($cours['classe_nom']); ?> | 
            <i class="bi bi-calendar-event me-1"></i> <?php echo date('d/m/Y à H:i', strtotime($cours['date_heure'])); ?>
        </p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="pages/gestion_cours_supplementaires.php" class="btn btn-outline-secondary rounded-pill shadow-sm px-4">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success shadow-sm rounded-4 mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $message; ?></div><?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="card-header bg-white py-3 border-0">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0 fw-bold">Liste des élèves</h5>
            <span id="callCount" class="badge bg-primary rounded-pill">0 / <?php echo $students->num_rows; ?></span>
        </div>
        <div class="progress" style="height: 10px;">
            <div id="callProgress" class="progress-bar bg-success progress-bar-striped" style="width: 0%"></div>
        </div>
    </div>
    <div class="card-body p-0">
        <form action="pages/faire_appel_supp.php?cours_id=<?php echo $cours_id; ?>" method="POST" id="suppCallForm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        <?php while($s = $students->fetch_assoc()): 
                            $current = $existing[$s['id']] ?? 'Présent';
                            $avatar = !empty($s['photo']) ? "/uploads/photos/".$s['photo'] : "https://ui-avatars.com/api/?name=".urlencode($s['nom']);
                        ?>
                            <tr>
                                <td class="ps-4" style="width: 60px;">
                                    <img src="<?php echo $avatar; ?>" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($s['nom'].' '.$s['prenom']); ?></div>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="attendance-options d-inline-flex gap-1">
                                        <?php 
                                        $opts = ['Présent' => 'success', 'Absent' => 'danger', 'Retard' => 'warning', 'Excusé' => 'secondary'];
                                        foreach($opts as $status => $color): 
                                            $isChecked = ($current == $status);
                                        ?>
                                            <input type="radio" class="btn-check" name="attendance[<?php echo $s['id']; ?>]" 
                                                   id="s_<?php echo $s['id'].'_'.$status; ?>" value="<?php echo $status; ?>" 
                                                   <?php echo $isChecked ? 'checked' : ''; ?> onchange="updateProgress()">
                                            <label class="btn btn-sm btn-outline-<?php echo $color; ?> rounded-pill px-3" for="s_<?php echo $s['id'].'_'.$status; ?>">
                                                <span class="d-none d-md-inline"><?php echo $status; ?></span>
                                                <i class="bi bi-record-circle d-md-none"></i>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 text-center bg-light border-top">
                <button type="submit" name="submit_attendance" class="btn btn-success btn-lg px-5 rounded-pill shadow">
                    <i class="bi bi-cloud-check-fill me-2"></i> Valider l'appel de la séance
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateProgress() {
        const total = <?php echo $students->num_rows; ?>;
        const checked = document.querySelectorAll('input[type="radio"]:checked').length;
        const pct = (checked / total) * 100;
        document.getElementById('callCount').innerText = `${checked} / ${total}`;
        document.getElementById('callProgress').style.width = `${pct}%`;
    }
    window.onload = updateProgress;
</script>

<style>
    .btn-check:checked + .btn-outline-success { background-color: #198754; color: white; }
    .btn-check:checked + .btn-outline-danger { background-color: #dc3545; color: white; }
    .btn-check:checked + .btn-outline-warning { background-color: #ffc107; color: white; }
    .btn-check:checked + .btn-outline-secondary { background-color: #6c757d; color: white; }
</style>

<?php include '../includes/footer.php'; ?>

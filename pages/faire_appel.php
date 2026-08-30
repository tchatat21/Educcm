<?php
$page_title = 'Faire l\'appel';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'enseignant') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Accès refusé.</div></div>";
    include '../includes/footer.php';
    exit;
}

$enseignant_id = $_SESSION['user_id'];
$message = $_GET['message'] ?? '';
$error = '';
$selected_edt_id = isset($_GET['edt_id']) ? (int)$_GET['edt_id'] : 0;
$today_fr = ['Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'][date('l')];

// Traitement de l'enregistrement (POST)
if (isset($_POST['submit_attendance'])) {
    require_once '../includes/notifications.php';
    $edt_id_submit = $_POST['edt_id'];
    $date_cours = date('Y-m-d');
    $attendances = $_POST['attendance'];

    // Récupérer le nom de la matière pour le message
    $stmt_m = $conn->prepare("SELECT m.nom FROM emploi_du_temps edt JOIN matieres m ON edt.matiere_id = m.id WHERE edt.id = ?");
    $stmt_m->bind_param("i", $edt_id_submit);
    $stmt_m->execute();
    $matiere_nom = $stmt_m->get_result()->fetch_assoc()['nom'];

    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM presences WHERE emploi_du_temps_id = $edt_id_submit AND date_cours = '$date_cours'");
        $ins = $conn->prepare("INSERT INTO presences (emploi_du_temps_id, eleve_id, date_cours, statut, enregistre_par) VALUES (?, ?, ?, ?, ?)");
        foreach ($attendances as $eleve_id => $statut) {
            $ins->bind_param("iissi", $edt_id_submit, $eleve_id, $date_cours, $statut, $enseignant_id);
            $ins->execute();
            
            // Envoyer une notification si l'élève n'est pas présent
            if ($statut !== 'Présent') {
                notifierParent($conn, $eleve_id, $statut, $matiere_nom);
            }
        }
        $conn->commit();
        header("Location: faire_appel.php?message=" . urlencode("Appel enregistré et parents notifiés") . "&edt_id=$edt_id_submit");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les cours du jour
$stmt = $conn->prepare("SELECT edt.id, edt.heure_debut, c.nom as classe_nom, m.nom as matiere_nom FROM emploi_du_temps edt JOIN classes c ON edt.classe_id = c.id JOIN matieres m ON edt.matiere_id = m.id WHERE edt.enseignant_id = ? AND edt.jour_semaine = ? ORDER BY edt.heure_debut");
$stmt->bind_param("is", $enseignant_id, $today_fr);
$stmt->execute();
$today_courses = $stmt->get_result();
?>

<!-- AFFICHAGE DES MESSAGES -->
<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Liste des cours (Gauche) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-event text-primary"></i> Mes cours du jour</h5>
                <small class="text-muted"><?php echo $today_fr . ' ' . date('d/m/Y'); ?></small>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush rounded-bottom-4 overflow-hidden">
                    <?php if ($today_courses->num_rows > 0): ?>
                        <?php while($c = $today_courses->fetch_assoc()): ?>
                            <a href="pages/faire_appel.php?edt_id=<?php echo $c['id']; ?>" 
                               class="list-group-item list-group-item-action p-3 <?php echo ($selected_edt_id == $c['id']) ? 'active bg-primary' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($c['matiere_nom']); ?></h6>
                                    <span class="badge <?php echo ($selected_edt_id == $c['id']) ? 'bg-white text-primary' : 'bg-light text-dark border'; ?> rounded-pill">
                                        <?php echo date('H:i', strtotime($c['heure_debut'])); ?>
                                    </span>
                                </div>
                                <p class="mb-0 small opacity-75">Classe : <?php echo htmlspecialchars($c['classe_nom']); ?></p>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-emoji-sunglasses fs-1 d-block mb-2"></i>
                            Aucun cours prévu aujourd'hui.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire d'appel (Droite) -->
    <div class="col-lg-8">
        <?php if ($selected_edt_id > 0): 
            // Récupérer infos cours
            $stmt_c = $conn->prepare("SELECT edt.id, c.id as classe_id, c.nom as classe_nom, m.nom as matiere_nom FROM emploi_du_temps edt JOIN classes c ON edt.classe_id = c.id JOIN matieres m ON edt.matiere_id = m.id WHERE edt.id = ?");
            $stmt_c->bind_param("i", $selected_edt_id);
            $stmt_c->execute();
            $course = $stmt_c->get_result()->fetch_assoc();

            // Récupérer élèves
            $stmt_s = $conn->prepare("SELECT u.id, u.nom, u.prenom, u.photo FROM utilisateurs u JOIN inscriptions i ON u.id = i.eleve_id WHERE i.classe_id = ? ORDER BY u.nom, u.prenom");
            $stmt_s->bind_param("i", $course['classe_id']);
            $stmt_s->execute();
            $students = $stmt_s->get_result();
            
            // Récupérer présences existantes
            $existing = [];
            $res_ex = $conn->query("SELECT eleve_id, statut FROM presences WHERE emploi_du_temps_id = $selected_edt_id AND date_cours = '".date('Y-m-d')."'");
            while($row = $res_ex->fetch_assoc()) $existing[$row['eleve_id']] = $row['statut'];
        ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Appel : <?php echo htmlspecialchars($course['matiere_nom']); ?> (<?php echo htmlspecialchars($course['classe_nom']); ?>)</h5>
                        <div class="text-end">
                            <span id="callCount" class="badge bg-primary rounded-pill">0 / <?php echo $students->num_rows; ?></span>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div id="callProgress" class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="p-3 border-bottom bg-light">
                        <input type="text" id="studentSearch" class="form-control rounded-pill" placeholder="Chercher un élève...">
                    </div>
                    <form action="<?php echo BASE_URL; ?>pages/faire_appel.php" method="POST" id="attendanceForm">
                        <input type="hidden" name="edt_id" value="<?php echo $selected_edt_id; ?>">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="studentTable">
                                <tbody>
                                    <?php while($s = $students->fetch_assoc()): 
                                        $current_statut = $existing[$s['id']] ?? 'Présent';
                                        
                                        $photo_name = $s['photo'];
                                        $photo_path = "../uploads/photos/" . $photo_name;
                                        if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                                            $avatar = "/G/uploads/photos/" . $photo_name;
                                        } else {
                                            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($s['prenom'] . ' ' . $s['nom']) . "&background=random&color=fff";
                                        }
                                    ?>
                                        <tr>
                                            <td class="ps-4" style="width: 50px;">
                                                <img src="<?php echo $avatar; ?>" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <div class="fw-bold mb-0"><?php echo htmlspecialchars($s['nom'].' '.$s['prenom']); ?></div>
                                            </td>
                                            <td class="pe-4">
                                                <div class="attendance-control d-flex justify-content-end gap-1">
                                                    <?php 
                                                    $options = ['Présent' => 'check-circle', 'Absent' => 'x-circle', 'Retard' => 'clock', 'Excusé' => 'info-circle'];
                                                    $colors = ['Présent' => 'btn-outline-success', 'Absent' => 'btn-outline-danger', 'Retard' => 'btn-outline-warning', 'Excusé' => 'btn-outline-secondary'];
                                                    foreach($options as $val => $icon): 
                                                        $isChecked = ($current_statut == $val);
                                                    ?>
                                                        <input type="radio" class="btn-check" name="attendance[<?php echo $s['id']; ?>]" 
                                                               id="s_<?php echo $s['id'].'_'.$val; ?>" value="<?php echo $val; ?>" 
                                                               <?php echo $isChecked ? 'checked' : ''; ?> autocomplete="off" onchange="updateProgress()">
                                                        <label class="btn btn-sm <?php echo $colors[$val]; ?> rounded-pill px-3" for="s_<?php echo $s['id'].'_'.$val; ?>">
                                                            <i class="bi bi-<?php echo $icon; ?>"></i> <span class="d-none d-md-inline"><?php echo $val; ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 text-end bg-light rounded-bottom-4">
                            <button type="submit" name="submit_attendance" class="btn btn-success btn-lg px-5 rounded-pill shadow">
                                <i class="bi bi-cloud-check-fill me-2"></i> Enregistrer l'appel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 h-100 d-flex align-items-center justify-content-center p-5 text-center">
                <div class="opacity-25">
                    <i class="bi bi-arrow-left-circle display-1"></i>
                    <h4 class="mt-3 fw-bold">Sélectionnez un cours à gauche pour commencer l'appel</h4>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function updateProgress() {
        const total = <?php echo $selected_edt_id > 0 ? $students->num_rows : 0; ?>;
        if(total === 0) return;
        
        // On compte combien d'élèves ont un statut différent de "Présent" par défaut si vous voulez forcer l'appel
        // Ici on va juste compter combien de boutons ont été cliqués/activés
        const inputs = document.querySelectorAll('input[type="radio"]:checked');
        const count = inputs.length;
        const percent = (count / total) * 100;
        
        document.getElementById('callCount').innerText = `${count} / ${total}`;
        document.getElementById('callProgress').style.width = `${percent}%`;
    }

    // Recherche instantanée
    document.getElementById('studentSearch')?.addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#studentTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });

    // Initialisation au chargement
    window.onload = updateProgress;
</script>

<style>
    .btn-check:checked + .btn-outline-success { background-color: #198754; color: white; }
    .btn-check:checked + .btn-outline-danger { background-color: #dc3545; color: white; }
    .btn-check:checked + .btn-outline-warning { background-color: #ffc107; color: white; }
    .btn-check:checked + .btn-outline-secondary { background-color: #6c757d; color: white; }
    .list-group-item.active { border-color: transparent; }
    #studentTable tr:hover { background-color: #f8fafc; }
</style>

<?php include '../includes/footer.php'; ?>

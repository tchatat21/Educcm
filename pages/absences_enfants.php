<?php
$page_title = 'Suivi Scolaire de mes Enfants';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'parent') {
    die("Accès refusé.");
}

$parent_id = $_SESSION['user_id'];

// 1. Récupérer tous les enfants liés (Table parents_eleves)
$stmt_children = $conn->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo, c.nom as classe_nom 
    FROM parents_eleves pe 
    JOIN utilisateurs u ON pe.eleve_id = u.id 
    LEFT JOIN inscriptions i ON u.id = i.eleve_id
    LEFT JOIN classes c ON i.classe_id = c.id
    WHERE pe.parent_id = ?
");
$stmt_children->bind_param("i", $parent_id);
$stmt_children->execute();
$children = $stmt_children->get_result();

// Si l'ID d'un enfant spécifique est demandé
$selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-navy mb-0">Espace Parent : Suivi d'Assiduité</h3>
        <p class="text-muted small">Consultez les présences et les retards de vos enfants en temps réel.</p>
    </div>
</div>

<?php if ($children->num_rows > 0): ?>
    <div class="row g-4">
        <?php while($child = $children->fetch_assoc()): 
            // Statistiques pour cet enfant
            $cid = $child['id'];
            $stats = $conn->query("SELECT SUM(CASE WHEN statut = 'Absent' THEN 1 ELSE 0 END) as abs, SUM(CASE WHEN statut = 'Retard' THEN 1 ELSE 0 END) as ret FROM presences WHERE eleve_id = $cid")->fetch_assoc();
            $absences_count = $stats['abs'] ?: 0;
            $retards_count = $stats['ret'] ?: 0;
            
            $status_color = ($absences_count > 3) ? 'danger' : (($absences_count > 0) ? 'warning' : 'success');
            $avatar = !empty($child['photo']) ? "/uploads/photos/".$child['photo'] : "https://ui-avatars.com/api/?name=".urlencode($child['nom'])."&background=random";
        ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="position-relative">
                                <img src="<?php echo $avatar; ?>" class="rounded-circle shadow-sm border border-3 border-white" style="width: 70px; height: 70px; object-fit: cover;">
                                <span class="position-absolute bottom-0 end-0 p-2 bg-<?php echo $status_color; ?> border border-2 border-white rounded-circle"></span>
                            </div>
                            <div class="ms-3">
                                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($child['prenom'].' '.$child['nom']); ?></h5>
                                <span class="badge bg-light text-dark border rounded-pill small"><?php echo htmlspecialchars($child['classe_nom'] ?: 'Classe non définie'); ?></span>
                            </div>
                            <div class="ms-auto d-flex gap-2">
                                <a href="pages/emploi_du_temps_enfant.php?child_id=<?php echo $cid; ?>" class="btn btn-sm btn-outline-info rounded-pill" title="Emploi du temps"><i class="bi bi-calendar3"></i></a>
                                <a href="pages/carte_scolaire.php?id=<?php echo $cid; ?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-card-image"></i> Carte ID</a>
                            </div>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="bg-light p-3 rounded-3 text-center">
                                    <div class="text-danger fw-black h4 mb-0"><?php echo $absences_count; ?></div>
                                    <div class="text-muted x-small fw-bold">ABSENCES</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light p-3 rounded-3 text-center">
                                    <div class="text-warning fw-black h4 mb-0"><?php echo $retards_count; ?></div>
                                    <div class="text-muted x-small fw-bold">RETARDS</div>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold small text-muted mb-3"><i class="bi bi-clock-history"></i> DERNIERS ÉVÉNEMENTS</h6>
                        <div class="list-group list-group-flush small">
                            <?php
                            $stmt_abs = $conn->prepare("
                                SELECT p.*, m.nom as matiere_nom 
                                FROM presences p 
                                JOIN emploi_du_temps edt ON p.emploi_du_temps_id = edt.id 
                                JOIN matieres m ON edt.matiere_id = m.id 
                                WHERE p.eleve_id = ? AND p.statut != 'Présent' 
                                ORDER BY p.date_cours DESC LIMIT 3
                            ");
                            $stmt_abs->bind_param("i", $cid);
                            $stmt_abs->execute();
                            $res_abs = $stmt_abs->get_result();
                            
                            if ($res_abs->num_rows > 0):
                                while($a = $res_abs->fetch_assoc()): ?>
                                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom-dashed">
                                        <div>
                                            <span class="fw-bold"><?php echo date('d/m', strtotime($a['date_cours'])); ?></span> - 
                                            <span class="text-muted"><?php echo htmlspecialchars($a['matiere_nom']); ?></span>
                                        </div>
                                        <span class="badge <?php echo ($a['statut']=='Absent')?'bg-danger-subtle text-danger':'bg-warning-subtle text-warning'; ?> rounded-pill">
                                            <?php echo $a['statut']; ?>
                                        </span>
                                    </div>
                                <?php endwhile;
                            else: ?>
                                <p class="text-success small mb-0"><i class="bi bi-check-circle"></i> Aucune absence signalée récemment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 p-3 text-center">
                        <button class="btn btn-link btn-sm text-decoration-none fw-bold"><i class="bi bi-chat-dots"></i> Contacter la vie scolaire</button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="bi bi-people display-1 text-muted opacity-25"></i>
        <h4 class="mt-3 fw-bold">Aucun enfant lié</h4>
        <p class="text-muted">Il semblerait qu'aucun compte élève ne soit rattaché à votre profil parent.<br>Veuillez contacter l'administration pour effectuer la liaison.</p>
    </div>
<?php endif; ?>

<style>
    .fw-black { font-weight: 900; }
    .x-small { font-size: 0.7rem; }
    .border-bottom-dashed { border-bottom: 1px dashed #eee !important; }
    .bg-danger-subtle { background-color: #fee2e2; }
    .bg-warning-subtle { background-color: #fef3c7; }
</style>

<?php include '../includes/footer.php'; ?>

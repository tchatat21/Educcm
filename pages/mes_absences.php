<?php
$page_title = 'Mon Bilan de Présence';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'eleve') {
    die("Accès refusé.");
}

$eleve_id = $_SESSION['user_id'];

// Statistiques personnelles
$stats = $conn->query("
    SELECT 
        SUM(CASE WHEN statut = 'Absent' THEN 1 ELSE 0 END) as absents,
        SUM(CASE WHEN statut = 'Retard' THEN 1 ELSE 0 END) as retards,
        SUM(CASE WHEN statut = 'Excusé' THEN 1 ELSE 0 END) as excuses
    FROM presences 
    WHERE eleve_id = $eleve_id
")->fetch_assoc();

// Liste chronologique enrichie avec infos prof et horaires
$absences = $conn->query("
    SELECT p.*, m.nom as matiere_nom, edt.heure_debut, edt.heure_fin, 
           u_prof.nom as prof_nom, u_prof.prenom as prof_prenom
    FROM presences p
    JOIN emploi_du_temps edt ON p.emploi_du_temps_id = edt.id
    JOIN matieres m ON edt.matiere_id = m.id
    JOIN utilisateurs u_prof ON p.enregistre_par = u_prof.id
    WHERE p.eleve_id = $eleve_id AND p.statut != 'Présent'
    ORDER BY p.date_cours DESC
");
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-primary mb-0">Mon Bilan de Présence</h3>
        <p class="text-muted small">Suivez votre assiduité tout au long de l'année.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
            <div class="card-body">
                <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-x-circle-fill fs-3"></i>
                </div>
                <h2 class="fw-black mb-0"><?php echo $stats['absents'] ?: 0; ?></h2>
                <p class="text-muted small fw-bold mb-0">ABSENCES</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
            <div class="card-body">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-clock-fill fs-3"></i>
                </div>
                <h2 class="fw-black mb-0"><?php echo $stats['retards'] ?: 0; ?></h2>
                <p class="text-muted small fw-bold mb-0">RETARDS</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
            <div class="card-body">
                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-check-circle-fill fs-3"></i>
                </div>
                <h2 class="fw-black mb-0"><?php echo $stats['excuses'] ?: 0; ?></h2>
                <p class="text-muted small fw-bold mb-0">JUSTIFIÉES</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold">Historique détaillé</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Matière</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($absences->num_rows > 0): ?>
                        <?php while($a = $absences->fetch_assoc()): 
                            $badge = ($a['statut'] == 'Absent') ? 'bg-danger' : (($a['statut'] == 'Retard') ? 'bg-warning text-dark' : 'bg-secondary');
                            
                            // Préparation des données pour la modale
                            $details_json = json_encode([
                                'date' => date('d/m/Y', strtotime($a['date_cours'])),
                                'matiere' => $a['matiere_nom'],
                                'horaire' => date('H:i', strtotime($a['heure_debut'])) . ' - ' . date('H:i', strtotime($a['heure_fin'])),
                                'prof' => $a['prof_prenom'] . ' ' . $a['prof_nom'],
                                'statut' => $a['statut']
                            ]);
                        ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo date('d/m/Y', strtotime($a['date_cours'])); ?></td>
                                <td><?php echo htmlspecialchars($a['matiere_nom']); ?></td>
                                <td><span class="badge <?php echo $badge; ?> rounded-pill px-3"><?php echo $a['statut']; ?></span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3" 
                                            onclick='showDetails(<?php echo htmlspecialchars($details_json, ENT_QUOTES); ?>)'>
                                        Détails
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-success"><i class="bi bi-emoji-smile me-2"></i> Félicitations ! Vous n'avez aucune absence enregistrée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALE DE DÉTAILS -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalMatiere">Détails du cours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3">
                    <div class="rounded-circle bg-primary text-white p-3 me-3">
                        <i class="bi bi-info-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">STATUT</div>
                        <div id="modalStatut" class="badge rounded-pill"></div>
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-6">
                        <label class="text-muted small fw-bold d-block">DATE</label>
                        <span id="modalDate" class="fw-bold"></span>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small fw-bold d-block">HORAIRE</label>
                        <span id="modalHoraire" class="fw-bold"></span>
                    </div>
                    <div class="col-12 border-top pt-3">
                        <label class="text-muted small fw-bold d-block">PROFESSEUR</label>
                        <span id="modalProf" class="fw-bold text-primary"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(data) {
    document.getElementById('modalMatiere').innerText = data.matiere;
    document.getElementById('modalDate').innerText = data.date;
    document.getElementById('modalHoraire').innerText = data.horaire;
    document.getElementById('modalProf').innerText = data.prof;
    
    const statusBadge = document.getElementById('modalStatut');
    statusBadge.innerText = data.statut;
    statusBadge.className = 'badge rounded-pill px-3 ' + (data.statut === 'Absent' ? 'bg-danger' : (data.statut === 'Retard' ? 'bg-warning text-dark' : 'bg-secondary'));
    
    const myModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    myModal.show();
}
</script>

<?php include '../includes/footer.php'; ?>

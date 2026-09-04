<?php
$page_title = 'Suivi des Absences';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur' && $_SESSION['user_role'] !== 'enseignant') {
    die("Accès refusé.");
}

// Filtres
$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : date('Y-m-01'); // 1er du mois
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : date('Y-m-d');

// Requête de base pour les statistiques
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'Absent' THEN 1 ELSE 0 END) as absents,
        SUM(CASE WHEN statut = 'Retard' THEN 1 ELSE 0 END) as retards,
        SUM(CASE WHEN statut = 'Excusé' THEN 1 ELSE 0 END) as excuses
    FROM presences 
    WHERE date_cours BETWEEN '$date_debut' AND '$date_fin'
")->fetch_assoc();

// Liste détaillée des absences
$where_classe = $classe_id > 0 ? "AND i.classe_id = $classe_id" : "";
$query_details = "
    SELECT p.*, u.nom, u.prenom, u.photo, c.nom as classe_nom, m.nom as matiere_nom 
    FROM presences p
    JOIN utilisateurs u ON p.eleve_id = u.id
    JOIN emploi_du_temps edt ON p.emploi_du_temps_id = edt.id
    JOIN classes c ON edt.classe_id = c.id
    JOIN matieres m ON edt.matiere_id = m.id
    JOIN inscriptions i ON u.id = i.eleve_id
    WHERE p.statut != 'Présent' 
    AND p.date_cours BETWEEN ? AND ?
    $where_classe
    ORDER BY p.date_cours DESC
";
$stmt = $conn->prepare($query_details);
$stmt->bind_param("ss", $date_debut, $date_fin);
$stmt->execute();
$absences = $stmt->get_result();

$classes = $conn->query("SELECT id, nom FROM classes ORDER BY nom");
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-0">Analyse des Absences</h3>
        <p class="text-muted small">Suivi détaillé des présences et ponctualité.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button class="btn btn-outline-primary rounded-pill btn-sm me-2" onclick="window.print()">
            <i class="bi bi-file-earmark-pdf"></i> Exporter Rapport
        </button>
    </div>
</div>

<!-- Cartes Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-danger text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="small fw-bold opacity-75 mb-1">TOTAL ABSENCES</p>
                        <h2 class="fw-black mb-0"><?php echo $stats['absents'] ?: 0; ?></h2>
                    </div>
                    <i class="bi bi-person-x fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-warning text-dark">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="small fw-bold opacity-75 mb-1">TOTAL RETARDS</p>
                        <h2 class="fw-black mb-0"><?php echo $stats['retards'] ?: 0; ?></h2>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-success text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="small fw-bold opacity-75 mb-1">JUSTIFIÉES (EXCUSÉ)</p>
                        <h2 class="fw-black mb-0"><?php echo $stats['excuses'] ?: 0; ?></h2>
                    </div>
                    <i class="bi bi-check2-square fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
    <div class="card-body p-4">
        <form action="pages/suivi_absences.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Classe</label>
                <select name="classe_id" class="form-select">
                    <option value="0">Toutes les classes</option>
                    <?php while($c = $classes->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $classe_id == $c['id'] ? 'selected' : ''; ?>><?php echo $c['nom']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Du</label>
                <input type="date" name="date_debut" class="form-control" value="<?php echo $date_debut; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Au</label>
                <input type="date" name="date_fin" class="form-control" value="<?php echo $date_fin; ?>">
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary rounded-pill">Appliquer les filtres</button>
            </div>
        </form>
    </div>
</div>

<!-- Liste détaillée -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Détails des manquements</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Élève</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($absences->num_rows > 0): ?>
                        <?php while($a = $absences->fetch_assoc()): 
                            $badge_class = ($a['statut'] == 'Absent') ? 'bg-danger' : (($a['statut'] == 'Retard') ? 'bg-warning text-dark' : 'bg-secondary');
                            
                            $photo_name = $a['photo'];
                            $photo_path = "../uploads/photos/" . $photo_name;
                            if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                                $avatar = "/uploads/photos/" . $photo_name;
                            } else {
                                $avatar = "https://ui-avatars.com/api/?name=" . urlencode($a['prenom'] . ' ' . $a['nom']) . "&background=random&color=fff";
                            }
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $avatar; ?>" class="rounded-circle me-3 shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">
                                        <span class="fw-bold"><?php echo htmlspecialchars($a['nom'].' '.$a['prenom']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($a['classe_nom']); ?></td>
                                <td><span class="text-muted small"><?php echo htmlspecialchars($a['matiere_nom']); ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($a['date_cours'])); ?></td>
                                <td><span class="badge <?php echo $badge_class; ?> rounded-pill px-3"><?php echo $a['statut']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Aucune absence enregistrée sur cette période.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

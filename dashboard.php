<?php
$page_title = 'Tableau de Bord';
include 'includes/header.php';
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between bg-white p-4 rounded-4 shadow-sm">
            <div>
                <h2 class="fw-bold mb-1">Bonjour, <?php echo htmlspecialchars($_SESSION['user_prenom']); ?> ! 👋</h2>
                <p class="text-muted mb-0">Voici un aperçu de votre activité pour aujourd'hui.</p>
            </div>
            <div class="text-end d-none d-md-block">
                <span class="badge bg-light text-primary border px-3 py-2 rounded-pill">
                    <i class="bi bi-calendar3 me-2"></i> <?php echo date('d F Y'); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<?php
switch ($user_role) {
    case 'administrateur':
        $total_users = $conn->query("SELECT COUNT(*) as total FROM utilisateurs")->fetch_assoc()['total'];
        $total_classes = $conn->query("SELECT COUNT(*) as total FROM classes")->fetch_assoc()['total'];
        $total_matieres = $conn->query("SELECT COUNT(*) as total FROM matieres")->fetch_assoc()['total'];
        $attendance = $conn->query("SELECT (SELECT COUNT(*) FROM presences WHERE statut = 'Présent') AS present, (SELECT COUNT(*) FROM presences) AS total")->fetch_assoc();
        $rate = ($attendance['total'] > 0) ? round(($attendance['present'] / $attendance['total']) * 100, 1) : 0;
        ?>
        <div class="row g-4">
            <!-- Stat Cards -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 border-start border-primary border-5 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small fw-bold mb-1">UTILISATEURS</p>
                                <h3 class="fw-black mb-0"><?php echo $total_users; ?></h3>
                            </div>
                            <div class="icon-box bg-primary-subtle text-primary rounded-3 p-3">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>
                        </div>
                        <a href="pages/gestion_utilisateurs.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 border-start border-success border-5 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small fw-bold mb-1">CLASSES</p>
                                <h3 class="fw-black mb-0"><?php echo $total_classes; ?></h3>
                            </div>
                            <div class="icon-box bg-success-subtle text-success rounded-3 p-3">
                                <i class="bi bi-door-open-fill fs-3"></i>
                            </div>
                        </div>
                        <a href="pages/gestion_classes.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 border-start border-warning border-5 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small fw-bold mb-1">IMPRESSION</p>
                                <h3 class="fw-black mb-0">CARTES</h3>
                            </div>
                            <div class="icon-box bg-warning-subtle text-warning rounded-3 p-3">
                                <i class="bi bi-printer-fill fs-3"></i>
                            </div>
                        </div>
                        <a href="pages/impression_groupée.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 border-start border-info border-5 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small fw-bold mb-1">PRÉSENCE</p>
                                <h3 class="fw-black mb-0"><?php echo $rate; ?>%</h3>
                            </div>
                            <div class="icon-box bg-info-subtle text-info rounded-3 p-3">
                                <i class="bi bi-graph-up-arrow fs-3"></i>
                            </div>
                        </div>
                        <a href="pages/suivi_absences.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Graphique et Activités -->
            <div class="row mt-4 g-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Statistiques de Présence (7 derniers jours)</h5>
                            <i class="bi bi-three-dots-vertical text-muted"></i>
                        </div>
                        <div class="card-body">
                            <canvas id="presenceChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 fw-bold">Dernières Actions</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success-subtle p-2 me-3"><i class="bi bi-printer text-success"></i></div>
                                        <div>
                                            <p class="mb-0 small fw-bold">Cartes imprimées</p>
                                            <p class="mb-0 x-small text-muted">Il y a 10 minutes</p>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary-subtle p-2 me-3"><i class="bi bi-person-plus text-primary"></i></div>
                                        <div>
                                            <p class="mb-0 small fw-bold">Nouvel élève inscrit</p>
                                            <p class="mb-0 x-small text-muted">Il y a 2 heures</p>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-4 py-3 border-0">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-warning-subtle p-2 me-3"><i class="bi bi-qr-code-scan text-warning"></i></div>
                                        <div>
                                            <p class="mb-0 small fw-bold">Vérification QR Token</p>
                                            <p class="mb-0 x-small text-muted">Hier à 16:45</p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('presenceChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                            datasets: [{
                                label: 'Taux de Présence (%)',
                                data: [92, 88, 95, 91, 85, 0, 0],
                                borderColor: '#223E6F',
                                backgroundColor: 'rgba(34, 62, 111, 0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointBackgroundColor: '#39A9C3',
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, max: 100, grid: { borderDash: [5, 5] } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                });
            </script>

            <div class="col-lg-4 mt-4">
                <div class="card bg-educ-navy text-white shadow-lg overflow-hidden" style="background-color: #223E6F;">
                    <div class="card-body p-4 position-relative">
                        <div style="position:absolute; right: -20px; bottom: -20px; opacity: 0.1; font-size: 8rem;">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Sécurité & Contrôle</h5>
                        <p class="small opacity-75">Le système de scan QR Code est actif. Veillez à ce que les enseignants utilisent la nouvelle interface de pointage.</p>
                        <a href="pages/gestion_utilisateurs.php" class="btn btn-light btn-sm rounded-pill px-4">Gérer les accès</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    case 'enseignant':
        ?>
        <div class="row g-4">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Gestion des cours</h4>
                        <div class="d-grid gap-3">
                            <a href="pages/faire_appel.php" class="btn btn-primary btn-lg d-flex align-items-center justify-content-between px-4 py-3 rounded-4">
                                <span><i class="bi bi-clipboard-check me-3"></i> Faire l'appel (Manuel)</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <a href="pages/scanner_appel.php" class="btn btn-dark btn-lg d-flex align-items-center justify-content-between px-4 py-3 rounded-4">
                                <span><i class="bi bi-qr-code-scan me-3"></i> Scanner les Cartes ID</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <a href="pages/gestion_cours_supplementaires.php" class="btn btn-outline-primary btn-lg d-flex align-items-center justify-content-between px-4 py-3 rounded-4">
                                <span><i class="bi bi-plus-circle me-3"></i> Cours supplémentaires</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-4 bg-light">
                    <div class="card-header bg-white border-0 py-3"><h5 class="mb-0 fw-bold">Mes Classes</h5></div>
                    <div class="card-body">
                        <?php
                        $stmt = $conn->prepare("SELECT c.nom, c.niveau FROM enseignants_classes ec JOIN classes c ON ec.classe_id = c.id WHERE ec.enseignant_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while($row = $res->fetch_assoc()) {
                            echo '<div class="d-flex align-items-center p-3 mb-2 bg-white rounded-3 shadow-sm">
                                    <div class="rounded-circle bg-primary-subtle p-2 me-3"><i class="bi bi-mortarboard text-primary"></i></div>
                                    <div class="fw-bold">'.htmlspecialchars($row['nom']).'</div>
                                  </div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    case 'parent':
        // Code parent déjà amélioré précédemment
        include __DIR__ . '/pages/parent_dashboard_snippet.php'; // Sécurité du chemin
        break;
}

include 'includes/footer.php';
$conn->close();
?>

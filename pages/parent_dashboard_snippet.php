<?php
// pages/parent_dashboard_snippet.php
// Ce fichier est inclus par dashboard.php pour le rôle parent

// 1. Récupérer tous les enfants liés (Table parents_eleves)
$stmt_children = $conn->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo, c.nom as classe_nom 
    FROM parents_eleves pe 
    JOIN utilisateurs u ON pe.eleve_id = u.id 
    LEFT JOIN inscriptions i ON u.id = i.eleve_id
    LEFT JOIN classes c ON i.classe_id = c.id
    WHERE pe.parent_id = ?
");
$stmt_children->bind_param("i", $user_id);
$stmt_children->execute();
$result_children = $stmt_children->get_result();
?>

<div class="row">
    <div class="col-12">
         <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-white py-3 border-0">
                <h4 class="mb-0 fw-bold" style="color: #223E6F;"><i class="bi bi-people-fill"></i> Suivi de mes enfants</h4>
            </div>
             <div class="card-body p-4">
                <?php if ($result_children->num_rows > 0): ?>
                    <div class="row g-4">
                        <?php while ($child = $result_children->fetch_assoc()): 
                            $cid = $child['id'];
                            $stats = $conn->query("SELECT COUNT(*) as total FROM presences WHERE eleve_id = $cid AND statut != 'Présent'")->fetch_assoc();
                            
                            $photo_name = $child['photo'];
                            $photo_path = "../uploads/photos/" . $photo_name;
                            if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                                $avatar = "/G/uploads/photos/" . $photo_name;
                            } else {
                                $avatar = "https://ui-avatars.com/api/?name=" . urlencode($child['prenom'] . ' ' . $child['nom']) . "&background=random&color=fff";
                            }
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #39A9C3 !important;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="<?php echo $avatar; ?>" class="rounded-circle shadow-sm me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($child['prenom'] . ' ' . $child['nom']); ?></h6>
                                                <span class="badge bg-light text-dark border small"><?php echo htmlspecialchars($child['classe_nom'] ?: 'N/A'); ?></span>
                                            </div>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <a href="pages/absences_enfants.php?child_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                                Absences (<?php echo $stats['total']; ?>)
                                            </a>
                                            <a href="pages/emploi_du_temps_enfant.php?child_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-info rounded-pill">
                                                <i class="bi bi-calendar3"></i> Emploi du temps
                                            </a>
                                            <a href="pages/carte_scolaire.php?id=<?php echo $child['id']; ?>" class="btn btn-sm btn-dark rounded-pill">
                                                <i class="bi bi-card-image"></i> Carte ID
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-person-x fs-1 text-muted"></i>
                        <p class="mt-3">Aucun enfant n'est lié à votre compte (ID: <?php echo $user_id; ?>).</p>
                    </div>
                <?php endif; ?>
             </div>
         </div>
    </div>
</div>

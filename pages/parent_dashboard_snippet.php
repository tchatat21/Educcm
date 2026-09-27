<?php
// pages/parent_dashboard_snippet.php
// Ce fichier est inclus par dashboard.php pour le rôle parent

$parent_classes = $conn->query("SELECT * FROM classes ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$students_by_class = [];
$class_map_result = $conn->query("SELECT DISTINCT i.classe_id, u.id, CONCAT(u.prenom, ' ', u.nom) AS label FROM utilisateurs u JOIN inscriptions i ON i.eleve_id = u.id WHERE u.role = 'eleve' AND NOT EXISTS (SELECT 1 FROM parents_eleves pe WHERE pe.eleve_id = u.id) ORDER BY i.classe_id, u.nom, u.prenom");
while ($student_row = $class_map_result->fetch_assoc()) {
    $students_by_class[(string)$student_row['classe_id']][] = [
        'id' => (int)$student_row['id'],
        'label' => $student_row['label']
    ];
}

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
         <div class="card shadow-lg border-0 rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #f3fbff 0%, #ffffff 100%);">
            <div class="card-header border-0 py-3 px-4" style="background: linear-gradient(135deg, #223E6F 0%, #39A9C3 100%); color: white;">
                <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2"></i> Ajouter un enfant</h4>
            </div>
             <div class="card-body p-4">
                <form action="dashboard.php" method="POST" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-uppercase text-primary">Classes</label>
                        <div class="border rounded-4 p-3 bg-white shadow-sm" id="parent_dashboard_class_wrapper" style="max-height: 260px; overflow-y: auto;">
                            <?php foreach ($parent_classes as $classe): ?>
                                <label class="d-flex align-items-center gap-2 mb-2 p-2 rounded-3 hover-bg-light" style="transition: 0.2s ease;">
                                    <input type="checkbox" class="form-check-input border-primary" name="classe_enfant_ids[]" value="<?php echo $classe['id']; ?>">
                                    <span class="small fw-semibold"><?php echo htmlspecialchars($classe['nom'] . ' (' . $classe['niveau'] . ')'); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Cliquez simplement sur les classes concernées.</small>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-uppercase text-success">Enfants disponibles</label>
                        <div class="border rounded-4 p-3 bg-white shadow-sm" id="parent_dashboard_child_wrapper" style="min-height: 120px; max-height: 260px; overflow-y: auto;">
                            <div class="text-muted small">Sélectionnez d’abord une ou plusieurs classes.</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_child_link" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">
                            <i class="bi bi-plus-circle me-2"></i> Ajouter
                        </button>
                    </div>
                </form>
             </div>
         </div>

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
                            $photo_path = __DIR__ . "/../uploads/photos/" . $photo_name;
                            if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                                $avatar = "../uploads/view_file.php?folder=photos&file=" . rawurlencode($photo_name);
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

<script>
const dashboardStudentsByClass = <?php echo json_encode($students_by_class, JSON_UNESCAPED_UNICODE); ?>;

function updateDashboardChildOptions() {
    const classCheckboxes = document.querySelectorAll('input[name="classe_enfant_ids[]"]');
    const childWrapper = document.getElementById('parent_dashboard_child_wrapper');
    if (!childWrapper) return;

    const selectedClasses = Array.from(classCheckboxes)
        .filter(checkbox => checkbox.checked)
        .map(checkbox => checkbox.value);

    const children = [];
    selectedClasses.forEach(function(classId) {
        (dashboardStudentsByClass[classId] || []).forEach(function(student) {
            children.push(student);
        });
    });

    childWrapper.innerHTML = '';
    if (children.length === 0) {
        childWrapper.innerHTML = '<div class="text-muted small">Aucun enfant disponible pour les classes sélectionnées.<br><span class="text-secondary">Tous les élèves de ces classes sont déjà liés à un parent.</span></div>';
        return;
    }

    const uniqueChildren = [];
    const seen = new Set();
    children.forEach(function(student) {
        if (!seen.has(String(student.id))) {
            seen.add(String(student.id));
            uniqueChildren.push(student);
        }
    });

    uniqueChildren.forEach(function(student) {
        const label = document.createElement('label');
        label.className = 'd-flex align-items-center gap-2 mb-2 p-2 rounded-3';
        label.style.background = '#f6fbff';
        label.innerHTML = '<input type="checkbox" class="form-check-input border-success" name="enfant_ids[]" value="' + student.id + '"> <span class="small fw-semibold">' + student.label + '</span>';
        childWrapper.appendChild(label);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const classCheckboxes = document.querySelectorAll('input[name="classe_enfant_ids[]"]');
    classCheckboxes.forEach(function(item) {
        item.addEventListener('change', updateDashboardChildOptions);
    });
    updateDashboardChildOptions();
});
</script>

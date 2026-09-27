<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$role = $_SESSION['user_role'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);
if (!in_array($role, ['administrateur', 'enseignant'], true)) {
    http_response_code(403);
    exit('Accès refusé.');
}

$date = $_GET['date'] ?? date('Y-m-d');
$date_object = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$date_object || $date_object->format('Y-m-d') !== $date) {
    http_response_code(400);
    exit('Date invalide.');
}
$class_id = filter_input(INPUT_GET, 'classe_id', FILTER_VALIDATE_INT) ?: 0;
$school_year = getCurrentSchoolYear();
$school_name = getSchoolDisplayName($conn);
$school_logo = getSchoolLogoDataUri($conn) ?: '../educ.jpeg';

if ($role === 'administrateur') {
    $classes_stmt = $conn->prepare('SELECT id, nom, niveau FROM classes ORDER BY niveau, nom');
} else {
    $classes_stmt = $conn->prepare("SELECT c.id, c.nom, c.niveau FROM classes c JOIN emploi_du_temps e ON e.classe_id = c.id WHERE e.enseignant_id = ?
        UNION
        SELECT c.id, c.nom, c.niveau FROM classes c JOIN cours_supplementaires cs ON cs.classe_id = c.id WHERE cs.enseignant_id = ?
        ORDER BY niveau, nom");
    $classes_stmt->bind_param('ii', $user_id, $user_id);
}
$classes_stmt->execute();
$classes = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$classes_stmt->close();

$selected_class = null;
foreach ($classes as $class) {
    if ((int)$class['id'] === $class_id) {
        $selected_class = $class;
        break;
    }
}

$course_columns = [];
$students = [];
$attendance = [];
$weekday_names = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
$weekday = $weekday_names[(int)$date_object->format('N')];

if ($selected_class) {
    $courses_stmt = $conn->prepare('SELECT e.id, e.heure_debut, m.nom AS matiere FROM emploi_du_temps e JOIN matieres m ON m.id = e.matiere_id WHERE e.classe_id = ? AND e.jour_semaine = ? ORDER BY e.heure_debut');
    $courses_stmt->bind_param('is', $class_id, $weekday);
    $courses_stmt->execute();
    $course_columns = $courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $courses_stmt->close();
    foreach ($course_columns as &$course_column) {
        $course_column['type'] = 'normal';
    }
    unset($course_column);

    $report_date = $date_object->format('Y-m-d');
    $supp_courses_stmt = $conn->prepare('SELECT cs.id, cs.date_heure, m.nom AS matiere FROM cours_supplementaires cs JOIN matieres m ON m.id = cs.matiere_id WHERE cs.classe_id = ? AND DATE(cs.date_heure) = ? ORDER BY cs.date_heure');
    $supp_courses_stmt->bind_param('is', $class_id, $report_date);
    $supp_courses_stmt->execute();
    $supp_courses = $supp_courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $supp_courses_stmt->close();
    foreach ($supp_courses as $supp_course) {
        $course_columns[] = [
            'id' => $supp_course['id'],
            'heure_debut' => $supp_course['date_heure'],
            'matiere' => $supp_course['matiere'] . ' (Suppl.)',
            'type' => 'supplementaire',
        ];
    }
    usort($course_columns, static function ($left, $right) {
        return strcmp(date('H:i:s', strtotime($left['heure_debut'])), date('H:i:s', strtotime($right['heure_debut'])));
    });

    $students_stmt = $conn->prepare("SELECT u.id, u.nom, u.prenom
        FROM inscriptions i JOIN utilisateurs u ON u.id = i.eleve_id
        WHERE i.classe_id = ? AND i.annee_scolaire = ? AND u.role = 'eleve'
        ORDER BY u.nom, u.prenom");
    $students_stmt->bind_param('is', $class_id, $school_year);
    $students_stmt->execute();
    $students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $students_stmt->close();

    $attendance_stmt = $conn->prepare('SELECT p.eleve_id, p.emploi_du_temps_id, p.statut FROM presences p JOIN emploi_du_temps e ON e.id = p.emploi_du_temps_id WHERE e.classe_id = ? AND p.date_cours = ?');
    $attendance_stmt->bind_param('is', $class_id, $report_date);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    while ($row = $attendance_result->fetch_assoc()) {
        $attendance[(int)$row['eleve_id']]['normal:' . (int)$row['emploi_du_temps_id']] = $row['statut'];
    }
    $attendance_stmt->close();

    $supp_attendance_stmt = $conn->prepare('SELECT p.eleve_id, p.cours_supp_id, p.statut FROM presences_supplementaires p JOIN cours_supplementaires cs ON cs.id = p.cours_supp_id WHERE cs.classe_id = ? AND DATE(cs.date_heure) = ?');
    $supp_attendance_stmt->bind_param('is', $class_id, $report_date);
    $supp_attendance_stmt->execute();
    $supp_attendance_result = $supp_attendance_stmt->get_result();
    while ($row = $supp_attendance_result->fetch_assoc()) {
        $attendance[(int)$row['eleve_id']]['supplementaire:' . (int)$row['cours_supp_id']] = $row['statut'];
    }
    $supp_attendance_stmt->close();
}

$status_classes = [
    'Présent' => 'present',
    'Absent' => 'absent',
    'Retard' => 'retard',
    'Excusé' => 'excuse',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport des présences - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f3f6f8; color: #18222c; font: 14px Arial, sans-serif; margin: 0 auto; max-width: 1100px; padding: 22px clamp(12px, 3vw, 30px); }
        .toolbar { align-items: end; background: #fff; border: 1px solid #dce5eb; border-radius: 10px; box-shadow: 0 5px 18px rgba(23, 74, 115, 0.08); display: flex; flex-wrap: wrap; gap: 14px; justify-content: space-between; margin-bottom: 24px; padding: 16px; }
        .filters { align-items: end; display: flex; flex: 1 1 560px; flex-wrap: wrap; gap: 12px; }
        .filters label { color: #385365; display: grid; flex: 1 1 210px; font-size: 12px; gap: 6px; text-transform: uppercase; }
        .filters select, .filters input, .toolbar button { border: 1px solid #cbd8e0; border-radius: 7px; font: inherit; min-height: 42px; padding: 9px 12px; }
        .filters select, .filters input { background: #fff; color: #18222c; width: 100%; }
        .toolbar button { background: #174a73; border-color: #174a73; color: #fff; cursor: pointer; font-weight: 700; transition: background-color 0.15s ease, transform 0.15s ease; }
        .toolbar button:hover { background: #0f3859; transform: translateY(-1px); }
        .school-heading { align-items: center; background: #fff; border: 1px solid #dce5eb; border-left: 4px solid #39a9c3; border-radius: 8px; display: flex; gap: 16px; padding: 15px; }
        .school-heading img { height: 64px; object-fit: contain; width: 64px; }
        h1 { color: #174a73; font-size: 22px; margin: 0 0 4px; }
        .report-title { margin: 22px 0 14px; text-align: center; }
        .report-title h2 { font-size: 19px; margin: 0 0 5px; }
        .report-meta { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .scroll { overflow-x: auto; }
        table { border-collapse: collapse; min-width: 700px; width: 100%; }
        th, td { border: 1px solid #7b8790; padding: 8px; text-align: left; }
        th { background: #edf2f5; }
        .number { text-align: center; width: 38px; }
        .present { color: #146c43; font-weight: 700; }
        .absent { color: #b02a37; font-weight: 700; }
        .retard { color: #8a5a00; font-weight: 700; }
        .excuse { color: #495057; font-weight: 700; }
        .unreported { color: #6c757d; font-style: italic; }
        .signature { display: flex; justify-content: flex-end; margin-top: 42px; }
        .signature span { border-top: 1px solid #333; min-width: 210px; padding-top: 7px; text-align: center; }
        @media (max-width: 600px) {
            body { padding: 12px; }
            .toolbar { align-items: stretch; padding: 12px; }
            .filters { flex-basis: 100%; }
            .filters label { flex-basis: 100%; }
            .toolbar > button { width: 100%; }
            .school-heading { gap: 11px; padding: 11px; }
            .school-heading img { height: 48px; width: 48px; }
            h1 { font-size: 18px; overflow-wrap: anywhere; }
            .report-meta { gap: 8px; flex-wrap: wrap; }
            .signature { margin-top: 28px; }
        }
        @media print {
            @page { margin: 14mm; }
            body { margin: 0; max-width: none; }
            .toolbar { display: none; }
            .scroll { overflow: visible; }
            table { min-width: 0; }
            tr { break-inside: avoid; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <form class="filters" method="get">
            <label>Classe
                <select name="classe_id" required>
                    <option value="">Choisir une classe</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo (int)$class['id']; ?>" <?php echo $class_id === (int)$class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['nom'] . ' (' . $class['niveau'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date
                <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" required>
            </label>
            <button type="submit"><i class="bi bi-funnel-fill" aria-hidden="true"></i> Afficher</button>
        </form>
        <?php if ($selected_class): ?><button type="button" onclick="window.print()"><i class="bi bi-printer-fill" aria-hidden="true"></i> Imprimer le rapport</button><?php endif; ?>
    </div>

    <?php if (!$selected_class): ?>
        <p>Sélectionnez une classe et une date pour consulter les présences.</p>
    <?php else: ?>
        <header class="school-heading">
            <img src="<?php echo htmlspecialchars($school_logo); ?>" alt="Logo <?php echo htmlspecialchars($school_name); ?>">
            <div><h1><?php echo htmlspecialchars($school_name); ?></h1><div>Rapport journalier des présences</div></div>
        </header>
        <section class="report-title">
            <h2><?php echo htmlspecialchars($selected_class['nom'] . ' (' . $selected_class['niveau'] . ')'); ?></h2>
            <div><?php echo htmlspecialchars($date_object->format('d/m/Y')); ?> · <?php echo htmlspecialchars($weekday); ?></div>
        </section>
        <div class="report-meta">
            <span>Effectif : <?php echo count($students); ?></span>
            <span>Séances prévues : <?php echo count($course_columns); ?></span>
        </div>
        <div class="scroll">
            <table>
                <thead>
                    <tr>
                        <th class="number">N°</th>
                        <th>Élève</th>
                        <?php foreach ($course_columns as $course): ?>
                            <th><?php echo htmlspecialchars($course['matiere'] . ' · ' . date('H:i', strtotime($course['heure_debut']))); ?></th>
                        <?php endforeach; ?>
                        <?php if (!$course_columns): ?><th>Présence</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td class="number"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></td>
                            <?php foreach ($course_columns as $course):
                                $attendance_key = $course['type'] . ':' . (int)$course['id'];
                                $status = $attendance[(int)$student['id']][$attendance_key] ?? null;
                                $class_name = $status_classes[$status] ?? ($status === null ? 'unreported' : '');
                            ?>
                                <td class="<?php echo $class_name; ?>"><?php echo htmlspecialchars($status ?? 'Non renseigné'); ?></td>
                            <?php endforeach; ?>
                            <?php if (!$course_columns): ?><td>Aucun cours programmé ce jour.</td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$students): ?><tr><td colspan="<?php echo max(3, count($course_columns) + 2); ?>">Aucun élève inscrit dans cette classe pour l’année scolaire en cours.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="signature"><span>Signature de l’enseignant / administration</span></div>
    <?php endif; ?>
</body>
</html>
<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (($_SESSION['user_role'] ?? '') !== 'administrateur') {
    http_response_code(403);
    exit('Accès refusé.');
}

$annee_scolaire = getCurrentSchoolYear();
$stmt = $conn->prepare("SELECT c.id AS classe_id, c.nom AS classe_nom, c.niveau,
        u.nom AS eleve_nom, u.prenom AS eleve_prenom
    FROM classes c
    LEFT JOIN inscriptions i ON i.classe_id = c.id AND i.annee_scolaire = ?
    LEFT JOIN utilisateurs u ON u.id = i.eleve_id AND u.role = 'eleve'
    ORDER BY c.niveau, c.nom, u.nom, u.prenom");
$stmt->bind_param('s', $annee_scolaire);
$stmt->execute();
$result = $stmt->get_result();
$classes = [];
while ($row = $result->fetch_assoc()) {
    $class_id = (int)$row['classe_id'];
    if (!isset($classes[$class_id])) {
        $classes[$class_id] = [
            'nom' => trim($row['classe_nom'] . ' ' . ($row['niveau'] ?? '')),
            'eleves' => [],
        ];
    }
    if ($row['eleve_nom'] !== null) {
        $classes[$class_id]['eleves'][] = [
            'nom' => $row['eleve_nom'],
            'prenom' => $row['eleve_prenom'],
        ];
    }
}
$stmt->close();
$school_name = getSchoolDisplayName($conn);
$school_logo = getSchoolLogoDataUri($conn) ?: '../educ.jpeg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listes des classes - <?php echo htmlspecialchars($school_name); ?></title>
    <style>
        body { color: #17202a; font: 14px Arial, sans-serif; margin: 24px auto; max-width: 900px; }
        .toolbar { display: flex; justify-content: flex-end; margin-bottom: 18px; }
        .toolbar button { background: #174a73; border: 0; color: #fff; cursor: pointer; padding: 10px 16px; }
        .class-list { break-after: page; page-break-after: always; }
        .class-list:last-child { break-after: auto; page-break-after: auto; }
        .school-heading { align-items: center; border-bottom: 2px solid #174a73; display: flex; gap: 18px; padding-bottom: 14px; }
        .school-heading img { height: 72px; object-fit: contain; width: 72px; }
        h1 { color: #174a73; font-size: 22px; margin: 0 0 5px; }
        .subtitle { color: #59636e; }
        .document-title { margin: 24px 0 16px; text-align: center; }
        .document-title h2 { font-size: 19px; margin: 0 0 6px; text-transform: uppercase; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #7f8c8d; padding: 8px 10px; text-align: left; }
        th { background: #eaf0f4; }
        .number { text-align: center; width: 52px; }
        .signature { display: flex; justify-content: flex-end; margin-top: 52px; }
        .signature div { min-width: 220px; text-align: center; }
        .signature span { border-top: 1px solid #333; display: block; margin-top: 55px; padding-top: 7px; }
        @media print {
            @page { margin: 16mm; }
            body { margin: 0; max-width: none; }
            .toolbar { display: none; }
            tr { break-inside: avoid; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Imprimer toutes les listes</button></div>
    <?php if (!$classes): ?>
        <p>Aucune classe n'est enregistrée.</p>
    <?php endif; ?>
    <?php foreach ($classes as $classe): ?>
        <section class="class-list">
            <header class="school-heading">
                <img src="<?php echo htmlspecialchars($school_logo); ?>" alt="Logo <?php echo htmlspecialchars($school_name); ?>">
                <div>
                    <h1><?php echo htmlspecialchars($school_name); ?></h1>
                    <div class="subtitle">Établissement scolaire</div>
                </div>
            </header>
            <section class="document-title">
                <h2>Liste des élèves</h2>
                <div><?php echo htmlspecialchars($classe['nom']); ?></div>
            </section>
            <div class="meta">
                <span>Année scolaire : <?php echo htmlspecialchars($annee_scolaire); ?></span>
                <span>Effectif : <?php echo count($classe['eleves']); ?></span>
            </div>
            <table>
                <thead><tr><th class="number">N°</th><th>Nom</th><th>Prénom</th><th>Émargement</th></tr></thead>
                <tbody>
                    <?php foreach ($classe['eleves'] as $index => $eleve): ?>
                        <tr>
                            <td class="number"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($eleve['nom']); ?></td>
                            <td><?php echo htmlspecialchars($eleve['prenom']); ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$classe['eleves']): ?><tr><td colspan="4">Aucun élève inscrit pour cette année scolaire.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <div class="signature"><div><span>Signature et cachet</span></div></div>
        </section>
    <?php endforeach; ?>
</body>
</html>
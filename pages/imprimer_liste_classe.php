<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (($_SESSION['user_role'] ?? '') !== 'administrateur') {
    http_response_code(403);
    exit('Accès refusé.');
}

$classe_id = filter_input(INPUT_GET, 'classe_id', FILTER_VALIDATE_INT);
if (!$classe_id || $classe_id < 1) {
    http_response_code(400);
    exit('Classe invalide.');
}

$classe_stmt = $conn->prepare('SELECT nom, niveau FROM classes WHERE id = ?');
$classe_stmt->bind_param('i', $classe_id);
$classe_stmt->execute();
$classe = $classe_stmt->get_result()->fetch_assoc();
$classe_stmt->close();

if (!$classe) {
    http_response_code(404);
    exit('Classe introuvable.');
}

// Une liste imprimée ne doit contenir que les inscriptions de l'année scolaire active.
$annee_scolaire = getCurrentSchoolYear();
$eleves_stmt = $conn->prepare("SELECT u.nom, u.prenom
    FROM inscriptions i
    JOIN utilisateurs u ON u.id = i.eleve_id
    WHERE i.classe_id = ? AND i.annee_scolaire = ? AND u.role = 'eleve'
    ORDER BY u.nom, u.prenom");
$eleves_stmt->bind_param('is', $classe_id, $annee_scolaire);
$eleves_stmt->execute();
$eleves = $eleves_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$eleves_stmt->close();
$nom_classe = trim($classe['nom'] . ' ' . ($classe['niveau'] ?? ''));
$school_name = getSchoolDisplayName($conn);
$school_logo = getSchoolLogoDataUri($conn) ?: '../educ.jpeg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liste de classe - <?php echo htmlspecialchars($nom_classe); ?> - <?php echo htmlspecialchars($school_name); ?></title>
    <style>
        body { color: #17202a; font: 14px Arial, sans-serif; margin: 24px auto; max-width: 900px; }
        .toolbar { display: flex; justify-content: flex-end; margin-bottom: 18px; }
        .toolbar button { background: #174a73; border: 0; color: #fff; cursor: pointer; padding: 10px 16px; }
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
    <div class="toolbar"><button type="button" onclick="window.print()">Imprimer la liste</button></div>
    <header class="school-heading">
        <img src="<?php echo htmlspecialchars($school_logo); ?>" alt="Logo <?php echo htmlspecialchars($school_name); ?>">
        <div>
            <h1><?php echo htmlspecialchars($school_name); ?></h1>
            <div class="subtitle">Établissement scolaire</div>
        </div>
    </header>
    <section class="document-title">
        <h2>Liste des élèves</h2>
        <div><?php echo htmlspecialchars($nom_classe); ?></div>
    </section>
    <div class="meta">
        <span>Année scolaire : <?php echo htmlspecialchars($annee_scolaire); ?></span>
        <span>Effectif : <?php echo count($eleves); ?></span>
    </div>
    <table>
        <thead>
            <tr><th class="number">N°</th><th>Nom</th><th>Prénom</th><th>Émargement</th></tr>
        </thead>
        <tbody>
            <?php foreach ($eleves as $index => $eleve): ?>
                <tr>
                    <td class="number"><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($eleve['nom']); ?></td>
                    <td><?php echo htmlspecialchars($eleve['prenom']); ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$eleves): ?>
                <tr><td colspan="4">Aucun élève inscrit pour cette année scolaire.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="signature"><div><span>Signature et cachet</span></div></div>
</body>
</html>
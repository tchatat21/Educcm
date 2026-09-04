<?php
// On récupère l'ID avant toute chose
$eleve_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// MODE IMPRESSION : Si on demande l'impression, on n'affiche QUE la carte
if (isset($_GET['print']) && $_GET['print'] == '1') {
    require_once '../includes/db.php';
    $query = "SELECT u.nom, u.prenom, u.id, u.photo, u.qr_token, c.nom as classe_nom, p.nom as parent_nom, p.prenom as parent_prenom, p.telephone as parent_tel FROM utilisateurs u LEFT JOIN inscriptions i ON u.id = i.eleve_id LEFT JOIN classes c ON i.classe_id = c.id LEFT JOIN parents_eleves pe ON u.id = pe.eleve_id LEFT JOIN utilisateurs p ON pe.parent_id = p.id WHERE u.id = ? AND u.role = 'eleve'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $eleve_id);
    $stmt->execute();
    $eleve = $stmt->get_result()->fetch_assoc();
    
    if (!$eleve) die("Erreur : Élève introuvable.");
    
    $qr_data = $eleve['qr_token'];
    $logo_url = "/educ.jpeg";
    $photo_final = "https://ui-avatars.com/api/?name=" . urlencode($eleve['prenom'] . '+' . $eleve['nom']) . "&size=300&background=223E6F&color=fff";
    $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Impression Carte - <?php echo $eleve['nom']; ?></title>
        <style>
            body { margin: 0; padding: 0; background: white; font-family: 'Segoe UI', sans-serif; }
            .print-wrapper { width: 100%; height: 100vh; display: flex; align-items: center; justify-content: center; }
            <?php include 'card_style_css.php'; // On va externaliser le style pour plus de proprete ?>
        </style>
    </head>
    <body onload="window.print();">
        <div class="print-wrapper">
            <?php include 'card_content_html.php'; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// MODE AFFICHAGE NORMAL (Tableau de bord)
$page_title = 'Carte d\'Identité Scolaire';
include '../includes/header.php';
require_once '../includes/db.php';

$query = "SELECT u.nom, u.prenom, u.id, u.photo, u.qr_token, c.nom as classe_nom, p.nom as parent_nom, p.prenom as parent_prenom, p.telephone as parent_tel FROM utilisateurs u LEFT JOIN inscriptions i ON u.id = i.eleve_id LEFT JOIN classes c ON i.classe_id = c.id LEFT JOIN parents_eleves pe ON u.id = pe.eleve_id LEFT JOIN utilisateurs p ON pe.parent_id = p.id WHERE u.id = ? AND u.role = 'eleve'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $eleve_id);
$stmt->execute();
$eleve = $stmt->get_result()->fetch_assoc();

if (!$eleve) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Élève introuvable.</div></div>";
    include '../includes/footer.php';
    exit;
}

$qr_data = $eleve['qr_token'] ?: bin2hex(random_bytes(16));
$logo_url = "/educ.jpeg";
$photo_final = "https://ui-avatars.com/api/?name=" . urlencode($eleve['prenom'] . '+' . $eleve['nom']) . "&size=300&background=223E6F&color=fff";
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
?>

<div class="container py-4 text-center">
    <div class="card shadow-sm border-0 mb-4 p-3 bg-light">
        <h5>Aperçu de la Carte Scolaire</h5>
        <p class="text-muted small">Cliquez sur imprimer pour générer le format PVC officiel.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="pages/carte_scolaire_verso.php?id=<?php echo $eleve_id; ?>" class="btn btn-outline-secondary btn-lg rounded-pill px-4 shadow">
                <i class="bi bi-arrow-right-circle"></i> VOIR LE VERSO
            </a>
            <!-- On ouvre l'impression dans une nouvelle fenetre -->
            <a href="pages/carte_scolaire.php?id=<?php echo $eleve_id; ?>&print=1" target="_blank" class="btn btn-primary btn-lg rounded-pill px-4 shadow">
                <i class="bi bi-printer-fill me-2"></i> IMPRIMER
            </a>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-5" style="transform: scale(1.2);">
        <?php include 'card_content_html.php'; ?>
    </div>
</div>

<style>
    <?php include 'card_style_css.php'; ?>
</style>

<?php
include '../includes/footer.php';
?>

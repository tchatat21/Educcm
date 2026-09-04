<?php
// On récupère l'ID pour avoir un contexte, même si le verso est le même pour tous
$eleve_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// MODE IMPRESSION : Si on demande l'impression du verso
if (isset($_GET['print']) && $_GET['print'] == '1') {
    $logo_url = "/educ.jpeg";
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Impression Verso - Carte Scolaire</title>
        <style>
            body { margin: 0; padding: 0; background: white; font-family: 'Segoe UI', sans-serif; }
            .print-wrapper { width: 100%; height: 100vh; display: flex; align-items: center; justify-content: center; }
            <?php include 'card_verso_style_css.php'; ?>
        </style>
    </head>
    <body onload="window.print();">
        <div class="print-wrapper">
            <?php include 'card_verso_content_html.php'; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// MODE AFFICHAGE NORMAL
$page_title = 'Carte Scolaire - Verso';
include '../includes/header.php';

$logo_url = "/educ.jpeg";
?>

<div class="container py-4 text-center">
    <div class="card shadow-sm border-0 mb-4 p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Aperçu du Verso de la Carte</h5>
            <div class="btn-group">
                <a href="pages/carte_scolaire.php?id=<?php echo $eleve_id; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i> Voir le Recto
                </a>
                <a href="pages/carte_scolaire_verso.php?id=<?php echo $eleve_id; ?>&print=1" target="_blank" class="btn btn-primary btn-sm rounded-pill px-4 shadow">
                    <i class="bi bi-printer-fill me-2"></i> IMPRIMER LE VERSO
                </a>
            </div>
        </div>
        <p class="text-muted small mt-2">Le verso contient le règlement, les contacts de l'école et le filigrane du logo.</p>
    </div>

    <!-- Aperçu Écran -->
    <div class="d-flex justify-content-center mt-5">
        <?php include 'card_verso_content_html.php'; ?>
    </div>
</div>

<style>
    <?php include 'card_style_css.php'; // On garde les couleurs globales ?>
    <?php include 'card_verso_style_css.php'; ?>
</style>

<?php
include '../includes/footer.php';
?>

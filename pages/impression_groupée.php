<?php
require_once '../includes/db.php';
session_start();

if ($_SESSION['user_role'] !== 'administrateur') {
    die("Accès refusé.");
}

$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$face = isset($_GET['face']) ? $_GET['face'] : 'recto';

// --- ORDRE STRICT POUR LE RECTO-VERSO ---
$order_sql = "ORDER BY u.nom ASC, u.prenom ASC";

// --- MODE IMPRESSION PUR ---
if (isset($_GET['print']) && $_GET['print'] == '1') {
    $stmt = $conn->prepare("
        SELECT u.id, u.nom, u.prenom, u.photo, u.qr_token, c.nom as classe_nom, 
               p.nom as parent_nom, p.prenom as parent_prenom, p.telephone as parent_tel 
        FROM utilisateurs u 
        JOIN inscriptions i ON u.id = i.eleve_id 
        JOIN classes c ON i.classe_id = c.id 
        LEFT JOIN parents_eleves pe ON u.id = pe.eleve_id 
        LEFT JOIN utilisateurs p ON pe.parent_id = p.id 
        WHERE i.classe_id = ? AND u.role = 'eleve'
        $order_sql
    ");
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Impression - <?php echo strtoupper($face); ?></title>
        <style>
            body { margin: 0; padding: 0; background: white; }
            .print-bulk-container { display: block; }
            .card-item-bulk { 
                page-break-inside: avoid; 
                page-break-after: always; 
                display: flex;
                justify-content: center;
                align-items: center;
                width: 100%;
                height: 100vh;
            }
            <?php include 'card_style_css.php'; ?>
            <?php include 'card_verso_style_css.php'; ?>
            .avant-garde-card, .avant-garde-card-verso { transform: scale(1) !important; margin: 0 !important; border: 0.1pt solid #eee !important; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="print-bulk-container">
            <?php foreach ($students as $eleve): 
                $qr_data = $eleve['qr_token'] ?: bin2hex(random_bytes(16));
                $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
                $logo_url = "/G/educ.jpeg";
                $photo_final = "https://ui-avatars.com/api/?name=" . urlencode($eleve['prenom'] . '+' . $eleve['nom']) . "&size=300&background=223E6F&color=fff";
            ?>
                <div class="card-item-bulk">
                    <?php 
                    if ($face == 'recto') include 'card_content_html.php'; 
                    else include 'card_verso_content_html.php';
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- MODE APERÇU ---
$page_title = 'Impression Groupée';
include '../includes/header.php';
$classes = $conn->query("SELECT id, nom FROM classes ORDER BY nom");
?>

<div class="container py-4 no-print">
    <div class="card shadow-lg border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-printer-fill text-primary"></i> Gestion de l'impression en masse</h4>
            
            <form action="pages/impression_groupée.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">1. Sélectionner la classe</label>
                    <select name="classe_id" class="form-select form-select-lg rounded-3" onchange="this.form.submit()">
                        <option value="0">-- Choisir une classe --</option>
                        <?php while($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($classe_id == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nom']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">2. Face à imprimer</label>
                    <select name="face" class="form-select form-select-lg rounded-3" onchange="this.form.submit()">
                        <option value="recto" <?php echo ($face == 'recto') ? 'selected' : ''; ?>>Rectos (Informations)</option>
                        <option value="verso" <?php echo ($face == 'verso') ? 'selected' : ''; ?>>Versos (Règlement)</option>
                    </select>
                </div>
                <div class="col-md-5 text-end">
                    <?php if ($classe_id > 0): ?>
                        <a href="pages/impression_groupée.php?classe_id=<?php echo $classe_id; ?>&face=<?php echo $face; ?>&print=1" 
                           target="_blank" class="btn btn-primary btn-lg rounded-pill px-4 shadow">
                            <i class="bi bi-play-circle-fill"></i> GÉNÉRER LE FICHIER D'IMPRESSION
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($classe_id > 0): ?>
        <div class="alert alert-warning py-2 mb-4">
            <i class="bi bi-info-circle"></i> <strong>Astuce Recto-Verso :</strong> L'ordre des élèves est alphabétique. Imprimez d'abord tous les Rectos, puis retournez vos cartes et imprimez tous les Versos.
        </div>

        <div class="preview-gallery">
            <?php
            $stmt = $conn->prepare("SELECT u.id, u.nom, u.prenom, u.photo, u.qr_token, c.nom as classe_nom, p.nom as parent_nom, p.prenom as parent_prenom, p.telephone as parent_tel FROM utilisateurs u JOIN inscriptions i ON u.id = i.eleve_id JOIN classes c ON i.classe_id = c.id LEFT JOIN parents_eleves pe ON u.id = pe.eleve_id LEFT JOIN utilisateurs p ON pe.parent_id = p.id WHERE i.classe_id = ? AND u.role = 'eleve' $order_sql");
            $stmt->bind_param("i", $classe_id);
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($students as $eleve):
                $qr_data = $eleve['qr_token'] ?: bin2hex(random_bytes(16));
                $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
                $logo_url = "/G/educ.jpeg";
                $photo_final = "https://ui-avatars.com/api/?name=" . urlencode($eleve['prenom'] . '+' . $eleve['nom']) . "&size=300&background=223E6F&color=fff";
                ?>
                <div class="preview-item shadow-sm">
                    <div class="preview-label"><?php echo htmlspecialchars($eleve['nom'].' '.$eleve['prenom']); ?></div>
                    <div class="card-scale-wrapper">
                        <?php 
                        if ($face == 'recto') include 'card_content_html.php'; 
                        else include 'card_verso_content_html.php';
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    /* On inclut les styles pour que l'aperçu fonctionne */
    <?php include 'card_style_css.php'; ?>
    <?php include 'card_verso_style_css.php'; ?>

    .preview-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        padding: 20px 0;
    }

    .preview-item {
        background: #fff;
        border-radius: 15px;
        padding: 15px;
        border: 1px solid #eee;
    }

    .preview-label {
        font-size: 0.8rem;
        font-weight: bold;
        color: #666;
        margin-bottom: 10px;
        text-align: center;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 5px;
    }

    /* On réduit la taille des cartes UNIQUEMENT pour l'aperçu écran */
    .card-scale-wrapper {
        display: flex;
        justify-content: center;
        overflow: hidden;
    }
    .card-scale-wrapper .avant-garde-card, 
    .card-scale-wrapper .avant-garde-card-verso {
        transform: scale(0.4); /* Petit aperçu sans casser le CSS interne */
        transform-origin: center top;
        margin-bottom: -250px; /* Compense le vide créé par le scale */
    }
</style>

<?php include '../includes/footer.php'; ?>

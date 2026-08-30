<?php
// On s'assure que la connexion DB est présente (si incluse via carte_scolaire_verso.php)
if (isset($conn)) {
    $res_settings = $conn->query("SELECT * FROM settings");
    $card_settings = [];
    while ($s = $res_settings->fetch_assoc()) {
        $card_settings[$s['setting_key']] = $s['setting_value'];
    }
}
$stamp_img = !empty($card_settings['school_stamp']) ? "/G/uploads/school/" . $card_settings['school_stamp'] : "";
?>

<!-- CONTENU DU VERSO DE LA CARTE AVANT-GARDE -->
<div class="avant-garde-card-verso" id="myCardVerso">
    
    <!-- Filigrane (Watermark) Logo École -->
    <img src="<?php echo $logo_url; ?>" alt="Watermark" class="watermark-logo">
    
    <div class="bg-blob-v1"></div>
    <div class="bg-blob-v2"></div>

    <div class="verso-content">
        <!-- Header Verso -->
        <div class="verso-header">
            <h5>CONDITIONS D'UTILISATION</h5>
        </div>

        <!-- Règles et Détails -->
        <div class="rules-box">
            <ul class="rules-list">
                <li>Cette carte est strictement personnelle et incessible.</li>
                <li>Elle doit être présentée lors de chaque entrée au sein de l'établissement.</li>
                <li>En cas de perte ou de vol, veuillez informer immédiatement l'administration.</li>
                <li>Le port de cette carte est obligatoire pendant toute la durée des cours.</li>
                <li>Toute falsification de ce document est passible de sanctions disciplinaires.</li>
            </ul>
        </div>

        <!-- Contact de l'école -->
        <div class="school-contact-box">
            <div class="contact-item"><strong>ADRESSE :</strong> Quartier du Savoir, B.P. 2025 - Yaoundé</div>
            <div class="contact-item"><strong>TÉLÉPHONE :</strong> +237 600 000 000 / 611 111 111</div>
            <div class="contact-item"><strong>EMAIL :</strong> contact@ecole-gsg.com</div>
            <div class="contact-item"><strong>SITE WEB :</strong> www.gestion-scolaire-g.com</div>
        </div>

        <!-- Signature Direction -->
        <div class="signature-section">
            <div class="signature-box">
                <?php if ($stamp_img): ?>
                    <div class="stamp-container">
                        <img src="<?php echo $stamp_img; ?>" alt="Cachet École">
                    </div>
                <?php else: ?>
                    <div class="stamp-placeholder">Cachet École</div>
                <?php endif; ?>
                <div class="signature-line"></div>
                <span>Signature de la Direction</span>
            </div>
        </div>

        <!-- Footer de Sécurité -->
        <div class="verso-footer">
            CARTE D'IDENTITÉ SCOLAIRE • GSG PROJET • TOUS DROITS RÉSERVÉS
        </div>
    </div>
</div>

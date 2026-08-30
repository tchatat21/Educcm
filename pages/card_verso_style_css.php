<?php
// On récupère les paramètres pour le style dynamique si non déjà présents
if (!isset($card_settings) && isset($conn)) {
    $res_settings = $conn->query("SELECT * FROM settings");
    $card_settings = [];
    while ($s = $res_settings->fetch_assoc()) {
        $card_settings[$s['setting_key']] = $s['setting_value'];
    }
}
$s_top = isset($card_settings['stamp_top']) ? $card_settings['stamp_top'] : '-8';
$s_right = isset($card_settings['stamp_right']) ? $card_settings['stamp_right'] : '2';
$s_size = isset($card_settings['stamp_size']) ? $card_settings['stamp_size'] : '12';
?>
/* STYLE DU VERSO - DESIGN ÉLITE */
:root {
    --navy: #223E6F;
    --turquoise: #39A9C3;
    --green: #64B89A;
}

.avant-garde-card-verso {
    width: 85.6mm; 
    height: 53.98mm;
    background: #ffffff;
    border-radius: 3.2mm;
    position: relative;
    overflow: hidden;
    padding: 4mm;
    display: flex;
    flex-direction: column;
    font-family: 'Segoe UI', Arial, sans-serif;
    border: 0.1pt solid #ddd;
    box-sizing: border-box;
}

/* Filigrane (Watermark) du Logo */
.watermark-logo {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 45mm;
    height: 45mm;
    opacity: 0.06; /* Très léger pour ne pas gêner la lecture */
    z-index: 0;
    pointer-events: none;
}

/* Décors (Blobs inversés par rapport au recto) */
.bg-blob-v1 { position: absolute; top: -15mm; left: -10mm; width: 40mm; height: 40mm; background: radial-gradient(circle, var(--turquoise) 0%, transparent 70%); opacity: 0.1; z-index: 0; filter: blur(10px); }
.bg-blob-v2 { position: absolute; bottom: -15mm; right: -10mm; width: 40mm; height: 40mm; background: radial-gradient(circle, var(--green) 0%, transparent 70%); opacity: 0.1; z-index: 0; filter: blur(10px); }

.verso-content { position: relative; z-index: 10; display: flex; flex-direction: column; height: 100%; }

/* Titre et Règles */
.verso-header { border-bottom: 1px solid var(--navy); padding-bottom: 1mm; margin-bottom: 2mm; }
.verso-header h5 { margin: 0; font-size: 7.5pt; color: var(--navy); font-weight: 900; text-transform: uppercase; letter-spacing: 0.5pt; }

.rules-list { font-size: 5.5pt; color: #333; line-height: 1.4; padding-left: 3mm; margin-bottom: 3mm; }
.rules-list li { margin-bottom: 0.5mm; }

/* Contact École */
.school-contact-box { background: rgba(34, 62, 111, 0.03); border: 0.5pt solid #eee; padding: 1.5mm; border-radius: 1.5mm; margin-bottom: auto; }
.contact-item { font-size: 5pt; color: #555; display: flex; align-items: center; margin-bottom: 0.5mm; }
.contact-item strong { color: var(--navy); margin-right: 1mm; }

/* Zone Signature */
.signature-section { display: flex; justify-content: flex-end; align-items: flex-end; margin-top: 1mm; }
.signature-box { text-align: center; width: 35mm; position: relative; }
.signature-line { border-top: 0.8pt solid #333; width: 100%; margin-bottom: 1mm; }
.signature-box span { font-size: 5pt; font-weight: 800; color: var(--navy); text-transform: uppercase; }

.stamp-placeholder, .stamp-container { 
    position: absolute; 
    top: <?php echo $s_top; ?>mm; 
    right: <?php echo $s_right; ?>mm; 
    width: <?php echo $s_size; ?>mm; 
    height: <?php echo $s_size; ?>mm; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    transform: rotate(-15deg);
}

.stamp-placeholder {
    border: 1pt dashed rgba(220, 53, 69, 0.3); 
    border-radius: 50%; 
    font-size: 3pt; 
    color: rgba(220, 53, 69, 0.3);
    text-transform: uppercase;
}

.stamp-container img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.verso-footer { text-align: center; font-size: 5pt; font-weight: 900; color: #888; border-top: 0.5pt solid #eee; padding-top: 1mm; margin-top: 2mm; }

/* IMPRESSION */
@media print {
    @page { size: 85.6mm 54mm; margin: 0 !important; }
    html, body { width: 85.6mm; height: 54mm; margin: 0 !important; padding: 0 !important; }
    .print-wrapper { width: 85.6mm !important; height: 54mm !important; display: block !important; }
    .avant-garde-card-verso { margin: 0 !important; border: none !important; -webkit-print-color-adjust: exact !important; }
}

/* ÉCRAN */
@media screen {
    .avant-garde-card-verso { transform: scale(1.8); transform-origin: center; margin: 80px auto; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
}

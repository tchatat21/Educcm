/* PALETTE COULEURS */
:root {
    --navy: #223E6F;
    --turquoise: #39A9C3;
    --cyan: #6EC6D0;
    --green: #64B89A;
    --mint: #8ED0B2;
}

/* LA CARTE (Format ISO CR80 : 85.60mm x 53.98mm) */
.avant-garde-card {
    width: 85.6mm; 
    height: 53.98mm;
    background: #fff;
    border-radius: 3.2mm;
    position: relative;
    overflow: hidden;
    padding: 3mm; /* Réduit pour gagner de l'espace */
    display: flex;
    flex-direction: column;
    font-family: 'Segoe UI', Arial, sans-serif;
    border: 0.1pt solid #eee;
    box-sizing: border-box;
    background-color: white;
}

/* Blobs Décoratifs */
.bg-blob-1 { position: absolute; top: -10mm; right: -5mm; width: 45mm; height: 45mm; background: radial-gradient(circle, var(--cyan) 0%, transparent 70%); opacity: 0.15; z-index: 0; filter: blur(10px); }
.bg-blob-2 { position: absolute; bottom: -10mm; left: -5mm; width: 45mm; height: 45mm; background: radial-gradient(circle, var(--mint) 0%, transparent 70%); opacity: 0.15; z-index: 0; filter: blur(10px); }
.glass-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(2px); z-index: 1; }

.card-header-v2, .card-body-v2, .card-footer-v2 { position: relative; z-index: 10; }

/* Header (Réduit à 10mm pour libérer de l'espace pour l'ID) */
.card-header-v2 { display: flex; align-items: center; border-bottom: 0.4pt solid #f0f0f0; padding-bottom: 1mm; margin-bottom: 1.5mm; height: 10mm; }
.logo-box-v2 { width: 9mm; height: 9mm; background: #fff; padding: 1mm; border-radius: 1.5mm; display: flex; align-items: center; justify-content: center; box-shadow: 0 0.5mm 1mm rgba(0,0,0,0.05); }
.logo-box-v2 img { width: 100%; height: 100%; object-fit: contain; }
.school-name-v2 { font-size: 9.5pt; font-weight: 900; color: var(--navy); margin: 0; line-height: 1; text-transform: uppercase; }
.academic-year-v2 { font-size: 5pt; font-weight: 700; color: var(--turquoise); margin-top: 0.5mm; }
.official-stamp { position: absolute; top: -3mm; right: -3mm; background: var(--turquoise); color: #fff; font-size: 5pt; padding: 1.2mm 6mm; border-radius: 0 0 0 4mm; font-weight: 900; }

/* Body */
.card-body-v2 { display: flex; flex-grow: 1; }

/* Left Section (Photo + ID) */
.side-left-v2 { width: 22mm; text-align: center; flex-shrink: 0; display: flex; flex-direction: column; }
.photo-wrapper-v2 { position: relative; width: 17mm; height: 20mm; margin: 0 auto; } /* Taille photo réduite de 1mm */
.student-photo-v2 { width: 100%; height: 100%; object-fit: cover; border-radius: 2mm; border: 1.2pt solid #fff; box-shadow: 0 1mm 2mm rgba(0,0,0,0.1); position: relative; z-index: 5; }
.green-decorator-v2 { position: absolute; top: -0.8mm; right: -0.8mm; width: 5.5mm; height: 5.5mm; background: var(--turquoise); border-radius: 1.2mm; z-index: 1; opacity: 0.6; }

/* Matricule ID (Fixé pour ne pas être masqué) */
.id-badge-v2 { margin-top: 1.5mm; background: #f8f9fa; border-radius: 1.2mm; padding: 0.8mm; border: 0.4pt solid #ddd; }
.id-label { display: block; font-size: 4.5pt; color: #666; font-weight: 800; line-height: 1; margin-bottom: 0.3mm; }
.id-value { font-size: 7.5pt; color: var(--navy); font-weight: 900; line-height: 1; }

/* Center Section */
.side-center-v2 { flex-grow: 1; padding: 0 2mm; }
.info-group-v2 label, .info-block-v2 label { font-size: 4.8pt; font-weight: 900; color: #aaa; letter-spacing: 0.3pt; display: block; margin-bottom: 0.3mm; }
.student-name-v2 { font-size: 10.5pt; font-weight: 900; color: #111; text-transform: uppercase; margin-bottom: 1mm; line-height: 1.1; }
.student-class-v2 { font-size: 7.5pt; font-weight: 800; color: var(--turquoise); margin: 0; }

.parent-box-v2 { background: rgba(34, 62, 111, 0.05); border-left: 1.5pt solid var(--navy); padding: 1.2mm; border-radius: 1.2mm; margin-top: 1.5mm; }
.p-header-v2 { font-size: 4.5pt; font-weight: 900; color: #888; margin-bottom: 0.5mm; }
.p-data-v2 { display: flex; justify-content: space-between; font-weight: 800; color: #333; font-size: 6.5pt; width: 100%; }
.p-phone { color: #d32f2f; }

/* Right Section (QR) */
.side-right-v2 { width: 15mm; text-align: center; flex-shrink: 0; }
.qr-frame-v2 { background: #fff; padding: 0.8mm; border-radius: 1.5mm; border: 0.8pt solid var(--cyan); box-shadow: 0 0.5mm 1mm rgba(0,0,0,0.05); }
.qr-img-v2 { width: 12mm; height: 12mm; display: block; }
.qr-text-v2 { font-size: 4pt; font-weight: 900; color: var(--navy); margin-top: 1mm; }

/* Footer (Noir et bien positionné en bas) */
.card-footer-v2 { 
    margin-top: auto; 
    border-top: 0.4pt solid #eee; 
    padding-top: 1.2mm; 
    text-align: center; 
    font-size: 6pt; 
    font-weight: 900; 
    color: #000; /* Passé en NOIR */
    letter-spacing: 0.1pt; 
    width: 100%; 
    text-transform: uppercase;
}

/* RÈGLES D'IMPRESSION STRICTES */
@media print {
    @page { size: 85.6mm 54mm; margin: 0 !important; }
    html, body { width: 85.6mm; height: 54mm; background: #fff !important; margin: 0 !important; padding: 0 !important; }
    .print-wrapper { width: 85.6mm !important; height: 54mm !important; margin: 0 !important; padding: 0 !important; display: block !important; }
    .avant-garde-card { margin: 0 !important; border: none !important; box-shadow: none !important; transform: none !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}

/* AFFICHAGE ÉCRAN */
@media screen {
    .avant-garde-card {
        transform: scale(1.8);
        transform-origin: center;
        margin: 80px auto;
    }
}

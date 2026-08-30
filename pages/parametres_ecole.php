<?php
$page_title = 'Paramètres de l\'Établissement';
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'administrateur') {
    die("Accès refusé.");
}

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_stamp'])) {
        // Mise à jour des positions et taille
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        $top = $_POST['stamp_top'];
        $right = $_POST['stamp_right'];
        $size = $_POST['stamp_size'];
        
        $keys = ['stamp_top' => $top, 'stamp_right' => $right, 'stamp_size' => $size];
        foreach ($keys as $key => $val) {
            $stmt->bind_param("ss", $val, $key);
            $stmt->execute();
        }
        
        // Gestion de l'image du cachet
        if (!empty($_FILES['school_stamp']['name'])) {
            $target_dir = "../uploads/school/";
            $file_extension = strtolower(pathinfo($_FILES["school_stamp"]["name"], PATHINFO_EXTENSION));
            $new_filename = "stamp_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["school_stamp"]["tmp_name"], $target_file)) {
                $stmt->bind_param("ss", $new_filename, $key = 'school_stamp');
                $stmt->execute();
                $message = "Paramètres et cachet mis à jour !";
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $message = "Paramètres de position mis à jour !";
        }
    }
}

// Récupération des paramètres actuels
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$stamp_url = !empty($settings['school_stamp']) ? "/G/uploads/school/" . $settings['school_stamp'] : "";
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-navy">Configuration du Cachet Officiel</h3>
        <p class="text-muted small">Personnalisez l'emplacement et l'image du cachet qui apparaîtra sur toutes les cartes scolaires.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Formulaire de réglage -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="pages/parametres_ecole.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Image du Cachet (PNG transparent conseillé)</label>
                        <input type="file" name="school_stamp" class="form-control rounded-3" accept="image/*" onchange="previewImage(this)">
                        <?php if ($stamp_url): ?>
                            <div class="mt-2 small text-muted">Fichier actuel : <?php echo $settings['school_stamp']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Position Verticale (Top: <?php echo $settings['stamp_top']; ?>mm)</label>
                        <input type="range" name="stamp_top" class="form-range" min="-30" max="30" step="0.5" value="<?php echo $settings['stamp_top']; ?>" oninput="updatePreview()">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Position Horizontale (Right: <?php echo $settings['stamp_right']; ?>mm)</label>
                        <input type="range" name="stamp_right" class="form-range" min="-30" max="30" step="0.5" value="<?php echo $settings['stamp_right']; ?>" oninput="updatePreview()">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Taille du Cachet (Diamètre: <?php echo $settings['stamp_size']; ?>mm)</label>
                        <input type="range" name="stamp_size" class="form-range" min="5" max="30" step="0.5" value="<?php echo $settings['stamp_size']; ?>" oninput="updatePreview()">
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="update_stamp" class="btn btn-navy text-white rounded-pill py-2">
                            <i class="bi bi-save me-2"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Aperçu interactif -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-light-subtle">
            <div class="card-header bg-white border-0 py-3 text-center">
                <h6 class="mb-0 fw-bold">Aperçu du Rendu (Verso)</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 400px; background: #e9ecef;">
                
                <!-- Simulation de la carte verso -->
                <div class="card-simulation bg-white shadow-lg overflow-hidden position-relative">
                    <div class="p-3">
                         <div style="border-bottom: 1px solid #223E6F; font-size: 8pt; font-weight: 900; color: #223E6F;">RÈGLEMENT INTÉRIEUR</div>
                         <div style="font-size: 5pt; line-height: 1.4; margin-top: 2mm;">
                             1. La carte est strictement personnelle.<br>
                             2. En cas de perte, le titulaire doit informer...<br>
                             3. Présentation obligatoire à l'entrée.
                         </div>
                    </div>

                    <!-- Zone Signature & Cachet -->
                    <div class="position-absolute bottom-0 end-0 p-3 text-center" style="width: 40mm;">
                        <!-- Le Cachet Dynamique -->
                        <div id="stampPreview" class="stamp-wrapper">
                            <?php if ($stamp_url): ?>
                                <img src="<?php echo $stamp_url; ?>" id="stampImg">
                            <?php else: ?>
                                <div class="stamp-placeholder">Cachet École</div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="border-top: 1px solid #333; margin-bottom: 1mm;"></div>
                        <span style="font-size: 5pt; font-weight: 800; color: #223E6F;">SIGNATURE DE LA DIRECTION</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    .btn-navy { background-color: #223E6F; transition: all 0.3s; }
    .btn-navy:hover { background-color: #1a2f55; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(34, 62, 111, 0.2); }
    
    .card-simulation {
        width: 85.6mm;
        height: 53.98mm;
        border-radius: 3mm;
        transform: scale(1.5);
    }
    
    .stamp-wrapper {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: rotate(-15deg);
        pointer-events: none;
    }
    
    .stamp-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .stamp-placeholder {
        width: 100%;
        height: 100%;
        border: 1pt dashed rgba(220, 53, 69, 0.4);
        border-radius: 50%;
        color: rgba(220, 53, 69, 0.4);
        font-size: 3pt;
        display: flex;
        align-items: center;
        justify-content: center;
        text-transform: uppercase;
    }
</style>

<script>
    function updatePreview() {
        const top = document.querySelector('[name="stamp_top"]').value;
        const right = document.querySelector('[name="stamp_right"]').value;
        const size = document.querySelector('[name="stamp_size"]').value;
        
        const preview = document.getElementById('stampPreview');
        preview.style.top = top + 'mm';
        preview.style.right = right + 'mm';
        preview.style.width = size + 'mm';
        preview.style.height = size + 'mm';
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('stampPreview');
                preview.innerHTML = `<img src="${e.target.result}" id="stampImg">`;
                updatePreview();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', updatePreview);
</script>

<?php include '../includes/footer.php'; ?>

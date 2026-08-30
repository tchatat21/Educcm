<?php
$page_title = "Scanner d'Appel";
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['user_role'] !== 'enseignant' && $_SESSION['user_role'] !== 'administrateur') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Accès non autorisé.</div></div>";
    include '../includes/footer.php';
    exit;
}

$enseignant_id = $_SESSION['user_id'];
$today_fr = ['Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'][date('l')];

// 1. Récupérer les cours normaux d'aujourd'hui
$stmt_norm = $conn->prepare("SELECT edt.id, edt.heure_debut, c.nom as classe_nom, m.nom as matiere_nom FROM emploi_du_temps edt JOIN classes c ON edt.classe_id = c.id JOIN matieres m ON edt.matiere_id = m.id WHERE edt.enseignant_id = ? AND edt.jour_semaine = ?");
$stmt_norm->bind_param("is", $enseignant_id, $today_fr);
$stmt_norm->execute();
$courses_norm = $stmt_norm->get_result();

// 2. Récupérer les cours supplémentaires d'aujourd'hui
$today_date = date('Y-m-d');
$stmt_supp = $conn->prepare("SELECT cs.id, cs.date_heure, c.nom as classe_nom, m.nom as matiere_nom FROM cours_supplementaires cs JOIN classes c ON cs.classe_id = c.id JOIN matieres m ON cs.matiere_id = m.id WHERE cs.enseignant_id = ? AND DATE(cs.date_heure) = ?");
$stmt_supp->bind_param("is", $enseignant_id, $today_date);
$stmt_supp->execute();
$courses_supp = $stmt_supp->get_result();
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-navy text-white p-4 rounded-top-4" style="background-color: #223E6F;">
                <h4 class="mb-0 fw-bold"><i class="bi bi-qr-code-scan me-2 text-turquoise"></i> Scanner de Présence</h4>
            </div>
            <div class="card-body p-4">
                
                <!-- Sélecteur de type de cours -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">1. Type de séance</label>
                    <div class="d-flex gap-2">
                        <input type="radio" class="btn-check" name="course_type" id="type_normal" value="normal" checked>
                        <label class="btn btn-outline-primary rounded-pill flex-grow-1" for="type_normal">Cours Normal</label>

                        <input type="radio" class="btn-check" name="course_type" id="type_supp" value="supplementaire">
                        <label class="btn btn-outline-warning rounded-pill flex-grow-1" for="type_supp">Cours Suppl.</label>
                    </div>
                </div>

                <!-- Sélecteur du cours (Dynamique selon le type) -->
                <div class="mb-4">
                    <label for="course_id" class="form-label small fw-bold text-muted text-uppercase">2. Sélectionner le cours</label>
                    
                    <!-- Liste Cours Normaux -->
                    <select id="select_normal" class="form-select form-select-lg rounded-3 course-select">
                        <option value="">-- Choisir un cours normal --</option>
                        <?php while($c = $courses_norm->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['matiere_nom'] . ' (' . $c['classe_nom'] . ') - ' . date('H:i', strtotime($c['heure_debut']))); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <!-- Liste Cours Supplémentaires (Cachée par défaut) -->
                    <select id="select_supp" class="form-select form-select-lg rounded-3 course-select d-none">
                        <option value="">-- Choisir un cours suppl. --</option>
                        <?php while($cs = $courses_supp->fetch_assoc()): ?>
                            <option value="<?php echo $cs['id']; ?>">
                                <?php echo htmlspecialchars($cs['matiere_nom'] . ' (' . $cs['classe_nom'] . ') - ' . date('H:i', strtotime($cs['date_heure']))); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Zone du Scanner -->
                <div id="reader-wrapper" style="display:none;">
                    <div class="alert alert-info py-2 small rounded-3"><i class="bi bi-camera-fill me-2"></i> Caméra active : Scannez la carte scolaire.</div>
                    <div id="reader" style="width: 100%; border-radius: 20px; overflow: hidden; border: 2px solid #39A9C3;"></div>
                    <div id="scan-result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    const typeRadios = document.getElementsByName('course_type');
    const selectNormal = document.getElementById('select_normal');
    const selectSupp = document.getElementById('select_supp');
    const readerWrapper = document.getElementById('reader-wrapper');
    const scanResult = document.getElementById('scan-result');
    let html5QrcodeScanner = null;

    // Basculer entre normal et suppl.
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'normal') {
                selectNormal.classList.remove('d-none');
                selectSupp.classList.add('d-none');
            } else {
                selectSupp.classList.remove('d-none');
                selectNormal.classList.add('d-none');
            }
            resetScanner();
        });
    });

    // Écouter le changement de sélection
    document.querySelectorAll('.course-select').forEach(select => {
        select.addEventListener('change', function() {
            if (this.value) {
                readerWrapper.style.display = 'block';
                startScanner();
            } else {
                resetScanner();
            }
        });
    });

    function resetScanner() {
        readerWrapper.style.display = 'none';
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().catch(e => console.log(e));
            html5QrcodeScanner = null;
        }
        scanResult.innerHTML = '';
    }

    function startScanner() {
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
                fps: 10, qrbox: { width: 250, height: 250 } 
            });
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    function onScanSuccess(decodedText) {
        html5QrcodeScanner.pause();
        
        const type = document.querySelector('input[name="course_type"]:checked').value;
        const courseId = (type === 'normal') ? selectNormal.value : selectSupp.value;
        
        // Appel AJAX
        fetch('valider_presence_qr.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `qr_token=${encodeURIComponent(decodedText)}&course_id=${courseId}&type=${type}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                scanResult.innerHTML = `<div class="alert alert-success fw-bold py-3 shadow-sm">
                    <i class="bi bi-check-circle-fill me-2 fs-4"></i> ${data.eleve_nom} marqué PRÉSENT !
                </div>`;
            } else {
                scanResult.innerHTML = `<div class="alert alert-danger fw-bold py-3 shadow-sm">
                    <i class="bi bi-x-circle-fill me-2 fs-4"></i> Erreur: ${data.message}
                </div>`;
            }
            setTimeout(() => { scanResult.innerHTML = ''; html5QrcodeScanner.resume(); }, 3000);
        })
        .catch(err => { console.error(err); html5QrcodeScanner.resume(); });
    }

    function onScanFailure(error) {}
</script>

<style>
    .bg-navy { background-color: #223E6F; }
    .text-turquoise { color: #39A9C3; }
</style>

<?php include '../includes/footer.php'; ?>

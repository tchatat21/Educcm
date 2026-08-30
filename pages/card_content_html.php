<!-- CONTENU DE LA CARTE AVANT-GARDE -->
<div class="avant-garde-card" id="myCard">
    <div class="bg-blob-1"></div>
    <div class="bg-blob-2"></div>
    <div class="glass-overlay"></div>

    <!-- Header -->
    <div class="card-header-v2">
        <div class="logo-box-v2">
            <img src="/G/educ.jpeg" alt="Logo" onerror="this.src='https://via.placeholder.com/100?text=LOGO'">
        </div>
        <div class="header-titles-v2">
            <h1 class="school-name-v2">EDUC.CM</h1>
            <div class="academic-year-v2">SESSION ACADÉMIQUE 2025 - 2026</div>
        </div>
        <div class="official-stamp">OFFICIEL</div>
    </div>

    <!-- Body -->
    <div class="card-body-v2">
        <!-- Photo & ID -->
        <div class="side-left-v2">
            <div class="photo-wrapper-v2">
                <div class="green-decorator-v2"></div>
                <?php 
                $photo_name = $eleve['photo'];
                $photo_path = "../uploads/photos/" . $photo_name;
                
                if (!empty($photo_name) && $photo_name !== 'default_avatar.png' && file_exists($photo_path)) {
                    $src_photo = "/G/uploads/photos/" . $photo_name;
                } else {
                    // Avatar par défaut stylé avec les initiales
                    $src_photo = "https://ui-avatars.com/api/?name=" . urlencode($eleve['prenom'] . ' ' . $eleve['nom']) . "&background=223E6F&color=fff&size=300";
                }
                ?>
                <img src="<?php echo $src_photo; ?>" class="student-photo-v2" alt="Student">
            </div>
            <div class="id-badge-v2">
                <span class="id-label">ID ÉLÈVE</span>
                <span class="id-value">#GS-<?php echo str_pad($eleve['id'], 5, '0', STR_PAD_LEFT); ?></span>
            </div>
        </div>

        <!-- Informations -->
        <div class="side-center-v2">
            <div class="info-group-v2">
                <label>NOM & PRÉNOMS</label>
                <h2 class="student-name-v2"><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></h2>
            </div>
            <div class="info-block-v2">
                <label>CLASSE / NIVEAU</label>
                <h4 class="student-class-v2"><?php echo htmlspecialchars($eleve['classe_nom'] ?: 'A DÉFINIR'); ?></h4>
            </div>
            <div class="parent-box-v2">
                <div class="p-header-v2">PARENT / TÉLÉPHONE D'URGENCE</div>
                <div class="p-data-v2">
                    <span class="p-name"><?php echo htmlspecialchars($eleve['parent_nom']); ?></span>
                    <span class="p-phone"><?php echo htmlspecialchars($eleve['parent_tel'] ?: '---'); ?></span>
                </div>
            </div>
        </div>

        <!-- QR Code -->
        <div class="side-right-v2">
            <div class="qr-frame-v2">
                <img src="<?php echo $qr_code_url; ?>" alt="QR" class="qr-img-v2">
            </div>
            <div class="qr-text-v2">POINTAGE</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer-v2">
        CARTE D'IDENTITÉ SCOLAIRE OFFICIELLE • EDUC.CM
    </div>
</div>

<?php
require_once __DIR__ . '/db.php';

class InscriptionValidationBot
{
    public function analyzeRegistration($conn, $user_id, $role, $photo_path, $document_path)
    {
        $score = 0;
        $checks = [];
        $issues = [];

        $photo_check = $this->verifyPhoto($photo_path);
        if ($photo_check['valid']) {
            $score += 40;
            $checks[] = 'photo_valide';
        } else {
            $issues[] = $photo_check['message'];
        }

        $document_check = $this->verifyDocument($document_path, $role);
        if ($document_check['valid']) {
            $score += 45;
            $checks[] = 'document_valide';
        } else {
            $issues[] = $document_check['message'];
        }

        $identity_check = $this->verifyIdentity($conn, $user_id, $role);
        if ($identity_check['valid']) {
            $score += 15;
            $checks[] = 'identite_coherente';
        } else {
            $issues[] = $identity_check['message'];
        }

        $valid = ($score >= 80 && empty($issues));

        $this->saveResult($conn, $user_id, $role, $score, $valid, $checks, $issues);

        if ($valid) {
            $this->notifyAdministrators($conn, $user_id, $role, $score);
        } elseif (!empty($issues)) {
            $this->notifyAdministratorsOfRejection($conn, $user_id, $role, $issues, $score);
            $conn->query("UPDATE utilisateurs SET statut_compte = 'bloque' WHERE id = " . (int)$user_id);
        }

        return [
            'valid' => $valid,
            'score' => $score,
            'checks' => $checks,
            'issues' => $issues,
        ];
    }

    protected function verifyPhoto($photo_path)
    {
        if (!is_file($photo_path)) {
            return ['valid' => false, 'message' => 'Photo de profil absente ou invalide.'];
        }

        $size = filesize($photo_path);
        if ($size === false || $size < 1000) {
            return ['valid' => false, 'message' => 'La photo est trop petite ou illisible.'];
        }

        $image_info = @getimagesize($photo_path);
        if ($image_info === false) {
            return ['valid' => false, 'message' => 'Le fichier photo n’est pas une image valide.'];
        }

        $width = (int)$image_info[0];
        $height = (int)$image_info[1];
        if ($width < 250 || $height < 250) {
            return ['valid' => false, 'message' => 'La photo doit avoir une résolution minimale de 250x250 pixels.'];
        }

        return ['valid' => true, 'message' => 'Photo conforme.'];
    }

    protected function verifyDocument($document_path, $role)
    {
        if (!is_file($document_path)) {
            return ['valid' => false, 'message' => 'Document justificatif absent.'];
        }

        $extension = strtolower(pathinfo($document_path, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($extension, $allowed_extensions, true)) {
            return ['valid' => false, 'message' => 'Le document n’a pas un format autorisé.'];
        }

        $file_size = filesize($document_path);
        if ($file_size === false || $file_size < 500) {
            return ['valid' => false, 'message' => 'Le document est vide ou corrompu.'];
        }

        $content = file_get_contents($document_path);
        if ($content === false) {
            return ['valid' => false, 'message' => 'Lecture du document impossible.'];
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $info = @getimagesize($document_path);
            if ($info === false) {
                return ['valid' => false, 'message' => 'Le justificatif image n’est pas exploitable.'];
            }

            if ($info[0] < 300 || $info[1] < 300) {
                return ['valid' => false, 'message' => 'Le document image est trop petit pour être fiable.'];
            }
        }

        if ($extension === 'pdf') {
            if (strpos($content, '%PDF') === false) {
                return ['valid' => false, 'message' => 'Le PDF ne semble pas être un document valide.'];
            }

            if (preg_match('/(javascript|/JavaScript|<script)/i', $content)) {
                return ['valid' => false, 'message' => 'Le PDF contient du contenu scripté non autorisé.'];
            }
        }

        if ($role === 'parent' && preg_match('/(cni|cin|carte nationale|passport)/i', $content) === 0 && !in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            // Ce contrôle ne bloque pas un document correct, il sert surtout d’alerte.
        }

        return ['valid' => true, 'message' => 'Document conforme au format et aux règles minimales.'];
    }

    protected function verifyIdentity($conn, $user_id, $role)
    {
        $stmt = $conn->prepare('SELECT nom, prenom, email, role, enfants_noms, statut_compte FROM utilisateurs WHERE id = ?');
        if (!$stmt) {
            return ['valid' => false, 'message' => 'Impossible de vérifier les données utilisateur.'];
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return ['valid' => false, 'message' => 'Utilisateur introuvable pour validation.'];
        }

        if (empty($user['nom']) || empty($user['prenom']) || empty($user['email'])) {
            return ['valid' => false, 'message' => 'Les informations identité du dossier sont incomplètes.'];
        }

        if ($role === 'parent') {
            $linked_children = $conn->query("SELECT COUNT(*) AS total FROM parents_eleves WHERE parent_id = " . (int)$user_id);
            if ($linked_children && $linked_children->fetch_assoc()['total'] > 0) {
                return ['valid' => true, 'message' => 'Le parent est relié à un enfant enregistré.'];
            }

            if (!empty(trim((string)$user['enfants_noms']))) {
                return ['valid' => true, 'message' => 'La liste des enfants est renseignée.'];
            }

            return ['valid' => false, 'message' => 'Le parent n’est relié à aucun enfant.'];
        }

        return ['valid' => true, 'message' => 'Données utilisateur cohérentes.'];
    }

    protected function saveResult($conn, $user_id, $role, $score, $valid, $checks, $issues)
    {
        $checks_json = json_encode($checks, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $issues_json = json_encode($issues, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $conn->query("CREATE TABLE IF NOT EXISTS registration_validations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role VARCHAR(30) NOT NULL,
            validation_score INT NOT NULL,
            is_valid TINYINT(1) NOT NULL DEFAULT 0,
            checks JSON DEFAULT NULL,
            issues JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_validation (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $conn->prepare('INSERT INTO registration_validations (user_id, role, validation_score, is_valid, checks, issues) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role = VALUES(role), validation_score = VALUES(validation_score), is_valid = VALUES(is_valid), checks = VALUES(checks), issues = VALUES(issues), created_at = CURRENT_TIMESTAMP');
        $stmt->bind_param('isibss', $user_id, $role, $score, $valid, $checks_json, $issues_json);
        $stmt->execute();
        $stmt->close();
    }

    protected function notifyAdministrators($conn, $user_id, $role, $score)
    {
        $user = $conn->query('SELECT nom, prenom, email FROM utilisateurs WHERE id = ' . (int)$user_id)->fetch_assoc();
        if (!$user) {
            return;
        }

        $admins = $conn->query("SELECT id, email FROM utilisateurs WHERE role = 'administrateur'");
        if (!$admins || $admins->num_rows === 0) {
            return;
        }

        $full_name = trim($user['prenom'] . ' ' . $user['nom']);
        $message = 'Nouvelle inscription validée par le bot : ' . $full_name . ' (' . strtoupper($role) . ') - score ' . $score . '/100. Vérifiez les pièces justificatives et activez le compte.';

        while ($admin = $admins->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, message, type, status, is_read) VALUES (?, ?, 'email', 'sent', 0)");
            $stmt->bind_param('is', $admin['id'], $message);
            $stmt->execute();
            $stmt->close();

            $log_entry = "========================================\n";
            $log_entry .= "DATE : " . date('Y-m-d H:i:s') . "\n";
            $log_entry .= "DESTINATAIRE : " . $admin['email'] . "\n";
            $log_entry .= "MESSAGE : " . $message . "\n";
            $log_entry .= "STATUT : Notification admin envoyée\n";
            $log_entry .= "========================================\n\n";
            file_put_contents(__DIR__ . '/../logs/emails.log', $log_entry, FILE_APPEND);
        }
    }

    protected function notifyAdministratorsOfRejection($conn, $user_id, $role, $issues, $score)
    {
        $user = $conn->query('SELECT nom, prenom, email FROM utilisateurs WHERE id = ' . (int)$user_id)->fetch_assoc();
        if (!$user) {
            return;
        }

        $admins = $conn->query("SELECT id, email FROM utilisateurs WHERE role = 'administrateur'");
        if (!$admins || $admins->num_rows === 0) {
            return;
        }

        $full_name = trim($user['prenom'] . ' ' . $user['nom']);
        $issue_summary = implode(' | ', $issues);
        $message = 'Inscription refusée par le bot : ' . $full_name . ' (' . strtoupper($role) . ') - score ' . $score . '/100. Motif : ' . $issue_summary;

        while ($admin = $admins->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, message, type, status, is_read) VALUES (?, ?, 'email', 'sent', 0)");
            $stmt->bind_param('is', $admin['id'], $message);
            $stmt->execute();
            $stmt->close();
        }
    }
}

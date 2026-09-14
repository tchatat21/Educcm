<?php
// includes/notifications.php
require_once __DIR__ . '/db.php';

/**
 * Envoie une notification interne (Base de données + Log local)
 */
function notifierParent($conn, $eleve_id, $statut, $matiere_nom) {
    if ($statut === 'Présent') return;

    $stmt = $conn->prepare("
        SELECT u.nom, u.prenom, p.id as parent_id, p.email as parent_email
        FROM utilisateurs u
        JOIN parents_eleves pe ON u.id = pe.eleve_id
        JOIN utilisateurs p ON pe.parent_id = p.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $eleve_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $date = date('d/m/Y à H:i');
    
    while ($row = $result->fetch_assoc()) {
        $parent_id = $row['parent_id'];
        $parent_email = $row['parent_email'];
        $eleve_nom = $row['prenom'] . ' ' . $row['nom'];
        
        $sujet = "Alerte Présence : $eleve_nom (" . strtoupper($statut) . ")";
        $message_text = "Bonjour, votre enfant <b>$eleve_nom</b> a été marqué comme '<b>$statut</b>' en <b>$matiere_nom</b> le $date.";
        
        // 1. Enregistrement pour le compte parent interne (Statut 'sent' par défaut car interne)
        $stmt_ins = $conn->prepare("INSERT INTO notifications (recipient_id, message, type, status, is_read) VALUES (?, ?, 'email', 'sent', 0)");
        $stmt_ins->bind_param("is", $parent_id, $message_text);
        $stmt_ins->execute();
        $stmt_ins->close();
        
        // 2. Log local de secours
        $log_entry = "========================================\n";
        $log_entry .= "DATE : " . date('Y-m-d H:i:s') . "\n";
        $log_entry .= "DESTINATAIRE : $parent_email (Parent ID: $parent_id)\n";
        $log_entry .= "MESSAGE : $message_text\n";
        $log_entry .= "STATUT : Notification interne envoyée\n";
        $log_entry .= "========================================\n\n";
        
        file_put_contents(__DIR__ . '/../logs/emails.log', $log_entry, FILE_APPEND);
    }
    $stmt->close();
}

/**
 * Confirmation d'arrivée par Scan QR (Notification interne)
 */
function notifierScanQR($conn, $eleve_id, $matiere_nom) {
    $stmt = $conn->prepare("
        SELECT u.nom, u.prenom, p.id as parent_id, p.email as parent_email
        FROM utilisateurs u
        JOIN parents_eleves pe ON u.id = pe.eleve_id
        JOIN utilisateurs p ON pe.parent_id = p.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $eleve_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $parent_id = $row['parent_id'];
        $parent_email = $row['parent_email'];
        $eleve_nom = $row['prenom'] . ' ' . $row['nom'];
        
        $message = "Bonjour, votre enfant <b>$eleve_nom</b> est bien arrivé en cours de <b>$matiere_nom</b> (Scan QR effectué à " . date('H:i') . ").";
        
        // Enregistrement base
        $stmt_ins = $conn->prepare("INSERT INTO notifications (recipient_id, message, type, status, is_read) VALUES (?, ?, 'email', 'sent', 0)");
        $stmt_ins->bind_param("is", $parent_id, $message);
        $stmt_ins->execute();
        $stmt_ins->close();

        // Log local
        $log_entry = "--- SCAN QR SUCCESS ---\n";
        $log_entry .= "DEST : $parent_email | MSG : $message\n\n";
        file_put_contents(__DIR__ . '/../logs/emails.log', $log_entry, FILE_APPEND);
    }
}
?>

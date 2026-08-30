<?php
// api/check_notifications.php
header('Content-Type: application/json');
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer le nombre de notifications non lues
$unread_count = 0;
try {
    $res_notif = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE recipient_id = $user_id AND is_read = 0");
    if ($res_notif) {
        $unread_count = (int)$res_notif->fetch_assoc()['unread'];
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur SQL']);
    exit;
}

// Optionnel : Récupérer le dernier message non lu pour l'afficher en popup (Toast)
$last_message = "";
if ($unread_count > 0) {
    $res_last = $conn->query("SELECT message FROM notifications WHERE recipient_id = $user_id AND is_read = 0 ORDER BY date_creation DESC LIMIT 1");
    if ($res_last) {
        $last_message = $res_last->fetch_assoc()['message'];
    }
}

echo json_encode([
    'unread_count' => $unread_count,
    'last_message' => strip_tags($last_message) // On retire le HTML pour le toast
]);
?>

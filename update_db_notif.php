<?php
require_once 'includes/db.php';
$conn->query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER status");
echo "Base de données mise à jour avec succès.";
unlink(__FILE__);
?>

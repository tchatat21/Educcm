<?php
// Script pour générer un hash bcrypt valide
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo $hash;
?>
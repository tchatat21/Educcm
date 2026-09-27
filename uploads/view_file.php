<?php
$allowed_folders = ['photos', 'justificatifs', 'school'];
$folder = $_GET['folder'] ?? '';
$file = $_GET['file'] ?? '';

if (!in_array($folder, $allowed_folders, true)) {
    http_response_code(400);
    echo 'Dossier non autorisé.';
    exit;
}

if ($file === '' || strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
    http_response_code(400);
    echo 'Nom de fichier invalide.';
    exit;
}

$root = __DIR__;
$target = $root . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $file;

if (!is_file($target)) {
    http_response_code(404);
    echo 'Fichier introuvable.';
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($target) ?: 'application/octet-stream';
$extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));

$browserSafe = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'pdf'];
if (!in_array($extension, $browserSafe, true) && strpos($mime, 'image/') === false && strpos($mime, 'application/pdf') === false) {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($target));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . basename($file) . '"');
readfile($target);
exit;

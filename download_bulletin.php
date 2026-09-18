<?php
/**
 * Sert un bulletin uploadé UNIQUEMENT à un admin connecté — le fichier
 * physique lui-même est bloqué en accès direct (voir uploads/.htaccess),
 * mais ce script protège aussi contre l'accès direct sur les serveurs
 * (comme le serveur de dev PHP intégré) qui n'appliquent pas .htaccess.
 */
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require_permission($pdo, 'preinscriptions');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT bulletin_path, bulletin_label FROM preinscriptions WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['bulletin_path'])) {
    http_response_code(404);
    exit('Document introuvable.');
}

$relativePath = $row['bulletin_path'];
// Le chemin est toujours généré par handle_document_upload() sous la forme
// "uploads/bulletins/xxx.ext" — on rejette tout ce qui s'en écarte (défense
// en profondeur contre une éventuelle traversée de répertoire).
if (!preg_match('#^uploads/bulletins/[A-Za-z0-9._-]+$#', $relativePath)) {
    http_response_code(400);
    exit('Chemin invalide.');
}

$fullPath = __DIR__ . '/' . $relativePath;
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

$mime = mime_content_type($fullPath) ?: 'application/octet-stream';
$label = $row['bulletin_label'] ?: basename($fullPath);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $label) . '"');
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;

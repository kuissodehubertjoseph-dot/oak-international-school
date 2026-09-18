<?php
/**
 * Gère l'upload d'une photo (staff ou galerie) vers /images.
 * Retourne le chemin relatif ("images/xxx.jpg") ou null si aucun fichier valide.
 * Lève une exception si un fichier a été envoyé mais est invalide.
 */
function handle_photo_upload(string $fieldName, string $prefix): ?string {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur lors de l'envoi du fichier.");
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Image trop volumineuse (max 4 Mo).');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format non supporté (JPEG, PNG ou WebP uniquement).');
    }
    $ext = $allowed[$mime];
    $name = $prefix . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = dirname(__DIR__) . '/images/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Impossible d'enregistrer l'image.");
    }
    return 'images/' . $name;
}

/**
 * Gère l'upload d'un document (bulletin / relevé de notes) vers /uploads/bulletins.
 * Accepte PDF, JPEG et PNG. Retourne le chemin relatif ou null si aucun fichier.
 * Lève une exception si un fichier a été envoyé mais est invalide.
 */
function handle_document_upload(string $fieldName): ?string {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur lors de l'envoi du document.");
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Document trop volumineux (max 8 Mo).');
    }
    $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format non supporté (PDF, JPEG ou PNG uniquement).');
    }
    $ext = $allowed[$mime];
    $dir = dirname(__DIR__) . '/uploads/bulletins';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $name = 'bulletin-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException("Impossible d'enregistrer le document.");
    }
    return 'uploads/bulletins/' . $name;
}

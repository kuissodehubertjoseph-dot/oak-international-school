<?php
/**
 * API réglages du site — équivalent de /api/admin/settings.
 * (Sur le serveur intégré PHP `php -S`, pas de routeur : on utilise un nom
 * de fichier plat, comme le reste des endpoints du projet — cf. contact-submit.php.)
 *
 * GET  : lecture publique (logo_mode / logo_text) — utilisée par assets/logo.js
 *        sur les pages publiques statiques pour appliquer l'identité visuelle.
 * PUT  : réservé au super-admin (onglet Développeur) — modifie les réglages.
 */
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/site_settings.php';
require __DIR__ . '/includes/activity.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = get_site_settings($pdo);
    echo json_encode([
        'logo_mode' => $settings['logo_mode'],
        'logo_text' => $settings['logo_text'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'PUT') {
    if (empty($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentification requise.']);
        exit;
    }
    require_super_admin($pdo);

    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $partial = [];
    if (isset($body['logo_mode'])) { $partial['logo_mode'] = $body['logo_mode'] === 'text' ? 'text' : 'image'; }
    if (isset($body['logo_text'])) { $partial['logo_text'] = trim((string) $body['logo_text']); }

    $updated = save_site_settings($pdo, $partial);

    logActivity($pdo, 'reglages_modifies', 'site_settings', 'main',
        'Logo : mode=' . $updated['logo_mode'] . ($updated['logo_mode'] === 'text' ? ', texte="' . $updated['logo_text'] . '"' : ''));

    echo json_encode(['ok' => true, 'settings' => ['logo_mode' => $updated['logo_mode'], 'logo_text' => $updated['logo_text']]], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non supportée.']);

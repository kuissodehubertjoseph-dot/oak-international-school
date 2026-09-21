<?php
require_once __DIR__ . '/render.php';

/**
 * Réglages globaux du site (table site_settings, ligne unique id='main',
 * colonne JSON). Pour l'instant : identité visuelle du logo.
 */

function get_site_settings(PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT data FROM site_settings WHERE id = ?');
    $stmt->execute(['main']);
    $row = $stmt->fetch();
    $data = $row ? (json_decode($row['data'], true) ?: []) : [];

    return array_merge([
        'logo_mode' => 'image',
        'logo_text' => 'Collège Catholique Saint Jean-Baptiste',
    ], $data);
}

function save_site_settings(PDO $pdo, array $partial): array {
    $current = get_site_settings($pdo);
    $updated = array_merge($current, $partial);

    $updated['logo_mode'] = $updated['logo_mode'] === 'text' ? 'text' : 'image';
    $updated['logo_text'] = trim((string) ($updated['logo_text'] ?? '')) ?: 'Collège Catholique Saint Jean-Baptiste';

    $stmt = $pdo->prepare("INSERT INTO site_settings (id, data) VALUES ('main', ?)
        ON CONFLICT(id) DO UPDATE SET data = excluded.data");
    $stmt->execute([json_encode($updated, JSON_UNESCAPED_UNICODE)]);

    return $updated;
}

/**
 * Rendu HTML du logo pour les écrans admin (server-side, PHP). Sur les pages
 * publiques statiques (.html), c'est assets/logo.js qui applique le même
 * réglage côté client (voir api-settings.php pour la lecture publique).
 */
function render_brand_logo(array $settings, string $imgClass = ''): string {
    if (($settings['logo_mode'] ?? 'image') === 'text') {
        return '<span class="brand-logo-text-dynamic">' . h($settings['logo_text']) . '</span>';
    }
    $class = $imgClass !== '' ? ' class="' . h($imgClass) . '"' : '';
    return '<img src="images/romaric.jpeg" alt="' . h($settings['logo_text']) . '"' . $class . '>';
}

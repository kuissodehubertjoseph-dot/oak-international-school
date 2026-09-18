<?php
/**
 * API journal d'activité — équivalent de GET /api/admin/activity-logs.
 * Réservé au super-admin (403 sinon). Retourne les 500 dernières entrées ;
 * l'historique complet reste conservé en base (table activity_logs).
 */
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/activity.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentification requise.']);
    exit;
}
require_super_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non supportée.']);
    exit;
}

$rows = $pdo->query('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 500')->fetchAll();

$logs = array_map(function ($r) {
    return [
        'id'          => (int) $r['id'],
        'actor_id'    => $r['actor_id'] !== null ? (int) $r['actor_id'] : null,
        'actor_name'  => $r['actor_name'],
        'actor_role'  => $r['actor_role'],
        'action'      => $r['action'],
        'target_type' => $r['target_type'],
        'target_id'   => $r['target_id'],
        'details'     => $r['details'],
        'ip_address'  => $r['ip_address'],
        'created_at'  => $r['created_at'],
        'sensitive'   => activity_is_sensitive($r['action']),
        'icon'        => activity_icon($r['action']),
    ];
}, $rows);

echo json_encode(['logs' => $logs, 'count' => count($logs)], JSON_UNESCAPED_UNICODE);

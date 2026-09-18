<?php
/**
 * API utilisateurs — équivalent de GET /api/admin/users.
 * Le compte super-admin (is_super_admin=1) est filtré pour tout le monde
 * sauf lui-même. Réservé aux admins connectés.
 */
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentification requise.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non supportée.']);
    exit;
}

$viewer = current_admin($pdo);
$rows = visible_admins($pdo, $viewer);

$users = array_map(fn($r) => [
    'id'             => (int) $r['id'],
    'email'          => $r['email'],
    'nom'            => $r['nom'],
    'role'           => $r['role'],
    'statut'         => $r['statut'],
    'is_super_admin' => (bool) $r['is_super_admin'],
    'created_at'     => $r['created_at'],
], $rows);

echo json_encode(['users' => $users], JSON_UNESCAPED_UNICODE);

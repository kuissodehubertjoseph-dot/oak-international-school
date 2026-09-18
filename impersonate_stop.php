<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/activity.php';

if (!empty($_SESSION['impersonator_id'])) {
    $stmt = $pdo->prepare('SELECT id, password_hash, email FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['impersonator_id']]);
    $original = $stmt->fetch();

    if ($original) {
        logActivity($pdo, 'fin_connexion_en_tant_que', 'admin', $_SESSION['admin_id'] ?? null, "Retour au compte Développeur ({$original['email']}).", [
            'id'   => $original['id'],
            'name' => $_SESSION['impersonator_nom'] ?? $original['email'],
            'role' => 'developpeur',
        ]);
        $_SESSION['admin_id'] = $original['id'];
        $_SESSION['pwd_hash'] = $original['password_hash'];
    }
    unset($_SESSION['impersonator_id'], $_SESSION['impersonator_nom']);
}

header('Location: admin_users.php');
exit;

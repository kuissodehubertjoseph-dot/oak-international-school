<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
if (session_id()) {
    revoke_session($pdo, session_id());
}
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;

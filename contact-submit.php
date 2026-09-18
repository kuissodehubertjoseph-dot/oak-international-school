<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/activity.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

// Honeypot anti-spam
if (!empty($_POST['website'])) {
    header('Location: contact.html?sent=1');
    exit;
}

$nom       = trim($_POST['nom'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$sujet     = trim($_POST['sujet'] ?? '');
$message   = trim($_POST['message'] ?? '');

if ($nom === '' || $email === '' || $sujet === '' || strlen($message) < 20 || empty($_POST['rgpd'])) {
    header('Location: contact.html?error=1');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO messages_contact (nom, email, telephone, sujet, message) VALUES (?,?,?,?,?)');
$stmt->execute([$nom, $email, $telephone, $sujet, $message]);

logActivity($pdo, 'message_recu', 'messages_contact', $pdo->lastInsertId(), "Message de {$nom} — {$sujet}", ['id' => null]);

header('Location: contact.html?sent=1');
exit;

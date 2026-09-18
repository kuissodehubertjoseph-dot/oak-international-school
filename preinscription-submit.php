<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/upload.php';
require __DIR__ . '/includes/activity.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: preinscription.html');
    exit;
}

// Honeypot anti-spam
if (!empty($_POST['website'])) {
    header('Location: index.html?sent=1');
    exit;
}

$nomEleve        = trim($_POST['nom_eleve'] ?? '');
$prenomEleve     = trim($_POST['prenom_eleve'] ?? '');
$sexe            = trim($_POST['sexe'] ?? '');
$dateNaissance   = trim($_POST['date_naissance'] ?? '');
$classeSouhaitee = trim($_POST['classe_souhaitee'] ?? '');
$nomParent       = trim($_POST['nom_parent'] ?? '');
$telephoneParent = trim($_POST['telephone_parent'] ?? '');
$emailParent     = trim($_POST['email_parent'] ?? '');

// Formulaire complet vs formulaire rapide (page d'accueil)
$isFullForm = isset($_POST['consentement']);
$source = $isFullForm ? 'formulaire complet' : 'formulaire rapide (accueil)';
$backTo = $isFullForm ? 'preinscription.html' : 'index.html';

if ($nomEleve === '' || $prenomEleve === '' || $classeSouhaitee === '' || $telephoneParent === '') {
    header('Location: ' . $backTo . '?error=1');
    exit;
}

// Document requis selon la classe souhaitée : aucun pour la Maternelle,
// relevé de notes du BEPC pour l'entrée en 2nde, bulletin de passage sinon.
$classesSansDocument = ['Maternelle 1', 'Maternelle 2'];
$classesReleveBepc   = ['2nde A', '2nde B', '2nde C', '2nde D'];

if (in_array($classeSouhaitee, $classesSansDocument, true)) {
    $bulletinLabel = null;
} elseif (in_array($classeSouhaitee, $classesReleveBepc, true)) {
    $bulletinLabel = 'Relevé de notes du BEPC';
} else {
    $bulletinLabel = 'Bulletin de passage';
}

$bulletinPath = null;
try {
    $bulletinPath = handle_document_upload('bulletin');
} catch (RuntimeException $e) {
    header('Location: ' . $backTo . '?error=bulletin');
    exit;
}

if ($bulletinLabel !== null && $isFullForm && $bulletinPath === null) {
    header('Location: ' . $backTo . '?error=bulletin_requis');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO preinscriptions
    (nom_eleve, prenom_eleve, sexe, date_naissance, classe_souhaitee, nom_parent, telephone_parent, email_parent, source, bulletin_path, bulletin_label)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)');
$stmt->execute([$nomEleve, $prenomEleve, $sexe, $dateNaissance, $classeSouhaitee, $nomParent, $telephoneParent, $emailParent, $source, $bulletinPath, $bulletinPath ? $bulletinLabel : null]);

logActivity($pdo, 'inscription', 'preinscriptions', $pdo->lastInsertId(),
    "Pré-inscription de {$prenomEleve} {$nomEleve} — {$classeSouhaitee} ({$source})", ['id' => null]);

header('Location: ' . $backTo . '?sent=1');
exit;

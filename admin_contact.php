<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/publish.php';
require __DIR__ . '/includes/activity.php';

$activePage = 'contact';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $stmt = $pdo->prepare('UPDATE contact_info SET adresse_ligne1=?, adresse_ligne2=?, telephone1=?, telephone2=?, horaires_semaine=?, horaires_samedi=?, email=? WHERE id=1');
        $stmt->execute([
            trim($_POST['adresse_ligne1'] ?? ''),
            trim($_POST['adresse_ligne2'] ?? ''),
            trim($_POST['telephone1'] ?? ''),
            trim($_POST['telephone2'] ?? ''),
            trim($_POST['horaires_semaine'] ?? ''),
            trim($_POST['horaires_samedi'] ?? ''),
            trim($_POST['email'] ?? ''),
        ]);
        publish_contact($pdo);
        logActivity($pdo, 'reglages_modifies', 'contact_info', 1, 'Coordonnées de contact mises à jour.');
        header('Location: admin_contact.php?saved=1');
        exit;
    }

    if ($action === 'publish') {
        publish_contact($pdo);
        $flash = ['type' => 'success', 'text' => 'Publié sur le site public (contact + pied de page de toutes les pages).'];
    }
}

if (isset($_GET['saved'])) { $flash = ['type' => 'success', 'text' => 'Coordonnées enregistrées et publiées sur le site.']; }

$c = $pdo->query('SELECT * FROM contact_info WHERE id = 1')->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Infos de contact | St Romaric</title>
<link rel="icon" type="image/jpeg" href="images/romaric.jpeg">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="assets/admin-theme.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
  <?php require __DIR__ . '/includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1>Infos de contact</h1>
        <div class="subtitle">Publié sur : page Contact et le pied de page de toutes les pages.</div>
      </div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="publish">
        <button type="submit" class="btn-admin btn-admin-publish"><i class="fa-solid fa-cloud-arrow-up"></i> Publier sur le site</button>
      </form>
    </div>

    <?php if ($flash): ?>
      <div class="alert-premium"><i class="fa-solid fa-circle-check"></i> <?= h($flash['text']) ?></div>
    <?php endif; ?>

    <div class="edit-card">
      <div class="edit-head"><h2>Coordonnées de l'établissement</h2></div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <div class="field-row">
          <div class="field"><label>Adresse — ligne 1</label><input type="text" name="adresse_ligne1" value="<?= h($c['adresse_ligne1']) ?>"></div>
          <div class="field"><label>Adresse — ligne 2</label><input type="text" name="adresse_ligne2" value="<?= h($c['adresse_ligne2']) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Téléphone 1</label><input type="tel" name="telephone1" value="<?= h($c['telephone1']) ?>" placeholder="+229 01 95 86 51 20"></div>
          <div class="field"><label>Téléphone 2</label><input type="tel" name="telephone2" value="<?= h($c['telephone2']) ?>" placeholder="+229 01 96 91 17 19"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Horaires — semaine</label><input type="text" name="horaires_semaine" value="<?= h($c['horaires_semaine']) ?>"></div>
          <div class="field"><label>Horaires — samedi</label><input type="text" name="horaires_samedi" value="<?= h($c['horaires_samedi']) ?>"></div>
        </div>
        <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= h($c['email']) ?>"></div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
        </div>
      </form>
    </div>

  </main>
</div>
</body>
</html>

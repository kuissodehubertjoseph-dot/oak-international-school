<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/publish.php';
require __DIR__ . '/includes/upload.php';

$activePage = 'staff';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id    = (int) ($_POST['id'] ?? 0);
        $nom   = trim($_POST['nom'] ?? '');
        $role  = trim($_POST['role'] ?? '');
        $ordre = (int) ($_POST['ordre'] ?? 0);
        $photo = trim($_POST['photo'] ?? '');

        try {
            $uploaded = handle_photo_upload('photo_file', 'staff');
            if ($uploaded) { $photo = $uploaded; }
        } catch (RuntimeException $e) {
            $flash = ['type' => 'error', 'text' => $e->getMessage()];
        }

        if (!$flash && $nom === '') {
            $flash = ['type' => 'error', 'text' => 'Le nom est obligatoire.'];
        }

        if (!$flash) {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE staff SET nom=?, role=?, photo=?, ordre=? WHERE id=?');
                $stmt->execute([$nom, $role, $photo, $ordre, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO staff (nom, role, photo, ordre) VALUES (?,?,?,?)');
                $stmt->execute([$nom, $role, $photo, $ordre]);
            }
            publish_staff($pdo);
            header('Location: admin_staff.php?saved=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM staff WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
        publish_staff($pdo);
        header('Location: admin_staff.php?deleted=1');
        exit;
    }

    if ($action === 'publish') {
        publish_staff($pdo);
        $flash = ['type' => 'success', 'text' => 'Publié sur le site public.'];
    }
}

if (isset($_GET['saved']))   { $flash = ['type' => 'success', 'text' => 'Membre enregistré et publié sur le site.']; }
if (isset($_GET['deleted'])) { $flash = ['type' => 'success', 'text' => 'Membre supprimé et site mis à jour.']; }

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
$isNew = isset($_GET['new']);

$staff = $pdo->query('SELECT * FROM staff ORDER BY ordre ASC, id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Équipe administrative | St Romaric</title>
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
        <h1>Équipe administrative</h1>
        <div class="subtitle">Publié sur : page Galerie.</div>
      </div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="publish">
        <button type="submit" class="btn-admin btn-admin-publish"><i class="fa-solid fa-cloud-arrow-up"></i> Publier sur le site</button>
      </form>
    </div>

    <?php if ($flash): ?>
      <div class="alert-premium" style="<?= $flash['type'] === 'error' ? 'background:#fef2f2;border-color:#fecaca;color:#dc2626' : '' ?>">
        <i class="fa-solid <?= $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <?= h($flash['text']) ?>
      </div>
    <?php endif; ?>

    <?php if ($editing || $isNew): $e = $editing ?: ['id'=>0,'nom'=>'','role'=>'','photo'=>'','ordre'=>count($staff)+1]; ?>
    <div class="edit-card">
      <div class="edit-head">
        <h2><?= $editing ? 'Modifier le membre' : 'Ajouter un membre' ?></h2>
        <a href="admin_staff.php">Annuler</a>
      </div>
      <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
        <div class="field-row">
          <div class="field"><label>Nom complet</label><input type="text" name="nom" value="<?= h($e['nom']) ?>" placeholder="M. / Mme ..." required></div>
          <div class="field"><label>Fonction</label><input type="text" name="role" value="<?= h($e['role']) ?>" placeholder="Directeur fondateur"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Ordre d'affichage</label><input type="number" name="ordre" value="<?= h($e['ordre']) ?>"></div>
          <div class="field"><label>Photo (optionnelle)</label><input type="text" name="photo" value="<?= h($e['photo']) ?>" placeholder="images/staff-xxx.jpg">
            <div class="hint">Laisser vide pour afficher une icône par défaut.</div>
          </div>
        </div>
        <div class="field"><input type="file" name="photo_file" accept="image/*"></div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="admin_staff.php" class="btn-admin btn-admin-outline">Annuler</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Membres (<?= count($staff) ?>)</h2>
        <div class="toolbar-actions">
          <a href="admin_staff.php?new=1" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a>
        </div>
      </div>
      <?php if (empty($staff)): ?>
        <div class="empty-state"><i class="fa-solid fa-user-tie"></i> Aucun membre pour le moment.</div>
      <?php else: ?>
      <table class="premium-table">
        <thead><tr><th></th><th>Nom</th><th>Fonction</th><th>Ordre</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($staff as $s): ?>
          <tr>
            <td><?php if ($s['photo']): ?><img src="<?= h($s['photo']) ?>" class="thumb-sm"><?php else: ?><div class="thumb-placeholder"><i class="fa-solid fa-user"></i></div><?php endif; ?></td>
            <td><?= h($s['nom']) ?></td>
            <td><?= h($s['role']) ?></td>
            <td><?= (int) $s['ordre'] ?></td>
            <td class="row-actions">
              <a href="admin_staff.php?edit=<?= (int) $s['id'] ?>" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer ce membre ?');">
              <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button type="submit" class="btn-admin btn-admin-danger" style="padding:.35rem .7rem"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </main>
</div>
</body>
</html>

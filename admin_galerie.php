<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/publish.php';
require __DIR__ . '/includes/upload.php';

$activePage = 'galerie';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id    = (int) ($_POST['id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $ordre = (int) ($_POST['ordre'] ?? 0);
        $image = trim($_POST['image'] ?? '');

        try {
            $uploaded = handle_photo_upload('image_file', 'galerie');
            if ($uploaded) { $image = $uploaded; }
        } catch (RuntimeException $e) {
            $flash = ['type' => 'error', 'text' => $e->getMessage()];
        }

        if (!$flash && ($titre === '' || $image === '')) {
            $flash = ['type' => 'error', 'text' => 'Le titre et une image sont obligatoires.'];
        }

        if (!$flash) {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE galerie_photos SET titre=?, image=?, ordre=? WHERE id=?');
                $stmt->execute([$titre, $image, $ordre, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO galerie_photos (titre, image, ordre) VALUES (?,?,?)');
                $stmt->execute([$titre, $image, $ordre]);
            }
            publish_gallery($pdo);
            header('Location: admin_galerie.php?saved=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM galerie_photos WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
        publish_gallery($pdo);
        header('Location: admin_galerie.php?deleted=1');
        exit;
    }

    if ($action === 'publish') {
        publish_gallery($pdo);
        $flash = ['type' => 'success', 'text' => 'Publié sur le site public.'];
    }
}

if (isset($_GET['saved']))   { $flash = ['type' => 'success', 'text' => 'Photo enregistrée et publiée sur le site.']; }
if (isset($_GET['deleted'])) { $flash = ['type' => 'success', 'text' => 'Photo supprimée et site mis à jour.']; }

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM galerie_photos WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
$isNew = isset($_GET['new']);

$photos = $pdo->query('SELECT * FROM galerie_photos ORDER BY ordre ASC, id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Galerie photos | OAK International School</title>
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
        <h1>Galerie photos</h1>
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

    <?php if ($editing || $isNew): $e = $editing ?: ['id'=>0,'titre'=>'','image'=>'','ordre'=>count($photos)+1]; ?>
    <div class="edit-card">
      <div class="edit-head">
        <h2><?= $editing ? 'Modifier la photo' : 'Ajouter une photo' ?></h2>
        <a href="admin_galerie.php">Annuler</a>
      </div>
      <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
        <div class="field-row">
          <div class="field"><label>Titre / légende</label><input type="text" name="titre" value="<?= h($e['titre']) ?>" required></div>
          <div class="field"><label>Ordre d'affichage</label><input type="number" name="ordre" value="<?= h($e['ordre']) ?>"></div>
        </div>
        <div class="field"><label>Image</label><input type="text" name="image" value="<?= h($e['image']) ?>" placeholder="images/6-Large-1.jpeg">
          <div class="hint">Chemin d'une image déjà présente dans /images, ou envoyez un nouveau fichier ci-dessous.</div>
        </div>
        <div class="field"><input type="file" name="image_file" accept="image/*"></div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="admin_galerie.php" class="btn-admin btn-admin-outline">Annuler</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Photos (<?= count($photos) ?>)</h2>
        <div class="toolbar-actions">
          <a href="admin_galerie.php?new=1" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a>
        </div>
      </div>
      <?php if (empty($photos)): ?>
        <div class="empty-state"><i class="fa-solid fa-images"></i> Aucune photo pour le moment.</div>
      <?php else: ?>
      <table class="premium-table">
        <thead><tr><th></th><th>Titre</th><th>Fichier</th><th>Ordre</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($photos as $p): ?>
          <tr>
            <td><img src="<?= h($p['image']) ?>" class="thumb-sm"></td>
            <td><?= h($p['titre']) ?></td>
            <td style="color:var(--slate-light);font-size:.78rem"><?= h($p['image']) ?></td>
            <td><?= (int) $p['ordre'] ?></td>
            <td class="row-actions">
              <a href="admin_galerie.php?edit=<?= (int) $p['id'] ?>" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer cette photo ?');">
              <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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

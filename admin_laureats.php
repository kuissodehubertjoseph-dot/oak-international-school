<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/publish.php';
require __DIR__ . '/includes/upload.php';

$activePage = 'laureats';
$flash = null;
$publishResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $nom      = trim($_POST['nom'] ?? '');
        $classe   = trim($_POST['classe'] ?? '');
        $examen   = trim($_POST['examen'] ?? '');
        $annee    = (int) ($_POST['annee'] ?? date('Y'));
        $moyenne  = trim($_POST['moyenne'] ?? '');
        $rang     = trim($_POST['rang'] ?? '');
        $rangDep  = trim($_POST['rang_departemental'] ?? '');
        $serie    = trim($_POST['serie'] ?? '');
        $medaille = trim($_POST['medaille'] ?? '');
        $photo    = trim($_POST['photo'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;

        try {
            $uploaded = handle_photo_upload('photo_file', 'laureat');
            if ($uploaded) { $photo = $uploaded; }
        } catch (RuntimeException $e) {
            $flash = ['type' => 'error', 'text' => $e->getMessage()];
        }

        if (!$flash && $nom === '') {
            $flash = ['type' => 'error', 'text' => 'Le nom est obligatoire.'];
        }

        if (!$flash) {
            if ($featured) {
                $pdo->exec('UPDATE laureats SET featured = 0');
            }
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE laureats SET nom=?, classe=?, examen=?, annee=?, moyenne=?, rang=?, rang_departemental=?, serie=?, medaille=?, photo=?, featured=? WHERE id=?');
                $stmt->execute([$nom, $classe, $examen, $annee, $moyenne, $rang, $rangDep, $serie ?: null, $medaille, $photo, $featured, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO laureats (nom, classe, examen, annee, moyenne, rang, rang_departemental, serie, medaille, photo, featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$nom, $classe, $examen, $annee, $moyenne, $rang, $rangDep, $serie ?: null, $medaille, $photo, $featured]);
            }
            publish_laureats($pdo);
            header('Location: admin_laureats.php?saved=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM laureats WHERE id=?')->execute([$id]);
        publish_laureats($pdo);
        header('Location: admin_laureats.php?deleted=1');
        exit;
    }

    if ($action === 'publish') {
        $publishResult = publish_laureats($pdo);
        $flash = ['type' => 'success', 'text' => 'Publié sur le site public.'];
    }
}

if (isset($_GET['saved']))   { $flash = ['type' => 'success', 'text' => 'Lauréat enregistré et publié sur le site.']; }
if (isset($_GET['deleted'])) { $flash = ['type' => 'success', 'text' => 'Lauréat supprimé et site mis à jour.']; }

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM laureats WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
$isNew = isset($_GET['new']);

$laureats = $pdo->query('SELECT * FROM laureats ORDER BY annee DESC, id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lauréats | Collège Catholique Saint Jean-Baptiste</title>
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
        <h1>Lauréats & Résultats</h1>
        <div class="subtitle">Publié sur : page d'accueil (tableau d'honneur).</div>
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

    <?php if ($editing || $isNew): $e = $editing ?: ['id'=>0,'nom'=>'','classe'=>'','examen'=>'BEPC','annee'=>date('Y'),'moyenne'=>'','rang'=>'','rang_departemental'=>'','serie'=>'','medaille'=>'','photo'=>'','featured'=>0]; ?>
    <div class="edit-card">
      <div class="edit-head">
        <h2><?= $editing ? 'Modifier le lauréat' : 'Ajouter un lauréat' ?></h2>
        <a href="admin_laureats.php">Annuler</a>
      </div>
      <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
        <div class="field-row">
          <div class="field"><label>Nom complet</label><input type="text" name="nom" value="<?= h($e['nom']) ?>" required></div>
          <div class="field"><label>Classe</label><input type="text" name="classe" value="<?= h($e['classe']) ?>" placeholder="ex. 3ème, Tle C"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Examen</label>
            <select name="examen">
              <option value="BEPC" <?= $e['examen']==='BEPC'?'selected':'' ?>>BEPC</option>
              <option value="BAC" <?= $e['examen']==='BAC'?'selected':'' ?>>BAC</option>
            </select>
          </div>
          <div class="field"><label>Année</label><input type="number" name="annee" value="<?= h($e['annee']) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Moyenne</label><input type="text" name="moyenne" value="<?= h($e['moyenne']) ?>" placeholder="18.75"></div>
          <div class="field"><label>Médaille (emoji)</label><input type="text" name="medaille" value="<?= h($e['medaille']) ?>" placeholder="🥇 🥈 🥉"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Rang national</label><input type="text" name="rang" value="<?= h($e['rang']) ?>" placeholder="8ème"></div>
          <div class="field"><label>Rang départemental</label><input type="text" name="rang_departemental" value="<?= h($e['rang_departemental']) ?>" placeholder="1er"></div>
        </div>
        <div class="field">
          <label>Série (optionnel — affiché sur la fiche vedette de la page Résultats)</label>
          <input type="text" name="serie" value="<?= h($e['serie']) ?>" placeholder="Série A (Littéraire)">
        </div>
        <div class="field">
          <label>Photo</label>
          <input type="text" name="photo" value="<?= h($e['photo']) ?>" placeholder="images/47-Large.jpeg">
          <div class="hint">Chemin d'une image déjà présente dans /images, ou envoyez un nouveau fichier ci-dessous.</div>
        </div>
        <div class="field"><input type="file" name="photo_file" accept="image/*"></div>
        <div class="field" style="display:flex;align-items:center;gap:.5rem">
          <input type="checkbox" id="featured" name="featured" value="1" <?= (int) $e['featured'] === 1 ? 'checked' : '' ?> style="width:16px;height:16px">
          <label for="featured" style="margin:0">Mettre en avant comme lauréat vedette sur la page Résultats</label>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="admin_laureats.php" class="btn-admin btn-admin-outline">Annuler</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Tous les lauréats (<?= count($laureats) ?>)</h2>
        <div class="toolbar-actions">
          <a href="admin_laureats.php?new=1" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a>
        </div>
      </div>

      <?php if (empty($laureats)): ?>
        <div class="empty-state"><i class="fa-solid fa-trophy"></i> Aucun lauréat pour le moment.</div>
      <?php else: ?>
      <table class="premium-table">
        <thead><tr><th></th><th>Nom</th><th>Classe</th><th>Examen</th><th>Moyenne</th><th>Rang</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($laureats as $l): ?>
          <tr>
            <td><?php if ($l['photo']): ?><img src="<?= h($l['photo']) ?>" class="thumb-sm"><?php else: ?><div class="thumb-placeholder"><i class="fa-solid fa-user"></i></div><?php endif; ?></td>
            <td><span class="cell-person"><span class="name"><?= h($l['nom']) ?></span></span> <?php if ((int) $l['featured'] === 1): ?><span class="badge-status badge-nouveau" style="margin-left:.4rem"><i class="fa-solid fa-star"></i> Vedette</span><?php endif; ?></td>
            <td><?= h($l['classe']) ?></td>
            <td><?= h($l['examen']) ?> <?= h($l['annee']) ?></td>
            <td><?= h($l['moyenne']) ?></td>
            <td><?= h($l['rang']) ?></td>
            <td class="row-actions">
              <a href="admin_laureats.php?edit=<?= (int) $l['id'] ?>" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer ce lauréat ?');">
              <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
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

<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/publish.php';

$activePage = 'stats';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = (int) ($_POST['id'] ?? 0);
        $cle     = trim($_POST['cle'] ?? '');
        $valeur  = trim($_POST['valeur'] ?? '');
        $suffixe = trim($_POST['suffixe'] ?? '');
        $label   = trim($_POST['label'] ?? '');
        $ordre   = (int) ($_POST['ordre'] ?? 0);

        if ($cle === '' || $label === '') {
            $flash = ['type' => 'error', 'text' => 'La clé et le libellé sont obligatoires.'];
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE stats SET cle=?, valeur=?, suffixe=?, label=?, ordre=? WHERE id=?');
                $stmt->execute([$cle, $valeur, $suffixe, $label, $ordre, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO stats (cle, valeur, suffixe, label, ordre) VALUES (?,?,?,?,?)');
                $stmt->execute([$cle, $valeur, $suffixe, $label, $ordre]);
            }
            publish_stats($pdo);
            header('Location: admin_stats.php?saved=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM stats WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
        publish_stats($pdo);
        header('Location: admin_stats.php?deleted=1');
        exit;
    }

    if ($action === 'publish') {
        publish_stats($pdo);
        $flash = ['type' => 'success', 'text' => 'Publié sur le site public.'];
    }
}

if (isset($_GET['saved']))   { $flash = ['type' => 'success', 'text' => 'Chiffre enregistré et publié sur le site.']; }
if (isset($_GET['deleted'])) { $flash = ['type' => 'success', 'text' => 'Chiffre supprimé et site mis à jour.']; }

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM stats WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
$isNew = isset($_GET['new']);

$stats = $pdo->query('SELECT * FROM stats ORDER BY ordre ASC, id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chiffres clés | Collège Catholique Saint Jean-Baptiste</title>
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
        <h1>Chiffres clés</h1>
        <div class="subtitle">Publié sur : Accueil et À Propos (bande de statistiques).</div>
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

    <?php if ($editing || $isNew): $e = $editing ?: ['id'=>0,'cle'=>'','valeur'=>'','suffixe'=>'','label'=>'','ordre'=>count($stats)+1]; ?>
    <div class="edit-card">
      <div class="edit-head">
        <h2><?= $editing ? 'Modifier le chiffre' : 'Ajouter un chiffre' ?></h2>
        <a href="admin_stats.php">Annuler</a>
      </div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
        <div class="field-row">
          <div class="field"><label>Clé interne</label><input type="text" name="cle" value="<?= h($e['cle']) ?>" placeholder="eleves" required></div>
          <div class="field"><label>Ordre d'affichage</label><input type="number" name="ordre" value="<?= h($e['ordre']) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Valeur</label><input type="text" name="valeur" value="<?= h($e['valeur']) ?>" placeholder="800"></div>
          <div class="field"><label>Suffixe</label><input type="text" name="suffixe" value="<?= h($e['suffixe']) ?>" placeholder="+ ou %"></div>
        </div>
        <div class="field"><label>Libellé affiché</label><input type="text" name="label" value="<?= h($e['label']) ?>" placeholder="Élèves inscrits" required></div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="admin_stats.php" class="btn-admin btn-admin-outline">Annuler</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Chiffres (<?= count($stats) ?>)</h2>
        <div class="toolbar-actions">
          <a href="admin_stats.php?new=1" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a>
        </div>
      </div>
      <table class="premium-table">
        <thead><tr><th>Valeur</th><th>Libellé</th><th>Ordre</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($stats as $s): ?>
          <tr>
            <td><strong style="color:var(--green)"><?= h($s['valeur']) ?><?= h($s['suffixe']) ?></strong></td>
            <td><?= h($s['label']) ?></td>
            <td><?= (int) $s['ordre'] ?></td>
            <td class="row-actions">
              <a href="admin_stats.php?edit=<?= (int) $s['id'] ?>" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer ce chiffre ?');">
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
    </div>

  </main>
</div>
</body>
</html>

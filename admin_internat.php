<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/publish.php';

$activePage = 'internat';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_item') {
        $id      = (int) ($_POST['id'] ?? 0);
        $section = $_POST['section'] === 'inclus' ? 'inclus' : 'comprend';
        $icone   = trim($_POST['icone'] ?? '') ?: 'fa-solid fa-check';
        $titre   = trim($_POST['titre'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $ordre   = (int) ($_POST['ordre'] ?? 0);

        if ($titre === '') {
            $flash = ['type' => 'error', 'text' => 'Le titre est obligatoire.'];
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE internat_items SET icone=?, titre=?, description=?, ordre=? WHERE id=?');
                $stmt->execute([$icone, $titre, $desc, $ordre, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO internat_items (section, icone, titre, description, ordre) VALUES (?,?,?,?,?)');
                $stmt->execute([$section, $icone, $titre, $desc, $ordre]);
            }
            publish_internat($pdo);
            header('Location: admin_internat.php?saved=1#' . $section);
            exit;
        }
    }

    if ($action === 'delete_item') {
        $pdo->prepare('DELETE FROM internat_items WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
        publish_internat($pdo);
        header('Location: admin_internat.php?deleted=1');
        exit;
    }

    if ($action === 'save_horaire') {
        $id    = (int) ($_POST['id'] ?? 0);
        $heure = trim($_POST['heure'] ?? '');
        $icone = trim($_POST['icone'] ?? '') ?: 'fa-solid fa-clock';
        $desc  = trim($_POST['description'] ?? '');
        $ordre = (int) ($_POST['ordre'] ?? 0);

        if ($heure === '') {
            $flash = ['type' => 'error', 'text' => "L'heure est obligatoire."];
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE internat_horaires SET heure=?, icone=?, description=?, ordre=? WHERE id=?');
                $stmt->execute([$heure, $icone, $desc, $ordre, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO internat_horaires (heure, icone, description, ordre) VALUES (?,?,?,?)');
                $stmt->execute([$heure, $icone, $desc, $ordre]);
            }
            publish_internat($pdo);
            header('Location: admin_internat.php?saved=1#horaires');
            exit;
        }
    }

    if ($action === 'delete_horaire') {
        $pdo->prepare('DELETE FROM internat_horaires WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
        publish_internat($pdo);
        header('Location: admin_internat.php?deleted=1#horaires');
        exit;
    }

    if ($action === 'publish') {
        publish_internat($pdo);
        $flash = ['type' => 'success', 'text' => 'Publié sur le site public.'];
    }
}

if (isset($_GET['saved']))   { $flash = ['type' => 'success', 'text' => 'Enregistré et publié sur le site.']; }
if (isset($_GET['deleted'])) { $flash = ['type' => 'success', 'text' => 'Supprimé et site mis à jour.']; }

$editItem = null;
if (isset($_GET['edit_item'])) {
    $stmt = $pdo->prepare('SELECT * FROM internat_items WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_item']]);
    $editItem = $stmt->fetch();
}
$newSection = $_GET['new_item'] ?? null;

$editHoraire = null;
if (isset($_GET['edit_horaire'])) {
    $stmt = $pdo->prepare('SELECT * FROM internat_horaires WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_horaire']]);
    $editHoraire = $stmt->fetch();
}
$newHoraire = isset($_GET['new_horaire']);

$comprend = $pdo->query("SELECT * FROM internat_items WHERE section = 'comprend' ORDER BY ordre ASC, id ASC")->fetchAll();
$inclus   = $pdo->query("SELECT * FROM internat_items WHERE section = 'inclus' ORDER BY ordre ASC, id ASC")->fetchAll();
$horaires = $pdo->query('SELECT * FROM internat_horaires ORDER BY ordre ASC, id ASC')->fetchAll();

function item_form($e, $section, $title) {
    global $editItem;
    echo '<div class="edit-card">
      <div class="edit-head"><h2>' . h($title) . '</h2><a href="admin_internat.php">Annuler</a></div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_item">
        <input type="hidden" name="section" value="' . h($section) . '">
        <input type="hidden" name="id" value="' . (int) $e['id'] . '">
        <div class="field-row">
          <div class="field"><label>Titre</label><input type="text" name="titre" value="' . h($e['titre']) . '" required></div>
          <div class="field"><label>Icône (Font Awesome)</label><input type="text" name="icone" value="' . h($e['icone']) . '"></div>
        </div>
        <div class="field"><label>Description</label><input type="text" name="description" value="' . h($e['description']) . '"></div>
        <div class="field"><label>Ordre</label><input type="number" name="ordre" value="' . h($e['ordre']) . '" style="max-width:140px"></div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="admin_internat.php" class="btn-admin btn-admin-outline">Annuler</a>
        </div>
      </form>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Internat | Collège Catholique Saint Jean-Baptiste</title>
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
        <h1>Internat</h1>
        <div class="subtitle">Publié sur : page Internat (services, inclus dans les frais, emploi du temps).</div>
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

    <?php if ($editItem): item_form($editItem, $editItem['section'], 'Modifier l\'élément'); endif; ?>
    <?php if ($newSection): item_form(['id'=>0,'titre'=>'','icone'=>'fa-solid fa-check','description'=>'','ordre'=>0], $newSection, 'Ajouter un élément'); endif; ?>

    <?php if ($editHoraire || $newHoraire): $h = $editHoraire ?: ['id'=>0,'heure'=>'','icone'=>'fa-solid fa-clock','description'=>'','ordre'=>count($horaires)+1]; ?>
    <div class="edit-card">
      <div class="edit-head"><h2><?= $editHoraire ? 'Modifier le créneau' : 'Ajouter un créneau' ?></h2><a href="admin_internat.php">Annuler</a></div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_horaire">
        <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
        <div class="field-row">
          <div class="field"><label>Heure</label><input type="text" name="heure" value="<?= h($h['heure']) ?>" placeholder="7h30 – 12h30" required></div>
          <div class="field"><label>Icône</label><input type="text" name="icone" value="<?= h($h['icone']) ?>"></div>
        </div>
        <div class="field"><label>Description</label><input type="text" name="description" value="<?= h($h['description']) ?>"></div>
        <div class="field"><label>Ordre</label><input type="number" name="ordre" value="<?= h($h['ordre']) ?>" style="max-width:140px"></div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="admin_internat.php" class="btn-admin btn-admin-outline">Annuler</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="panel" id="comprend">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Ce que comprend l'internat (<?= count($comprend) ?>)</h2>
        <div class="toolbar-actions"><a href="admin_internat.php?new_item=comprend#comprend" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a></div>
      </div>
      <table class="premium-table">
        <thead><tr><th>Ordre</th><th>Titre</th><th>Description</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($comprend as $i): ?>
          <tr>
            <td><?= (int) $i['ordre'] ?></td>
            <td><i class="<?= h($i['icone']) ?>" style="color:var(--green-mid);margin-right:.5rem"></i><?= h($i['titre']) ?></td>
            <td style="color:var(--slate-light);font-size:.8rem"><?= h($i['description']) ?></td>
            <td class="row-actions">
              <a href="admin_internat.php?edit_item=<?= (int) $i['id'] ?>#comprend" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer ?');">
              <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                <button type="submit" class="btn-admin btn-admin-danger" style="padding:.35rem .7rem"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel" id="inclus">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Inclus dans les frais (<?= count($inclus) ?>)</h2>
        <div class="toolbar-actions"><a href="admin_internat.php?new_item=inclus#inclus" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a></div>
      </div>
      <table class="premium-table">
        <thead><tr><th>Ordre</th><th>Titre</th><th>Description</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($inclus as $i): ?>
          <tr>
            <td><?= (int) $i['ordre'] ?></td>
            <td><i class="<?= h($i['icone']) ?>" style="color:var(--green-mid);margin-right:.5rem"></i><?= h($i['titre']) ?></td>
            <td style="color:var(--slate-light);font-size:.8rem"><?= h($i['description']) ?></td>
            <td class="row-actions">
              <a href="admin_internat.php?edit_item=<?= (int) $i['id'] ?>#inclus" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer ?');">
              <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                <button type="submit" class="btn-admin btn-admin-danger" style="padding:.35rem .7rem"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel" id="horaires">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Emploi du temps type (<?= count($horaires) ?>)</h2>
        <div class="toolbar-actions"><a href="admin_internat.php?new_horaire=1#horaires" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a></div>
      </div>
      <table class="premium-table">
        <thead><tr><th>Ordre</th><th>Heure</th><th>Description</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($horaires as $h): ?>
          <tr>
            <td><?= (int) $h['ordre'] ?></td>
            <td><i class="<?= h($h['icone']) ?>" style="color:var(--green-mid);margin-right:.5rem"></i><?= h($h['heure']) ?></td>
            <td style="color:var(--slate-light);font-size:.8rem"><?= h($h['description']) ?></td>
            <td class="row-actions">
              <a href="admin_internat.php?edit_horaire=<?= (int) $h['id'] ?>#horaires" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer ?');">
              <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_horaire">
                <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
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

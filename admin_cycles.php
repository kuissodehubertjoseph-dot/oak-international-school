<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/publish.php';
require __DIR__ . '/includes/activity.php';

$activePage = 'cycles';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = (int) ($_POST['id'] ?? 0);
        $nom     = trim($_POST['nom'] ?? '');
        $age     = trim($_POST['age'] ?? '');
        $icone   = trim($_POST['icone'] ?? '') ?: 'fa-solid fa-star';
        $badge   = trim($_POST['badge_serie'] ?? '');
        $resume  = trim($_POST['resume'] ?? '');
        $ordre   = (int) ($_POST['ordre'] ?? 0);
        $matieres = trim(str_replace("\r\n", "\n", $_POST['matieres'] ?? ''));

        if ($nom === '' || $resume === '') {
            $flash = ['type' => 'error', 'text' => 'Le nom et le résumé sont obligatoires.'];
        } else {
            $badgeVal = $badge === '' ? null : $badge;
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE cycles_niveaux SET nom=?, age=?, icone=?, badge_serie=?, resume=?, matieres=?, ordre=? WHERE id=?');
                $stmt->execute([$nom, $age, $icone, $badgeVal, $resume, $matieres, $ordre, $id]);
                logActivity($pdo, 'categorie_modifiee', 'cycles_niveaux', $id, "Niveau modifié : {$nom}");
            } else {
                $stmt = $pdo->prepare('INSERT INTO cycles_niveaux (nom, age, icone, badge_serie, resume, matieres, ordre) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$nom, $age, $icone, $badgeVal, $resume, $matieres, $ordre]);
                logActivity($pdo, 'categorie_creee', 'cycles_niveaux', $pdo->lastInsertId(), "Niveau créé : {$nom}");
            }
            publish_cycles($pdo);
            header('Location: admin_cycles.php?saved=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM cycles_niveaux WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
        publish_cycles($pdo);
        header('Location: admin_cycles.php?deleted=1');
        exit;
    }

    if ($action === 'publish') {
        publish_cycles($pdo);
        $flash = ['type' => 'success', 'text' => 'Publié sur le site public.'];
    }
}

if (isset($_GET['saved']))   { $flash = ['type' => 'success', 'text' => 'Niveau enregistré et publié sur le site.']; }
if (isset($_GET['deleted'])) { $flash = ['type' => 'success', 'text' => 'Niveau supprimé et site mis à jour.']; }

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM cycles_niveaux WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
$isNew = isset($_GET['new']);

$niveaux = $pdo->query('SELECT * FROM cycles_niveaux ORDER BY ordre ASC, id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cycles & matières | St Romaric</title>
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
        <h1>Cycles & matières</h1>
        <div class="subtitle">Publié sur : page Cycles. Ordre 1–8 = École Primaire, 9 et plus = Collège/Lycée.</div>
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

    <?php if ($editing || $isNew): $e = $editing ?: ['id'=>0,'nom'=>'','age'=>'','icone'=>'fa-solid fa-star','badge_serie'=>'','resume'=>'','matieres'=>'','ordre'=>count($niveaux)+1]; ?>
    <div class="edit-card">
      <div class="edit-head">
        <h2><?= $editing ? 'Modifier le niveau' : 'Ajouter un niveau' ?></h2>
        <a href="admin_cycles.php">Annuler</a>
      </div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
        <div class="field-row">
          <div class="field"><label>Nom du niveau</label><input type="text" name="nom" value="<?= h($e['nom']) ?>" placeholder="ex. CM2, 2nde A" required></div>
          <div class="field"><label>Âge</label><input type="text" name="age" value="<?= h($e['age']) ?>" placeholder="11 ans"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Icône (classe Font Awesome)</label><input type="text" name="icone" value="<?= h($e['icone']) ?>" placeholder="fa-solid fa-star"></div>
          <div class="field"><label>Badge série (optionnel)</label>
            <select name="badge_serie">
              <option value="" <?= empty($e['badge_serie'])?'selected':'' ?>>Aucun</option>
              <?php foreach (['Série A','Série B','Série C','Série D'] as $s): ?>
              <option value="<?= h($s) ?>" <?= ($e['badge_serie'] ?? '')===$s?'selected':'' ?>><?= h($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field"><label>Résumé (affiché sous le titre)</label><input type="text" name="resume" value="<?= h($e['resume']) ?>" required></div>
        <div class="field">
          <label>Matières enseignées</label>
          <textarea name="matieres" rows="8" placeholder="Une matière par ligne"><?= h($e['matieres']) ?></textarea>
          <div class="hint">Une matière par ligne — chaque ligne devient une étiquette sur la fiche.</div>
        </div>
        <div class="field"><label>Ordre d'affichage</label><input type="number" name="ordre" value="<?= h($e['ordre']) ?>" style="max-width:140px"></div>
        <div class="form-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="admin_cycles.php" class="btn-admin btn-admin-outline">Annuler</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="toolbar">
        <h2 style="font-family:var(--font-display);font-size:1.08rem;color:var(--green);margin:0">Niveaux (<?= count($niveaux) ?>)</h2>
        <div class="toolbar-actions">
          <a href="admin_cycles.php?new=1" class="btn-admin btn-admin-primary"><i class="fa-solid fa-plus"></i> Ajouter</a>
        </div>
      </div>
      <table class="premium-table">
        <thead><tr><th>Ordre</th><th>Niveau</th><th>Série</th><th>Matières</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($niveaux as $n): ?>
          <tr>
            <td><?= (int) $n['ordre'] ?></td>
            <td><strong style="color:var(--green)"><?= h($n['nom']) ?></strong> <span style="color:var(--slate-light);font-size:.78rem">— <?= h($n['age']) ?></span></td>
            <td><?= h($n['badge_serie'] ?: '—') ?></td>
            <td style="color:var(--slate-light);font-size:.78rem"><?= count(array_filter(explode("\n", $n['matieres']))) ?> matières</td>
            <td class="row-actions">
              <a href="admin_cycles.php?edit=<?= (int) $n['id'] ?>" class="btn-mini"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" onsubmit="return confirm('Supprimer ce niveau ?');">
              <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
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

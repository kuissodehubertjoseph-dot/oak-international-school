<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require_permission($pdo, 'preinscriptions');

$admin = current_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = (int) $_POST['id'];
    $newStatut = $_POST['action'] === 'traiter' ? 'traite' : 'nouveau';
    $stmt = $pdo->prepare('UPDATE preinscriptions SET statut = ? WHERE id = ?');
    $stmt->execute([$newStatut, $id]);
    header('Location: admin_preinscriptions.php');
    exit;
}

$preinscriptions = $pdo->query('SELECT * FROM preinscriptions ORDER BY created_at DESC')->fetchAll();
$nouvellesPreinscriptions = (int) $pdo->query("SELECT COUNT(*) FROM preinscriptions WHERE statut = 'nouveau'")->fetchColumn();
$nouveauxMessages = (int) $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE statut = 'nouveau'")->fetchColumn();

function initials($a, $b = '') {
    $s = trim(mb_substr($a, 0, 1) . mb_substr($b, 0, 1));
    return mb_strtoupper($s ?: '?');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pré-inscriptions | St Romaric</title>
<link rel="icon" type="image/jpeg" href="images/romaric.jpeg">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="assets/admin-theme.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-shell">

  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <img src="images/romaric.jpeg" alt="Logo">
      <div><div class="name">St Romaric</div><div class="tag">Administration</div></div>
    </div>

    <div class="sidebar-section-label">Principal</div>
    <nav class="admin-nav">
      <a href="admin.php"><span class="ic"><i class="fa-solid fa-gauge"></i></span> Tableau de bord</a>
      <a href="admin_preinscriptions.php" class="active">
        <span class="ic"><i class="fa-solid fa-file-pen"></i></span> Pré-inscriptions
        <?php if ($nouvellesPreinscriptions > 0): ?><span class="badge-count"><?= $nouvellesPreinscriptions ?></span><?php endif; ?>
      </a>
      <a href="admin_messages.php">
        <span class="ic"><i class="fa-solid fa-envelope"></i></span> Messages contact
        <?php if ($nouveauxMessages > 0): ?><span class="badge-count"><?= $nouveauxMessages ?></span><?php endif; ?>
      </a>
    </nav>

    <div class="sidebar-section-label">Contenu du site</div>
    <nav class="admin-nav">
      <a href="admin_laureats.php"><span class="ic"><i class="fa-solid fa-trophy"></i></span> Lauréats & Résultats</a>
      <a href="admin_staff.php"><span class="ic"><i class="fa-solid fa-user-tie"></i></span> Équipe administrative</a>
      <a href="admin_galerie.php"><span class="ic"><i class="fa-solid fa-images"></i></span> Galerie photos</a>
      <a href="admin_stats.php"><span class="ic"><i class="fa-solid fa-chart-simple"></i></span> Chiffres clés</a>
      <a href="admin_contact.php"><span class="ic"><i class="fa-solid fa-address-card"></i></span> Infos de contact</a>
      <a href="admin_cycles.php"><span class="ic"><i class="fa-solid fa-layer-group"></i></span> Cycles & matières</a>
      <a href="admin_internat.php"><span class="ic"><i class="fa-solid fa-bed"></i></span> Internat</a>
    </nav>

    <div class="sidebar-section-label">Site</div>
    <nav class="admin-nav">
      <a href="index.html" target="_blank"><span class="ic"><i class="fa-solid fa-arrow-up-right-from-square"></i></span> Voir le site public</a>
    </nav>

    <div class="sidebar-footer">
      <hr class="sidebar-divider">
      <div class="sidebar-user">
        <div class="avatar"><?= initials($admin['email']) ?></div>
        <div class="who">
          <span class="email"><?= htmlspecialchars($admin['email']) ?></span>
          <span class="role">Administrateur</span>
        </div>
        <a href="logout.php" class="logout" title="Déconnexion"><i class="fa-solid fa-right-from-bracket"></i></a>
      </div>
    </div>
  </aside>

  <main class="admin-main">

    <div class="admin-topbar">
      <div>
        <h1>Pré-inscriptions</h1>
        <div class="subtitle"><strong><?= count($preinscriptions) ?></strong> demande(s) au total</div>
      </div>
      <span class="chip"><i class="fa-solid fa-hourglass-half"></i> <?= $nouvellesPreinscriptions ?> en attente</span>
    </div>

    <div class="panel">
      <?php if (empty($preinscriptions)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-inbox"></i>
          Aucune demande de pré-inscription pour le moment.<br>
          Remplis le formulaire sur <a href="index.html">la page d'accueil</a> ou <a href="preinscription.html">la page de pré-inscription</a> pour tester.
        </div>
      <?php else: ?>
      <div style="overflow-x:auto">
      <table class="premium-table">
        <thead>
          <tr>
            <th>Élève</th><th>Classe</th><th>Parent</th><th>Téléphone</th><th>Email</th><th>Document</th><th>Source</th><th>Reçu le</th><th>Statut</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($preinscriptions as $p): ?>
          <tr>
            <td>
              <div class="cell-person">
                <div class="cell-avatar"><?= initials($p['prenom_eleve'], $p['nom_eleve']) ?></div>
                <div>
                  <div class="name"><?= htmlspecialchars($p['prenom_eleve'] . ' ' . $p['nom_eleve']) ?></div>
                  <div class="sub"><?= htmlspecialchars($p['sexe'] ?: '—') ?></div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($p['classe_souhaitee']) ?></td>
            <td><?= htmlspecialchars($p['nom_parent'] ?: '—') ?></td>
            <td><?= htmlspecialchars($p['telephone_parent']) ?></td>
            <td><?= htmlspecialchars($p['email_parent'] ?: '—') ?></td>
            <td>
              <?php if (!empty($p['bulletin_path'])): ?>
                <a href="download_bulletin.php?id=<?= (int) $p['id'] ?>" target="_blank" class="btn-mini">
                  <i class="fa-solid fa-file-arrow-down"></i> <?= htmlspecialchars($p['bulletin_label'] ?: 'Document') ?>
                </a>
              <?php else: ?>
                <span style="color:var(--slate-light);font-size:.78rem">—</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['source']) ?></td>
            <td><?= htmlspecialchars($p['created_at']) ?></td>
            <td><span class="badge-status <?= $p['statut'] === 'nouveau' ? 'badge-nouveau' : 'badge-traite' ?>"><?= $p['statut'] === 'nouveau' ? 'Nouveau' : 'Traité' ?></span></td>
            <td>
              <form method="POST" style="display:inline">
              <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="<?= $p['statut'] === 'nouveau' ? 'traiter' : 'reouvrir' ?>">
                <button type="submit" class="btn-mini"><?= $p['statut'] === 'nouveau' ? 'Marquer traité' : 'Rouvrir' ?></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>

</body>
</html>

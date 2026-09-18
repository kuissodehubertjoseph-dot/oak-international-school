<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';

$activePage = 'dashboard';
$admin = current_admin($pdo);

$canSeePreinscriptions = has_permission($admin, 'preinscriptions');
$canSeeMessages        = has_permission($admin, 'messages');

$totalPreinscriptions = $nouvellesPreinscriptions = $totalMessages = $nouveauxMessages = 0;
$dernieresPreinscriptions = $derniersMessages = [];

if ($canSeePreinscriptions) {
    $totalPreinscriptions     = (int) $pdo->query("SELECT COUNT(*) FROM preinscriptions")->fetchColumn();
    $nouvellesPreinscriptions = (int) $pdo->query("SELECT COUNT(*) FROM preinscriptions WHERE statut = 'nouveau'")->fetchColumn();
    $dernieresPreinscriptions = $pdo->query("SELECT * FROM preinscriptions ORDER BY created_at DESC LIMIT 5")->fetchAll();
}
if ($canSeeMessages) {
    $totalMessages    = (int) $pdo->query("SELECT COUNT(*) FROM messages_contact")->fetchColumn();
    $nouveauxMessages = (int) $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE statut = 'nouveau'")->fetchColumn();
    $derniersMessages = $pdo->query("SELECT * FROM messages_contact ORDER BY created_at DESC LIMIT 5")->fetchAll();
}

function initials($a, $b = '') {
    $s = trim(mb_substr($a, 0, 1) . mb_substr($b, 0, 1));
    return mb_strtoupper($s ?: '?');
}

$jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$mois  = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
$now   = new DateTime();
$dateLabel = $jours[(int)$now->format('w')] . ' ' . (int)$now->format('j') . ' ' . $mois[(int)$now->format('n')] . ' ' . $now->format('Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de bord | St Romaric</title>
<link rel="icon" type="image/jpeg" href="images/romaric.jpeg">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="assets/admin-theme.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-shell">

  <?php require __DIR__ . '/includes/admin_sidebar.php'; ?>

  <!-- MAIN -->
  <main class="admin-main">

    <div class="admin-topbar">
      <div>
        <h1>Tableau de bord</h1>
        <div class="subtitle">Bienvenue, <strong><?= htmlspecialchars($admin['email']) ?></strong> — voici l'activité de l'établissement</div>
      </div>
      <span class="chip"><i class="fa-regular fa-calendar"></i> <?= $dateLabel ?></span>
    </div>

    <?php if (isset($_GET['permission_denied'])): ?>
    <div class="alert-premium" style="background:#fef2f2;border-color:#fecaca;color:#dc2626">
      <i class="fa-solid fa-lock"></i>
      Accès refusé — vous n'avez pas la permission de consulter cette donnée. Un Développeur ou Directeur peut vous l'accorder dans "Utilisateurs & Rôles".
    </div>
    <?php endif; ?>

    <div class="alert-premium">
      <i class="fa-solid fa-circle-check"></i>
      Backend connecté à une vraie base de données locale — les formulaires du site enregistrent réellement leurs données ici.
    </div>

    <?php if (!$canSeePreinscriptions && !$canSeeMessages): ?>
    <div class="panel" style="text-align:center;padding:2.5rem;color:var(--slate-light)">
      <i class="fa-solid fa-shield-halved" style="font-size:1.6rem;color:var(--green-mid);margin-bottom:.6rem;display:block"></i>
      Votre compte n'a pas accès aux données métier sensibles (pré-inscriptions, messages).<br>
      Un Développeur ou Directeur peut vous accorder cet accès dans "Utilisateurs & Rôles".
    </div>
    <?php else: ?>

    <div class="stat-grid">
      <?php if ($canSeePreinscriptions): ?>
      <div class="stat-card">
        <div class="icon" style="background:linear-gradient(135deg,var(--green-mid),var(--green))"><i class="fa-solid fa-file-pen"></i></div>
        <div class="num"><?= $totalPreinscriptions ?></div>
        <div class="label">Pré-inscriptions reçues</div>
      </div>
      <div class="stat-card">
        <div class="icon" style="background:linear-gradient(135deg,var(--accent),var(--accent-dark))"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="num"><?= $nouvellesPreinscriptions ?></div>
        <div class="label">En attente de traitement</div>
      </div>
      <?php endif; ?>
      <?php if ($canSeeMessages): ?>
      <div class="stat-card" style="--stat-glow:rgba(37,99,235,.12); --stat-shadow:rgba(37,99,235,.4)">
        <div class="icon" style="background:linear-gradient(135deg,#3b82f6,#2563eb)"><i class="fa-solid fa-envelope"></i></div>
        <div class="num"><?= $totalMessages ?></div>
        <div class="label">Messages reçus</div>
      </div>
      <div class="stat-card" style="--stat-glow:rgba(220,38,38,.1); --stat-shadow:rgba(220,38,38,.35)">
        <div class="icon" style="background:linear-gradient(135deg,var(--accent),var(--accent-dark))"><i class="fa-solid fa-envelope-open-text"></i></div>
        <div class="num"><?= $nouveauxMessages ?></div>
        <div class="label">Messages non traités</div>
      </div>
      <?php endif; ?>
    </div>

    <div class="grid-2">
      <?php if ($canSeePreinscriptions): ?>
      <div class="panel">
        <div class="panel-head">
          <h2>Dernières pré-inscriptions</h2>
          <a href="admin_preinscriptions.php" class="panel-link">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <?php if (empty($dernieresPreinscriptions)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            Aucune pré-inscription pour le moment.<br>Teste le formulaire sur le site public.
          </div>
        <?php else: ?>
        <table class="premium-table">
          <thead><tr><th>Élève</th><th>Classe</th><th>Téléphone</th><th>Statut</th></tr></thead>
          <tbody>
            <?php foreach ($dernieresPreinscriptions as $p): ?>
            <tr>
              <td>
                <div class="cell-person">
                  <div class="cell-avatar"><?= initials($p['prenom_eleve'], $p['nom_eleve']) ?></div>
                  <div class="name"><?= htmlspecialchars($p['prenom_eleve'] . ' ' . $p['nom_eleve']) ?></div>
                </div>
              </td>
              <td><?= htmlspecialchars($p['classe_souhaitee']) ?></td>
              <td><?= htmlspecialchars($p['telephone_parent']) ?></td>
              <td><span class="badge-status <?= $p['statut'] === 'nouveau' ? 'badge-nouveau' : 'badge-traite' ?>"><?= $p['statut'] === 'nouveau' ? 'Nouveau' : 'Traité' ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($canSeeMessages): ?>
      <div class="panel">
        <div class="panel-head">
          <h2>Derniers messages</h2>
          <a href="admin_messages.php" class="panel-link">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <?php if (empty($derniersMessages)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-comment-slash"></i>
            Aucun message pour le moment.
          </div>
        <?php else: ?>
          <?php foreach ($derniersMessages as $m): ?>
          <div style="display:flex;gap:.7rem;align-items:flex-start;padding:.7rem 0;border-bottom:1px solid #f1f3f2">
            <div class="cell-avatar" style="flex-shrink:0"><?= initials($m['nom']) ?></div>
            <div style="min-width:0">
              <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($m['nom']) ?></div>
              <div style="font-size:.76rem;color:var(--text-faint);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($m['sujet']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

</body>
</html>

<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require_permission($pdo, 'messages');
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/activity.php';

$activePage = 'messages';
$admin = current_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'repondre') {
    $id = (int) ($_POST['id'] ?? 0);
    $reponse = trim($_POST['reponse'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM messages_contact WHERE id = ?');
    $stmt->execute([$id]);
    $m = $stmt->fetch();

    if ($m && $reponse !== '') {
        $pdo->prepare("UPDATE messages_contact SET statut = 'traite', reponse = ?, reponse_date = datetime('now'), repondu_par = ? WHERE id = ?")
            ->execute([$reponse, $admin['nom'] ?: $admin['email'], $id]);
        logActivity($pdo, 'message_traite', 'messages_contact', $id, 'Réponse envoyée à ' . $m['nom'] . ' (' . $m['email'] . ') — ' . $m['sujet']);

        $mailtoSubject = rawurlencode('Re: ' . $m['sujet']);
        $mailtoBody    = rawurlencode($reponse);
        header('Location: admin_messages.php?replied=' . $id . '&mailto_email=' . rawurlencode($m['email']) . '&mailto_subject=' . $mailtoSubject . '&mailto_body=' . $mailtoBody);
        exit;
    }
    header('Location: admin_messages.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action']) && in_array($_POST['action'], ['traiter', 'reouvrir'], true)) {
    $id = (int) $_POST['id'];
    $newStatut = $_POST['action'] === 'traiter' ? 'traite' : 'nouveau';
    $stmt = $pdo->prepare('UPDATE messages_contact SET statut = ? WHERE id = ?');
    $stmt->execute([$newStatut, $id]);
    if ($newStatut === 'traite') {
        $msg = $pdo->prepare('SELECT nom, sujet FROM messages_contact WHERE id = ?');
        $msg->execute([$id]);
        $m = $msg->fetch();
        logActivity($pdo, 'message_traite', 'messages_contact', $id, 'Message marqué traité — ' . ($m['nom'] ?? '') . ' — ' . ($m['sujet'] ?? ''));
    }
    header('Location: admin_messages.php');
    exit;
}

$messages = $pdo->query('SELECT * FROM messages_contact ORDER BY created_at DESC')->fetchAll();
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
<title>Messages contact | St Romaric</title>
<link rel="icon" type="image/jpeg" href="images/romaric.jpeg">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="assets/admin-theme.css" rel="stylesheet">
<style>
  .msg-reply-box {
    margin-top: 1rem; padding: 1rem 1.1rem; background: var(--green-pale);
    border-left: 3px solid var(--green-mid); border-radius: var(--radius-sm);
  }
  .msg-reply-box .lbl { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--green); margin-bottom: .4rem; }
  .msg-reply-box .txt { font-size: .87rem; color: var(--slate); white-space: pre-wrap; }
  .msg-reply-box .meta { font-size: .72rem; color: var(--slate-light); margin-top: .5rem; }

  .modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 2000;
    background: rgba(14,18,68,.45); align-items: center; justify-content: center; padding: 1.5rem;
  }
  .modal-overlay.active { display: flex; }
  .modal-box { background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); width: 100%; max-width: 520px; padding: 1.7rem; }
  .modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.1rem; }
  .modal-head h3 { font-family: var(--font-display); font-size: 1.1rem; color: var(--green); margin: 0; }
  .modal-close { cursor: pointer; color: var(--slate-light); font-size: 1.1rem; background: none; border: none; }
  .modal-close:hover { color: var(--slate); }
  .modal-box textarea {
    width: 100%; box-sizing: border-box; min-height: 160px; resize: vertical;
    border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: .75rem;
    font-family: inherit; font-size: .87rem; color: var(--slate); margin-bottom: 1rem;
  }
  .modal-box .to-line { font-size: .82rem; color: var(--slate-light); margin-bottom: .8rem; }
  .modal-box .to-line strong { color: var(--slate); }
</style>
</head>
<body class="admin-body">

<div class="admin-shell">

  <?php require __DIR__ . '/includes/admin_sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <div>
        <h1>Messages de contact</h1>
        <div class="subtitle"><strong><?= count($messages) ?></strong> message(s) au total</div>
      </div>
      <span class="chip"><i class="fa-solid fa-envelope-open-text"></i> <?= $nouveauxMessages ?> non traités</span>
    </div>

    <?php if (empty($messages)): ?>
      <div class="panel">
        <div class="empty-state">
          <i class="fa-solid fa-comment-slash"></i>
          Aucun message pour le moment.<br>
          Envoie un message via la <a href="contact.html">page contact</a> pour tester.
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($messages as $m): ?>
      <div class="msg-card">
        <div class="head">
          <div class="cell-person">
            <div class="cell-avatar"><?= initials($m['nom']) ?></div>
            <div>
              <div class="name"><?= htmlspecialchars($m['nom']) ?></div>
              <div class="sub">
                <?= htmlspecialchars($m['email']) ?><?= $m['telephone'] ? ' · ' . htmlspecialchars($m['telephone']) : '' ?>
                · reçu le <?= htmlspecialchars($m['created_at']) ?>
              </div>
            </div>
          </div>
          <div class="d-flex" style="display:flex;align-items:center;gap:.6rem">
            <span class="badge-status <?= $m['statut'] === 'nouveau' ? 'badge-nouveau' : 'badge-traite' ?>"><?= $m['statut'] === 'nouveau' ? 'Nouveau' : 'Traité' ?></span>
            <button type="button" class="btn-mini btn-admin-primary"
              data-id="<?= (int) $m['id'] ?>" data-nom="<?= htmlspecialchars($m['nom']) ?>" data-email="<?= htmlspecialchars($m['email']) ?>" data-sujet="<?= htmlspecialchars($m['sujet']) ?>"
              onclick="openReplyModal(this)"><i class="fa-solid fa-reply"></i> Répondre</button>
            <form method="POST">
            <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <input type="hidden" name="action" value="<?= $m['statut'] === 'nouveau' ? 'traiter' : 'reouvrir' ?>">
              <button type="submit" class="btn-mini"><?= $m['statut'] === 'nouveau' ? 'Marquer traité' : 'Rouvrir' ?></button>
            </form>
          </div>
        </div>
        <div class="sujet"><?= htmlspecialchars($m['sujet']) ?></div>
        <div class="body-text"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
        <?php if (!empty($m['reponse'])): ?>
        <div class="msg-reply-box">
          <div class="lbl"><i class="fa-solid fa-reply"></i> Votre réponse</div>
          <div class="txt"><?= nl2br(htmlspecialchars($m['reponse'])) ?></div>
          <div class="meta">Envoyée le <?= htmlspecialchars($m['reponse_date']) ?><?= !empty($m['repondu_par']) ? ' par ' . htmlspecialchars($m['repondu_par']) : '' ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </main>
</div>

<!-- MODALE : Répondre au message -->
<div class="modal-overlay" id="replyModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3><i class="fa-solid fa-reply" style="color:var(--green-mid);margin-right:.4rem"></i> Répondre au message</h3>
      <button type="button" class="modal-close" onclick="document.getElementById('replyModal').classList.remove('active')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="to-line">À : <strong id="replyTo"></strong> — Objet : <strong id="replySujet"></strong></div>
    <form method="POST" id="replyForm">
    <?= csrf_field() ?>
      <input type="hidden" name="action" value="repondre">
      <input type="hidden" name="id" id="replyId">
      <textarea name="reponse" id="replyTextarea" placeholder="Votre réponse…" required></textarea>
      <button type="submit" class="btn-admin btn-admin-primary" style="width:100%;justify-content:center"><i class="fa-solid fa-paper-plane"></i> Envoyer la réponse</button>
    </form>
  </div>
</div>

<script>
  function openReplyModal(btn) {
    document.getElementById('replyId').value = btn.dataset.id;
    document.getElementById('replyTo').textContent = btn.dataset.nom + ' (' + btn.dataset.email + ')';
    document.getElementById('replySujet').textContent = btn.dataset.sujet;
    document.getElementById('replyTextarea').value = 'Bonjour ' + btn.dataset.nom + ',\n\n';
    document.getElementById('replyModal').classList.add('active');
  }

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('active'); });
  });

  <?php if (isset($_GET['replied'])):
    $mailtoUrl = 'mailto:' . ($_GET['mailto_email'] ?? '')
      . '?subject=' . rawurlencode($_GET['mailto_subject'] ?? '')
      . '&body=' . rawurlencode($_GET['mailto_body'] ?? '');
  ?>
  // La réponse a été enregistrée côté site — on ouvre aussi le client mail local pour l'envoi effectif.
  window.location.href = <?= json_encode($mailtoUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  <?php endif; ?>
</script>

</body>
</html>

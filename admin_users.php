<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/activity.php';

$activePage = 'users';
$viewer = current_admin($pdo);
$flash = null;

/* La Secrétaire n'a pas accès à la gestion des comptes. */
if (($viewer['role'] ?? '') === 'secretaire') {
    header('Location: admin.php');
    exit;
}

/* Le Censeur peut voir la liste des comptes, mais sans aucune action dessus. */
$isCenseur = ($viewer['role'] ?? '') === 'censeur';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $targetId = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!$target) {
        http_response_code(404);
        $flash = ['type' => 'error', 'text' => 'Utilisateur introuvable.'];
    } elseif (!can_modify_admin($target, $viewer)) {
        http_response_code(403);
        logActivity($pdo, 'tentative_non_autorisee', 'admin', $targetId, "Tentative de modification du compte super-admin par un autre compte.");
        $flash = ['type' => 'error', 'text' => "403 — Seul le titulaire du compte super-admin peut modifier son rôle ou son statut."];
    } else {
        $newRole   = trim($_POST['role'] ?? $target['role']) ?: $target['role'];
        $newStatut = ($_POST['statut'] ?? $target['statut']) === 'suspendu' ? 'suspendu' : 'actif';
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($newRole !== $target['role']) {
            $pdo->prepare('UPDATE admins SET role = ? WHERE id = ?')->execute([$newRole, $targetId]);
            logActivity($pdo, 'role_modifie', 'admin', $targetId, "Rôle changé : {$target['role']} → {$newRole} (compte : {$target['email']})");
        }
        if ($newStatut !== $target['statut']) {
            $pdo->prepare('UPDATE admins SET statut = ? WHERE id = ?')->execute([$newStatut, $targetId]);
            logActivity($pdo, 'statut_modifie', 'admin', $targetId, "Statut changé : {$target['statut']} → {$newStatut} (compte : {$target['email']})");
        }
        if ($newPassword !== '') {
            if (mb_strlen($newPassword) < 8) {
                header('Location: admin_users.php?pwderror=1');
                exit;
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$newHash, $targetId]);
            $isOwn = (int) $targetId === (int) $viewer['id'];
            if ($isOwn) {
                // Il vient de saisir ce mot de passe lui-même : pas de déconnexion surprise.
                $_SESSION['pwd_hash'] = $newHash;
            }
            $action = $isOwn ? 'mot_de_passe_modifie' : 'mot_de_passe_reinitialise';
            logActivity($pdo, $action, 'admin', $targetId, "Mot de passe " . ($isOwn ? 'modifié' : 'réinitialisé') . " (compte : {$target['email']})");
        }
        header('Location: admin_users.php?saved=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!can_manage_users($viewer)) {
        http_response_code(403);
        logActivity($pdo, 'tentative_non_autorisee', 'admin', null, "Tentative de création de compte par un compte non autorisé.");
        $flash = ['type' => 'error', 'text' => "403 — Seuls le Développeur et le Directeur peuvent créer un compte."];
    } else {
        $email    = trim($_POST['email'] ?? '');
        $nom      = trim($_POST['nom'] ?? '');
        $role     = trim($_POST['role'] ?? '') ?: 'admin';
        $password = trim($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
        $stmt->execute([$email]);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'error', 'text' => "Adresse email invalide."];
        } elseif ($stmt->fetch()) {
            $flash = ['type' => 'error', 'text' => "Un compte existe déjà avec cet email."];
        } elseif ($nom === '') {
            $flash = ['type' => 'error', 'text' => "Le nom est requis."];
        } elseif (mb_strlen($password) < 8) {
            $flash = ['type' => 'error', 'text' => "Le mot de passe doit contenir au moins 8 caractères."];
        } else {
            $pdo->prepare('INSERT INTO admins (email, password_hash, nom, role) VALUES (?,?,?,?)')
                ->execute([$email, password_hash($password, PASSWORD_DEFAULT), $nom, $role]);
            $newId = (int) $pdo->lastInsertId();
            logActivity($pdo, 'compte_cree', 'admin', $newId, "Nouveau compte créé : {$email} (rôle : {$role})");
            header('Location: admin_users.php?created=1');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $targetId = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!can_manage_users($viewer)) {
        http_response_code(403);
        logActivity($pdo, 'tentative_non_autorisee', 'admin', $targetId, "Tentative de suppression de compte par un compte non autorisé.");
        $flash = ['type' => 'error', 'text' => "403 — Seuls le Développeur et le Directeur peuvent supprimer un compte."];
    } elseif (!$target) {
        http_response_code(404);
        $flash = ['type' => 'error', 'text' => 'Utilisateur introuvable.'];
    } elseif (!can_delete_admin($target, $viewer)) {
        http_response_code(403);
        $flash = ['type' => 'error', 'text' => "Impossible de supprimer ce compte."];
    } else {
        $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$targetId]);
        logActivity($pdo, 'compte_supprime', 'admin', $targetId, "Compte supprimé : {$target['email']}");
        header('Location: admin_users.php?deleted=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'impersonate') {
    $targetId = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (empty($viewer['is_super_admin'])) {
        http_response_code(403);
        logActivity($pdo, 'tentative_non_autorisee', 'admin', $targetId, "Tentative de connexion en tant qu'un autre compte par un compte non-développeur.");
        $flash = ['type' => 'error', 'text' => "403 — Seul le Développeur peut se connecter en tant qu'un autre compte."];
    } elseif (!$target) {
        http_response_code(404);
        $flash = ['type' => 'error', 'text' => 'Utilisateur introuvable.'];
    } elseif (!empty($target['is_super_admin'])) {
        http_response_code(403);
        $flash = ['type' => 'error', 'text' => "Action impossible sur ce compte."];
    } elseif ($target['statut'] === 'suspendu') {
        $flash = ['type' => 'error', 'text' => "Ce compte est suspendu — connexion impossible."];
    } else {
        logActivity($pdo, 'connexion_en_tant_que', 'admin', $targetId, "Connexion en tant que {$target['email']} (rôle : {$target['role']})");
        $_SESSION['impersonator_id']   = $viewer['id'];
        $_SESSION['impersonator_nom']  = $viewer['nom'] ?: $viewer['email'];
        $_SESSION['admin_id']          = $targetId;
        $_SESSION['pwd_hash']          = $target['password_hash'];
        header('Location: admin.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'permissions') {
    $targetId = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!can_manage_users($viewer)) {
        http_response_code(403);
        logActivity($pdo, 'tentative_non_autorisee', 'admin', $targetId, "Tentative de modification des permissions par un compte non autorisé.");
        $flash = ['type' => 'error', 'text' => "403 — Seuls le Développeur et le Directeur peuvent modifier les permissions."];
    } elseif (!$target) {
        http_response_code(404);
        $flash = ['type' => 'error', 'text' => 'Utilisateur introuvable.'];
    } else {
        $granted = array_values(array_intersect((array) ($_POST['perm'] ?? []), array_keys(BUSINESS_PERMISSIONS)));
        $pdo->prepare('UPDATE admins SET permissions = ? WHERE id = ?')->execute([json_encode($granted), $targetId]);
        $labels = $granted ? implode(', ', array_map(fn($k) => BUSINESS_PERMISSIONS[$k], $granted)) : 'aucune';
        logActivity($pdo, 'permissions_modifiees', 'admin', $targetId, "Permissions de {$target['email']} définies sur : $labels");
        header('Location: admin_users.php?permsaved=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revoke_session') {
    $targetId = (int) ($_POST['id'] ?? 0);
    $sessionId = (string) ($_POST['session_id'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!can_manage_users($viewer)) {
        http_response_code(403);
        $flash = ['type' => 'error', 'text' => "403 — Action réservée au Développeur et au Directeur."];
    } elseif ($target && $sessionId !== '') {
        revoke_session($pdo, $sessionId);
        logActivity($pdo, 'session_deconnectee', 'admin', $targetId, "Session déconnectée à distance pour {$target['email']}");
        header('Location: admin_users.php?sessionrevoked=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revoke_all_sessions') {
    $targetId = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!can_manage_users($viewer)) {
        http_response_code(403);
        $flash = ['type' => 'error', 'text' => "403 — Action réservée au Développeur et au Directeur."];
    } elseif ($target) {
        revoke_all_sessions($pdo, $targetId);
        logActivity($pdo, 'session_deconnectee', 'admin', $targetId, "Toutes les sessions déconnectées à distance pour {$target['email']}");
        header('Location: admin_users.php?sessionrevoked=1');
        exit;
    }
}

if (isset($_GET['saved']))   { $flash = ['type' => 'success', 'text' => 'Utilisateur mis à jour.']; }
if (isset($_GET['created'])) { $flash = ['type' => 'success', 'text' => 'Compte créé avec succès.']; }
if (isset($_GET['deleted'])) { $flash = ['type' => 'success', 'text' => 'Compte supprimé.']; }
if (isset($_GET['pwderror'])) { $flash = ['type' => 'error', 'text' => 'Le mot de passe doit contenir au moins 8 caractères.']; }
if (isset($_GET['permsaved'])) { $flash = ['type' => 'success', 'text' => 'Permissions mises à jour.']; }
if (isset($_GET['sessionrevoked'])) { $flash = ['type' => 'success', 'text' => 'Déconnexion forcée effectuée.']; }

$users = visible_admins($pdo, $viewer);

function initials3($a, $b = '') {
    $s = trim(mb_substr($a, 0, 1) . mb_substr($b, 0, 1));
    return mb_strtoupper($s ?: '?');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Utilisateurs & Rôles | OAK International School</title>
<link rel="icon" type="image/jpeg" href="images/romaric.jpeg">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="assets/admin-theme.css" rel="stylesheet">
<style>
  .role-select, .statut-select {
    font-size: .78rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    padding: .35rem .55rem; font-family: var(--font-body); background: #fff; color: var(--slate);
  }
  .badge-super { background: linear-gradient(135deg,var(--green),var(--green-mid)); color:#fff; font-size:.66rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; padding:.22rem .6rem; border-radius:100px; display:inline-flex; align-items:center; gap:.3rem; }
  .badge-suspendu { background:#fef2f2; color:#dc2626; }
  .badge-actif { background: var(--green-pale); color: var(--green); }
  .self-note { font-size: .7rem; color: var(--slate-light); font-style: italic; }
  .pwd-mini-wrap { display: inline-flex; align-items: center; background: #fff; border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 0 .4rem; }
  .pwd-mini-wrap input { border: none; outline: none; font-size: .78rem; padding: .32rem .2rem; width: 130px; font-family: inherit; color: var(--slate); }
  .pwd-mini-wrap i { color: var(--slate-light); font-size: .78rem; cursor: pointer; }
  .row-actions { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; }
  .btn-danger-mini { color: #dc2626 !important; border-color: #fecaca !important; }
  .btn-danger-mini:hover { background: #fef2f2 !important; }
  .btn-impersonate { color: var(--green) !important; border-color: var(--border-green) !important; }
  .btn-impersonate:hover { background: var(--green-pale) !important; }
  #createPanel { display: none; margin-bottom: 1.2rem; }
  .create-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .8rem; align-items: end; }
  @media (max-width: 900px) { .create-grid { grid-template-columns: 1fr 1fr; } }
  .create-grid .field { margin-bottom: 0; }
  .create-grid label { display: block; font-size: .74rem; font-weight: 700; color: var(--slate); margin-bottom: .35rem; }
  .create-grid input { width: 100%; box-sizing: border-box; font-size: .84rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: .55rem .7rem; font-family: inherit; }

  /* ── Boutons d'action par ligne ─────────────────────────── */
  .btn-mini.btn-view i, .btn-mini.btn-edit i { color: var(--slate-light); }

  /* ── Modales ─────────────────────────────────────────────── */
  .modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 2000;
    background: rgba(14,18,68,.45); align-items: center; justify-content: center; padding: 1.5rem;
  }
  .modal-overlay.active { display: flex; }
  .modal-box {
    background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
    width: 100%; max-width: 440px; padding: 1.7rem;
  }
  .modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.3rem; }
  .modal-head h3 { font-family: var(--font-display); font-size: 1.15rem; color: var(--green); margin: 0; }
  .modal-close { cursor: pointer; color: var(--slate-light); font-size: 1.1rem; background:none; border:none; }
  .modal-close:hover { color: var(--slate); }

  .view-row { display: flex; justify-content: space-between; gap: 1rem; padding: .6rem 0; border-bottom: 1px solid var(--border); font-size: .86rem; }
  .view-row:last-child { border-bottom: none; }
  .view-row .k { color: var(--slate-light); font-weight: 600; }
  .view-row .v { color: var(--slate); font-weight: 600; text-align: right; }

  .edit-field { margin-bottom: 1rem; }
  .edit-field label { display: block; font-size: .76rem; font-weight: 700; color: var(--slate); margin-bottom: .4rem; }
  .edit-field input, .edit-field select {
    width: 100%; box-sizing: border-box; font-size: .86rem; border: 1.5px solid var(--border);
    border-radius: var(--radius-sm); padding: .6rem .75rem; font-family: inherit; color: var(--slate);
  }
  .edit-field .pwd-wrap { position: relative; }
  .edit-field .pwd-wrap i { position: absolute; right: .8rem; top: 50%; transform: translateY(-50%); color: var(--slate-light); cursor: pointer; }

  .perm-checkbox {
    display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem;
    border: 1.5px solid var(--border); border-radius: var(--radius-sm); margin-bottom: .6rem;
    font-size: .87rem; color: var(--slate); cursor: pointer;
  }
  .perm-checkbox input { width: 16px; height: 16px; accent-color: var(--green); }

  .session-row {
    display: flex; align-items: center; justify-content: space-between; gap: .8rem;
    padding: .7rem .9rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); margin-bottom: .5rem;
  }
  .session-row .sr-info { font-size: .82rem; color: var(--slate); }
  .session-row .sr-info .device { font-weight: 700; }
  .session-row .sr-info .meta { font-size: .74rem; color: var(--slate-light); margin-top: .15rem; }
  .session-empty { text-align: center; padding: 1.5rem; color: var(--slate-light); font-size: .85rem; }
</style>
</head>
<body class="admin-body">
<div class="admin-shell">
  <?php require __DIR__ . '/includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1>Utilisateurs & Rôles</h1>
        <div class="subtitle">Gestion des comptes administrateurs de l'établissement.</div>
      </div>
      <?php if (can_manage_users($viewer)): ?>
      <button type="button" class="btn-admin btn-admin-primary" id="toggleCreateBtn"><i class="fa-solid fa-user-plus"></i> Nouveau compte</button>
      <?php endif; ?>
    </div>

    <?php if ($flash): ?>
      <div class="alert-premium" style="<?= $flash['type'] === 'error' ? 'background:#fef2f2;border-color:#fecaca;color:#dc2626' : '' ?>">
        <i class="fa-solid <?= $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <?= h($flash['text']) ?>
      </div>
    <?php endif; ?>

    <?php if (can_manage_users($viewer)): ?>
    <div class="panel" id="createPanel">
      <div class="panel-head"><h2><i class="fa-solid fa-user-plus" style="color:var(--green-mid);margin-right:.4rem"></i> Nouveau compte administrateur</h2></div>
      <form method="POST">
      <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="create-grid">
          <div class="field"><label>Nom complet</label><input type="text" name="nom" required></div>
          <div class="field"><label>Email professionnel</label><input type="email" name="email" required></div>
          <div class="field"><label>Mot de passe (8+ car.)</label><input type="password" name="password" minlength="8" required></div>
          <div class="field"><label>Rôle</label><input type="text" name="role" placeholder="admin" value="admin"></div>
        </div>
        <button type="submit" class="btn-admin btn-admin-primary" style="margin-top:1rem"><i class="fa-solid fa-check"></i> Créer le compte</button>
      </form>
    </div>
    <?php endif; ?>

    <div class="panel">
      <table class="premium-table">
        <thead><tr><th>Compte</th><th>Rôle</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): $isSelf = (int) $u['id'] === (int) $viewer['id']; $editable = can_modify_admin($u, $viewer); $canDelete = can_manage_users($viewer) && can_delete_admin($u, $viewer); ?>
          <tr>
            <td>
              <div class="cell-person">
                <div class="cell-avatar"><?= initials3($u['nom'] ?: $u['email']) ?></div>
                <div>
                  <div class="name"><?= h($u['nom'] ?: $u['email']) ?> <?php if ($u['is_super_admin']): ?><span class="badge-super"><i class="fa-solid fa-star"></i> Super-admin</span><?php endif; ?></div>
                  <div class="sub"><?= h($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td><?= h($u['role']) ?></td>
            <td><span class="badge-status <?= $u['statut'] === 'actif' ? 'badge-actif' : 'badge-suspendu' ?>"><?= $u['statut'] === 'actif' ? 'Actif' : 'Suspendu' ?></span></td>
            <td class="row-actions">
              <?php if (!$isCenseur): ?>
              <button type="button" class="btn-mini btn-view"
                data-nom="<?= h($u['nom'] ?: $u['email']) ?>" data-email="<?= h($u['email']) ?>"
                data-role="<?= h($u['role']) ?>" data-statut="<?= $u['statut'] === 'actif' ? 'Actif' : 'Suspendu' ?>"
                data-created="<?= h($u['created_at'] ?? '—') ?>" data-super="<?= $u['is_super_admin'] ? '1' : '0' ?>"
                data-lastlogin="<?= h($u['last_login_at'] ?: 'Jamais connecté') ?>"
                onclick="openViewModal(this)" title="Voir les accès"><i class="fa-solid fa-eye"></i> Voir</button>

              <?php if (can_manage_users($viewer)):
                $effPerms = effective_permissions($u);
                $sessions = admin_sessions_for($pdo, (int) $u['id']);
              ?>
              <button type="button" class="btn-mini btn-perms"
                data-id="<?= (int) $u['id'] ?>" data-nom="<?= h($u['nom'] ?: $u['email']) ?>"
                data-perms='<?= h(json_encode($effPerms)) ?>'
                onclick="openPermsModal(this)" title="Modifier les permissions"><i class="fa-solid fa-shield-halved"></i> Permissions</button>
              <?php
                $sessionsPayload = [];
                foreach ($sessions as $s) {
                    $sessionsPayload[] = [
                        'session_id' => $s['session_id'],
                        'device'     => device_label_from_ua((string) $s['user_agent']),
                        'ip'         => $s['ip_address'],
                        'last_seen'  => $s['last_seen_at'],
                    ];
                }
              ?>
              <button type="button" class="btn-mini btn-sessions"
                data-id="<?= (int) $u['id'] ?>" data-nom="<?= h($u['nom'] ?: $u['email']) ?>"
                data-sessions='<?= h(json_encode($sessionsPayload)) ?>'
                onclick="openSessionsModal(this)" title="Voir les appareils connectés"><i class="fa-solid fa-desktop"></i> Sessions (<?= count($sessions) ?>)</button>
              <?php endif; ?>

              <?php if ($editable): ?>
              <button type="button" class="btn-mini btn-edit"
                data-id="<?= (int) $u['id'] ?>" data-role="<?= h($u['role']) ?>" data-statut="<?= h($u['statut']) ?>" data-email="<?= h($u['email']) ?>"
                onclick="openEditModal(this)" title="Modifier les accès"><i class="fa-solid fa-pen"></i> Modifier</button>
              <button type="button" class="btn-mini btn-reset"
                data-id="<?= (int) $u['id'] ?>" data-role="<?= h($u['role']) ?>" data-statut="<?= h($u['statut']) ?>" data-nom="<?= h($u['nom'] ?: $u['email']) ?>"
                onclick="openResetModal(this)" title="Réinitialiser le mot de passe"><i class="fa-solid fa-key"></i> Réinitialiser</button>
              <?php else: ?>
              <span class="self-note">Réservé au titulaire</span>
              <?php endif; ?>
              <?php endif; ?>

              <?php if (!empty($viewer['is_super_admin']) && empty($u['is_super_admin']) && $u['statut'] === 'actif'): ?>
              <button type="button" class="btn-mini btn-impersonate" onclick="confirmImpersonate(<?= (int) $u['id'] ?>, '<?= h(addslashes($u['nom'] ?: $u['email'])) ?>')" title="Se connecter en tant que ce compte, sans mot de passe"><i class="fa-solid fa-right-to-bracket"></i> Se connecter</button>
              <?php endif; ?>

              <?php if ($canDelete): ?>
              <button type="button" class="btn-mini btn-danger-mini" onclick="confirmDelete(<?= (int) $u['id'] ?>, '<?= h(addslashes($u['email'])) ?>')" title="Supprimer ce compte"><i class="fa-solid fa-trash"></i> Supprimer</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<form method="POST" id="deleteForm" style="display:none">
<?= csrf_field() ?>
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="deleteFormId">
</form>

<form method="POST" id="impersonateForm" style="display:none">
<?= csrf_field() ?>
  <input type="hidden" name="action" value="impersonate">
  <input type="hidden" name="id" id="impersonateFormId">
</form>

<form method="POST" id="revokeSessionForm" style="display:none">
<?= csrf_field() ?>
  <input type="hidden" name="action" value="revoke_session">
  <input type="hidden" name="id" id="revokeSessionAdminId">
  <input type="hidden" name="session_id" id="revokeSessionId">
</form>

<!-- MODALE : Voir les accès -->
<div class="modal-overlay" id="viewModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3><i class="fa-solid fa-eye" style="color:var(--green-mid);margin-right:.4rem"></i> Accès de connexion</h3>
      <button type="button" class="modal-close" onclick="closeModal('viewModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="view-row"><span class="k">Nom</span><span class="v" id="viewNom"></span></div>
    <div class="view-row"><span class="k">Email de connexion</span><span class="v" id="viewEmail"></span></div>
    <div class="view-row"><span class="k">Rôle</span><span class="v" id="viewRole"></span></div>
    <div class="view-row"><span class="k">Statut</span><span class="v" id="viewStatut"></span></div>
    <div class="view-row"><span class="k">Compte créé le</span><span class="v" id="viewCreated"></span></div>
    <div class="view-row"><span class="k">Dernière connexion</span><span class="v" id="viewLastLogin"></span></div>
  </div>
</div>

<!-- MODALE : Permissions (accès aux données métier) -->
<div class="modal-overlay" id="permsModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3><i class="fa-solid fa-shield-halved" style="color:var(--green-mid);margin-right:.4rem"></i> Permissions</h3>
      <button type="button" class="modal-close" onclick="closeModal('permsModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <p style="font-size:.85rem;color:var(--slate-light);margin:0 0 1rem">
      Données métier sensibles accessibles par <strong id="permsNom" style="color:var(--slate)"></strong> :
    </p>
    <form method="POST" id="permsForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="permissions">
      <input type="hidden" name="id" id="permsId">
      <?php foreach (BUSINESS_PERMISSIONS as $key => $label): ?>
      <label class="perm-checkbox">
        <input type="checkbox" name="perm[]" value="<?= h($key) ?>" class="perms-checkbox-input" data-perm="<?= h($key) ?>">
        <?= h($label) ?>
      </label>
      <?php endforeach; ?>
      <button type="submit" class="btn-admin btn-admin-primary" style="width:100%;justify-content:center;margin-top:1rem"><i class="fa-solid fa-check"></i> Enregistrer les permissions</button>
    </form>
  </div>
</div>

<!-- MODALE : Sessions / appareils connectés -->
<div class="modal-overlay" id="sessionsModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3><i class="fa-solid fa-desktop" style="color:var(--green-mid);margin-right:.4rem"></i> Appareils connectés</h3>
      <button type="button" class="modal-close" onclick="closeModal('sessionsModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <p style="font-size:.85rem;color:var(--slate-light);margin:0 0 1rem">
      Sessions actives pour <strong id="sessionsNom" style="color:var(--slate)"></strong> :
    </p>
    <div id="sessionsList"></div>
    <form method="POST" id="revokeAllForm" style="margin-top:1rem">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="revoke_all_sessions">
      <input type="hidden" name="id" id="revokeAllId">
      <button type="submit" class="btn-admin" style="width:100%;justify-content:center;border-color:#fecaca;color:#dc2626" onclick="return confirm('Déconnecter TOUS les appareils de ce compte ?')"><i class="fa-solid fa-plug-circle-xmark"></i> Déconnecter tous les appareils</button>
    </form>
  </div>
</div>

<!-- MODALE : Modifier les accès -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3><i class="fa-solid fa-pen" style="color:var(--green-mid);margin-right:.4rem"></i> Modifier les accès</h3>
      <button type="button" class="modal-close" onclick="closeModal('editModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" id="editForm">
    <?= csrf_field() ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId">
      <div class="edit-field">
        <label>Email de connexion</label>
        <input type="text" id="editEmailDisplay" disabled style="background:var(--off-white)">
      </div>
      <div class="edit-field">
        <label>Rôle</label>
        <input type="text" name="role" id="editRole">
      </div>
      <div class="edit-field">
        <label>Statut</label>
        <select name="statut" id="editStatut">
          <option value="actif">Actif</option>
          <option value="suspendu">Suspendu</option>
        </select>
      </div>
      <div class="edit-field">
        <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
        <div class="pwd-wrap">
          <input type="password" name="new_password" id="editPassword" minlength="8" placeholder="••••••••">
          <i class="fa-solid fa-eye toggle-pwd"></i>
        </div>
      </div>
      <button type="submit" class="btn-admin btn-admin-primary" style="width:100%;justify-content:center"><i class="fa-solid fa-check"></i> Enregistrer</button>
    </form>
  </div>
</div>

<!-- MODALE : Réinitialiser le mot de passe -->
<div class="modal-overlay" id="resetModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3><i class="fa-solid fa-key" style="color:var(--green-mid);margin-right:.4rem"></i> Réinitialiser le mot de passe</h3>
      <button type="button" class="modal-close" onclick="closeModal('resetModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <p style="font-size:.86rem;color:var(--slate-light);margin:0 0 1rem">
      Nouveau mot de passe généré pour <strong id="resetNom" style="color:var(--slate)"></strong> :
    </p>
    <div class="edit-field">
      <div class="pwd-wrap">
        <input type="text" id="resetPasswordDisplay" readonly style="font-weight:700;letter-spacing:.03em">
        <i class="fa-solid fa-copy" id="resetCopyBtn" title="Copier"></i>
      </div>
      <div id="resetCopiedNote" style="font-size:.74rem;color:var(--green);margin-top:.4rem;display:none"><i class="fa-solid fa-check"></i> Copié.</div>
    </div>
    <p style="font-size:.78rem;color:#dc2626;margin:0 0 1rem">
      <i class="fa-solid fa-triangle-exclamation"></i> Notez-le maintenant — il ne sera plus affiché après confirmation.
    </p>
    <form method="POST" id="resetForm">
    <?= csrf_field() ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="resetId">
      <input type="hidden" name="role" id="resetRole">
      <input type="hidden" name="statut" id="resetStatut">
      <input type="hidden" name="new_password" id="resetPasswordField">
      <button type="submit" class="btn-admin btn-admin-primary" style="width:100%;justify-content:center"><i class="fa-solid fa-check"></i> Confirmer la réinitialisation</button>
    </form>
  </div>
</div>

<script>
  function confirmDelete(id, email) {
    if (confirm('Supprimer définitivement le compte ' + email + ' ? Cette action est irréversible.')) {
      document.getElementById('deleteFormId').value = id;
      document.getElementById('deleteForm').submit();
    }
  }

  function closeModal(id) { document.getElementById(id).classList.remove('active'); }

  function confirmImpersonate(id, nom) {
    if (confirm('Se connecter en tant que ' + nom + ' ? Vous verrez exactement son tableau de bord, sans avoir besoin de son mot de passe.')) {
      document.getElementById('impersonateFormId').value = id;
      document.getElementById('impersonateForm').submit();
    }
  }

  function openViewModal(btn) {
    document.getElementById('viewNom').textContent = btn.dataset.nom;
    document.getElementById('viewEmail').textContent = btn.dataset.email;
    document.getElementById('viewRole').textContent = btn.dataset.role + (btn.dataset.super === '1' ? ' (Super-admin)' : '');
    document.getElementById('viewStatut').textContent = btn.dataset.statut;
    document.getElementById('viewCreated').textContent = btn.dataset.created;
    document.getElementById('viewLastLogin').textContent = btn.dataset.lastlogin;
    document.getElementById('viewModal').classList.add('active');
  }

  function openPermsModal(btn) {
    const granted = JSON.parse(btn.dataset.perms || '[]');
    document.getElementById('permsNom').textContent = btn.dataset.nom;
    document.getElementById('permsId').value = btn.dataset.id;
    document.querySelectorAll('.perms-checkbox-input').forEach(cb => {
      cb.checked = granted.includes(cb.dataset.perm);
    });
    document.getElementById('permsModal').classList.add('active');
  }

  function openSessionsModal(btn) {
    const sessions = JSON.parse(btn.dataset.sessions || '[]');
    document.getElementById('sessionsNom').textContent = btn.dataset.nom;
    document.getElementById('revokeAllId').value = btn.dataset.id;
    const list = document.getElementById('sessionsList');
    if (!sessions.length) {
      list.innerHTML = '<div class="session-empty">Aucune session active.</div>';
    } else {
      list.innerHTML = sessions.map(s => `
        <div class="session-row">
          <div class="sr-info">
            <div class="device">${s.device}</div>
            <div class="meta">${s.ip || 'IP inconnue'} · dernière activité : ${s.last_seen}</div>
          </div>
          <button type="button" class="btn-mini btn-danger-mini" onclick="revokeSession(${btn.dataset.id}, '${s.session_id}')"><i class="fa-solid fa-plug-circle-xmark"></i> Déconnecter</button>
        </div>
      `).join('');
    }
    document.getElementById('sessionsModal').classList.add('active');
  }

  function revokeSession(adminId, sessionId) {
    if (confirm('Déconnecter cet appareil ?')) {
      document.getElementById('revokeSessionAdminId').value = adminId;
      document.getElementById('revokeSessionId').value = sessionId;
      document.getElementById('revokeSessionForm').submit();
    }
  }

  function openEditModal(btn) {
    document.getElementById('editId').value = btn.dataset.id;
    document.getElementById('editRole').value = btn.dataset.role;
    document.getElementById('editStatut').value = btn.dataset.statut;
    document.getElementById('editEmailDisplay').value = btn.dataset.email;
    document.getElementById('editPassword').value = '';
    document.getElementById('editModal').classList.add('active');
  }

  function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    const arr = new Uint32Array(12);
    crypto.getRandomValues(arr);
    let pwd = '';
    for (let i = 0; i < 12; i++) pwd += chars[arr[i] % chars.length];
    return pwd;
  }

  function openResetModal(btn) {
    const pwd = generatePassword();
    document.getElementById('resetNom').textContent = btn.dataset.nom;
    document.getElementById('resetPasswordDisplay').value = pwd;
    document.getElementById('resetPasswordField').value = pwd;
    document.getElementById('resetId').value = btn.dataset.id;
    document.getElementById('resetRole').value = btn.dataset.role;
    document.getElementById('resetStatut').value = btn.dataset.statut;
    document.getElementById('resetCopiedNote').style.display = 'none';
    document.getElementById('resetModal').classList.add('active');
  }

  document.getElementById('resetCopyBtn').addEventListener('click', () => {
    const input = document.getElementById('resetPasswordDisplay');
    navigator.clipboard.writeText(input.value).then(() => {
      document.getElementById('resetCopiedNote').style.display = 'block';
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('active'); });
  });

  document.querySelectorAll('.toggle-pwd').forEach(icon => {
    icon.addEventListener('click', () => {
      const input = icon.previousElementSibling;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      icon.classList.toggle('fa-eye', !show);
      icon.classList.toggle('fa-eye-slash', show);
    });
  });

  const toggleCreateBtn = document.getElementById('toggleCreateBtn');
  const createPanel = document.getElementById('createPanel');
  if (toggleCreateBtn && createPanel) {
    toggleCreateBtn.addEventListener('click', () => {
      createPanel.style.display = createPanel.style.display === 'none' || !createPanel.style.display ? 'block' : 'none';
    });
  }
</script>

</body>
</html>

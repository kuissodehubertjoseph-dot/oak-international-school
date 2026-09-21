<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/activity.php';
require __DIR__ . '/includes/site_settings.php';

$error = '';

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    $isLocked = $admin && !empty($admin['locked_until']) && strtotime($admin['locked_until']) > time();

    if ($admin && $admin['statut'] === 'suspendu') {
        $error = 'Ce compte a été suspendu.';
        logActivity($pdo, 'connexion_echouee', 'admin', $admin['id'], "Tentative sur un compte suspendu ($email)", ['id' => null]);
    } elseif ($isLocked) {
        $minutesLeft = (int) ceil((strtotime($admin['locked_until']) - time()) / 60);
        $error = "Trop de tentatives échouées. Réessayez dans $minutesLeft minute(s).";
        logActivity($pdo, 'connexion_echouee', 'admin', $admin['id'], "Tentative sur un compte temporairement verrouillé ($email)", ['id' => null]);
    } elseif ($admin && password_verify($password, $admin['password_hash'])) {
        $pdo->prepare("UPDATE admins SET failed_attempts = 0, locked_until = NULL, last_login_at = datetime('now') WHERE id = ?")->execute([$admin['id']]);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['pwd_hash'] = $admin['password_hash'];
        register_session($pdo, (int) $admin['id']);
        logActivity($pdo, 'connexion_reussie', 'admin', $admin['id'], null, ['id' => $admin['id'], 'name' => $admin['nom'] ?: $admin['email'], 'role' => $admin['role']]);
        header('Location: admin.php');
        exit;
    } else {
        if ($admin) {
            $attempts = (int) $admin['failed_attempts'] + 1;
            if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCK_MINUTES * 60);
                $pdo->prepare('UPDATE admins SET failed_attempts = ?, locked_until = ? WHERE id = ?')->execute([$attempts, $lockUntil, $admin['id']]);
                $error = "Trop de tentatives échouées. Compte verrouillé " . LOGIN_LOCK_MINUTES . " minutes.";
            } else {
                $pdo->prepare('UPDATE admins SET failed_attempts = ? WHERE id = ?')->execute([$attempts, $admin['id']]);
                $error = 'Identifiant ou mot de passe incorrect.';
            }
        } else {
            $error = 'Identifiant ou mot de passe incorrect.';
        }
        logActivity($pdo, 'connexion_echouee', 'admin', $admin['id'] ?? null, "Échec de connexion pour $email", ['id' => null]);
    }
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$notice = '';
if (isset($_GET['pwdchanged'])) {
    $notice = 'Votre mot de passe a été modifié. Reconnectez-vous avec le nouveau mot de passe.';
} elseif (isset($_GET['forcedout'])) {
    $notice = 'Votre session a été déconnectée à distance par un administrateur.';
}

$siteSettings = get_site_settings($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion Administration | OAK International School</title>
<link rel="icon" type="image/jpeg" href="images/romaric.jpeg">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="assets/admin-theme.css" rel="stylesheet">
<style>
  body {
    font-family: var(--font-body);
    margin: 0; min-height: 100vh; background: var(--off-white);
    display: flex;
  }
  .login-visual {
    flex: 1.05; position: relative; overflow: hidden;
    background:
      linear-gradient(to bottom, rgba(14,81,43,.5) 0%, rgba(3,56,26,.7) 50%, rgba(3,45,32,.94) 100%),
      linear-gradient(160deg, var(--green) 0%, #043d2c 100%);
    color: #fff; display: flex; flex-direction: column; justify-content: space-between;
    padding: 3rem 3.2rem;
  }
  .login-visual .brand { display: flex; align-items: center; gap: .8rem; }
  .login-visual .brand img { width: 44px; }
  .login-visual .brand .brand-logo-text-dynamic { font-size: 1.35rem; background: linear-gradient(135deg, #fff 0%, var(--green-light) 100%); -webkit-background-clip: text; background-clip: text; }
  .login-visual .brand .name { font-family: var(--font-display); font-weight: 700; font-size: 1.15rem; }
  .login-visual .brand .tag { font-size: .66rem; letter-spacing: .14em; text-transform: uppercase; color: var(--green-light); font-weight: 700; }

  .login-visual .pitch { max-width: 420px; }
  .login-visual .pitch .eyebrow {
    font-size: .7rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
    color: var(--green-light); margin-bottom: 1.1rem; display: block;
  }
  .login-visual .pitch h2 {
    font-family: var(--font-display); color: #fff;
    font-size: 2.1rem; font-weight: 700; line-height: 1.18; margin: 0 0 1rem;
  }
  .login-visual .pitch h2 em { font-style: italic; color: var(--green-light); }
  .login-visual .pitch p { color: rgba(255,255,255,.68); font-size: .92rem; line-height: 1.75; }

  .login-visual .stats-row { display: flex; gap: 2.2rem; margin-top: 2.2rem; }
  .login-visual .stats-row div .n { font-family: var(--font-num); font-size: 1.7rem; font-weight: 800; color: #fff; line-height: 1; }
  .login-visual .stats-row div .l { font-size: .7rem; color: rgba(255,255,255,.5); margin-top: .3rem; text-transform: uppercase; letter-spacing: .08em; }

  .login-visual .footnote { font-size: .74rem; color: rgba(255,255,255,.4); }

  .login-panel {
    flex: 1; display: flex; align-items: center; justify-content: center; padding: 2.5rem;
  }
  .login-box { width: 100%; max-width: 380px; }
  .login-box .kicker { font-size: .72rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--green-mid); margin-bottom: .6rem; display: block; }
  .login-box h1 { font-family: var(--font-display); font-size: 1.65rem; font-weight: 700; color: var(--green); margin: 0 0 .4rem; }
  .login-box .sub { font-size: .87rem; color: var(--slate-light); margin-bottom: 1.9rem; }

  .field { margin-bottom: 1.1rem; }
  .field label { display: block; font-size: .76rem; font-weight: 700; color: var(--slate); margin-bottom: .45rem; }
  .field .wrap {
    display: flex; align-items: center; background: #fff;
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    padding: 0 .95rem; transition: var(--transition);
  }
  .field .wrap:focus-within { border-color: var(--green-mid); box-shadow: 0 0 0 3px rgba(5,150,105,.12); }
  .field .wrap i { color: var(--slate-light); font-size: .85rem; width: 20px; }
  .field .wrap input {
    flex: 1; border: none; outline: none; background: transparent;
    padding: .78rem .2rem; font-size: .9rem; font-family: inherit; color: var(--slate);
  }
  .field .wrap .toggle-eye { cursor: pointer; color: var(--slate-light); }
  .field .wrap .toggle-eye:hover { color: var(--slate); }

  .btn-submit {
    width: 100%; border: 2px solid var(--green); border-radius: var(--radius-sm);
    background: var(--green);
    color: #fff; font-family: var(--font-body); font-weight: 600; font-size: .9rem;
    padding: .8rem; cursor: pointer; margin-top: .4rem;
    transition: var(--transition);
    display: flex; align-items: center; justify-content: center; gap: .55rem;
  }
  .btn-submit:hover { background: var(--green-mid); border-color: var(--green-mid); box-shadow: var(--shadow-green); transform: translateY(-1px); }

  .demo-note {
    margin-top: 1.6rem; background: var(--green-ultra); border: 1px solid var(--border-green);
    border-radius: var(--radius-sm); padding: .8rem .95rem; font-size: .76rem; color: var(--slate-light);
    display: flex; gap: .55rem; align-items: flex-start;
  }
  .demo-note i { color: var(--green-mid); margin-top: .1rem; }
  .demo-note code { background: #fff; border: 1px solid var(--border); border-radius: 5px; padding: .05rem .35rem; font-size: .73rem; }

  .error-box {
    background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
    border-radius: var(--radius-sm); padding: .7rem .9rem; font-size: .82rem; font-weight: 600;
    margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem;
  }
  .notice-box {
    background: var(--green-ultra); border: 1px solid var(--border-green); color: var(--green);
    border-radius: var(--radius-sm); padding: .7rem .9rem; font-size: .82rem; font-weight: 600;
    margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem;
  }

  .back-link { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; color: var(--slate-light); margin-top: 1.6rem; }
  .back-link:hover { color: var(--green); }

  @media (max-width: 900px) { .login-visual { display: none; } }
</style>
</head>
<body>

<div class="login-visual">
  <div class="brand">
    <?= render_brand_logo($siteSettings) ?>
    <div>
      <div class="name">OAK International School</div>
      <div class="tag">Espace administration</div>
    </div>
  </div>

  <div class="pitch">
    <span class="eyebrow">Console de gestion</span>
    <h2>Pilotez l'établissement <em>en toute sérénité</em></h2>
    <p>
      Pré-inscriptions, messages des familles, résultats et vie scolaire —
      retrouvez tout ce qui compte au même endroit, dans un espace pensé
      pour votre équipe administrative.
    </p>
    <div class="stats-row">
      <div><div class="n">800+</div><div class="l">Élèves</div></div>
      <div><div class="n">20+</div><div class="l">Ans d'excellence</div></div>
      <div><div class="n">95%</div><div class="l">Réussite BAC</div></div>
    </div>
  </div>

  <div class="footnote">© 2026 OAK International School — Cotonou, Bénin</div>
</div>

<div class="login-panel">
  <div class="login-box">
    <span class="kicker">Bon retour</span>
    <h1>Connexion</h1>
    <p class="sub">Accédez à votre tableau de bord administrateur.</p>

    <?php if ($error): ?>
    <div class="error-box"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($notice): ?>
    <div class="notice-box"><i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <div class="field">
        <label>Email professionnel</label>
        <div class="wrap">
          <i class="fa-regular fa-envelope"></i>
          <input type="email" name="email" placeholder="admin@oisbenin.com" required autofocus>
        </div>
      </div>

      <div class="field">
        <label>Mot de passe</label>
        <div class="wrap">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="password" placeholder="••••••••" required>
          <i class="fa-solid fa-eye toggle-eye" id="toggleIcon" onclick="togglePassword()"></i>
        </div>
      </div>

      <button type="submit" class="btn-submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Se connecter</button>
    </form>

    <a href="index.html" class="back-link"><i class="fa-solid fa-arrow-left"></i> Revenir au site public</a>
  </div>
</div>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  const icon = document.getElementById('toggleIcon');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  icon.classList.toggle('fa-eye', !show);
  icon.classList.toggle('fa-eye-slash', show);
}
</script>

</body>
</html>

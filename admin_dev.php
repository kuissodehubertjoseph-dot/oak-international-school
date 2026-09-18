<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/render.php';
require __DIR__ . '/includes/site_settings.php';
require __DIR__ . '/includes/system_status.php';
require __DIR__ . '/includes/version.php';
require __DIR__ . '/includes/activity.php';
require_super_admin($pdo);

$activePage = 'dev';
$siteSettings = get_site_settings($pdo);

$backupFlash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'backup') {
    try {
        run_backup();
        logActivity($pdo, 'sauvegarde_creee', 'system', null, 'Sauvegarde manuelle de la base de données créée.');
        header('Location: admin_dev.php?backup=1');
        exit;
    } catch (Throwable $e) {
        $backupFlash = ['type' => 'error', 'text' => $e->getMessage()];
    }
}
if (isset($_GET['backup'])) { $backupFlash = ['type' => 'success', 'text' => 'Sauvegarde créée avec succès.']; }

$dbStatus     = status_database($pdo);
$diskStatus   = status_disk();
$sessStatus   = status_active_sessions();
$secEvents24h = status_security_events($pdo);
$lastCodeChange = status_last_code_change();
$lastBackup   = status_last_backup();
$serverLoad   = status_server_load();

$uploadsWritable = is_dir(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads');
$imagesWritable  = is_dir(__DIR__ . '/images') && is_writable(__DIR__ . '/images');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Développeur | St Romaric</title>
<link rel="icon" type="image/jpeg" href="images/romaric.jpeg">
<script>
  // Appliqué avant le premier rendu pour éviter le flash clair→sombre.
  (function () {
    try {
      var pref = localStorage.getItem('devDashboardTheme') || 'system';
      if (pref !== 'system') { document.documentElement.setAttribute('data-theme', pref); }
    } catch (e) {}
  })();
</script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="assets/admin-theme.css" rel="stylesheet">
<style>
  .dev-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.3rem; align-items: start; }
  @media (max-width: 1100px) { .dev-grid { grid-template-columns: 1fr; } }

  /* ── État technique ─────────────────────────────────────── */
  .status-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .9rem; }
  @media (max-width: 1100px) { .status-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 575px) { .status-grid { grid-template-columns: 1fr; } }
  .status-card { background: var(--off-white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem 1.1rem; }
  .status-card .sc-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--slate-light); margin-bottom: .45rem; }
  .status-card .sc-value { font-size: 1.02rem; font-weight: 700; color: var(--slate); }
  .status-card .sc-sub { font-size: .74rem; color: var(--slate-light); margin-top: .3rem; }

  .services-row { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.1rem; padding-top: 1.1rem; border-top: 1px solid var(--border); }
  .service-pill { font-size: .8rem; font-weight: 600; color: var(--slate); background: var(--off-white); border: 1px solid var(--border); border-radius: 100px; padding: .35rem .9rem; }

  /* ── Journal d'activité ─────────────────────────────────── */
  .log-list { display: flex; flex-direction: column; gap: .1rem; max-height: 640px; overflow-y: auto; }
  .log-row { display: flex; gap: .8rem; align-items: flex-start; padding: .75rem .3rem; border-bottom: 1px solid var(--border); }
  .log-row:last-child { border-bottom: none; }
  .log-ic {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: var(--green-pale); color: var(--green); font-size: .85rem; margin-top: .1rem;
  }
  .log-row.sensitive .log-ic { background: #fef2f2; color: #dc2626; }
  .log-main { min-width: 0; flex: 1; }
  .log-top { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
  .log-actor { font-weight: 700; font-size: .84rem; color: var(--slate); }
  .log-role { font-size: .68rem; color: var(--slate-light); background: var(--off-white); border: 1px solid var(--border); border-radius: 100px; padding: .05rem .55rem; }
  .badge-sensitive { background: #dc2626; color: #fff; font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; padding: .16rem .5rem; border-radius: 100px; }
  .log-action { font-size: .82rem; color: var(--slate); margin-top: .15rem; }
  .log-details { font-size: .76rem; color: var(--slate-light); margin-top: .15rem; }
  .log-meta { font-size: .7rem; color: var(--slate-light); margin-top: .3rem; display: flex; gap: .9rem; flex-wrap: wrap; }
  .log-meta i { margin-right: .25rem; }
  .log-empty { text-align: center; padding: 2.5rem; color: var(--slate-light); }
  .refresh-btn { display: inline-flex; align-items: center; gap: .4rem; }
  .refresh-btn.spinning i { animation: spin .7s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .auto-refresh-note { font-size: .72rem; color: var(--slate-light); }

  /* ── Identité visuelle ──────────────────────────────────── */
  .logo-mode-toggle { display: flex; gap: .6rem; margin-bottom: 1.1rem; }
  .logo-mode-btn {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: .4rem;
    padding: .9rem .6rem; border: 1.5px solid var(--border); border-radius: var(--radius-md);
    background: #fff; cursor: pointer; transition: var(--transition); font-family: var(--font-body);
  }
  .logo-mode-btn i { font-size: 1.15rem; color: var(--slate-light); }
  .logo-mode-btn span { font-size: .78rem; font-weight: 700; color: var(--slate); }
  .logo-mode-btn.active { border-color: var(--green-mid); background: var(--green-pale); }
  .logo-mode-btn.active i, .logo-mode-btn.active span { color: var(--green); }

  .logo-preview {
    background: linear-gradient(190deg, var(--green) 0%, #043d2c 100%);
    border-radius: var(--radius-lg); padding: 2.2rem 1.5rem; display: flex; align-items: center;
    justify-content: center; margin-bottom: 1.2rem; min-height: 110px;
  }
  .logo-preview img { width: 64px; height: 64px; object-fit: contain; border-radius: 12px; background: #fff; padding: 4px; }
  .logo-preview .brand-logo-text-dynamic { font-size: 1.7rem; background: linear-gradient(135deg, #fff 0%, var(--green-light) 100%); -webkit-background-clip: text; background-clip: text; }

  #saveLogoBtn:disabled { opacity: .55; cursor: not-allowed; }
  .save-status { font-size: .76rem; margin-top: .6rem; display: flex; align-items: center; gap: .4rem; }
  .save-status.ok { color: var(--green); }
  .save-status.err { color: #dc2626; }

  /* ── Sélecteur de thème (Système / Clair / Sombre) ──────── */
  .theme-switch { display: inline-flex; background: #fff; border: 1px solid var(--border); border-radius: 100px; padding: .2rem; gap: .15rem; box-shadow: var(--shadow-sm); }
  .theme-switch button {
    display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;
    border: none; background: transparent; border-radius: 50%; color: var(--slate-light);
    cursor: pointer; font-size: .82rem; transition: var(--transition);
  }
  .theme-switch button:hover { color: var(--green); background: var(--green-pale); }
  .theme-switch button.active { background: var(--green); color: #fff; }

  body, .panel, .chip, .btn-mini, .field input, .logo-mode-btn { transition: background-color .2s ease, color .2s ease, border-color .2s ease; }

  /* ── Thème sombre — suit le système par défaut, ou le choix explicite ── */
  @media (prefers-color-scheme: dark) {
    html:not([data-theme="light"]) body.admin-body { background: #0b1119; color: #e2e8f0; }
    html:not([data-theme="light"]) .admin-topbar h1 { color: #a5b4fc; }
    html:not([data-theme="light"]) .admin-topbar .subtitle { color: #8b98ab; }
    html:not([data-theme="light"]) .chip { background: #131c27; border-color: #263241; color: #8b98ab; }
    html:not([data-theme="light"]) .panel { background: #131c27; border-color: #263241; box-shadow: none; }
    html:not([data-theme="light"]) .panel-head h2 { color: #a5b4fc; }
    html:not([data-theme="light"]) .btn-mini { background: #182330; border-color: #263241; color: #cbd5e1; }
    html:not([data-theme="light"]) .btn-mini:hover { border-color: var(--green-mid); color: #a5b4fc; background: #1c2b3d; }
    html:not([data-theme="light"]) .log-row { border-color: #21303f; }
    html:not([data-theme="light"]) .log-ic { background: #1c2b3d; color: #a5b4fc; }
    html:not([data-theme="light"]) .log-row.sensitive .log-ic { background: #3a1a1a; color: #f87171; }
    html:not([data-theme="light"]) .log-actor { color: #e2e8f0; }
    html:not([data-theme="light"]) .log-role { background: #0b1119; border-color: #263241; color: #8b98ab; }
    html:not([data-theme="light"]) .log-action { color: #cbd5e1; }
    html:not([data-theme="light"]) .log-details, html:not([data-theme="light"]) .log-meta, html:not([data-theme="light"]) .log-empty, html:not([data-theme="light"]) .auto-refresh-note { color: #8b98ab; }
    html:not([data-theme="light"]) .logo-mode-btn { background: #182330; border-color: #263241; }
    html:not([data-theme="light"]) .logo-mode-btn i { color: #8b98ab; }
    html:not([data-theme="light"]) .logo-mode-btn span { color: #cbd5e1; }
    html:not([data-theme="light"]) .logo-mode-btn.active { background: #16281f; border-color: var(--green-mid); }
    html:not([data-theme="light"]) .logo-mode-btn.active i, html:not([data-theme="light"]) .logo-mode-btn.active span { color: #7de3ac; }
    html:not([data-theme="light"]) .field label { color: #cbd5e1; }
    html:not([data-theme="light"]) .field input[type="text"] { background: #0b1119; border-color: #263241; color: #e2e8f0; }
    html:not([data-theme="light"]) .theme-switch { background: #131c27; border-color: #263241; }
    html:not([data-theme="light"]) .theme-switch button { color: #8b98ab; }
    html:not([data-theme="light"]) .theme-switch button.active { background: var(--green-mid); color: #fff; }
    html:not([data-theme="light"]) .status-card { background: #0b1119; border-color: #263241; }
    html:not([data-theme="light"]) .status-card .sc-value { color: #e2e8f0; }
    html:not([data-theme="light"]) .status-card .sc-label, html:not([data-theme="light"]) .status-card .sc-sub { color: #8b98ab; }
    html:not([data-theme="light"]) .services-row { border-color: #263241; }
    html:not([data-theme="light"]) .service-pill { background: #0b1119; border-color: #263241; color: #cbd5e1; }
  }
  html[data-theme="dark"] body.admin-body { background: #0b1119; color: #e2e8f0; }
  html[data-theme="dark"] .admin-topbar h1 { color: #a5b4fc; }
  html[data-theme="dark"] .admin-topbar .subtitle { color: #8b98ab; }
  html[data-theme="dark"] .chip { background: #131c27; border-color: #263241; color: #8b98ab; }
  html[data-theme="dark"] .panel { background: #131c27; border-color: #263241; box-shadow: none; }
  html[data-theme="dark"] .panel-head h2 { color: #a5b4fc; }
  html[data-theme="dark"] .btn-mini { background: #182330; border-color: #263241; color: #cbd5e1; }
  html[data-theme="dark"] .btn-mini:hover { border-color: var(--green-mid); color: #a5b4fc; background: #1c2b3d; }
  html[data-theme="dark"] .log-row { border-color: #21303f; }
  html[data-theme="dark"] .log-ic { background: #1c2b3d; color: #a5b4fc; }
  html[data-theme="dark"] .log-row.sensitive .log-ic { background: #3a1a1a; color: #f87171; }
  html[data-theme="dark"] .log-actor { color: #e2e8f0; }
  html[data-theme="dark"] .log-role { background: #0b1119; border-color: #263241; color: #8b98ab; }
  html[data-theme="dark"] .log-action { color: #cbd5e1; }
  html[data-theme="dark"] .log-details, html[data-theme="dark"] .log-meta, html[data-theme="dark"] .log-empty, html[data-theme="dark"] .auto-refresh-note { color: #8b98ab; }
  html[data-theme="dark"] .logo-mode-btn { background: #182330; border-color: #263241; }
  html[data-theme="dark"] .logo-mode-btn i { color: #8b98ab; }
  html[data-theme="dark"] .logo-mode-btn span { color: #cbd5e1; }
  html[data-theme="dark"] .logo-mode-btn.active { background: #16281f; border-color: var(--green-mid); }
  html[data-theme="dark"] .logo-mode-btn.active i, html[data-theme="dark"] .logo-mode-btn.active span { color: #7de3ac; }
  html[data-theme="dark"] .field label { color: #cbd5e1; }
  html[data-theme="dark"] .field input[type="text"] { background: #0b1119; border-color: #263241; color: #e2e8f0; }
  html[data-theme="dark"] .theme-switch { background: #131c27; border-color: #263241; }
  html[data-theme="dark"] .theme-switch button { color: #8b98ab; }
  html[data-theme="dark"] .theme-switch button.active { background: var(--green-mid); color: #fff; }
  html[data-theme="dark"] .status-card { background: #0b1119; border-color: #263241; }
  html[data-theme="dark"] .status-card .sc-value { color: #e2e8f0; }
  html[data-theme="dark"] .status-card .sc-label, html[data-theme="dark"] .status-card .sc-sub { color: #8b98ab; }
  html[data-theme="dark"] .services-row { border-color: #263241; }
  html[data-theme="dark"] .service-pill { background: #0b1119; border-color: #263241; color: #cbd5e1; }
</style>
</head>
<body class="admin-body">
<div class="admin-shell">
  <?php require __DIR__ . '/includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1><i class="fa-solid fa-code" style="color:var(--green-mid);margin-right:.4rem"></i> Développeur</h1>
        <div class="subtitle">Onglet réservé au super-administrateur — journal d'activité et identité visuelle du site.</div>
      </div>
      <div class="theme-switch" id="themeSwitch" role="group" aria-label="Thème de l'interface">
        <button type="button" data-theme-choice="system" title="Suivre le système"><i class="fa-solid fa-circle-half-stroke"></i></button>
        <button type="button" data-theme-choice="light" title="Clair"><i class="fa-solid fa-sun"></i></button>
        <button type="button" data-theme-choice="dark" title="Sombre"><i class="fa-solid fa-moon"></i></button>
      </div>
    </div>

    <?php if ($backupFlash): ?>
      <div class="alert-premium" style="<?= $backupFlash['type'] === 'error' ? 'background:#fef2f2;border-color:#fecaca;color:#dc2626' : '' ?>">
        <i class="fa-solid <?= $backupFlash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <?= h($backupFlash['text']) ?>
      </div>
    <?php endif; ?>

    <!-- ÉTAT TECHNIQUE -->
    <div class="panel" style="margin-bottom:1.3rem">
      <div class="panel-head"><h2><i class="fa-solid fa-server" style="color:var(--green-mid);margin-right:.4rem"></i> État technique</h2></div>

      <div class="status-grid">
        <div class="status-card">
          <div class="sc-label">Base de données</div>
          <div class="sc-value"><?= $dbStatus['ok'] ? '🟢' : '🔴' ?> <?= h($dbStatus['label']) ?></div>
          <?php if ($dbStatus['ms'] !== null): ?><div class="sc-sub"><?= $dbStatus['ms'] ?> ms de réponse</div><?php endif; ?>
        </div>

        <div class="status-card">
          <div class="sc-label">Stockage disque</div>
          <?php if ($diskStatus['ok']): $dPct = $diskStatus['percent']; $dIcon = $dPct >= 90 ? '🔴' : ($dPct >= 70 ? '🟡' : '🟢'); ?>
          <div class="sc-value"><?= $dIcon ?> <?= $dPct ?>% utilisé</div>
          <div class="sc-sub"><?= $diskStatus['free_gb'] ?> Go libres / <?= $diskStatus['total_gb'] ?> Go</div>
          <?php else: ?>
          <div class="sc-value">⚪ Indisponible</div>
          <div class="sc-sub">Non exposé par cet hébergement</div>
          <?php endif; ?>
        </div>

        <div class="status-card">
          <div class="sc-label">Sessions actives</div>
          <div class="sc-value"><?= $sessStatus['ok'] ? '🟢 ' . $sessStatus['count'] : '⚪ Indisponible' ?></div>
          <div class="sc-sub">Connexions en cours (approx.)</div>
        </div>

        <div class="status-card">
          <div class="sc-label">Événements de sécurité (24h)</div>
          <div class="sc-value"><?= $secEvents24h > 0 ? '🟡' : '🟢' ?> <?= $secEvents24h ?></div>
          <div class="sc-sub">Échecs de connexion / accès refusés</div>
        </div>

        <div class="status-card">
          <div class="sc-label">CPU</div>
          <div class="sc-value"><?= $serverLoad['cpu'] !== null ? '🟢 ' . $serverLoad['cpu'] : '⚪ Indisponible' ?></div>
          <div class="sc-sub"><?= $serverLoad['cpu'] !== null ? ($serverLoad['cpu_label'] ?? '') : 'Non exposé par cet hébergement' ?></div>
        </div>

        <div class="status-card">
          <div class="sc-label">RAM</div>
          <?php if ($serverLoad['ram_percent'] !== null): $rPct = $serverLoad['ram_percent']; $rIcon = $rPct >= 90 ? '🔴' : ($rPct >= 70 ? '🟡' : '🟢'); ?>
          <div class="sc-value"><?= $rIcon ?> <?= $rPct ?>% utilisée</div>
          <?php else: ?>
          <div class="sc-value">⚪ Indisponible</div>
          <div class="sc-sub">Non exposé par cet hébergement</div>
          <?php endif; ?>
        </div>

        <div class="status-card">
          <div class="sc-label">Version de l'application</div>
          <div class="sc-value">🏷️ <?= h(APP_VERSION) ?></div>
          <div class="sc-sub"><?= $lastCodeChange ? 'Code modifié le ' . date('d/m/Y à H:i', $lastCodeChange) : '—' ?></div>
        </div>

        <div class="status-card">
          <div class="sc-label">Dernière sauvegarde</div>
          <?php if ($lastBackup): ?>
          <div class="sc-value">🟢 <?= date('d/m/Y à H:i', $lastBackup['time']) ?></div>
          <div class="sc-sub"><?= $lastBackup['count'] ?> sauvegarde(s) conservée(s) · <?= $lastBackup['size_kb'] ?> Ko</div>
          <?php else: ?>
          <div class="sc-value">🔴 Aucune sauvegarde</div>
          <div class="sc-sub">Créez-en une maintenant</div>
          <?php endif; ?>
          <form method="POST" style="margin-top:.6rem">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="backup">
            <button type="submit" class="btn-mini"><i class="fa-solid fa-floppy-disk"></i> Sauvegarder maintenant</button>
          </form>
        </div>
      </div>

      <div class="services-row">
        <div class="service-pill"><?= $dbStatus['ok'] ? '🟢' : '🔴' ?> Base de données</div>
        <div class="service-pill"><?= $uploadsWritable ? '🟢' : '🔴' ?> Stockage bulletins</div>
        <div class="service-pill"><?= $imagesWritable ? '🟢' : '🔴' ?> Stockage images</div>
        <div class="service-pill">🟢 Journal d'activité</div>
      </div>
    </div>

    <div class="dev-grid">
      <!-- JOURNAL D'ACTIVITÉ -->
      <div class="panel">
        <div class="panel-head">
          <h2><i class="fa-solid fa-clock-rotate-left" style="color:var(--green-mid);margin-right:.4rem"></i> Journal d'activité</h2>
          <div style="display:flex;align-items:center;gap:.8rem">
            <span class="auto-refresh-note" id="lastRefreshLabel">—</span>
            <button type="button" class="btn-mini refresh-btn" id="refreshLogsBtn"><i class="fa-solid fa-arrows-rotate"></i> Actualiser</button>
          </div>
        </div>
        <div id="logList" class="log-list">
          <div class="log-empty"><i class="fa-solid fa-spinner fa-spin"></i> Chargement du journal…</div>
        </div>
      </div>

      <!-- IDENTITÉ VISUELLE -->
      <div class="panel">
        <div class="panel-head"><h2><i class="fa-solid fa-palette" style="color:var(--green-mid);margin-right:.4rem"></i> Identité visuelle</h2></div>

        <div class="logo-mode-toggle">
          <button type="button" class="logo-mode-btn" id="btnModeImage"><i class="fa-solid fa-image"></i><span>Logo image</span></button>
          <button type="button" class="logo-mode-btn" id="btnModeText"><i class="fa-solid fa-font"></i><span>Nom de marque texte</span></button>
        </div>

        <div class="field" id="logoTextField" style="display:none">
          <label>Texte du logo</label>
          <input type="text" id="logoTextInput" placeholder="OAK International School" maxlength="60">
        </div>

        <div class="field"><label>Aperçu en direct</label></div>
        <div class="logo-preview" id="logoPreview"></div>

        <button type="button" class="btn-admin btn-admin-primary" id="saveLogoBtn" style="width:100%;justify-content:center">
          <i class="fa-solid fa-check"></i> Enregistrer
        </button>
        <div class="save-status" id="saveStatus"></div>
      </div>
    </div>
  </main>
</div>

<script>
(function () {
  /* ── Thème (système / clair / sombre) ─────────────────────
     Par défaut on suit prefers-color-scheme ; un choix explicite est
     mémorisé dans localStorage et posé en attribut data-theme sur <html>. */
  const themeSwitch = document.getElementById('themeSwitch');
  const themeButtons = [...themeSwitch.querySelectorAll('button')];

  function currentPref() {
    try { return localStorage.getItem('devDashboardTheme') || 'system'; } catch (e) { return 'system'; }
  }

  function applyTheme(pref) {
    if (pref === 'system') {
      document.documentElement.removeAttribute('data-theme');
    } else {
      document.documentElement.setAttribute('data-theme', pref);
    }
    themeButtons.forEach(b => b.classList.toggle('active', b.dataset.themeChoice === pref));
  }

  themeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const pref = btn.dataset.themeChoice;
      try { localStorage.setItem('devDashboardTheme', pref); } catch (e) {}
      applyTheme(pref);
    });
  });

  applyTheme(currentPref());

  /* ── Journal d'activité ────────────────────────────────── */
  const logList = document.getElementById('logList');
  const refreshBtn = document.getElementById('refreshLogsBtn');
  const lastRefreshLabel = document.getElementById('lastRefreshLabel');

  const actionLabels = {
    connexion_reussie: 'Connexion réussie',
    connexion_echouee: 'Échec de connexion',
    inscription: 'Pré-inscription reçue',
    role_modifie: 'Rôle modifié',
    statut_modifie: 'Statut de compte modifié',
    categorie_creee: 'Catégorie/service créé',
    categorie_modifiee: 'Catégorie/service modifié',
    reglages_modifies: 'Réglages du site modifiés',
    message_recu: 'Message client reçu',
    message_traite: 'Réponse à un message client',
    tentative_non_autorisee: 'Tentative non autorisée',
    compte_cree: 'Compte administrateur créé',
    compte_supprime: 'Compte administrateur supprimé',
    mot_de_passe_reinitialise: 'Mot de passe réinitialisé',
    mot_de_passe_modifie: 'Mot de passe modifié',
    connexion_en_tant_que: "Connexion en tant qu'un autre compte",
    fin_connexion_en_tant_que: 'Retour au compte Développeur',
    sauvegarde_creee: 'Sauvegarde de la base créée',
    permissions_modifiees: "Permissions d'un compte modifiées",
    session_deconnectee: 'Session déconnectée à distance',
  };

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function renderLogs(logs) {
    if (!logs.length) {
      logList.innerHTML = '<div class="log-empty"><i class="fa-solid fa-inbox"></i><br>Aucun événement pour le moment.</div>';
      return;
    }
    logList.innerHTML = logs.map(l => `
      <div class="log-row ${l.sensitive ? 'sensitive' : ''}">
        <div class="log-ic"><i class="fa-solid ${l.icon}"></i></div>
        <div class="log-main">
          <div class="log-top">
            <span class="log-actor">${escapeHtml(l.actor_name || 'Visiteur / système')}</span>
            ${l.actor_role ? `<span class="log-role">${escapeHtml(l.actor_role)}</span>` : ''}
            ${l.sensitive ? '<span class="badge-sensitive">Sensible</span>' : ''}
          </div>
          <div class="log-action">${escapeHtml(actionLabels[l.action] || l.action)}</div>
          ${l.details ? `<div class="log-details">${escapeHtml(l.details)}</div>` : ''}
          <div class="log-meta">
            <span><i class="fa-regular fa-clock"></i>${escapeHtml(l.created_at)}</span>
            ${l.ip_address ? `<span><i class="fa-solid fa-location-crosshairs"></i>${escapeHtml(l.ip_address)}</span>` : ''}
          </div>
        </div>
      </div>
    `).join('');
  }

  async function loadLogs(manual) {
    if (manual) refreshBtn.classList.add('spinning');
    try {
      const res = await fetch('api-activity-logs.php', { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      renderLogs(data.logs || []);
      lastRefreshLabel.textContent = 'Actualisé à ' + new Date().toLocaleTimeString('fr-FR');
    } catch (e) {
      logList.innerHTML = '<div class="log-empty"><i class="fa-solid fa-triangle-exclamation"></i><br>Impossible de charger le journal.</div>';
    } finally {
      if (manual) refreshBtn.classList.remove('spinning');
    }
  }

  refreshBtn.addEventListener('click', () => loadLogs(true));
  loadLogs(false);
  setInterval(() => loadLogs(false), 15000);

  /* ── Identité visuelle (logo) ─────────────────────────────
     L'aperçu reflète TOUJOURS la saisie non enregistrée, pas la valeur
     déjà sauvegardée en base — d'où l'état local `draft` distinct des
     réglages chargés au démarrage. */
  const initial = {
    logo_mode: <?= json_encode($siteSettings['logo_mode']) ?>,
    logo_text: <?= json_encode($siteSettings['logo_text']) ?>,
  };
  let draft = { ...initial };

  const btnModeImage = document.getElementById('btnModeImage');
  const btnModeText = document.getElementById('btnModeText');
  const logoTextField = document.getElementById('logoTextField');
  const logoTextInput = document.getElementById('logoTextInput');
  const logoPreview = document.getElementById('logoPreview');
  const saveBtn = document.getElementById('saveLogoBtn');
  const saveStatus = document.getElementById('saveStatus');

  logoTextInput.value = draft.logo_text;

  function renderPreview() {
    btnModeImage.classList.toggle('active', draft.logo_mode === 'image');
    btnModeText.classList.toggle('active', draft.logo_mode === 'text');
    logoTextField.style.display = draft.logo_mode === 'text' ? '' : 'none';

    if (draft.logo_mode === 'text') {
      logoPreview.innerHTML = `<span class="brand-logo-text-dynamic">${escapeHtml(draft.logo_text || 'OAK International School')}</span>`;
    } else {
      logoPreview.innerHTML = `<img src="images/romaric.jpeg" alt="${escapeHtml(draft.logo_text || 'OAK International School')}">`;
    }

    const dirty = draft.logo_mode !== initial.logo_mode || draft.logo_text !== initial.logo_text;
    saveBtn.disabled = !dirty;
  }

  btnModeImage.addEventListener('click', () => { draft.logo_mode = 'image'; renderPreview(); });
  btnModeText.addEventListener('click', () => { draft.logo_mode = 'text'; renderPreview(); });
  logoTextInput.addEventListener('input', () => { draft.logo_text = logoTextInput.value; renderPreview(); });

  saveBtn.addEventListener('click', async () => {
    saveBtn.disabled = true;
    saveStatus.className = 'save-status';
    saveStatus.textContent = 'Enregistrement…';
    try {
      const res = await fetch('api-settings.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ logo_mode: draft.logo_mode, logo_text: draft.logo_text }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Erreur serveur');

      initial.logo_mode = data.settings.logo_mode;
      initial.logo_text = data.settings.logo_text;
      draft = { ...initial };
      logoTextInput.value = draft.logo_text;
      renderPreview();

      // Invalide le cache client du logo (assets/logo.js) — sur les autres
      // écrans/pages ouverts dans ce navigateur, sans rechargement de page.
      try {
        localStorage.setItem('siteLogoSettings', JSON.stringify({ ...initial, cachedAt: Date.now() }));
      } catch (e) {}

      // Met à jour immédiatement le logo déjà visible dans la sidebar de cet
      // écran, sans recharger la page.
      document.querySelectorAll('.sidebar-brand img, .sidebar-brand .brand-logo-text-dynamic').forEach(el => el.remove());
      const brand = document.querySelector('.sidebar-brand');
      if (brand) {
        const holder = document.createElement('div');
        holder.innerHTML = initial.logo_mode === 'text'
          ? `<span class="brand-logo-text-dynamic">${escapeHtml(initial.logo_text)}</span>`
          : `<img src="images/romaric.jpeg" alt="${escapeHtml(initial.logo_text)}">`;
        brand.prepend(holder.firstChild);
      }

      saveStatus.className = 'save-status ok';
      saveStatus.textContent = 'Enregistré et appliqué.';
      loadLogs(false);
    } catch (e) {
      saveStatus.className = 'save-status err';
      saveStatus.textContent = 'Erreur : ' + e.message;
      saveBtn.disabled = false;
    }
  });

  renderPreview();
})();
</script>

</body>
</html>

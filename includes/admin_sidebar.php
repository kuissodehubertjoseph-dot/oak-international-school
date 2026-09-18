<?php
/**
 * Sidebar admin partagée par les écrans de contenu (admin_laureats.php, etc).
 * Attend une variable $activePage (ex. 'laureats') définie avant l'include.
 */
require_once __DIR__ . '/site_settings.php';

$admin = current_admin($pdo);
$siteSettings = get_site_settings($pdo);

function initials2($a, $b = '') {
    $s = trim(mb_substr($a, 0, 1) . mb_substr($b, 0, 1));
    return mb_strtoupper($s ?: '?');
}
$navItems = [
    'laureats' => ['admin_laureats.php', 'fa-trophy', 'Lauréats & Résultats'],
    'staff'    => ['admin_staff.php', 'fa-user-tie', 'Équipe administrative'],
    'galerie'  => ['admin_galerie.php', 'fa-images', 'Galerie photos'],
    'stats'    => ['admin_stats.php', 'fa-chart-simple', 'Chiffres clés'],
    'contact'  => ['admin_contact.php', 'fa-address-card', 'Infos de contact'],
    'cycles'   => ['admin_cycles.php', 'fa-layer-group', 'Cycles & matières'],
    'internat' => ['admin_internat.php', 'fa-bed', 'Internat'],
];
?>
  <?php if (!empty($_SESSION['impersonator_id'])): ?>
  <div class="impersonate-banner">
    <i class="fa-solid fa-user-secret"></i>
    Vous naviguez actuellement en tant que <strong><?= h($admin['nom'] ?: $admin['email']) ?></strong> (<?= h($admin['role']) ?>)
    <a href="impersonate_stop.php"><i class="fa-solid fa-right-from-bracket"></i> Revenir à mon compte Développeur</a>
  </div>
  <?php endif; ?>
  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <?= render_brand_logo($siteSettings) ?>
      <div><div class="name">St Romaric</div><div class="tag">Administration</div></div>
    </div>

    <div class="sidebar-section-label">Principal</div>
    <nav class="admin-nav">
      <a href="admin.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"><span class="ic"><i class="fa-solid fa-gauge"></i></span> Tableau de bord</a>
      <?php if (has_permission($admin, 'preinscriptions')): ?>
      <a href="admin_preinscriptions.php" class="<?= $activePage === 'preinscriptions' ? 'active' : '' ?>"><span class="ic"><i class="fa-solid fa-file-pen"></i></span> Pré-inscriptions</a>
      <?php endif; ?>
      <?php if (has_permission($admin, 'messages')): ?>
      <a href="admin_messages.php" class="<?= $activePage === 'messages' ? 'active' : '' ?>"><span class="ic"><i class="fa-solid fa-envelope"></i></span> Messages contact</a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-section-label">Contenu du site</div>
    <nav class="admin-nav">
      <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
      <a href="<?= h($href) ?>" class="<?= $key === $activePage ? 'active' : '' ?>">
        <span class="ic"><i class="fa-solid <?= h($icon) ?>"></i></span> <?= h($label) ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <?php if (($admin['role'] ?? '') !== 'secretaire'): ?>
    <div class="sidebar-section-label">Administration</div>
    <nav class="admin-nav">
      <a href="admin_users.php" class="<?= $activePage === 'users' ? 'active' : '' ?>"><span class="ic"><i class="fa-solid fa-users-gear"></i></span> Utilisateurs & Rôles</a>
      <?php if (!empty($admin['is_super_admin'])): ?>
      <a href="admin_dev.php" class="<?= $activePage === 'dev' ? 'active' : '' ?>"><span class="ic"><i class="fa-solid fa-code"></i></span> Développeur</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

    <div class="sidebar-section-label">Site</div>
    <nav class="admin-nav">
      <a href="index.html" target="_blank"><span class="ic"><i class="fa-solid fa-arrow-up-right-from-square"></i></span> Voir le site public</a>
    </nav>

    <div class="sidebar-footer">
      <hr class="sidebar-divider">
      <div class="sidebar-user">
        <div class="avatar"><?= initials2($admin['email']) ?></div>
        <div class="who">
          <span class="email"><?= h($admin['email']) ?></span>
          <span class="role"><?= !empty($admin['is_super_admin']) ? 'Super-administrateur' : h($admin['role'] ?: 'Administrateur') ?></span>
        </div>
        <a href="logout.php" class="logout" title="Déconnexion"><i class="fa-solid fa-right-from-bracket"></i></a>
      </div>
    </div>
  </aside>

<?php
require_once __DIR__ . '/render.php';

/**
 * Remplace le contenu entre <!-- CMS:$marker:START --> et <!-- CMS:$marker:END -->
 * dans $file par $html. Ne fait rien si les marqueurs sont absents (fichier non
 * préparé pour ce bloc) — retourne false dans ce cas.
 */
function replace_block(string $file, string $marker, string $html): bool {
    if (!is_file($file)) { return false; }
    $content = file_get_contents($file);
    $start = "<!-- CMS:{$marker}:START -->";
    $end   = "<!-- CMS:{$marker}:END -->";
    $pattern = '/' . preg_quote($start, '/') . '.*?' . preg_quote($end, '/') . '/s';
    if (!preg_match($pattern, $content)) { return false; }
    $replacement = $start . "\n" . $html . "\n" . $end;
    $content = preg_replace($pattern, str_replace('$', '$$', $replacement), $content, 1);
    file_put_contents($file, $content);
    return true;
}

function site_path(string $relative): string {
    return dirname(__DIR__) . '/' . $relative;
}

function publish_laureats(PDO $pdo): array {
    $rows = $pdo->query('SELECT * FROM laureats ORDER BY annee DESC, id ASC')->fetchAll();

    $results = [];
    $results['index.html'] = replace_block(site_path('index.html'), 'LAUREATS_INDEX', render_laureats_block($rows));
    $results['a_propos.html'] = replace_block(site_path('a_propos.html'), 'LAUREATS_APROPOS', render_laureats_apropos_block($rows));

    $featured = null;
    foreach ($rows as $r) { if ((int) $r['featured'] === 1) { $featured = $r; break; } }
    if (!$featured && $rows) { $featured = $rows[0]; }
    $grid = array_values(array_filter($rows, fn($r) => !$featured || (int) $r['id'] !== (int) $featured['id']));

    $results['resultats.html (vedette)'] = replace_block(site_path('resultats.html'), 'RESULTATS_FEATURED', render_resultat_featured($featured));
    $results['resultats.html (grille)']  = replace_block(site_path('resultats.html'), 'RESULTATS_GRID', render_resultats_grid_block($grid));

    return $results;
}

function publish_staff(PDO $pdo): array {
    $rows = $pdo->query('SELECT * FROM staff ORDER BY ordre ASC, id ASC')->fetchAll();
    $html = render_staff_block($rows);
    return ['galerie.html' => replace_block(site_path('galerie.html'), 'STAFF', $html)];
}

function publish_gallery(PDO $pdo): array {
    $rows = $pdo->query('SELECT * FROM galerie_photos ORDER BY ordre ASC, id ASC')->fetchAll();
    $html = render_gallery_block($rows);
    return ['galerie.html' => replace_block(site_path('galerie.html'), 'GALERIE_PHOTOS', $html)];
}

function publish_stats(PDO $pdo): array {
    $rows = $pdo->query('SELECT * FROM stats ORDER BY ordre ASC, id ASC')->fetchAll();
    $html = render_stats_block($rows);
    $results = [];
    foreach (['index.html' => 'STATS_INDEX', 'a_propos.html' => 'STATS_APROPOS'] as $file => $marker) {
        $results[$file] = replace_block(site_path($file), $marker, $html);
    }
    return $results;
}

function publish_contact(PDO $pdo): array {
    $stmt = $pdo->query('SELECT * FROM contact_info WHERE id = 1');
    $c = $stmt->fetch();
    if (!$c) { return []; }

    $results = [];
    $footerHtml = render_footer_contact($c);
    foreach (glob(site_path('*.html')) as $file) {
        $name = basename($file);
        $results[$name] = replace_block($file, 'FOOTER_CONTACT', $footerHtml);
    }

    $results['contact.html (cartes)'] = replace_block(site_path('contact.html'), 'CONTACT_QUICK_CARDS', render_contact_quick_cards($c));
    $results['contact.html (liste)']  = replace_block(site_path('contact.html'), 'CONTACT_INFO_LIST', render_contact_info_list($c));

    return $results;
}

function publish_cycles(PDO $pdo): array {
    $rows = $pdo->query('SELECT * FROM cycles_niveaux ORDER BY ordre ASC, id ASC')->fetchAll();
    $primaire = array_filter($rows, fn($n) => (int) $n['ordre'] <= 8);
    $college  = array_filter($rows, fn($n) => (int) $n['ordre'] > 8);
    return [
        'primaire' => replace_block(site_path('cycles.html'), 'CYCLES_PRIMAIRE', render_cycles_block($primaire)),
        'college'  => replace_block(site_path('cycles.html'), 'CYCLES_COLLEGE', render_cycles_block($college)),
    ];
}

function publish_internat(PDO $pdo): array {
    $comprend = $pdo->query("SELECT * FROM internat_items WHERE section = 'comprend' ORDER BY ordre ASC, id ASC")->fetchAll();
    $inclus   = $pdo->query("SELECT * FROM internat_items WHERE section = 'inclus' ORDER BY ordre ASC, id ASC")->fetchAll();
    $horaires = $pdo->query('SELECT * FROM internat_horaires ORDER BY ordre ASC, id ASC')->fetchAll();

    return [
        'comprend' => replace_block(site_path('internat.html'), 'INTERNAT_COMPREND', render_internat_comprend_block($comprend)),
        'inclus'   => replace_block(site_path('internat.html'), 'INTERNAT_INCLUS', render_internat_inclus_block($inclus)),
        'horaires' => replace_block(site_path('internat.html'), 'INTERNAT_HORAIRES', render_internat_horaires_block($horaires)),
    ];
}

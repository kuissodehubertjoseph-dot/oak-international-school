<?php
/**
 * Fonctions de rendu — transforment des lignes de la base en fragments HTML
 * identiques à ceux déjà présents dans les pages publiques. Utilisées
 * uniquement par includes/publish.php.
 */

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
}

/** Carte lauréat façon "temoignage-card" (index.html — tableau d'honneur). */
function render_laureat_card(array $l): string {
    return '<div class="col-md-4 reveal">
        <div class="temoignage-card text-center position-relative">
          <img src="' . h($l['photo']) . '" alt="' . h($l['nom']) . '" class="rounded-circle mx-auto mb-3" style="width:88px;height:88px;object-fit:cover;object-position:center 35%;border:3px solid var(--green-light);box-shadow:0 4px 12px rgba(26,107,60,.2)">
          <h4 style="font-size:1rem;color:var(--green);margin-bottom:.2rem">' . h($l['nom']) . '</h4>
          <p style="font-size:.8rem;color:var(--slate-light);margin:.1rem 0 1rem">' . h($l['classe']) . ' · <strong>' . h($l['examen']) . '</strong> ' . h($l['annee']) . '</p>
          <div class="d-flex justify-content-center gap-4 pt-3" style="border-top:1px solid var(--border)">
            <div>
              <span style="font-family:var(--font-num);font-size:1.7rem;font-weight:700;color:var(--green);display:block;line-height:1">' . h($l['moyenne']) . '</span>
              <span style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--slate-light)">Moyenne</span>
            </div>
            <div style="border-left:1px solid var(--border);padding-left:1rem">
              <span style="font-family:var(--font-num);font-size:1.7rem;font-weight:700;color:var(--green-light);display:block;line-height:1">' . h($l['rang']) . '</span>
              <span style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--slate-light)">Rang national</span>
            </div>
          </div>
        </div>
      </div>';
}

function render_laureats_block(array $rows): string {
    return implode("\n", array_map('render_laureat_card', $rows));
}

/** Carte lauréat façon "temoignage-card" (a_propos.html — avec lien "voir tous"). */
function render_laureat_card_apropos(array $l): string {
    return '<div class="col-md-4 reveal">
        <div class="temoignage-card text-center">
          <img src="' . h($l['photo']) . '"
               alt="' . h($l['nom']) . '"
               class="rounded-circle mx-auto mb-3"
               style="width:80px;height:80px;object-fit:cover;object-position:center 35%;border:3px solid var(--green-light)" />
          <h4 style="font-size:1rem;color:var(--green)">
            ' . h($l['nom']) . '          </h4>
          <p style="font-size:.8rem;color:var(--slate-light);margin:.2rem 0 .8rem">
            ' . h($l['classe']) . ' · ' . h($l['examen']) . ' ' . h($l['annee']) . '          </p>
          <div class="d-flex justify-content-center gap-3 mb-2">
            <div class="text-center">
              <span style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:var(--green);display:block;line-height:1">
                ' . h($l['moyenne']) . '              </span>
              <span style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--slate-light)">Moyenne</span>
            </div>
            <div class="text-center" style="border-left:1px solid var(--border);padding-left:1rem">
              <span style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:var(--green-light);display:block;line-height:1">
                ' . h($l['rang']) . '              </span>
              <span style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--slate-light)">Rang national</span>
            </div>
          </div>
          <a href="resultats.html" class="actu-link mt-2">
            Voir tous les lauréats <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>';
}

function render_laureats_apropos_block(array $rows): string {
    return implode("\n", array_map('render_laureat_card_apropos', $rows));
}

/** Carte vedette (resultats.html — un seul lauréat, le mieux classé). */
function render_resultat_featured(?array $l): string {
    if (!$l) { return ''; }
    $sousTitre = h($l['classe']);
    if (!empty($l['serie'])) { $sousTitre .= ' — ' . h($l['serie']); }
    return '<div class="actu-featured mb-4 reveal">
      <div class="row g-0 align-items-center">
        <div class="col-md-4">
          <img src="' . h($l['photo']) . '"
               alt="' . h($l['nom']) . '"
               class="actu-featured-img" style="aspect-ratio:1/1" />
        </div>
        <div class="col-md-8">
          <div class="actu-featured-body">
            <div style="display:inline-flex;align-items:center;gap:.5rem;
                        background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
                        border-radius:100px;padding:.3rem 1rem;font-size:.72rem;font-weight:600;
                        letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.9);
                        margin-bottom:1rem">
              <i class="fa-solid fa-trophy" style="color:#A8E6C0"></i>
              Meilleur résultat · ' . h($l['examen']) . ' ' . h($l['annee']) . '            </div>
            <h2>' . h($l['nom']) . '</h2>
            <p style="color:rgba(255,255,255,.8);font-size:.95rem">
              ' . $sousTitre . '            </p>
            <div class="d-flex flex-wrap gap-4 mt-3">
              <div>
                <div style="font-family:var(--font-display);font-size:2.5rem;font-weight:900;color:#A8E6C0;line-height:1">
                  ' . h($l['moyenne']) . '                </div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.6)">Moyenne générale</div>
              </div>
              <div>
                <div style="font-family:var(--font-display);font-size:2.5rem;font-weight:900;color:rgba(255,255,255,.9);line-height:1">
                  ' . h($l['rang_departemental']) . '                </div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.6)">Rang départemental</div>
              </div>
              <div>
                <div style="font-family:var(--font-display);font-size:2.5rem;font-weight:900;color:rgba(255,255,255,.9);line-height:1">
                  ' . h($l['rang']) . '                </div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.6)">Rang national</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>';
}

/** Carte grille (resultats.html — tous les lauréats sauf la vedette). */
function render_resultat_grid_card(array $l): string {
    return '<div class="col-sm-6 col-lg-4 reveal">
        <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);
                    overflow:hidden;box-shadow:var(--shadow-sm);transition:transform var(--transition),box-shadow var(--transition)"
             onmouseover="this.style.transform=\'translateY(-4px)\';this.style.boxShadow=\'var(--shadow-md)\'"
             onmouseout="this.style.transform=\'\';this.style.boxShadow=\'var(--shadow-sm)\'">

          <div style="aspect-ratio:3/2;overflow:hidden;position:relative">
            <img src="' . h($l['photo']) . '"
                 alt="' . h($l['nom']) . '"
                 style="width:100%;height:100%;object-fit:cover;object-position:center 35%;transition:transform .4s"
                 onmouseover="this.style.transform=\'scale(1.04)\'"
                 onmouseout="this.style.transform=\'\'" />
            <div style="position:absolute;top:.75rem;right:.75rem;
                        background:var(--green);color:#fff;
                        font-size:.65rem;font-weight:700;letter-spacing:.08em;
                        text-transform:uppercase;padding:.25rem .7rem;border-radius:100px">
              ' . h($l['examen']) . ' ' . h($l['annee']) . '            </div>
          </div>

          <div style="padding:1.25rem">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span style="font-size:1.2rem">' . h($l['medaille']) . '</span>
              <span style="font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--green-light)">
                ' . h($l['classe']) . '              </span>
            </div>

            <h4 style="font-size:.98rem;color:var(--green);margin-bottom:.4rem">
              ' . h($l['nom']) . '            </h4>

            <div class="d-flex gap-3 mt-2 pt-2" style="border-top:1px solid var(--border)">
              <div>
                <span style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;color:var(--green);display:block;line-height:1">
                  ' . h($l['moyenne']) . '                </span>
                <span style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--slate-light)">Moy.</span>
              </div>
              <div style="border-left:1px solid var(--border);padding-left:.75rem">
                <span style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;color:var(--green-light);display:block;line-height:1">
                  ' . h($l['rang_departemental']) . '                </span>
                <span style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--slate-light)">Rang Dép.</span>
              </div>
              <div style="border-left:1px solid var(--border);padding-left:.75rem">
                <span style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;color:var(--slate-light);display:block;line-height:1">
                  ' . h($l['rang']) . '                </span>
                <span style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--slate-light)">Rang Nat.</span>
              </div>
            </div>
          </div>
        </div>
      </div>';
}

function render_resultats_grid_block(array $rows): string {
    return implode("\n", array_map('render_resultat_grid_card', $rows));
}

/** Carte membre du personnel (galerie.html). */
function render_staff_card(array $s): string {
    if (!empty($s['photo'])) {
        $media = '<img src="' . h($s['photo']) . '" alt="' . h($s['role']) . '"
               class="rounded-circle mx-auto mb-3"
               style="width:120px;height:120px;object-fit:cover;object-position:center 25%;border:3px solid var(--green-light);box-shadow:var(--shadow-md)">';
    } else {
        $media = '<div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
               style="width:120px;height:120px;background:var(--green-pale);border:3px solid var(--green-light);box-shadow:var(--shadow-md);color:var(--green);font-size:2.2rem">
            <i class="fa-solid fa-user"></i>
          </div>';
    }
    return '<div class="staff-item">
          ' . $media . '
          <h4 style="font-size:.95rem;color:var(--green);margin-bottom:.15rem">' . h($s['nom']) . '</h4>
          <p style="font-size:.78rem;color:var(--slate-light);margin:0">' . h($s['role']) . '</p>
      </div>';
}

function render_staff_block(array $rows): string {
    return implode("\n", array_map('render_staff_card', $rows));
}

/** Vignette galerie (galerie.html masonry). */
function render_photo_item(array $p): string {
    $title = h($p['titre']);
    return '<div class="masonry-item" data-src="' . h($p['image']) . '" data-title="' . $title . '" onclick="openLightbox(this)">
        <img src="' . h($p['image']) . '" alt="' . $title . '" loading="lazy" />
        <div class="masonry-overlay"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
        <div style="position:absolute;bottom:.75rem;left:.75rem;
                    background:var(--green);color:#fff;
                    font-size:.65rem;font-weight:600;letter-spacing:.08em;
                    text-transform:uppercase;padding:.2rem .65rem;border-radius:100px">
          ' . $title . '        </div>
      </div>';
}

function render_gallery_block(array $rows): string {
    return implode("\n", array_map('render_photo_item', $rows));
}

/** Bande de chiffres clés (index.html, a_propos.html). */
function render_stat_card(array $s): string {
    return '<div class="col">
        <div class="stat-card-band">
          <span class="num" data-target="' . h($s['valeur']) . '" data-suffix="' . h($s['suffixe']) . '">' . h($s['valeur']) . h($s['suffixe']) . '</span>
          <span class="label">' . h($s['label']) . '</span>
        </div>
      </div>';
}

function render_stats_block(array $rows): string {
    return implode("\n      ", array_map('render_stat_card', $rows));
}

/** Bloc footer "Nous contacter" (répété sur toutes les pages). */
function render_footer_contact(array $c): string {
    $tel1Href = 'tel:+' . preg_replace('/\D/', '', $c['telephone1']);
    $tel2Href = 'tel:+' . preg_replace('/\D/', '', $c['telephone2']);
    return '<div class="footer-contact-item">
          <div class="footer-contact-icon"><i class="fa-solid fa-location-dot"></i></div>
          <div class="footer-contact-text">
            ' . h($c['adresse_ligne1']) . '<br>' . h($c['adresse_ligne2']) . '
          </div>
        </div>

        <div class="footer-contact-item">
          <div class="footer-contact-icon"><i class="fa-solid fa-phone"></i></div>
          <div class="footer-contact-text">
            <a href="' . h($tel1Href) . '">' . h($c['telephone1']) . '</a><br>
            <a href="' . h($tel2Href) . '">' . h($c['telephone2']) . '</a>
          </div>
        </div>

        <div class="footer-contact-item">
          <div class="footer-contact-icon"><i class="fa-regular fa-clock"></i></div>
          <div class="footer-contact-text">
            ' . h($c['horaires_semaine']) . '<br>' . h($c['horaires_samedi']) . '
          </div>
        </div>';
}

/** Cartes infos rapides (contact.html). */
function render_contact_quick_cards(array $c): string {
    $tel1Href = 'tel:+' . preg_replace('/\D/', '', $c['telephone1']);
    return '<div class="col-sm-6 col-lg-4 reveal">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;text-align:center;height:100%;box-shadow:var(--shadow-sm)">
          <div style="width:52px;height:52px;background:var(--green-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.1rem;color:var(--green)">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <div style="font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--green-light);margin-bottom:.4rem">Adresse</div>
          <a href="#map" style="font-size:.88rem;color:var(--slate);line-height:1.6">' . h($c['adresse_ligne1']) . '<br>' . h($c['adresse_ligne2']) . '</a>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4 reveal">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;text-align:center;height:100%;box-shadow:var(--shadow-sm)">
          <div style="width:52px;height:52px;background:var(--green-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.1rem;color:var(--green)">
            <i class="fa-solid fa-phone"></i>
          </div>
          <div style="font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--green-light);margin-bottom:.4rem">Téléphone</div>
          <a href="' . h($tel1Href) . '" style="font-size:.88rem;color:var(--slate);line-height:1.6">' . h($c['telephone1']) . '<br>' . h($c['telephone2']) . '</a>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4 reveal">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;text-align:center;height:100%;box-shadow:var(--shadow-sm)">
          <div style="width:52px;height:52px;background:var(--green-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.1rem;color:var(--green)">
            <i class="fa-solid fa-clock"></i>
          </div>
          <div style="font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--green-light);margin-bottom:.4rem">Heures d\'ouverture</div>
          <p style="font-size:.88rem;color:var(--slate);line-height:1.6;margin:0">' . h($c['horaires_semaine']) . '<br>' . h($c['horaires_samedi']) . '</p>
        </div>
      </div>';
}

/** Liste "Informations pratiques" (contact.html, colonne gauche). */
function render_contact_info_list(array $c): string {
    $tel1Href = 'tel:+' . preg_replace('/\D/', '', $c['telephone1']);
    $tel2Href = 'tel:+' . preg_replace('/\D/', '', $c['telephone2']);
    return '<div class="contact-info-item">
            <div class="contact-info-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <div class="contact-info-label">Adresse</div>
              <div class="contact-info-value">' . h($c['adresse_ligne1']) . '<br>' . h($c['adresse_ligne2']) . '</div>
            </div>
          </div>

          <div class="contact-info-item">
            <div class="contact-info-icon"><i class="fa-solid fa-phone"></i></div>
            <div>
              <div class="contact-info-label">Téléphone</div>
              <div class="contact-info-value">
                <a href="' . h($tel1Href) . '">' . h($c['telephone1']) . '</a><br>
                <a href="' . h($tel2Href) . '">' . h($c['telephone2']) . '</a>
              </div>
            </div>
          </div>

          <div class="contact-info-item">
            <div class="contact-info-icon"><i class="fa-solid fa-clock"></i></div>
            <div>
              <div class="contact-info-label">Heures d\'accueil</div>
              <div class="contact-info-value">' . h($c['horaires_semaine']) . '<br>' . h($c['horaires_samedi']) . '</div>
            </div>
          </div>';
}

/** Fiche niveau (cycles.html). */
function render_niveau_card(array $n, int $index): string {
    $badge = '';
    $colorMap = [
        'Série A' => ['#ede9fe', '#7c3aed'],
        'Série B' => ['#fef3c7', '#d97706'],
        'Série C' => ['var(--green-pale)', 'var(--green)'],
        'Série D' => ['#ccfbf1', '#0d9488'],
    ];
    if (!empty($n['badge_serie']) && isset($colorMap[$n['badge_serie']])) {
        [$bg, $fg] = $colorMap[$n['badge_serie']];
        $badge = '<span style="font-size:.68rem;background:' . $bg . ';color:' . $fg . ';padding:.15rem .55rem;border-radius:100px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;vertical-align:middle;margin-left:.4rem">' . h($n['badge_serie']) . '</span>';
    }
    $matieres = array_filter(array_map('trim', explode("\n", (string) $n['matieres'])));
    $matieresHtml = implode("\n            ", array_map(
        fn($m) => '<div class="matiere-tag"><i class="fa-solid fa-circle-dot"></i>' . h($m) . '</div>',
        $matieres
    ));
    $id = 'card-niveau-' . (int) $n['id'];
    return '<div class="cycle-card reveal" id="' . $id . '">
        <button class="cycle-header" onclick="toggleCycle(this)">
          <div class="cycle-icon"><i class="' . h($n['icone']) . '"></i></div>
          <div style="flex:1;text-align:left">
            <h3>' . h($n['nom']) . ' <span style="font-weight:400;font-size:.85em;color:var(--slate-light)">— ' . h($n['age']) . '</span>' . $badge . '</h3>
            <div class="cycle-sub">' . h($n['resume']) . '</div>
          </div>
          <i class="fa-solid fa-chevron-down cycle-chevron"></i>
        </button>
        <div class="cycle-body">
          <p style="font-size:.88rem;color:var(--slate);margin-bottom:1rem">' . h($n['resume']) . '</p>
          <h5 style="font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--green-light);margin-bottom:.5rem">Matières enseignées</h5>
          <div class="matieres-grid">
            ' . $matieresHtml . '
          </div>
        </div>
      </div>';
}

function render_cycles_block(array $rows): string {
    $out = [];
    foreach ($rows as $i => $n) { $out[] = render_niveau_card($n, $i); }
    return implode("\n", $out);
}

/** Carte "Ce que comprend l'internat". */
function render_internat_comprend_card(array $i): string {
    return '<div class="col-lg-4 col-md-6 reveal">
        <div class="programme-card">
          <div class="programme-body" style="padding-top:1.8rem">
            <div class="value-icon mb-3"><i class="' . h($i['icone']) . '"></i></div>
            <h3>' . h($i['titre']) . '</h3>
            <p>' . h($i['description']) . '</p>
          </div>
        </div>
      </div>';
}

function render_internat_comprend_block(array $rows): string {
    return implode("\n", array_map('render_internat_comprend_card', $rows));
}

/** Item "Inclus dans les frais". */
function render_internat_inclus_item(array $i): string {
    return '<li class="vie-list-item" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.14)">
              <span class="icon" style="color:var(--green-light)"><i class="' . h($i['icone']) . '"></i></span>
              <div><h4 style="color:#fff">' . h($i['titre']) . '</h4><p style="color:rgba(255,255,255,.65)">' . h($i['description']) . '</p></div>
            </li>';
}

function render_internat_inclus_block(array $rows): string {
    return implode("\n", array_map('render_internat_inclus_item', $rows));
}

/** Créneau de l'emploi du temps type. */
function render_internat_horaire_item(array $h): string {
    return '<div class="col-6 col-md-3 reveal">
        <div class="vie-list-item" style="height:100%">
          <span class="icon" style="color:var(--green-light)"><i class="' . h($h['icone']) . '"></i></span>
          <div><h4>' . h($h['heure']) . '</h4><p>' . h($h['description']) . '</p></div>
        </div>
      </div>';
}

function render_internat_horaires_block(array $rows): string {
    return implode("\n", array_map('render_internat_horaire_item', $rows));
}

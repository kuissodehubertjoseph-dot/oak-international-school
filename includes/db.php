<?php
/**
 * Connexion base de données + création du schéma si besoin.
 * SQLite (fichier local) pour fonctionner sans configuration de serveur MySQL.
 * Pour migrer vers MySQL : remplacer le DSN ci-dessous par
 *   "mysql:host=localhost;dbname=leselites;charset=utf8mb4"
 * et adapter les quelques types SQLite (AUTOINCREMENT -> AUTO_INCREMENT).
 */

$dbFile = getenv('DB_PATH') ?: (__DIR__ . '/../data/leselites.sqlite');
$dbDir  = dirname($dbFile);

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$isNewDb = !file_exists($dbFile);

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec("CREATE TABLE IF NOT EXISTS admins (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    email           TEXT UNIQUE NOT NULL,
    password_hash   TEXT NOT NULL,
    nom             TEXT NOT NULL,
    role            TEXT NOT NULL DEFAULT 'admin',
    statut          TEXT NOT NULL DEFAULT 'actif',
    is_super_admin  INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
)");

/* Migration douce : ajoute les colonnes rôle/statut/super-admin si la table existait déjà sans elles. */
$adminsCols = array_column($pdo->query('PRAGMA table_info(admins)')->fetchAll(), 'name');
foreach ([
    'role'             => "TEXT NOT NULL DEFAULT 'admin'",
    'statut'           => "TEXT NOT NULL DEFAULT 'actif'",
    'is_super_admin'   => 'INTEGER NOT NULL DEFAULT 0',
    'failed_attempts'  => 'INTEGER NOT NULL DEFAULT 0',
    'locked_until'     => 'TEXT',
    'last_login_at'    => 'TEXT',
    'permissions'      => 'TEXT',
] as $col => $type) {
    if (!in_array($col, $adminsCols, true)) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN $col $type");
    }
}

/* Sessions nommées / appareils connectés — permet de voir et de forcer la
   déconnexion d'un appareil précis (voir includes/auth.php). */
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_sessions (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id      INTEGER NOT NULL,
    session_id    TEXT NOT NULL UNIQUE,
    ip_address    TEXT,
    user_agent    TEXT,
    created_at    TEXT NOT NULL DEFAULT (datetime('now')),
    last_seen_at  TEXT NOT NULL DEFAULT (datetime('now')),
    revoked       INTEGER NOT NULL DEFAULT 0
)");
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_admin_sessions_admin_id ON admin_sessions (admin_id)');

/* Garantit l'existence d'un unique compte super-admin : si aucun n'est marqué,
   le tout premier compte admin créé (le plus ancien) le devient automatiquement. */
$superAdminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins WHERE is_super_admin = 1')->fetchColumn();
if ($superAdminCount === 0) {
    $firstAdminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($firstAdminId) {
        $pdo->prepare("UPDATE admins SET is_super_admin = 1, role = 'super-admin' WHERE id = ?")->execute([$firstAdminId]);
    }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_id    INTEGER,
    actor_name  TEXT,
    actor_role  TEXT,
    action      TEXT NOT NULL,
    target_type TEXT,
    target_id   TEXT,
    details     TEXT,
    ip_address  TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
)");
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs (created_at DESC)');

$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
    id   TEXT PRIMARY KEY,
    data TEXT NOT NULL DEFAULT '{}'
)");
if ((int) $pdo->query("SELECT COUNT(*) FROM site_settings WHERE id = 'main'")->fetchColumn() === 0) {
    $pdo->prepare("INSERT INTO site_settings (id, data) VALUES ('main', ?)")
        ->execute([json_encode(['logo_mode' => 'image', 'logo_text' => 'OAK International School'], JSON_UNESCAPED_UNICODE)]);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS preinscriptions (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_eleve         TEXT NOT NULL,
    prenom_eleve      TEXT NOT NULL,
    sexe              TEXT,
    date_naissance    TEXT,
    classe_souhaitee  TEXT NOT NULL,
    nom_parent        TEXT,
    telephone_parent  TEXT NOT NULL,
    email_parent      TEXT,
    statut            TEXT NOT NULL DEFAULT 'nouveau',
    source            TEXT NOT NULL DEFAULT 'formulaire complet',
    bulletin_path     TEXT,
    bulletin_label    TEXT,
    created_at        TEXT NOT NULL DEFAULT (datetime('now'))
)");

/* Migration douce : ajoute les colonnes bulletin si la table existait déjà sans elles. */
$preinsCols = array_column($pdo->query('PRAGMA table_info(preinscriptions)')->fetchAll(), 'name');
if (!in_array('bulletin_path', $preinsCols, true)) {
    $pdo->exec('ALTER TABLE preinscriptions ADD COLUMN bulletin_path TEXT');
}
if (!in_array('bulletin_label', $preinsCols, true)) {
    $pdo->exec('ALTER TABLE preinscriptions ADD COLUMN bulletin_label TEXT');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS messages_contact (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nom         TEXT NOT NULL,
    email       TEXT NOT NULL,
    telephone   TEXT,
    sujet       TEXT NOT NULL,
    message     TEXT NOT NULL,
    statut      TEXT NOT NULL DEFAULT 'nouveau',
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
)");

/* Migration douce : ajoute les colonnes de réponse si la table existait déjà sans elles. */
$msgCols = array_column($pdo->query('PRAGMA table_info(messages_contact)')->fetchAll(), 'name');
foreach ([
    'reponse'         => 'TEXT',
    'reponse_date'    => 'TEXT',
    'repondu_par'     => 'TEXT',
] as $col => $type) {
    if (!in_array($col, $msgCols, true)) {
        $pdo->exec("ALTER TABLE messages_contact ADD COLUMN $col $type");
    }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS laureats (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    nom                TEXT NOT NULL,
    classe             TEXT NOT NULL,
    examen             TEXT NOT NULL,
    annee              INTEGER NOT NULL,
    moyenne            REAL,
    rang               TEXT,
    rang_departemental TEXT,
    serie              TEXT,
    medaille           TEXT,
    photo              TEXT,
    featured           INTEGER NOT NULL DEFAULT 0
)");

$laureatsCols = array_column($pdo->query('PRAGMA table_info(laureats)')->fetchAll(), 'name');
foreach ([
    'rang_departemental' => 'TEXT',
    'serie'              => 'TEXT',
    'featured'           => 'INTEGER NOT NULL DEFAULT 0',
] as $col => $type) {
    if (!in_array($col, $laureatsCols, true)) {
        $pdo->exec("ALTER TABLE laureats ADD COLUMN $col $type");
    }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS actualites (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    titre      TEXT NOT NULL,
    slug       TEXT UNIQUE NOT NULL,
    categorie  TEXT NOT NULL,
    extrait    TEXT NOT NULL,
    image      TEXT,
    date_pub   TEXT NOT NULL
)");

/* Seed — uniquement si les tables sont vides, pour ne jamais écraser tes données. */

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    // Le mot de passe initial vient d'une variable d'environnement (ADMIN_DEFAULT_PASSWORD) ;
    // à défaut, un mot de passe aléatoire est généré et écrit uniquement dans les logs serveur
    // (jamais dans le code source, pour ne pas exposer de secret dans un dépôt public).
    $initialPassword = getenv('ADMIN_DEFAULT_PASSWORD');
    if (!$initialPassword) {
        $initialPassword = bin2hex(random_bytes(6));
        error_log("[setup] Compte admin initial créé : admin@oisbenin.com / mot de passe : $initialPassword — changez-le immédiatement après connexion.");
    }
    $stmt = $pdo->prepare('INSERT INTO admins (email, password_hash, nom) VALUES (?, ?, ?)');
    $stmt->execute(['admin@oisbenin.com', password_hash($initialPassword, PASSWORD_DEFAULT), 'Administrateur']);
}

$laureatCount = (int) $pdo->query('SELECT COUNT(*) FROM laureats')->fetchColumn();
if ($laureatCount === 0) {
    $stmt = $pdo->prepare('INSERT INTO laureats (nom, classe, examen, annee, moyenne, rang, rang_departemental, serie, medaille, photo, featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute(['Afiwa KODJO', '3ème', 'BEPC', 2024, 18.75, '8ème', '1er', 'Série A (Littéraire)', '🥇', 'images/laureat-a89816267a91.jpg', 1]);
    $stmt->execute(['Bernice TOSSOU', 'Tle C', 'BAC', 2024, 17.92, '12ème', '3ème', null, '🥈', 'images/39-Large-2.jpeg', 0]);
} else {
    /* Complète les anciennes fiches lauréats avec les nouveaux champs, une seule fois. */
    $stmt = $pdo->prepare("UPDATE laureats SET rang_departemental=?, serie=?, featured=? WHERE nom=? AND rang_departemental IS NULL");
    $stmt->execute(['1er', 'Série A (Littéraire)', 1, 'Afiwa KODJO']);
    $stmt->execute(['3ème', null, 0, 'Bernice TOSSOU']);
    if ((int) $pdo->query('SELECT COUNT(*) FROM laureats WHERE featured = 1')->fetchColumn() === 0) {
        $pdo->exec('UPDATE laureats SET featured = 1 WHERE id = (SELECT id FROM laureats ORDER BY moyenne DESC, annee DESC LIMIT 1)');
    }
}

$actuCount = (int) $pdo->query('SELECT COUNT(*) FROM actualites')->fetchColumn();
if ($actuCount === 0) {
    $stmt = $pdo->prepare('INSERT INTO actualites (titre, slug, categorie, extrait, image, date_pub) VALUES (?,?,?,?,?,?)');
    $stmt->execute(['5 astuces pour réussir son BAC au Bénin', '5-astuces-reussir-bac-benin', 'Conseils', "Découvrez les méthodes de révision les plus efficaces...", 'images/laureat-a89816267a91.jpg', '2026-03-26']);
    $stmt->execute(['Inauguration du nouveau laboratoire', 'inauguration-labo-sciences', 'Événements', 'Les Élites investissent dans le futur...', 'images/49-Large.jpeg', '2026-03-26']);
}

/* ══════════════════════════════════════════════════════════
   CMS — contenu éditable des pages publiques
══════════════════════════════════════════════════════════ */

$pdo->exec("CREATE TABLE IF NOT EXISTS staff (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    nom    TEXT NOT NULL,
    role   TEXT NOT NULL,
    photo  TEXT,
    ordre  INTEGER NOT NULL DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS galerie_photos (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    image  TEXT NOT NULL,
    titre  TEXT NOT NULL,
    ordre  INTEGER NOT NULL DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS stats (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    cle     TEXT UNIQUE NOT NULL,
    valeur  TEXT NOT NULL,
    suffixe TEXT NOT NULL DEFAULT '',
    label   TEXT NOT NULL,
    ordre   INTEGER NOT NULL DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS contact_info (
    id                INTEGER PRIMARY KEY CHECK (id = 1),
    adresse_ligne1    TEXT NOT NULL,
    adresse_ligne2    TEXT NOT NULL,
    telephone1        TEXT NOT NULL,
    telephone2        TEXT NOT NULL,
    horaires_semaine  TEXT NOT NULL,
    horaires_samedi   TEXT NOT NULL,
    email             TEXT NOT NULL DEFAULT ''
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS cycles_niveaux (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    ordre        INTEGER NOT NULL DEFAULT 0,
    nom          TEXT NOT NULL,
    age          TEXT NOT NULL,
    icone        TEXT NOT NULL DEFAULT 'fa-solid fa-star',
    badge_serie  TEXT,
    resume       TEXT NOT NULL,
    matieres     TEXT NOT NULL DEFAULT ''
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS internat_items (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    section      TEXT NOT NULL,
    ordre        INTEGER NOT NULL DEFAULT 0,
    icone        TEXT NOT NULL DEFAULT 'fa-solid fa-check',
    titre        TEXT NOT NULL,
    description  TEXT NOT NULL DEFAULT ''
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS internat_horaires (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    ordre        INTEGER NOT NULL DEFAULT 0,
    heure        TEXT NOT NULL,
    icone        TEXT NOT NULL DEFAULT 'fa-solid fa-clock',
    description  TEXT NOT NULL DEFAULT ''
)");

/* Seed CMS — uniquement si vide, reproduit exactement le contenu déjà en ligne. */

if ((int) $pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn() === 0) {
    $stmt = $pdo->prepare('INSERT INTO staff (nom, role, photo, ordre) VALUES (?,?,?,?)');
    $stmt->execute(['M. Abraham GODONOU', 'Directeur fondateur', 'images/staff-directeur.jpg', 1]);
    $stmt->execute(['M. Gustave YEKPE', 'Directeur du Primaire', '', 2]);
    $stmt->execute(['Mme Chantal AHOUANSOU', 'Censeur / Directrice adjointe', 'images/staff-censeur.jpg', 3]);
    $stmt->execute(['M. Fidèle HOUNKPATIN', 'Surveillant général', 'images/staff-surveillant.jpg', 4]);
    $stmt->execute(['Mme Sandrine ADJAHOUI', 'Secrétaire administrative', 'images/staff-secretaire.jpg', 5]);
}

if ((int) $pdo->query('SELECT COUNT(*) FROM galerie_photos')->fetchColumn() === 0) {
    $photos = [
        ['images/6-Large-1.jpeg', "Cérémonie d'ouverture"],
        ['images/22-Large-1.jpeg', 'Bâtiment principal'],
        ['images/25-Large-1.jpeg', 'Laboratoire de sciences'],
        ['images/32-Large-1.jpeg', 'Bibliothèque numérique'],
        ['images/33-Large-2.jpeg', 'Activités sportives'],
        ['images/34-Large-2.jpeg', 'Remise de diplômes'],
        ['images/35-Large-2.jpeg', 'Cour de récréation'],
        ['images/36-Large-1.jpeg', 'Salle de classe'],
        ['images/37-Large-1.jpeg', 'Fête culturelle'],
        ['images/39-Large-2.jpeg', "Concours d'éloquence"],
        ['images/40-Large-1.jpeg', 'Visite scientifique'],
        ['images/41-Large-1.jpeg', 'Journée portes ouvertes'],
        ['images/42-Large-2.jpeg', 'Club de lecture'],
        ['images/43-Large-1.jpeg', "Séance d'arts plastiques"],
        ['images/44-Large-2.jpeg', 'Tournoi inter-classes'],
        ['images/47-Large.jpeg', 'Lauréats du BEPC'],
        ['images/49-Large.jpeg', 'Inauguration labo'],
        ['images/68-Large.jpeg', "Séance d'informatique"],
        ['images/110-Large.jpeg', 'Excursion pédagogique'],
        ['images/146-Large.jpeg', 'Récompenses académiques'],
        ['images/159-Large.jpeg', 'Remise de prix annuelle'],
    ];
    $stmt = $pdo->prepare('INSERT INTO galerie_photos (image, titre, ordre) VALUES (?,?,?)');
    foreach ($photos as $i => $p) { $stmt->execute([$p[0], $p[1], $i + 1]); }
}

if ((int) $pdo->query('SELECT COUNT(*) FROM stats')->fetchColumn() === 0) {
    $stats = [
        ['eleves',       '800', '+', 'Élèves inscrits',    1],
        ['bac',          '95',  '%', 'Réussite BAC 2025',  2],
        ['bepc',         '98',  '%', 'Réussite BEPC 2025', 3],
        ['enseignants',  '60',  '+', 'Enseignants qualifiés', 4],
        ['departements', '4',   '',  "Départements : Maternelle, Primaire, Collège, Internat", 5],
    ];
    $stmt = $pdo->prepare('INSERT INTO stats (cle, valeur, suffixe, label, ordre) VALUES (?,?,?,?,?)');
    foreach ($stats as $s) { $stmt->execute($s); }
}

if ((int) $pdo->query('SELECT COUNT(*) FROM contact_info')->fetchColumn() === 0) {
    $stmt = $pdo->prepare('INSERT INTO contact_info (id, adresse_ligne1, adresse_ligne2, telephone1, telephone2, horaires_semaine, horaires_samedi, email) VALUES (1,?,?,?,?,?,?,?)');
    $stmt->execute(['Rue Pharmaquick, en face Société LABOREX', 'Cité Vie Nouvelle, Akpakpa, Cotonou, Bénin', '(+229) 0164 931 111', '(+229) 0160 022 222', 'Lun – Ven : 7h30 – 17h30', 'Samedi : 8h00 – 13h00', 'Info@oisbenin.com']);
}

if ((int) $pdo->query('SELECT COUNT(*) FROM cycles_niveaux')->fetchColumn() === 0) {
    $niveaux = [
        [1,  'Maternelle 1', '3 ans',  'fa-solid fa-shapes',            null, 'Éveil sensoriel, motricité, premiers repères et vie en collectivité.', "Éveil sensoriel\nMotricité\nChant & Comptines\nColoriage\nJeux éducatifs"],
        [2,  'Maternelle 2', '4 ans',  'fa-solid fa-palette',           null, 'Graphisme, langage oral, formes et couleurs, autonomie.', "Graphisme\nLangage oral\nFormes & Couleurs\nChant & Comptines\nEPS"],
        [3,  'CI',           '5 ans',  'fa-solid fa-pencil',            null, "Prélecture, préécriture et préparation à l'entrée au CP.", "Prélecture\nPréécriture\nInitiation au calcul\nÉveil\nEPS"],
        [4,  'CP',           '6 ans',  'fa-solid fa-star',              null, "Apprentissage de la lecture, de l'écriture cursive et des opérations de base.", "Lecture & Écriture\nMathématiques\nDictée\nÉveil\nChant\nEPS\nDessin"],
        [5,  'CE1',          '8 ans',  'fa-solid fa-book',              null, 'Découverte des sciences, premières rédactions et introduction à l\'anglais.', "Français\nMathématiques\nAnglais\nES\nEST\nDictée\nExpression écrite\nEPS"],
        [6,  'CE2',          '9 ans',  'fa-solid fa-book',              null, 'Rédaction structurée, fractions, géométrie et anglais parlé.', "Français\nMathématiques\nAnglais\nES\nEST\nDictée\nExpression écrite\nInformatique"],
        [7,  'CM1',          '10 ans', 'fa-solid fa-compass',           null, 'Grammaire avancée, problèmes complexes, premières leçons de méthode.', "Français\nMathématiques\nAnglais\nES\nEST\nDictée\nExpression écrite\nInformatique\nEPS"],
        [8,  'CM2',          '11 ans', 'fa-solid fa-microscope',        null, "Révisions approfondies et entraînement intensif au Certificat d'Études Primaires.", "Français\nMathématiques\nAnglais\nES\nEST\nDictée\nExpression écrite\nInformatique\nEPS"],
        [9,  '6ème',         '12 ans', 'fa-solid fa-book-open',         null, 'Adaptation au cycle secondaire, méthode de travail et organisation.', "Français\nMathématiques\nSciences de la Vie\nPhysique-Chimie\nAnglais\nHistoire-Géo\nEPS\nInformatique"],
        [10, '5ème',         '13 ans', 'fa-solid fa-compass',           null, "Renforcement disciplinaire, développement de l'esprit d'analyse.", "Français\nMathématiques\nSVT\nPhysique-Chimie\nAnglais\nHistoire-Géo\nEPS"],
        [11, '4ème',         '14 ans', 'fa-solid fa-microscope',        null, 'Introduction aux notions avancées et orientation progressive.', "Français\nMathématiques\nSVT\nPhysique-Chimie\nAnglais\nAllemand\nEspagnol\nHistoire-Géo\nEPS\nInformatique"],
        [12, '3ème',         '15 ans', 'fa-solid fa-award',             null, 'Révisions intensives, examens blancs et préparation au BEPC officiel.', "Français\nMathématiques\nSVT\nPhysique-Chimie\nAnglais\nAllemand\nEspagnol\nHistoire-Géo\nEPS"],
        [13, '2nde A',       '16 ans', 'fa-solid fa-pen-nib',           'Série A', 'Tronc commun avec accent sur les lettres et sciences humaines.', "Français\nPhilosophie\nMathématiques\nAnglais\nEspagnol\nAllemand\nHistoire-Géo\nEPS"],
        [14, '2nde B',       '16 ans', 'fa-solid fa-chart-line',        'Série B', "Tronc commun avec initiation à l'économie et à la gestion.", "Économie\nPhilosophie\nMathématiques\nFrançais\nAnglais\nHistoire-Géo\nEPS"],
        [15, '2nde C',       '16 ans', 'fa-solid fa-atom',              'Série C', 'Tronc commun avec accent sur les mathématiques et la physique-chimie.', "Mathématiques\nPhysique-Chimie\nSVT\nAnglais\nFrançais\nPhilosophie\nEPS"],
        [16, '2nde D',       '16 ans', 'fa-solid fa-leaf',              'Série D', 'Tronc commun avec accent sur les sciences de la vie et de la terre.', "SVT\nMathématiques\nPhysique-Chimie\nFrançais\nAnglais\nPhilosophie\nEPS"],
        [17, '1ère A',       '17 ans', 'fa-solid fa-pen-nib',           'Série A', 'Lettres, philosophie, langues et sciences humaines.', "Français\nPhilosophie\nMathématiques\nAnglais\nEspagnol\nAllemand\nHistoire-Géo\nEPS"],
        [18, '1ère B',       '17 ans', 'fa-solid fa-chart-line',        'Série B', 'Économie, gestion, comptabilité et mathématiques appliquées.', "Économie\nPhilosophie\nMathématiques\nFrançais\nAnglais\nHistoire-Géo\nEPS"],
        [19, '1ère C',       '17 ans', 'fa-solid fa-atom',              'Série C', 'Mathématiques avancées, physique-chimie et sciences de la vie.', "Mathématiques\nPhysique-Chimie\nSVT\nAnglais\nFrançais\nPhilosophie\nEPS"],
        [20, '1ère D',       '17 ans', 'fa-solid fa-leaf',              'Série D', 'Sciences de la vie et de la terre, avec mathématiques et physique-chimie.', "Mathématiques\nPhysique-Chimie\nSVT\nFrançais\nAnglais\nPhilosophie\nEPS"],
        [21, 'Tle A',        '18 ans', 'fa-solid fa-book-bookmark',     'Série A', 'Préparation intensive au BAC série A, dissertations et oral.', "Français\nPhilosophie\nMathématiques\nAnglais\nEspagnol\nAllemand\nHistoire-Géo\nEPS"],
        [22, 'Tle B',        '18 ans', 'fa-solid fa-chart-line',        'Série B', 'Préparation intensive au BAC série B, études de cas et concours.', "Économie\nPhilosophie\nMathématiques\nFrançais\nAnglais\nHistoire-Géo\nEPS"],
        [23, 'Tle C',        '18 ans', 'fa-solid fa-flask-vial',        'Série C', 'Préparation intensive au BAC série C, TP et concours grandes écoles.', "Mathématiques\nPhysique-Chimie\nSVT\nAnglais\nFrançais\nHistoire-Géo\nEPS"],
        [24, 'Tle D',        '18 ans', 'fa-solid fa-leaf',              'Série D', 'Préparation intensive au BAC série D, TP et concours grandes écoles.', "SVT\nMathématiques\nPhysique-Chimie\nFrançais\nAnglais\nPhilosophie\nEPS"],
    ];
    $stmt = $pdo->prepare('INSERT INTO cycles_niveaux (ordre, nom, age, icone, badge_serie, resume, matieres) VALUES (?,?,?,?,?,?,?)');
    foreach ($niveaux as $n) { $stmt->execute($n); }
}

if ((int) $pdo->query("SELECT COUNT(*) FROM internat_items WHERE section = 'comprend'")->fetchColumn() === 0) {
    $comprend = [
        ['fa-solid fa-utensils', 'Restauration', 'Trois repas équilibrés par jour, préparés sur place par notre cafétéria, avec un menu adapté aux besoins des adolescents.'],
        ['fa-solid fa-kit-medical', 'Infirmerie', 'Une infirmière est disponible sur le campus ; les parents sont informés en cas de besoin médical.'],
        ['fa-solid fa-shield-halved', 'Sécurité du site', 'Enceinte clôturée, accès contrôlé et gardiennage permanent pour la tranquillité des familles.'],
        ['fa-solid fa-shirt', 'Blanchisserie', 'Service de lessive hebdomadaire inclus pour le linge personnel des internes.'],
        ['fa-solid fa-futbol', 'Loisirs encadrés', 'Sport, jeux collectifs et temps libre organisés les soirs et le week-end.'],
        ['fa-solid fa-phone-volume', 'Lien avec les familles', 'Appels autorisés le soir et sorties encadrées un week-end sur deux.'],
    ];
    $stmt = $pdo->prepare("INSERT INTO internat_items (section, ordre, icone, titre, description) VALUES ('comprend',?,?,?,?)");
    foreach ($comprend as $i => $c) { $stmt->execute([$i + 1, $c[0], $c[1], $c[2]]); }
}

if ((int) $pdo->query("SELECT COUNT(*) FROM internat_items WHERE section = 'inclus'")->fetchColumn() === 0) {
    $inclus = [
        ['fa-solid fa-bed', 'Hébergement', 'Dortoir, literie, entretien des locaux'],
        ['fa-solid fa-utensils', 'Restauration', '3 repas par jour, 7 jours sur 7'],
        ['fa-solid fa-shirt', 'Blanchisserie', 'Lessive hebdomadaire du linge personnel'],
        ['fa-solid fa-user-shield', 'Encadrement', 'Surveillants et étude du soir'],
    ];
    $stmt = $pdo->prepare("INSERT INTO internat_items (section, ordre, icone, titre, description) VALUES ('inclus',?,?,?,?)");
    foreach ($inclus as $i => $c) { $stmt->execute([$i + 1, $c[0], $c[1], $c[2]]); }
}

if ((int) $pdo->query('SELECT COUNT(*) FROM internat_horaires')->fetchColumn() === 0) {
    $horaires = [
        ['5h30 – 7h00', 'fa-solid fa-sun', 'Réveil, toilette, petit-déjeuner'],
        ['7h30 – 12h30', 'fa-solid fa-chalkboard', 'Cours du matin'],
        ['12h30 – 15h30', 'fa-solid fa-bowl-food', "Déjeuner, puis cours de l'après-midi"],
        ['16h30 – 18h30', 'fa-solid fa-futbol', 'Sport, douche, temps libre'],
        ['19h00 – 19h45', 'fa-solid fa-utensils', 'Dîner'],
        ['20h00 – 21h30', 'fa-solid fa-book-open', 'Étude du soir encadrée'],
        ['22h00', 'fa-solid fa-moon', 'Extinction des feux, coucher'],
        ['Week-ends', 'fa-solid fa-calendar-week', 'Sorties encadrées un week-end sur deux'],
    ];
    $stmt = $pdo->prepare('INSERT INTO internat_horaires (ordre, heure, icone, description) VALUES (?,?,?,?)');
    foreach ($horaires as $i => $h) { $stmt->execute([$i + 1, $h[0], $h[1], $h[2]]); }
}

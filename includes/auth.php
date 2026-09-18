<?php
if (session_status() === PHP_SESSION_NONE) {
    // Durcissement du cookie de session : inaccessible en JS, pas envoyé
    // sur des requêtes cross-site, et marqué "secure" dès qu'on est en HTTPS.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Jeton anti-CSRF : un seul par session, à inclure dans tout formulaire qui
 * modifie des données (voir csrf_field()) et vérifié par require_login()
 * sur chaque requête POST.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check(): bool {
    $submitted = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && is_string($submitted) && hash_equals($_SESSION['csrf_token'], $submitted);
}

/**
 * Vérifie la session à chaque requête : compte actif ET mot de passe
 * inchangé depuis la connexion. Si le mot de passe a été réinitialisé
 * entre-temps (par le Développeur ou le Directeur), la session en cours
 * est immédiatement invalidée — l'utilisateur est déconnecté au prochain
 * chargement de page, sans attendre l'expiration naturelle de sa session.
 *
 * Vérifie aussi le jeton anti-CSRF sur toute requête POST : une page tierce
 * ne peut pas connaître ce jeton, donc ne peut pas forger une action au nom
 * d'un admin connecté (création/suppression de compte, etc.).
 */
function require_login() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE id = ? AND statut != ?');
    $stmt->execute([$_SESSION['admin_id'], 'suspendu']);
    $row = $stmt->fetch();
    $passwordChanged = $row && isset($_SESSION['pwd_hash']) && $_SESSION['pwd_hash'] !== $row['password_hash'];
    $forcedOut = $row && !$passwordChanged && session_is_revoked($pdo);
    if (!$row || $passwordChanged || $forcedOut) {
        $_SESSION = [];
        session_destroy();
        $reason = $passwordChanged ? '?pwdchanged=1' : ($forcedOut ? '?forcedout=1' : '');
        header('Location: login.php' . $reason);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_check()) {
        http_response_code(403);
        exit('403 — Jeton de sécurité invalide ou expiré. Rechargez la page et réessayez.');
    }
}

function current_admin(PDO $pdo) {
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, email, nom, role, statut, is_super_admin, last_login_at, permissions FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    return $admin ?: null;
}

function is_super_admin(PDO $pdo): bool {
    $admin = current_admin($pdo);
    return $admin !== null && (int) $admin['is_super_admin'] === 1;
}

/**
 * Coupe court avec un 403 si le compte connecté n'est pas le super-admin.
 * Réservé aux écrans/API du "onglet Développeur".
 */
/**
 * Le Directeur a, avec le Développeur (super-admin), la main sur la gestion
 * des comptes : création, suppression, et vue complète de la liste.
 */
function can_manage_users(array $admin): bool {
    return !empty($admin['is_super_admin']) || ($admin['role'] ?? '') === 'directeur';
}

/**
 * Liste "Utilisateurs & Rôles" — le compte Développeur (super-admin) est
 * totalement invisible pour tous les autres comptes, y compris le
 * Directeur : personne d'autre que son titulaire ne doit savoir qu'il existe.
 */
function visible_admins(PDO $pdo, array $viewer): array {
    $cols = 'id, email, nom, role, statut, is_super_admin, created_at, last_login_at, permissions';
    if (!empty($viewer['is_super_admin'])) {
        return $pdo->query("SELECT $cols FROM admins ORDER BY id ASC")->fetchAll();
    }
    $stmt = $pdo->prepare("SELECT $cols FROM admins WHERE is_super_admin = 0 ORDER BY id ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Personne d'autre que le titulaire du compte super-admin ne peut modifier
 * son rôle ou son statut — même un autre admin.
 */
function can_modify_admin(array $target, array $viewer): bool {
    if (!empty($target['is_super_admin'])) {
        return (int) $target['id'] === (int) $viewer['id'];
    }
    return true;
}

/**
 * Un compte ne peut jamais se supprimer lui-même, et personne d'autre que
 * le titulaire du compte super-admin ne peut supprimer ce compte.
 */
function can_delete_admin(array $target, array $viewer): bool {
    if ((int) $target['id'] === (int) $viewer['id']) {
        return false;
    }
    if (!empty($target['is_super_admin'])) {
        return false;
    }
    return true;
}

function require_super_admin(PDO $pdo) {
    if (!is_super_admin($pdo)) {
        http_response_code(403);
        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => "Accès réservé au super-administrateur."]);
        } else {
            echo "403 — Accès réservé au super-administrateur.";
        }
        exit;
    }
}

/* ══════════════════════════════════════════════════════════
   PERMISSIONS — accès aux données métier sensibles
   ══════════════════════════════════════════════════════════
   Le compte Développeur (is_super_admin) a la pleine main sur la partie
   technique (état système, journal, sauvegardes) ET sur la gestion des
   comptes/rôles — mais PAS automatiquement sur les données métier
   sensibles (pré-inscriptions des élèves, messages des familles). Ces deux
   permissions doivent être explicitement accordées via "Modifier les
   permissions", et c'est tracé dans le journal d'activité.
   Les autres comptes (Directeur, Censeur, Secrétaire, ...) gardent par
   défaut l'accès métier qu'ils avaient déjà — rien ne change pour eux
   tant que personne ne modifie leurs permissions. */
const BUSINESS_PERMISSIONS = [
    'preinscriptions' => 'Pré-inscriptions des élèves',
    'messages'         => 'Messages des familles',
];

function default_permissions(array $admin): array {
    // Développeur : aucune donnée métier par défaut.
    if (!empty($admin['is_super_admin'])) {
        return [];
    }
    // Tout autre compte : accès métier complet par défaut (comportement historique).
    return array_keys(BUSINESS_PERMISSIONS);
}

function effective_permissions(array $admin): array {
    if ($admin['permissions'] !== null && $admin['permissions'] !== '') {
        $decoded = json_decode($admin['permissions'], true);
        if (is_array($decoded)) {
            return array_values(array_intersect($decoded, array_keys(BUSINESS_PERMISSIONS)));
        }
    }
    return default_permissions($admin);
}

function has_permission(array $admin, string $key): bool {
    return in_array($key, effective_permissions($admin), true);
}

/**
 * Bloque l'accès à une page de données métier sensibles si le compte
 * connecté n'a pas la permission correspondante (voir BUSINESS_PERMISSIONS).
 */
function require_permission(PDO $pdo, string $key) {
    $admin = current_admin($pdo);
    if (!$admin || !has_permission($admin, $key)) {
        header('Location: admin.php?permission_denied=1');
        exit;
    }
}

/* ══════════════════════════════════════════════════════════
   SESSIONS / APPAREILS CONNECTÉS
   ══════════════════════════════════════════════════════════ */

function device_label_from_ua(string $ua): string {
    if (preg_match('/Windows/i', $ua)) $os = 'Windows';
    elseif (preg_match('/Mac OS/i', $ua)) $os = 'macOS';
    elseif (preg_match('/Android/i', $ua)) $os = 'Android';
    elseif (preg_match('/iPhone|iPad/i', $ua)) $os = 'iOS';
    elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';
    else $os = 'Appareil inconnu';

    if (preg_match('/Edg\//i', $ua)) $browser = 'Edge';
    elseif (preg_match('/Chrome\//i', $ua)) $browser = 'Chrome';
    elseif (preg_match('/Firefox\//i', $ua)) $browser = 'Firefox';
    elseif (preg_match('/Safari\//i', $ua)) $browser = 'Safari';
    else $browser = 'Navigateur inconnu';

    return "$browser sur $os";
}

function device_label(): string {
    return device_label_from_ua($_SERVER['HTTP_USER_AGENT'] ?? '');
}

/** Enregistre/rafraîchit la session courante comme "appareil connecté" pour cet admin. */
function register_session(PDO $pdo, int $adminId): void {
    $sid = session_id();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $pdo->prepare("INSERT INTO admin_sessions (admin_id, session_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?)
        ON CONFLICT(session_id) DO UPDATE SET admin_id = excluded.admin_id,
            ip_address = excluded.ip_address, user_agent = excluded.user_agent,
            last_seen_at = datetime('now'), revoked = 0")
        ->execute([$adminId, $sid, $ip, $ua]);
}

/** Vérifie que la session courante n'a pas été révoquée (forcer déconnexion) et met à jour last_seen_at. */
function session_is_revoked(PDO $pdo): bool {
    $sid = session_id();
    $stmt = $pdo->prepare('SELECT revoked FROM admin_sessions WHERE session_id = ?');
    $stmt->execute([$sid]);
    $row = $stmt->fetch();
    if (!$row) {
        // Session antérieure à cette fonctionnalité : on l'enregistre sans la couper.
        if (!empty($_SESSION['admin_id'])) {
            register_session($pdo, (int) $_SESSION['admin_id']);
        }
        return false;
    }
    if ((int) $row['revoked'] === 1) {
        return true;
    }
    $pdo->prepare("UPDATE admin_sessions SET last_seen_at = datetime('now') WHERE session_id = ?")->execute([$sid]);
    return false;
}

function admin_sessions_for(PDO $pdo, int $adminId): array {
    $stmt = $pdo->prepare('SELECT * FROM admin_sessions WHERE admin_id = ? AND revoked = 0 ORDER BY last_seen_at DESC');
    $stmt->execute([$adminId]);
    return $stmt->fetchAll();
}

function revoke_session(PDO $pdo, string $sessionId): void {
    $pdo->prepare('UPDATE admin_sessions SET revoked = 1 WHERE session_id = ?')->execute([$sessionId]);
}

function revoke_all_sessions(PDO $pdo, int $adminId): void {
    $pdo->prepare('UPDATE admin_sessions SET revoked = 1 WHERE admin_id = ?')->execute([$adminId]);
}

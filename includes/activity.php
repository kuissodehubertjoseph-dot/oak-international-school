<?php
/**
 * Journal d'activité — écrit un événement dans activity_logs.
 * Appelée à chaque événement sensible (connexion, inscription, changement de
 * rôle/statut, contenu publié, réglages modifiés, messages, ...).
 *
 * $actor peut être fourni explicitement (ex. tentative de connexion échouée,
 * avant toute session) ; sinon l'acteur courant de la session est utilisé.
 */
function logActivity(
    PDO $pdo,
    string $action,
    ?string $targetType = null,
    $targetId = null,
    ?string $details = null,
    ?array $actor = null
): void {
    $actorId   = $actor['id']   ?? ($_SESSION['admin_id'] ?? null);
    $actorName = $actor['name'] ?? null;
    $actorRole = $actor['role'] ?? null;

    if ($actorId && $actorName === null) {
        $stmt = $pdo->prepare('SELECT nom, email, role FROM admins WHERE id = ?');
        $stmt->execute([$actorId]);
        if ($row = $stmt->fetch()) {
            $actorName = $row['nom'] ?: $row['email'];
            $actorRole = $actorRole ?? $row['role'];
        }
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $pdo->prepare('INSERT INTO activity_logs
        (actor_id, actor_name, actor_role, action, target_type, target_id, details, ip_address)
        VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $actorId,
        $actorName,
        $actorRole,
        $action,
        $targetType,
        $targetId !== null ? (string) $targetId : null,
        $details,
        $ip,
    ]);
}

/** Actions considérées sensibles — affichées avec un badge rouge dans le journal. */
const ACTIVITY_SENSITIVE_ACTIONS = [
    'connexion_echouee',
    'compte_suspendu',
    'compte_reactive',
    'role_modifie',
    'statut_modifie',
    'tentative_non_autorisee',
    'compte_cree',
    'compte_supprime',
    'mot_de_passe_reinitialise',
    'connexion_en_tant_que',
    'permissions_modifiees',
    'session_deconnectee',
];

function activity_is_sensitive(string $action): bool {
    return in_array($action, ACTIVITY_SENSITIVE_ACTIONS, true);
}

/** Icône Font Awesome par type d'action, pour l'UI de l'onglet Développeur. */
function activity_icon(string $action): string {
    $map = [
        'connexion_reussie'        => 'fa-right-to-bracket',
        'connexion_echouee'        => 'fa-triangle-exclamation',
        'inscription'               => 'fa-file-pen',
        'role_modifie'              => 'fa-user-gear',
        'statut_modifie'            => 'fa-user-shield',
        'compte_suspendu'           => 'fa-user-slash',
        'compte_reactive'           => 'fa-user-check',
        'categorie_creee'           => 'fa-layer-group',
        'categorie_modifiee'        => 'fa-pen',
        'reglages_modifies'         => 'fa-gear',
        'message_recu'              => 'fa-envelope',
        'message_traite'            => 'fa-reply',
        'tentative_non_autorisee'   => 'fa-ban',
        'compte_cree'               => 'fa-user-plus',
        'compte_supprime'           => 'fa-user-xmark',
        'mot_de_passe_reinitialise' => 'fa-key',
        'mot_de_passe_modifie'      => 'fa-key',
        'connexion_en_tant_que'     => 'fa-user-secret',
        'fin_connexion_en_tant_que' => 'fa-right-from-bracket',
        'sauvegarde_creee'          => 'fa-floppy-disk',
        'permissions_modifiees'     => 'fa-key',
        'session_deconnectee'       => 'fa-plug-circle-xmark',
    ];
    return $map[$action] ?? 'fa-circle-dot';
}

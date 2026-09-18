<?php
/**
 * État technique réel de la solution — chaque valeur ici est mesurée en
 * direct (pas de chiffre inventé). Sur un environnement où une mesure n'est
 * pas disponible (ex : CPU/RAM sur un hébergement mutualisé qui bloque
 * shell_exec), on l'indique honnêtement plutôt que d'afficher un faux chiffre.
 */

function status_database(PDO $pdo): array {
    $start = microtime(true);
    try {
        $pdo->query('SELECT 1')->fetch();
        $ms = round((microtime(true) - $start) * 1000, 1);
        return ['ok' => true, 'label' => 'Opérationnelle', 'ms' => $ms];
    } catch (Throwable $e) {
        return ['ok' => false, 'label' => 'Erreur : ' . $e->getMessage(), 'ms' => null];
    }
}

function status_disk(): array {
    $path = __DIR__ . '/..';
    $free  = @disk_free_space($path);
    $total = @disk_total_space($path);
    if ($free === false || $total === false || $total <= 0) {
        return ['ok' => null, 'percent' => null, 'free_gb' => null, 'total_gb' => null];
    }
    $used = $total - $free;
    $percent = round(($used / $total) * 100, 1);
    return [
        'ok' => true,
        'percent' => $percent,
        'free_gb' => round($free / 1073741824, 1),
        'total_gb' => round($total / 1073741824, 1),
    ];
}

/**
 * Sessions PHP actives récemment (approximation du nombre de connexions en
 * cours) : on compte les fichiers de session modifiés dans la fenêtre de vie
 * de session PHP (session.gc_maxlifetime), méthode portable sans dépendre
 * d'une table de suivi dédiée.
 */
function status_active_sessions(): array {
    $path = session_save_path() ?: sys_get_temp_dir();
    if ($path === '' || !is_dir($path)) {
        return ['ok' => null, 'count' => null];
    }
    $maxlifetime = (int) ini_get('session.gc_maxlifetime') ?: 1440;
    $count = 0;
    foreach (glob(rtrim($path, '/\\') . '/sess_*') as $file) {
        if (is_file($file) && (time() - filemtime($file)) < $maxlifetime) {
            $count++;
        }
    }
    return ['ok' => true, 'count' => $count];
}

/** Tentatives de connexion échouées / actions non autorisées sur les dernières 24h. */
function status_security_events(PDO $pdo): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs
        WHERE action IN ('connexion_echouee', 'tentative_non_autorisee')
        AND created_at >= datetime('now', '-1 day')");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

/** Horodatage de la dernière modification de code (proxy de "dernier déploiement"). */
function status_last_code_change(): ?int {
    $latest = 0;
    foreach (glob(dirname(__DIR__) . '/*.php') as $file) {
        $latest = max($latest, filemtime($file));
    }
    return $latest ?: null;
}

/** Sauvegardes de la base : liste + dernière en date. */
function backups_dir(): string {
    $dir = dirname(__DIR__) . '/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function status_last_backup(): ?array {
    $files = glob(backups_dir() . '/*.sqlite');
    if (!$files) {
        return null;
    }
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    $latest = $files[0];
    return ['time' => filemtime($latest), 'size_kb' => round(filesize($latest) / 1024, 1), 'count' => count($files)];
}

function run_backup(): string {
    $dbFile = dirname(__DIR__) . '/data/leselites.sqlite';
    if (!is_file($dbFile)) {
        throw new RuntimeException('Base de données introuvable.');
    }
    $dest = backups_dir() . '/leselites-' . date('Y-m-d_His') . '.sqlite';
    if (!copy($dbFile, $dest)) {
        throw new RuntimeException('Échec de la copie de sauvegarde.');
    }
    // Conserve les 20 sauvegardes les plus récentes seulement.
    $files = glob(backups_dir() . '/*.sqlite');
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    foreach (array_slice($files, 20) as $old) {
        @unlink($old);
    }
    return $dest;
}

/**
 * CPU / RAM du serveur — meilleur effort seulement. Beaucoup d'hébergements
 * mutualisés désactivent shell_exec ; dans ce cas on l'indique clairement
 * plutôt que d'inventer un pourcentage.
 */
function status_server_load(): array {
    $result = ['cpu' => null, 'ram_percent' => null, 'available' => false];

    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        if ($load !== false) {
            $result['cpu'] = round($load[0], 2);
            $result['cpu_label'] = 'Charge moyenne (1 min)';
            $result['available'] = true;
        }
    }

    if (is_readable('/proc/meminfo')) {
        $meminfo = @file_get_contents('/proc/meminfo');
        if ($meminfo && preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mt) && preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $ma)) {
            $total = (int) $mt[1];
            $available = (int) $ma[1];
            if ($total > 0) {
                $result['ram_percent'] = round((($total - $available) / $total) * 100, 1);
                $result['available'] = true;
            }
        }
    } elseif (stripos(PHP_OS, 'WIN') === 0 && function_exists('shell_exec')) {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (!in_array('shell_exec', $disabled, true)) {
            $out = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /value');
            if ($out && preg_match('/FreePhysicalMemory=(\d+)/', $out, $free) && preg_match('/TotalVisibleMemorySize=(\d+)/', $out, $total)) {
                $totalKb = (int) $total[1];
                $freeKb = (int) $free[1];
                if ($totalKb > 0) {
                    $result['ram_percent'] = round((($totalKb - $freeKb) / $totalKb) * 100, 1);
                    $result['available'] = true;
                }
            }
        }
    }

    return $result;
}

<?php
// btc_faucet/config/rate_limit.php
// Rate limit simple en DB : clé + fenêtre + compteur.

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

/**
 * Exige la table rate_limits (voir SQL plus bas).
 *
 * @param string $key Clé (ex: "login:ip:1.2.3.4" ou "claim:user:123")
 * @param int $windowSeconds Fenêtre en secondes
 * @param int $maxHits Max hits dans la fenêtre
 */
function rate_limit_or_429(string $key, int $windowSeconds, int $maxHits): void
{
    $pdo = get_pdo();
    $now = time();
    $windowStart = $now - $windowSeconds;

    // Nettoyage léger opportuniste
    $pdo->prepare('DELETE FROM rate_limits WHERE updated_at < :cutoff')->execute([
        'cutoff' => date('Y-m-d H:i:s', $windowStart - 60),
    ]);

    // Upsert "à la main" compatible MySQL ancien
    $stmt = $pdo->prepare('SELECT hits, updated_at FROM rate_limits WHERE `key` = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();

    if (!$row) {
        $ins = $pdo->prepare('INSERT INTO rate_limits (`key`, hits, updated_at) VALUES (:key, 1, :updated_at)');
        $ins->execute([
            'key' => $key,
            'updated_at' => date('Y-m-d H:i:s', $now),
        ]);
        return;
    }

    $lastTs = strtotime((string)$row['updated_at']);
    $hits = (int)$row['hits'];

    // Si fenêtre expirée => reset
    if ($lastTs < $windowStart) {
        $upd = $pdo->prepare('UPDATE rate_limits SET hits = 1, updated_at = :updated_at WHERE `key` = :key');
        $upd->execute([
            'key' => $key,
            'updated_at' => date('Y-m-d H:i:s', $now),
        ]);
        return;
    }

    // Dans la fenêtre => incrément
    $hits++;
    if ($hits > $maxHits) {
        json_response([
            'error' => 'rate_limited',
            'message' => 'Trop de requêtes. Réessaie plus tard.',
            'retry_after' => max(1, ($windowSeconds - ($now - $lastTs))),
        ], 429);
    }

    $upd = $pdo->prepare('UPDATE rate_limits SET hits = :hits, updated_at = :updated_at WHERE `key` = :key');
    $upd->execute([
        'key' => $key,
        'hits' => $hits,
        'updated_at' => date('Y-m-d H:i:s', $now),
    ]);
}

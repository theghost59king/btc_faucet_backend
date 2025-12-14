<?php
// api/wheel_spin.php
// Tourne la roue : applique cooldown + crédite le wallet + enregistre l'historique
// + NOUVEAU : cagnotte globale (pool_remaining_sat) décrémentée atomiquement

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/rate_limit.php';

$user = require_auth_user();
rate_limit_or_429('wheel:user:' . $user['id'], 60, 3);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$user = require_auth_user();
$pdo  = get_pdo();

// Récupérer settings
$stmtSettings = $pdo->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$settings     = $stmtSettings->fetch();

if (!$settings) {
    json_response(['error' => 'settings_missing'], 500);
}

$wheelEnabled       = (int)($settings['wheel_enabled'] ?? 0) === 1;
$intervalHours      = (int)($settings['wheel_interval_hours'] ?? 24);
$rewardMin          = (int)($settings['wheel_reward_min_sat'] ?? 5);
$rewardMax          = (int)($settings['wheel_reward_max_sat'] ?? 25);
$jackpotSat         = (int)($settings['wheel_jackpot_sat'] ?? 100);
$jackpotChancePct   = (int)($settings['wheel_jackpot_chance_percent'] ?? 3);

if (!$wheelEnabled) {
    json_response([
        'error'   => 'wheel_disabled',
        'message' => 'La roue est désactivée.',
    ], 403);
}

if ($intervalHours <= 0 || $rewardMin < 0 || $rewardMax < $rewardMin || $jackpotChancePct < 0 || $jackpotChancePct > 100) {
    json_response(['error' => 'settings_invalid'], 500);
}

// Wallet BTC
$stmtWallet = $pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id AND currency = :currency LIMIT 1');
$stmtWallet->execute([
    'user_id'  => $user['id'],
    'currency' => 'BTC',
]);
$wallet = $stmtWallet->fetch();

if (!$wallet) {
    json_response(['error' => 'wallet_missing'], 500);
}

// Cooldown
$nowTs = time();
$lastWheelAt = $wallet['last_wheel_at'];

if (!empty($lastWheelAt)) {
    $lastTs = strtotime((string)$lastWheelAt);
    $nextTs = $lastTs + ($intervalHours * 3600);
    if ($nowTs < $nextTs) {
        json_response([
            'error'             => 'cooldown_not_finished',
            'seconds_remaining' => (int)($nextTs - $nowTs),
        ], 429);
    }
}

// Détermination du gain (jackpot rare + sinon random min/max)
// ⚠️ Le gain sera possiblement ajusté si la cagnotte globale a moins que ce gain.
$isJackpot = false;
$roll = random_int(1, 100);
if ($jackpotSat > 0 && $jackpotChancePct > 0 && $roll <= $jackpotChancePct) {
    $amountSat = $jackpotSat;
    $isJackpot = true;
} else {
    $amountSat = ($rewardMax === $rewardMin) ? $rewardMin : random_int($rewardMin, $rewardMax);
}

$nowDate = now_datetime();

try {
    $pdo->beginTransaction();

    // 🔒 Verrouille settings pour décrémenter la cagnotte globale de façon atomique
    $stmtLock = $pdo->query('SELECT pool_remaining_sat, pool_total_sat FROM settings WHERE id = 1 FOR UPDATE');
    $s = $stmtLock->fetch();
    if (!$s) {
        throw new RuntimeException('settings missing');
    }

    $poolRemaining = (int)($s['pool_remaining_sat'] ?? 0);

    // Si cagnotte vide : plus de gains, retraits ouverts
    if ($poolRemaining <= 0) {
        $pdo->rollBack();
        json_response([
            'error'              => 'pool_empty',
            'message'            => 'Cagnotte globale vide. Les retraits sont maintenant disponibles.',
            'pool_remaining_sat' => 0,
            'withdrawals_open'   => true,
        ], 200);
    }

    // Ajuste le gain si le pool ne suffit pas
    if ($amountSat > $poolRemaining) {
        $amountSat = $poolRemaining;

        // Si on ajuste le gain, un jackpot n'est plus vraiment un jackpot
        // (tu peux garder isJackpot=true si tu préfères, mais là c'est plus logique)
        if ($isJackpot && $amountSat !== $jackpotSat) {
            $isJackpot = false;
        }
    }

    // Décrémente la cagnotte globale
    $updPool = $pdo->prepare('UPDATE settings SET pool_remaining_sat = pool_remaining_sat - :amount WHERE id = 1');
    $updPool->execute(['amount' => $amountSat]);

    // Mettre à jour wallet
    $newBalance = (int)$wallet['balance_sat'] + $amountSat;
    $newTotal   = (int)$wallet['total_earned_sat'] + $amountSat;

    $updWallet = $pdo->prepare(
        'UPDATE wallets
         SET balance_sat = :balance,
             total_earned_sat = :total,
             last_wheel_at = :last_wheel_at,
             updated_at = :updated_at
         WHERE id = :id'
    );
    $updWallet->execute([
        'balance'       => $newBalance,
        'total'         => $newTotal,
        'last_wheel_at' => $nowDate,
        'updated_at'    => $nowDate,
        'id'            => $wallet['id'],
    ]);

    // Historique : wheel_spins
    $insSpin = $pdo->prepare(
        'INSERT INTO wheel_spins (user_id, amount_sat, is_jackpot, created_at)
         VALUES (:user_id, :amount, :is_jackpot, :created_at)'
    );
    $insSpin->execute([
        'user_id'    => $user['id'],
        'amount'     => $amountSat,
        'is_jackpot' => $isJackpot ? 1 : 0,
        'created_at' => $nowDate,
    ]);

    // Historique générique : earnings (pour que wallet.php affiche aussi ces gains)
    $insEarning = $pdo->prepare(
        'INSERT INTO earnings (user_id, currency, amount_sat, ad_id, created_at)
         VALUES (:user_id, :currency, :amount, :ad_id, :created_at)'
    );
    $insEarning->execute([
        'user_id'    => $user['id'],
        'currency'   => 'BTC',
        'amount'     => $amountSat,
        'ad_id'      => null,
        'created_at' => $nowDate,
    ]);

    // Relit pool après update (dans la même transaction)
    $stmtAfter = $pdo->query('SELECT pool_remaining_sat FROM settings WHERE id = 1');
    $after = $stmtAfter->fetch();
    $poolAfter = (int)($after['pool_remaining_sat'] ?? 0);

    $pdo->commit();

    $nextSpinAtTs = $nowTs + ($intervalHours * 3600);

    json_response([
        'amount_sat'          => $amountSat,
        'is_jackpot'          => $isJackpot,
        'new_balance'         => $newBalance,
        'total_earned'        => $newTotal,
        'seconds_remaining'   => (int)($intervalHours * 3600),
        'next_spin_at'        => date('c', $nextSpinAtTs),

        // Cagnotte globale
        'pool_remaining_sat'  => $poolAfter,
        'withdrawals_open'    => ($poolAfter <= 0),
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'error'   => 'wheel_spin_failed',
        'message' => 'Erreur lors du spin.',
    ], 500);
}


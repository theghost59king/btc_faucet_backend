<?php
// api/faucet_claim.php
// Réclame des satoshis via le bouton BTC : cooldown + crédite wallet + décrémente cagnotte globale
// ✅ Compatible avec earnings sans colonne "source" (même format que wheel_spin.php)

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$user = require_auth_user();
$pdo  = get_pdo();

// settings
$stmtSettings = $pdo->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$settings     = $stmtSettings->fetch();
if (!$settings) {
    json_response(['error' => 'settings_missing'], 500);
}

$min = (int)($settings['reward_min_sat'] ?? 1);
$max = (int)($settings['reward_max_sat'] ?? 5);
$intervalMinutes = (int)($settings['faucet_interval_minutes'] ?? 60);

if ($min < 1) $min = 1;
if ($max < $min) $max = $min;
if ($intervalMinutes < 1) $intervalMinutes = 60;

// wallet BTC
$stmtWallet = $pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id AND currency = :currency LIMIT 1');
$stmtWallet->execute([
    'user_id'  => $user['id'],
    'currency' => 'BTC',
]);
$wallet = $stmtWallet->fetch();

if (!$wallet) {
    json_response(['error' => 'wallet_missing'], 500);
}

// cooldown
$nowTs = time();
$lastClaimAt = $wallet['last_claim_at'] ?? null;
if (!empty($lastClaimAt)) {
    $lastTime = strtotime((string)$lastClaimAt);
    $nextTime = $lastTime + ($intervalMinutes * 60);
    if ($nowTs < $nextTime) {
        json_response([
            'error' => 'cooldown',
            'seconds_remaining' => max(0, $nextTime - $nowTs),
        ], 200);
    }
}

$nowDate = now_datetime();

// Tirage gain (sera ajusté si pool insuffisant)
$amountSat = random_int($min, $max);

$pdo->beginTransaction();
try {
    // 🔒 lock pool
    $stmtLock = $pdo->query('SELECT pool_remaining_sat FROM settings WHERE id = 1 FOR UPDATE');
    $s = $stmtLock->fetch();
    if (!$s) {
        throw new RuntimeException('settings missing');
    }

    $poolRemaining = (int)($s['pool_remaining_sat'] ?? 0);

    if ($poolRemaining <= 0) {
        $pdo->rollBack();
        json_response([
            'error'              => 'pool_empty',
            'message'            => 'Cagnotte globale vide. Les retraits sont maintenant disponibles.',
            'pool_remaining_sat' => 0,
            'withdrawals_open'   => true,
        ], 200);
    }

    // ajuste si pool < gain
    if ($amountSat > $poolRemaining) {
        $amountSat = $poolRemaining;
    }

    // décrémente pool
    $updPool = $pdo->prepare('UPDATE settings SET pool_remaining_sat = pool_remaining_sat - :amount WHERE id = 1');
    $updPool->execute(['amount' => $amountSat]);

    // update wallet
    $newBalance = (int)$wallet['balance_sat'] + $amountSat;
    $newTotal   = (int)$wallet['total_earned_sat'] + $amountSat;

    $updWallet = $pdo->prepare(
        'UPDATE wallets
         SET balance_sat = :balance,
             total_earned_sat = :total,
             last_claim_at = :last_claim_at,
             updated_at = :updated_at
         WHERE id = :id'
    );
    $updWallet->execute([
        'balance'       => $newBalance,
        'total'         => $newTotal,
        'last_claim_at' => $nowDate,
        'updated_at'    => $nowDate,
        'id'            => $wallet['id'],
    ]);

    // ✅ earnings (même format que wheel_spin)
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

    // relire pool
    $stmtAfter = $pdo->query('SELECT pool_remaining_sat FROM settings WHERE id = 1');
    $after = $stmtAfter->fetch();
    $poolAfter = (int)($after['pool_remaining_sat'] ?? 0);

    $pdo->commit();

    json_response([
        'amount_sat'         => $amountSat,
        'new_balance'        => $newBalance,
        'total_earned'       => $newTotal,
        'pool_remaining_sat' => $poolAfter,
        'withdrawals_open'   => ($poolAfter <= 0),
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'server_error', 'message' => 'Erreur lors de la réclamation.'], 500);
}

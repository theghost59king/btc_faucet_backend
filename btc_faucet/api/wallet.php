<?php
// api/wallet.php
// Retourne wallet + historique gains/retraits + état cagnotte globale
// ✅ Compatible avec une table earnings sans colonne "source"

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

$user = require_auth_user();
$pdo  = get_pdo();

// Settings + pool
$stmtSettings = $pdo->query('SELECT pool_total_sat, pool_remaining_sat FROM settings WHERE id = 1 LIMIT 1');
$settings     = $stmtSettings->fetch();
if (!$settings) {
    json_response(['error' => 'settings_missing'], 500);
}

$poolTotal     = (int)($settings['pool_total_sat'] ?? 0);
$poolRemaining = (int)($settings['pool_remaining_sat'] ?? 0);
if ($poolTotal < 0) $poolTotal = 0;
if ($poolRemaining < 0) $poolRemaining = 0;
if ($poolTotal > 0 && $poolRemaining > $poolTotal) $poolRemaining = $poolTotal;

$withdrawalsOpen = ($poolRemaining <= 0);

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

// ✅ earnings : on ne sélectionne que des colonnes "sûres"
$stmtEarnings = $pdo->prepare(
    'SELECT id, amount_sat, ad_id, created_at
     FROM earnings
     WHERE user_id = :user_id AND currency = :currency
     ORDER BY id DESC
     LIMIT 20'
);
$stmtEarnings->execute([
    'user_id'  => $user['id'],
    'currency' => 'BTC',
]);
$earnings = $stmtEarnings->fetchAll();

// withdrawals
$stmtWithdrawals = $pdo->prepare(
    'SELECT id, amount_sat, address, status, created_at
     FROM withdrawals
     WHERE user_id = :user_id AND currency = :currency
     ORDER BY id DESC
     LIMIT 20'
);
$stmtWithdrawals->execute([
    'user_id'  => $user['id'],
    'currency' => 'BTC',
]);
$withdrawals = $stmtWithdrawals->fetchAll();

json_response([
    'wallet' => [
        'balance_sat'      => (int)$wallet['balance_sat'],
        'total_earned_sat' => (int)$wallet['total_earned_sat'],
        'last_claim_at'    => $wallet['last_claim_at'] ?? null,
        'last_wheel_at'    => $wallet['last_wheel_at'] ?? null,
    ],
    'global_pool' => [
        'pool_total_sat'     => $poolTotal,
        'pool_remaining_sat' => $poolRemaining,
        'withdrawals_open'   => $withdrawalsOpen,
    ],
    'earnings'    => $earnings,
    'withdrawals' => $withdrawals,
]);

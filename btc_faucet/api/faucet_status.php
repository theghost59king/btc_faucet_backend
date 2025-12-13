<?php
// api/faucet_status.php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

$user = require_auth_user();
$pdo  = get_pdo();

$stmtSettings = $pdo->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$settings     = $stmtSettings->fetch();
if (!$settings) {
    json_response(['error' => 'settings_missing'], 500);
}

$poolRemaining = (int)($settings['pool_remaining_sat'] ?? 0);
$withdrawalsOpen = ($poolRemaining <= 0);

$stmtWallet = $pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id AND currency = :currency LIMIT 1');
$stmtWallet->execute([
    'user_id' => $user['id'],
    'currency' => 'BTC',
]);
$wallet = $stmtWallet->fetch();
if (!$wallet) {
    json_response(['error' => 'wallet_missing'], 500);
}

$intervalMinutes = (int)$settings['faucet_interval_minutes'];
if ($intervalMinutes < 1) $intervalMinutes = 60;

$canClaim = true;
$secondsRemaining = 0;

// Si pool vide -> plus de gains
if ($poolRemaining <= 0) {
    $canClaim = false;
    $secondsRemaining = 0;
} else {
    $lastClaimAt = $wallet['last_claim_at'];
    if (!empty($lastClaimAt)) {
        $lastTime = strtotime((string)$lastClaimAt);
        $nextTime = $lastTime + ($intervalMinutes * 60);
        $now      = time();

        if ($now >= $nextTime) {
            $canClaim = true;
        } else {
            $canClaim = false;
            $secondsRemaining = max(0, $nextTime - $now);
        }
    }
}

json_response([
    'can_claim'            => $canClaim,
    'seconds_remaining'    => $secondsRemaining,
    'ad_countdown_seconds' => (int)$settings['ad_countdown_seconds'],
    'interval_minutes'     => $intervalMinutes,
    'reward_min_sat'       => (int)$settings['reward_min_sat'],
    'reward_max_sat'       => (int)$settings['reward_max_sat'],

    // Cagnotte globale
    'pool_total_sat'       => (int)($settings['pool_total_sat'] ?? 0),
    'pool_remaining_sat'   => $poolRemaining,
    'withdrawals_open'     => $withdrawalsOpen,
]);

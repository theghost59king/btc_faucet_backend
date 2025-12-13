<?php
// api/wheel_status.php
// Indique si l'utilisateur peut tourner la roue + temps restant + paramètres

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

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

$canSpin         = false;
$secondsRemaining = 0;

if (!$wheelEnabled) {
    $canSpin = false;
} else {
    $lastWheelAt = $wallet['last_wheel_at'];
    $nowTs = time();

    if (empty($lastWheelAt)) {
        $canSpin = true;
    } else {
        $lastTs = strtotime((string)$lastWheelAt);
        $nextTs = $lastTs + ($intervalHours * 3600);

        if ($nowTs >= $nextTs) {
            $canSpin = true;
        } else {
            $secondsRemaining = $nextTs - $nowTs;
        }
    }
}

json_response([
    'wheel_enabled'              => $wheelEnabled,
    'can_spin'                   => $canSpin,
    'seconds_remaining'          => (int)$secondsRemaining,

    // Paramètres pour l'app (affichage / UI)
    'interval_hours'             => $intervalHours,
    'reward_min_sat'             => $rewardMin,
    'reward_max_sat'             => $rewardMax,
    'jackpot_sat'                => $jackpotSat,
    'jackpot_chance_percent'     => $jackpotChancePct,

    // Pour l’UI (12 segments demandé)
    'segments'                   => 12,
]);

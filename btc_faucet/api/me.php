<?php
// api/me.php
// Retourne les infos de l'utilisateur connecté + wallet BTC + paramètres globaux

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

$user = require_auth_user();
$pdo  = get_pdo();

// Récupérer le wallet BTC
$stmtWallet = $pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id AND currency = :currency LIMIT 1');
$stmtWallet->execute([
    'user_id'  => $user['id'],
    'currency' => 'BTC',
]);
$wallet = $stmtWallet->fetch();

// Récupérer les settings
$stmtSettings = $pdo->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$settings     = $stmtSettings->fetch();

json_response([
    'user' => [
        'id'     => (int)$user['id'],
        'email'  => $user['email'],
        'pseudo' => $user['pseudo'],
    ],
    'wallet'   => $wallet ? [
        'balance_sat'      => (int)$wallet['balance_sat'],
        'total_earned_sat' => (int)$wallet['total_earned_sat'],
        'last_claim_at'    => $wallet['last_claim_at'],
    ] : null,
    'settings' => $settings,
]);

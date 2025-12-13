<?php
// api/withdraw.php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$user  = require_auth_user();
$pdo   = get_pdo();
$input = get_json_input();

$address = trim((string)($input['address'] ?? ''));
$amount  = (int)($input['amount_sat'] ?? 0);

if ($address === '') {
    json_response(['error' => 'invalid_address', 'message' => 'Adresse de retrait requise.'], 400);
}

$stmtSettings = $pdo->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$settings     = $stmtSettings->fetch();
if (!$settings) {
    json_response(['error' => 'settings_missing'], 500);
}

$poolRemaining = (int)($settings['pool_remaining_sat'] ?? 0);
if ($poolRemaining > 0) {
    json_response([
        'error' => 'withdrawals_locked',
        'message' => 'Les retraits seront disponibles quand la cagnotte globale sera à zéro.',
        'pool_remaining_sat' => $poolRemaining,
    ], 403);
}

$minWithdraw = (int)$settings['withdraw_min_sat'];
if ($minWithdraw < 1) $minWithdraw = 1;

$stmtWallet = $pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id AND currency = :currency LIMIT 1');
$stmtWallet->execute([
    'user_id'  => $user['id'],
    'currency' => 'BTC',
]);
$wallet = $stmtWallet->fetch();
if (!$wallet) {
    json_response(['error' => 'wallet_missing'], 500);
}

$balance = (int)$wallet['balance_sat'];

if ($balance < $minWithdraw) {
    json_response([
        'error' => 'min_balance',
        'message' => "Montant minimum de retrait: {$minWithdraw} satoshis.",
    ], 400);
}

if ($amount <= 0) {
    $amount = $balance; // retire tout si non précisé
}

if ($amount <= 0 || $amount > $balance) {
    json_response(['error' => 'invalid_amount', 'message' => 'Montant invalide.'], 400);
}

$now = now_datetime();

$pdo->beginTransaction();
try {
    $updWallet = $pdo->prepare(
        'UPDATE wallets
         SET balance_sat = balance_sat - :amount,
             updated_at = :updated_at
         WHERE id = :id'
    );
    $updWallet->execute([
        'amount'     => $amount,
        'updated_at' => $now,
        'id'         => $wallet['id'],
    ]);

    $ins = $pdo->prepare(
        'INSERT INTO withdrawals (user_id, currency, amount_sat, address, status, created_at)
         VALUES (:user_id, :currency, :amount_sat, :address, :status, :created_at)'
    );
    $ins->execute([
        'user_id'     => $user['id'],
        'currency'    => 'BTC',
        'amount_sat'  => $amount,
        'address'     => $address,
        'status'      => 'pending',
        'created_at'  => $now,
    ]);

    $pdo->commit();

    json_response([
        'success'     => true,
        'amount_sat'  => $amount,
        'new_balance' => $balance - $amount,
        'status'      => 'pending',
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['error' => 'server_error', 'message' => 'Erreur lors du retrait.'], 500);
}

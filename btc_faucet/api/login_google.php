<?php
// api/login_google.php
// Connexion/Inscription via Google (simplifiée)

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$input = get_json_input();

$googleId = isset($input['google_id']) ? trim((string)$input['google_id']) : '';
$email    = isset($input['email']) ? trim((string)$input['email']) : '';
$pseudo   = isset($input['pseudo']) ? trim((string)$input['pseudo']) : '';

if ($googleId === '' || $email === '' || $pseudo === '') {
    json_response([
        'error'   => 'validation_error',
        'message' => 'google_id, email et pseudo sont obligatoires.',
    ], 400);
}

$pdo = get_pdo();

// Chercher un utilisateur existant par google_id OU par email
$stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = :google_id OR email = :email');
$stmt->execute([
    'google_id' => $googleId,
    'email'     => $email,
]);
$user = $stmt->fetch();

$now      = now_datetime();
$apiToken = generate_api_token();

if ($user) {
    // Mettre à jour google_id (au cas où) et token
    $upd = $pdo->prepare(
        'UPDATE users
         SET google_id = :google_id, pseudo = :pseudo, api_token = :token, updated_at = :updated_at
         WHERE id = :id'
    );
    $upd->execute([
        'google_id'  => $googleId,
        'pseudo'     => $pseudo,
        'token'      => $apiToken,
        'updated_at' => $now,
        'id'         => $user['id'],
    ]);

    json_response([
        'id'     => (int)$user['id'],
        'email'  => $email,
        'pseudo' => $pseudo,
        'token'  => $apiToken,
    ]);
}

// Sinon, créer un nouvel utilisateur + wallet
$pdo->beginTransaction();

try {
    $ins = $pdo->prepare(
        'INSERT INTO users (email, password_hash, google_id, pseudo, api_token, created_at, updated_at)
         VALUES (:email, NULL, :google_id, :pseudo, :api_token, :created_at, :updated_at)'
    );
    $ins->execute([
        'email'      => $email,
        'google_id'  => $googleId,
        'pseudo'     => $pseudo,
        'api_token'  => $apiToken,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $userId = (int)$pdo->lastInsertId();

    $insWallet = $pdo->prepare(
        'INSERT INTO wallets (user_id, currency, balance_sat, total_earned_sat, created_at, updated_at)
         VALUES (:user_id, :currency, 0, 0, :created_at, :updated_at)'
    );
    $insWallet->execute([
        'user_id'    => $userId,
        'currency'   => 'BTC',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $pdo->commit();

    json_response([
        'id'     => $userId,
        'email'  => $email,
        'pseudo' => $pseudo,
        'token'  => $apiToken,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response([
        'error'   => 'server_error',
        'message' => 'Erreur lors de la connexion Google.',
    ], 500);
}

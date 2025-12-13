<?php
// api/login.php
// Connexion par email + mot de passe

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$input = get_json_input();

$email    = isset($input['email']) ? trim((string)$input['email']) : '';
$password = isset($input['password']) ? (string)$input['password'] : '';

if ($email === '' || $password === '') {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Email et mot de passe sont obligatoires.',
    ], 400);
}

$pdo = get_pdo();

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    json_response([
        'error'   => 'invalid_credentials',
        'message' => 'Identifiants invalides.',
    ], 401);
}

// Générer un nouveau token
$apiToken = generate_api_token();

$upd = $pdo->prepare('UPDATE users SET api_token = :token, updated_at = :updated_at WHERE id = :id');
$upd->execute([
    'token'      => $apiToken,
    'updated_at' => now_datetime(),
    'id'         => $user['id'],
]);

json_response([
    'id'     => (int)$user['id'],
    'email'  => $user['email'],
    'pseudo' => $user['pseudo'],
    'token'  => $apiToken,
]);

<?php
// btc_faucet/api/login.php
// Connexion email + mot de passe

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
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
        'message' => 'Email et mot de passe obligatoires.',
    ], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Email invalide.',
    ], 400);
}

// ✅ Définir l’IP sans warning
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
// Si X_FORWARDED_FOR contient plusieurs IPs
if (is_string($ip) && str_contains($ip, ',')) {
    $ip = trim(explode(',', $ip)[0]);
}

$pdo = get_pdo();

$stmt = $pdo->prepare('SELECT id, email, pseudo, password_hash, api_token FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    json_response([
        'error'   => 'invalid_credentials',
        'message' => 'Identifiants incorrects.',
    ], 401);
}

$hash = (string)($user['password_hash'] ?? '');
if ($hash === '' || !password_verify($password, $hash)) {
    json_response([
        'error'   => 'invalid_credentials',
        'message' => 'Identifiants incorrects.',
    ], 401);
}

// Si pas de token, on en génère un
$token = (string)($user['api_token'] ?? '');
if ($token === '') {
    $token = bin2hex(random_bytes(32));
    $upd = $pdo->prepare('UPDATE users SET api_token = :t, updated_at = :u WHERE id = :id');
    $upd->execute([
        't' => $token,
        'u' => now_datetime(),
        'id' => (int)$user['id'],
    ]);
}

// (Optionnel) tu peux logger l’IP ici si tu as une table, sinon on ne fait rien.
// IMPORTANT : ne jamais echo/var_dump ici.

json_response([
    'id'     => (int)$user['id'],
    'email'  => (string)$user['email'],
    'pseudo' => (string)$user['pseudo'],
    'token'  => $token,
]);

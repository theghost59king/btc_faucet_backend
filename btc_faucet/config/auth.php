<?php
// config/auth.php
// Gestion de l'authentification par token pour l'API

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

/**
 * Récupère l'en-tête Authorization (Bearer TOKEN).
 */
function get_bearer_token(): ?string
{
    $headers = null;

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }

    if ($headers && isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } else {
        return null;
    }

    if (stripos($authHeader, 'Bearer ') === 0) {
        return trim(substr($authHeader, 7));
    }

    return null;
}

/**
 * Récupère un utilisateur à partir d'un token API.
 *
 * @return array<string,mixed>|null
 */
function get_user_from_token(string $token): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE api_token = :token');
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    return $user;
}

/**
 * Vérifie la présence d'un token et renvoie l'utilisateur associé.
 * Termine la requête avec un 401 si non autorisé.
 *
 * @return array<string,mixed>
 */
function require_auth_user(): array
{
    $token = get_bearer_token();

    if ($token === null) {
        json_response([
            'error'   => 'unauthorized',
            'message' => 'Token manquant.',
        ], 401);
    }

    $user = get_user_from_token($token);

    if ($user === null) {
        json_response([
            'error'   => 'unauthorized',
            'message' => 'Token invalide.',
        ], 401);
    }

    return $user;
}

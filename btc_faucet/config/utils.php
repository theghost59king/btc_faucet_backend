<?php
// config/utils.php
// Fonctions utilitaires communes (réponses JSON, génération de token, etc.)

declare(strict_types=1);

/**
 * Envoie une réponse JSON standard.
 *
 * @param mixed $data Données à encoder
 * @param int   $statusCode Code HTTP
 */
function json_response($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Lit le corps de la requête et retourne le JSON décodé.
 *
 * @return array<string,mixed>
 */
function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }

    return $data;
}

/**
 * Génère un token API pseudo-aléatoire.
 */
function generate_api_token(int $length = 64): string
{
    return bin2hex(random_bytes((int)($length / 2)));
}

/**
 * Retourne la date/heure actuelle au format MySQL DATETIME.
 */
function now_datetime(): string
{
    return date('Y-m-d H:i:s');
}

<?php
// btc_faucet/config/headers.php
// Headers de sécurité + CORS strict (prod).

declare(strict_types=1);

/**
 * Applique les headers de sécurité.
 */
function apply_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // CSP minimaliste (API JSON) : pas de scripts externes requis
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none';");
}

/**
 * CORS : autorise uniquement les origines listées.
 * - Pour Flutter mobile : généralement pas d'Origin => on laisse passer.
 * - Pour Flutter Web : Origin présent => doit être autorisé.
 */
function apply_cors(array $allowedOrigins): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

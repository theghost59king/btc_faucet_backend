<?php
// btc_faucet/api/_bootstrap.php
// À inclure en haut de chaque endpoint API : headers + cors + json + rate limit helpers.

declare(strict_types=1);

require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/config.php';

// ⚠️ Autorise uniquement ton domaine Render (Flutter Web), et ton domaine admin si différent.
// Si tu n'utilises pas Flutter Web, tu peux laisser uniquement Render.
$allowedOrigins = [
    // Exemple : ton front web si tu en as un
    // 'https://ton-front-web.com',
];

apply_security_headers();
apply_cors($allowedOrigins);

// Force JSON pour l'API
header('Content-Type: application/json; charset=utf-8');

// Attrape les erreurs fatales et renvoie JSON
set_exception_handler(function (Throwable $e) {
    json_response([
        'error' => 'server_error',
        'message' => 'Erreur serveur.',
    ], 500);
});

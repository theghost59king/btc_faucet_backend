<?php
declare(strict_types=1);

// btc_faucet/api/diag.php
// Diagnostic Render: affiche les env vars et constantes DB (sans exposer le mot de passe)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';

function mask(?string $s): ?string {
    if ($s === null) return null;
    $s = (string)$s;
    if ($s === '') return '';
    if (strlen($s) <= 4) return str_repeat('*', strlen($s));
    return substr($s, 0, 2) . str_repeat('*', strlen($s) - 4) . substr($s, -2);
}

echo json_encode([
    'app_debug_env' => getenv('APP_DEBUG') ?: null,

    'env' => [
        'DB_HOST' => getenv('DB_HOST') ?: null,
        'DB_NAME' => getenv('DB_NAME') ?: null,
        'DB_USER' => getenv('DB_USER') ?: null,
        'DB_PORT' => getenv('DB_PORT') ?: null,
        'DB_PASSWORD' => mask(getenv('DB_PASSWORD') ?: null),
        'BASE_URL' => getenv('BASE_URL') ?: null,
    ],

    'constants' => [
        'DB_HOST' => defined('DB_HOST') ? DB_HOST : null,
        'DB_NAME' => defined('DB_NAME') ? DB_NAME : null,
        'DB_USER' => defined('DB_USER') ? DB_USER : null,
        'DB_PORT' => defined('DB_PORT') ? DB_PORT : null,
        'DB_PASSWORD' => defined('DB_PASSWORD') ? mask(DB_PASSWORD) : null,
        'BASE_URL' => defined('BASE_URL') ? BASE_URL : null,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

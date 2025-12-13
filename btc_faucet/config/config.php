<?php
// btc_faucet/config/config.php
// Configuration globale du backend.
//
// Objectifs :
// - Fonctionner en LOCAL (Wamp) sans rien configurer
// - Fonctionner sur Render avec une base MySQL distante via variables d'environnement
//
// IMPORTANT : On "trim" les variables d'environnement pour éviter les \n qui cassent la connexion.

declare(strict_types=1);

/**
 * Helper : récupère une variable d'environnement, sinon retourne une valeur par défaut.
 * - Trim pour enlever espaces / retours à la ligne (Render peut en ajouter si on copie-colle mal)
 */
function env(string $key, string $default = ''): string
{
    $val = getenv($key);

    if ($val === false || $val === null) {
        return $default;
    }

    // ⚠️ Enlève \r \n et espaces autour
    $val = trim((string)$val);

    // Si vide après trim -> default
    if ($val === '') {
        return $default;
    }

    return $val;
}

// 🔌 Base de données
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'btc_faucet'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// Optionnel : port MySQL
define('DB_PORT', env('DB_PORT', ''));

// 🌍 URL de base
define('BASE_URL', env('BASE_URL', 'http://localhost/btc_faucet'));

// 🔐 Admin
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
define('ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'admin123'));

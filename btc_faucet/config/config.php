<?php
// config/config.php
// Configuration globale du backend.
//
// Objectifs :
// - Fonctionner en LOCAL (Wamp) sans rien configurer
// - Fonctionner sur Render avec une base MySQL distante via variables d'environnement
//
// IMPORTANT : Sur Render, on mettra les variables DB_HOST, DB_NAME, DB_USER, DB_PASSWORD (et éventuellement DB_PORT).

declare(strict_types=1);

/**
 * Helper : récupère une variable d'environnement, sinon retourne une valeur par défaut.
 * (Render utilise des env vars, Wamp non)
 */
function env(string $key, string $default = ''): string
{
    $val = getenv($key);
    if ($val === false || $val === null || $val === '') {
        return $default;
    }
    return (string)$val;
}

/**
 * 🔌 Base de données
 * - LOCAL (Wamp) : valeurs par défaut ci-dessous
 * - Render / prod : définir DB_HOST, DB_NAME, DB_USER, DB_PASSWORD (+ DB_PORT si besoin)
 */
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'btc_faucet'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

/**
 * Optionnel : port MySQL (certains fournisseurs imposent 3306/3307/etc.)
 * Si tu ne l'utilises pas, laisse vide.
 */
define('DB_PORT', env('DB_PORT', ''));

/**
 * 🌍 URL de base (utile si tu génères des liens).
 * - En local : http://localhost/btc_faucet
 * - Sur Render : https://btc-faucet-backend.onrender.com/btc_faucet
 */
define('BASE_URL', env('BASE_URL', 'http://localhost/btc_faucet'));

/**
 * 🔐 Admin (à sécuriser plus tard)
 * Sur Render : tu peux aussi les mettre en env ADMIN_USERNAME / ADMIN_PASSWORD
 */
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
define('ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'admin123')); // À changer en prod !


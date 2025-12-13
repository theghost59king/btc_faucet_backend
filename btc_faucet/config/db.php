<?php
// btc_faucet/config/db.php
// Connexion PDO à MySQL (local + Render)
// DEBUG TEMPORAIRE via APP_DEBUG=1

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $portPart = (defined('DB_PORT') && DB_PORT !== '') ? ';port=' . DB_PORT : '';
    $dsn = 'mysql:host=' . DB_HOST . $portPart . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // évite certains soucis réseau
        PDO::ATTR_TIMEOUT            => 8,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        $debug = getenv('APP_DEBUG') === '1';

        echo json_encode([
            'error'   => 'db_connection_error',
            'message' => 'Impossible de se connecter à la base de données.',
            'details' => $debug ? $e->getMessage() : null,
            'dsn'     => $debug ? $dsn : null,
            'host'    => $debug ? DB_HOST : null,
            'db'      => $debug ? DB_NAME : null,
            'user'    => $debug ? DB_USER : null,
            'port'    => $debug ? (defined('DB_PORT') ? DB_PORT : '') : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}

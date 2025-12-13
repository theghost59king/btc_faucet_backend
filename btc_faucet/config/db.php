<?php
// config/db.php
// Connexion PDO à MySQL

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Retourne une instance PDO connectée à la base.
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => 'db_connection_error',
                'message' => 'Impossible de se connecter à la base de données.',
            ]);
            exit;
        }
    }

    return $pdo;
}

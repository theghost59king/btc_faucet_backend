<?php
// api/update_profile.php
// Permet de modifier le pseudo de l'utilisateur connecté

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$user  = require_auth_user();
$input = get_json_input();

$pseudo = isset($input['pseudo']) ? trim((string)$input['pseudo']) : '';

if ($pseudo === '') {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Le pseudo est obligatoire.',
    ], 400);
}

$pdo = get_pdo();
$stmt = $pdo->prepare('UPDATE users SET pseudo = :pseudo, updated_at = :updated_at WHERE id = :id');
$stmt->execute([
    'pseudo'     => $pseudo,
    'updated_at' => now_datetime(),
    'id'         => $user['id'],
]);

json_response([
    'success' => true,
    'pseudo'  => $pseudo,
]);

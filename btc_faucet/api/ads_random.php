<?php
// api/ads_random.php
// Retourne une publicité active aléatoire

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/utils.php';

// Pas besoin d'être authentifié pour voir une pub (mais tu peux l’exiger si tu veux)
$pdo = get_pdo();

$stmt = $pdo->query('SELECT * FROM ads WHERE is_active = 1 ORDER BY RAND() LIMIT 1');
$ad   = $stmt->fetch();

if (!$ad) {
    json_response([
        'ad' => null,
    ]);
}

json_response([
    'ad' => [
        'id'         => (int)$ad['id'],
        'title'      => $ad['title'],
        'image_url'  => $ad['image_url'],
        'target_url' => $ad['target_url'],
    ],
]);

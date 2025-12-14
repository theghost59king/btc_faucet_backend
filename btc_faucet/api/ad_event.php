<?php
// btc_faucet/api/ad_event.php
// Enregistre une impression ou un clic : {ad_id, event_type, placement}

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$input = get_json_input();

$adId = isset($input['ad_id']) ? (int)$input['ad_id'] : 0;
$eventType = isset($input['event_type']) ? trim((string)$input['event_type']) : '';
$placement = isset($input['placement']) ? trim((string)$input['placement']) : 'faucet';

if ($adId <= 0) {
    json_response(['error' => 'validation_error', 'message' => 'ad_id invalide'], 400);
}
if (!in_array($eventType, ['impression', 'click'], true)) {
    json_response(['error' => 'validation_error', 'message' => 'event_type invalide'], 400);
}
if (!in_array($placement, ['faucet', 'wheel', 'home'], true)) {
    $placement = 'faucet';
}

$user = null;
try {
    $user = require_auth_user();
} catch (Throwable $e) {
    $user = null; // auth optionnelle
}

$pdo = get_pdo();

$stmt = $pdo->prepare(
    "INSERT INTO ad_events (ad_id, user_id, event_type, placement, created_at)
     VALUES (:ad_id, :user_id, :event_type, :placement, :created_at)"
);

$stmt->execute([
    'ad_id' => $adId,
    'user_id' => $user ? (int)$user['id'] : null,
    'event_type' => $eventType,
    'placement' => $placement,
    'created_at' => now_datetime(),
]);

json_response(['ok' => true]);

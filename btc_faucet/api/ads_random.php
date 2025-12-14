<?php
// btc_faucet/api/ads_random.php
// Retourne une pub aléatoire pour un emplacement (faucet|wheel) avec pondération + countdown admin.

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/utils.php';

$user = null;
// Auth optionnelle : on accepte sans token (ex: web) mais si token présent on le lit
try {
    $user = require_auth_user();
} catch (Throwable $e) {
    $user = null;
}

$placement = isset($_GET['placement']) ? trim((string)$_GET['placement']) : 'faucet';
$allowedPlacements = ['faucet', 'wheel', 'home'];
if (!in_array($placement, $allowedPlacements, true)) {
    $placement = 'faucet';
}

$pdo = get_pdo();

// settings
$settings = $pdo->query('SELECT * FROM settings WHERE id=1 LIMIT 1')->fetch();
if (!$settings) {
    json_response(['error' => 'settings_missing'], 500);
}

$adsEnabled = (int)($settings['ads_enabled'] ?? 1) === 1;
if (!$adsEnabled) {
    json_response([
        'ad' => null,
        'ads_enabled' => false,
        'countdown_seconds' => 0,
    ]);
}

// countdown par placement
$countdown = (int)($settings['ad_countdown_seconds'] ?? 20);
if ($placement === 'faucet') {
    $countdown = (int)($settings['ad_countdown_faucet_seconds'] ?? $countdown);
} elseif ($placement === 'wheel') {
    $countdown = (int)($settings['ad_countdown_wheel_seconds'] ?? $countdown);
}

$now = now_datetime();

// On récupère les pubs actives pour cet emplacement + fenêtre de dates
$stmt = $pdo->prepare(
    "SELECT id, title, image_url, target_url, is_active, placement, ad_type, weight, html_code
     FROM ads
     WHERE is_active = 1
       AND placement = :placement
       AND (starts_at IS NULL OR starts_at <= :now)
       AND (ends_at IS NULL OR ends_at >= :now)"
);
$stmt->execute(['placement' => $placement, 'now' => $now]);
$ads = $stmt->fetchAll();

if (!$ads || count($ads) === 0) {
    // Fallback : si aucune pub pour ce placement, on tente sans placement
    $stmt2 = $pdo->prepare(
        "SELECT id, title, image_url, target_url, is_active, placement, ad_type, weight, html_code
         FROM ads
         WHERE is_active = 1
           AND (starts_at IS NULL OR starts_at <= :now)
           AND (ends_at IS NULL OR ends_at >= :now)"
    );
    $stmt2->execute(['now' => $now]);
    $ads = $stmt2->fetchAll();
}

if (!$ads || count($ads) === 0) {
    json_response([
        'ad' => null,
        'ads_enabled' => true,
        'countdown_seconds' => max(0, $countdown),
    ]);
}

// Tirage pondéré (weight)
$totalWeight = 0;
foreach ($ads as $a) {
    $w = (int)($a['weight'] ?? 10);
    if ($w < 1) $w = 1;
    $totalWeight += $w;
}

$pick = random_int(1, max(1, $totalWeight));
$chosen = $ads[0];
$acc = 0;
foreach ($ads as $a) {
    $w = (int)($a['weight'] ?? 10);
    if ($w < 1) $w = 1;
    $acc += $w;
    if ($pick <= $acc) {
        $chosen = $a;
        break;
    }
}

// réponse normalisée
$ad = [
    'id' => (int)$chosen['id'],
    'title' => (string)$chosen['title'],
    'placement' => (string)$chosen['placement'],
    'type' => (string)($chosen['ad_type'] ?? 'url'),
    'image_url' => $chosen['image_url'] ? (string)$chosen['image_url'] : null,
    'target_url' => $chosen['target_url'] ? (string)$chosen['target_url'] : null,
    'html_code' => $chosen['html_code'] ? (string)$chosen['html_code'] : null,
];

json_response([
    'ads_enabled' => true,
    'countdown_seconds' => max(0, $countdown),
    'ad' => $ad,
]);

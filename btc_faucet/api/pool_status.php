<?php
// api/pool_status.php
// Retourne l'état de la cagnotte globale (total/restant) + si les retraits sont ouverts.

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

$user = require_auth_user(); // on exige un user connecté
$pdo  = get_pdo();

$stmtSettings = $pdo->query('SELECT pool_total_sat, pool_remaining_sat FROM settings WHERE id = 1 LIMIT 1');
$settings = $stmtSettings->fetch();

if (!$settings) {
    json_response(['error' => 'settings_missing'], 500);
}

$poolTotal = (int)($settings['pool_total_sat'] ?? 0);
$poolRemaining = (int)($settings['pool_remaining_sat'] ?? 0);
if ($poolTotal < 0) $poolTotal = 0;
if ($poolRemaining < 0) $poolRemaining = 0;
if ($poolRemaining > $poolTotal) $poolRemaining = $poolTotal;

json_response([
    'pool_total_sat'     => $poolTotal,
    'pool_remaining_sat' => $poolRemaining,
    'withdrawals_open'   => ($poolRemaining <= 0),
]);

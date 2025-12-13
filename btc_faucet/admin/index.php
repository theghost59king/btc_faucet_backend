<?php
// admin/index.php
// Dashboard simple : quelques infos + liens

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Petites stats
$totalUsers      = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalEarnings   = (int)$pdo->query('SELECT COALESCE(SUM(amount_sat),0) FROM earnings')->fetchColumn();
$totalWithdraws  = (int)$pdo->query('SELECT COALESCE(SUM(amount_sat),0) FROM withdrawals')->fetchColumn();
$totalAds        = (int)$pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn();
$activeAds       = (int)$pdo->query('SELECT COUNT(*) FROM ads WHERE is_active = 1')->fetchColumn();

include __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Vue d'ensemble</h2>
    <div class="flex mt-2">
        <div>
            <div style="font-size:0.8rem;color:#9ca3af;">Utilisateurs</div>
            <div style="font-size:1.3rem;font-weight:700;"><?= $totalUsers ?></div>
        </div>
        <div>
            <div style="font-size:0.8rem;color:#9ca3af;">Total satoshis gagnés</div>
            <div style="font-size:1.3rem;font-weight:700;"><?= $totalEarnings ?></div>
        </div>
        <div>
            <div style="font-size:0.8rem;color:#9ca3af;">Total demandé en retrait</div>
            <div style="font-size:1.3rem;font-weight:700;"><?= $totalWithdraws ?></div>
        </div>
        <div>
            <div style="font-size:0.8rem;color:#9ca3af;">Pubs actives / total</div>
            <div style="font-size:1.3rem;font-weight:700;"><?= $activeAds ?> / <?= $totalAds ?></div>
        </div>
    </div>

    <div class="mt-4">
        <a class="btn btn-primary" href="settings.php">Gérer les paramètres</a>
        <a class="btn btn-secondary" href="ads_list.php">Gérer les publicités</a>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

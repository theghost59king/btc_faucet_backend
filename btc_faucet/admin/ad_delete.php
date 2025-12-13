<?php
// admin/ad_delete.php
// Suppression d'une publicité

declare(strict_types=1);

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $pdo = get_pdo();
    $del = $pdo->prepare('DELETE FROM ads WHERE id = :id');
    $del->execute(['id' => $id]);
}

header('Location: ads_list.php');
exit;

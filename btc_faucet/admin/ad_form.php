<?php
// admin/ad_form.php
// Formulaire pour créer / éditer une publicité

declare(strict_types=1);

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pdo     = get_pdo();
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit  = $id > 0;
$message = null;
$isError = false;

$ad = [
    'title'      => '',
    'image_url'  => '',
    'target_url' => '',
    'is_active'  => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM ads WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $adDb = $stmt->fetch();

    if (!$adDb) {
        $isEdit  = false;
        $message = "Publicité introuvable, création d'une nouvelle.";
        $isError = true;
    } else {
        $ad = $adDb;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim((string)($_POST['title'] ?? ''));
    $imageUrl  = trim((string)($_POST['image_url'] ?? ''));
    $targetUrl = trim((string)($_POST['target_url'] ?? ''));
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        $isError = true;
        $message = 'Le titre est obligatoire.';
    } else {
        if ($isEdit) {
            $upd = $pdo->prepare(
                'UPDATE ads
                 SET title = :title, image_url = :image_url, target_url = :target_url,
                     is_active = :is_active, updated_at = NOW()
                 WHERE id = :id'
            );
            $upd->execute([
                'title'      => $title,
                'image_url'  => $imageUrl !== '' ? $imageUrl : null,
                'target_url' => $targetUrl !== '' ? $targetUrl : null,
                'is_active'  => $isActive,
                'id'         => $id,
            ]);
            $message = 'Publicité mise à jour.';
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO ads (title, image_url, target_url, is_active, created_at, updated_at)
                 VALUES (:title, :image_url, :target_url, :is_active, NOW(), NOW())'
            );
            $ins->execute([
                'title'      => $title,
                'image_url'  => $imageUrl !== '' ? $imageUrl : null,
                'target_url' => $targetUrl !== '' ? $targetUrl : null,
                'is_active'  => $isActive,
            ]);
            $id      = (int)$pdo->lastInsertId();
            $isEdit  = true;
            $message = 'Publicité créée.';
        }

        // Recharger les données
        $stmt = $pdo->prepare('SELECT * FROM ads WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $ad = $stmt->fetch();
    }
}

include __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2><?= $isEdit ? 'Modifier la publicité' : 'Nouvelle publicité' ?></h2>

    <?php if ($message): ?>
        <div class="alert <?= $isError ? 'alert-error' : 'alert-success' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-2">
        <label for="title">Titre</label>
        <input type="text" id="title" name="title" required
               value="<?= htmlspecialchars((string)$ad['title'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="image_url">URL de l'image (optionnel)</label>
        <input type="url" id="image_url" name="image_url"
               value="<?= htmlspecialchars((string)$ad['image_url'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="target_url">URL de destination au clic (optionnel)</label>
        <input type="url" id="target_url" name="target_url"
               value="<?= htmlspecialchars((string)$ad['target_url'], ENT_QUOTES, 'UTF-8') ?>">

        <label class="mt-1">
            <input type="checkbox" name="is_active" <?= (int)$ad['is_active'] === 1 ? 'checked' : '' ?>>
            Publicité active
        </label>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="ads_list.php" class="btn btn-secondary">Retour à la liste</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

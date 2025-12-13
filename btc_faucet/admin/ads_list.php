<?php
// admin/ads_list.php
// Liste des publicités + lien pour créer/modifier

declare(strict_types=1);

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pdo  = get_pdo();
$stmt = $pdo->query('SELECT * FROM ads ORDER BY created_at DESC');
$ads  = $stmt->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<div class="card">
    <div class="flex-between">
        <h2>Publicités</h2>
        <a class="btn btn-primary" href="ad_form.php">+ Nouvelle publicité</a>
    </div>

    <table class="mt-2">
        <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Image</th>
            <th>Lien</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ads as $ad): ?>
            <tr>
                <td><?= (int)$ad['id'] ?></td>
                <td><?= htmlspecialchars($ad['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if (!empty($ad['image_url'])): ?>
                        <a href="<?= htmlspecialchars($ad['image_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">Voir</a>
                    <?php else: ?>
                        <span style="color:#6b7280;">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($ad['target_url'])): ?>
                        <a href="<?= htmlspecialchars($ad['target_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">Lien</a>
                    <?php else: ?>
                        <span style="color:#6b7280;">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ((int)$ad['is_active'] === 1): ?>
                        <span class="tag tag-active">Active</span>
                    <?php else: ?>
                        <span class="tag tag-inactive">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn btn-secondary" href="ad_form.php?id=<?= (int)$ad['id'] ?>">Modifier</a>
                    <a class="btn btn-danger"
                       onclick="return confirm('Supprimer cette publicité ?');"
                       href="ad_delete.php?id=<?= (int)$ad['id'] ?>">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($ads) === 0): ?>
            <tr>
                <td colspan="6" style="color:#9ca3af;">Aucune publicité pour le moment.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

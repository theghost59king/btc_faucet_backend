<?php
// admin/login.php
// Formulaire de connexion admin

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/config.php';

$error = null;

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiants invalides.';
    }
}

include __DIR__ . '/partials/header.php';
?>

<div class="card" style="max-width: 420px; margin: 2rem auto;">
    <h2>Connexion administrateur</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
        <p style="font-size:0.85rem;color:#9ca3af;">
            Utilisateur par défaut : <strong>admin</strong> / Mot de passe : <strong>admin123</strong><br>
            (à modifier dans <code>config/config.php</code>).
        </p>
    <?php endif; ?>

    <form method="post">
        <label for="username">Utilisateur</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>

        <button class="btn btn-primary mt-2" type="submit">Se connecter</button>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

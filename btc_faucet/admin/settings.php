<?php
// admin/settings.php
// Formulaire de gestion des paramètres globaux (avec monétisation)

declare(strict_types=1);

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();

$message = '';
$error   = '';

// Récupère settings (id=1)
$stmt = $pdo->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$settings = $stmt->fetch();

if (!$settings) {
    $error = "Aucune ligne settings (id=1) trouvée. Crée-la d'abord dans la BDD.";
    $settings = [
        'faucet_interval_minutes' => 60,
        'reward_min_sat' => 1,
        'reward_max_sat' => 5,
        'withdraw_min_sat' => 20000,
        'ad_countdown_seconds' => 20,

        // Nouveaux (monétisation)
        'ads_enabled' => 1,
        'ad_countdown_faucet_seconds' => 20,
        'ad_countdown_wheel_seconds' => 20,

        'pool_total_sat' => 1000000,
        'pool_remaining_sat' => 1000000,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $faucetInterval = (int)($_POST['faucet_interval_minutes'] ?? 60);
    $rewardMin      = (int)($_POST['reward_min_sat'] ?? 1);
    $rewardMax      = (int)($_POST['reward_max_sat'] ?? 5);
    $withdrawMin    = (int)($_POST['withdraw_min_sat'] ?? 20000);

    // Ancien champ global (fallback)
    $adCountdown    = (int)($_POST['ad_countdown_seconds'] ?? 20);

    // Nouveaux
    $adsEnabled     = isset($_POST['ads_enabled']) ? 1 : 0;
    $adCdFaucet     = (int)($_POST['ad_countdown_faucet_seconds'] ?? $adCountdown);
    $adCdWheel      = (int)($_POST['ad_countdown_wheel_seconds'] ?? $adCountdown);

    $poolTotal      = (int)($_POST['pool_total_sat'] ?? 1000000);
    $poolRemaining  = (int)($_POST['pool_remaining_sat'] ?? $poolTotal);
    $resetPool      = !empty($_POST['reset_pool_to_total']);

    if ($faucetInterval < 1) $faucetInterval = 1;
    if ($rewardMin < 1) $rewardMin = 1;
    if ($rewardMax < $rewardMin) $rewardMax = $rewardMin;
    if ($withdrawMin < 1) $withdrawMin = 1;

    if ($adCountdown < 0) $adCountdown = 0;
    if ($adCdFaucet < 0) $adCdFaucet = 0;
    if ($adCdWheel < 0) $adCdWheel = 0;

    if ($poolTotal < 0) $poolTotal = 0;
    if ($poolRemaining < 0) $poolRemaining = 0;
    if ($poolRemaining > $poolTotal) $poolRemaining = $poolTotal;

    if ($resetPool) {
        $poolRemaining = $poolTotal;
    }

    try {
        $upd = $pdo->prepare(
            'UPDATE settings
             SET faucet_interval_minutes = :faucet_interval,
                 reward_min_sat = :reward_min,
                 reward_max_sat = :reward_max,
                 withdraw_min_sat = :withdraw_min,

                 ad_countdown_seconds = :ad_countdown, -- fallback
                 ads_enabled = :ads_enabled,
                 ad_countdown_faucet_seconds = :ad_cd_faucet,
                 ad_countdown_wheel_seconds = :ad_cd_wheel,

                 pool_total_sat = :pool_total,
                 pool_remaining_sat = :pool_remaining
             WHERE id = 1'
        );
        $upd->execute([
            'faucet_interval' => $faucetInterval,
            'reward_min'      => $rewardMin,
            'reward_max'      => $rewardMax,
            'withdraw_min'    => $withdrawMin,

            'ad_countdown'    => $adCountdown,
            'ads_enabled'     => $adsEnabled,
            'ad_cd_faucet'    => $adCdFaucet,
            'ad_cd_wheel'     => $adCdWheel,

            'pool_total'      => $poolTotal,
            'pool_remaining'  => $poolRemaining,
        ]);

        $message = 'Paramètres mis à jour avec succès.';

        $settings['faucet_interval_minutes'] = $faucetInterval;
        $settings['reward_min_sat'] = $rewardMin;
        $settings['reward_max_sat'] = $rewardMax;
        $settings['withdraw_min_sat'] = $withdrawMin;

        $settings['ad_countdown_seconds'] = $adCountdown;
        $settings['ads_enabled'] = $adsEnabled;
        $settings['ad_countdown_faucet_seconds'] = $adCdFaucet;
        $settings['ad_countdown_wheel_seconds'] = $adCdWheel;

        $settings['pool_total_sat'] = $poolTotal;
        $settings['pool_remaining_sat'] = $poolRemaining;
    } catch (Throwable $e) {
        $error = "Erreur lors de la mise à jour des paramètres.";
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="container" style="max-width: 860px;">
    <h2>Paramètres</h2>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" style="display:grid; gap: 12px;">
        <div class="card">
            <h3>Faucet (bouton BTC)</h3>

            <label for="faucet_interval_minutes">Intervalle entre deux gains (minutes)</label>
            <input type="number" id="faucet_interval_minutes" name="faucet_interval_minutes" min="1"
                   value="<?= (int)$settings['faucet_interval_minutes'] ?>">

            <label for="reward_min_sat">Récompense minimum (satoshis)</label>
            <input type="number" id="reward_min_sat" name="reward_min_sat" min="1"
                   value="<?= (int)$settings['reward_min_sat'] ?>">

            <label for="reward_max_sat">Récompense maximum (satoshis)</label>
            <input type="number" id="reward_max_sat" name="reward_max_sat" min="1"
                   value="<?= (int)$settings['reward_max_sat'] ?>">

            <p style="color:#6b7280;margin:8px 0 0;">
                💡 Le compte à rebours pub est maintenant <b>séparé</b> : Faucet / Roue.
            </p>
        </div>

        <div class="card">
            <h3>Monétisation (publicités)</h3>

            <label style="display:flex; gap:10px; align-items:center;">
                <input type="checkbox" name="ads_enabled" value="1" <?= ((int)($settings['ads_enabled'] ?? 1) === 1) ? 'checked' : '' ?>>
                Activer les publicités dans l'app
            </label>

            <label for="ad_countdown_faucet_seconds">Compte à rebours pub (Faucet) secondes</label>
            <input type="number" id="ad_countdown_faucet_seconds" name="ad_countdown_faucet_seconds" min="0"
                   value="<?= (int)($settings['ad_countdown_faucet_seconds'] ?? ($settings['ad_countdown_seconds'] ?? 20)) ?>">

            <label for="ad_countdown_wheel_seconds">Compte à rebours pub (Roue) secondes</label>
            <input type="number" id="ad_countdown_wheel_seconds" name="ad_countdown_wheel_seconds" min="0"
                   value="<?= (int)($settings['ad_countdown_wheel_seconds'] ?? ($settings['ad_countdown_seconds'] ?? 20)) ?>">

            <details style="margin-top:10px;">
                <summary style="cursor:pointer;">Compatibilité (ancien champ)</summary>
                <div style="margin-top:10px; color:#6b7280;">
                    <p>Ce champ sert de <b>fallback</b> si les nouveaux champs n'existent pas encore.</p>
                    <label for="ad_countdown_seconds">Compte à rebours pub (fallback) secondes</label>
                    <input type="number" id="ad_countdown_seconds" name="ad_countdown_seconds" min="0"
                           value="<?= (int)($settings['ad_countdown_seconds'] ?? 20) ?>">
                </div>
            </details>
        </div>

        <div class="card">
            <h3>Retraits</h3>

            <label for="withdraw_min_sat">Seuil minimum de retrait (satoshis)</label>
            <input type="number" id="withdraw_min_sat" name="withdraw_min_sat" min="1"
                   value="<?= (int)$settings['withdraw_min_sat'] ?>">

            <p style="color:#6b7280;margin:8px 0 0;">
                ⚠️ Avec la cagnotte globale : les retraits seront autorisés uniquement quand <b>pool_remaining_sat = 0</b>.
            </p>
        </div>

        <div class="card">
            <h3>Cagnotte globale</h3>

            <label for="pool_total_sat">Cagnotte totale (satoshis)</label>
            <input type="number" id="pool_total_sat" name="pool_total_sat" min="0"
                   value="<?= (int)$settings['pool_total_sat'] ?>">

            <label for="pool_remaining_sat">Cagnotte restante (satoshis)</label>
            <input type="number" id="pool_remaining_sat" name="pool_remaining_sat" min="0"
                   value="<?= (int)$settings['pool_remaining_sat'] ?>">

            <label style="display:flex; gap:10px; align-items:center; margin-top:8px;">
                <input type="checkbox" name="reset_pool_to_total" value="1">
                Réinitialiser la cagnotte restante = cagnotte totale
            </label>
        </div>

        <button type="submit" class="btn">Enregistrer</button>
    </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

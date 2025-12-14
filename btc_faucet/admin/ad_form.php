<?php
// admin/ad_form.php
// Formulaire pour créer / éditer une publicité (monétisation avancée)

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

// Valeurs par défaut (important si colonnes ajoutées récemment)
$ad = [
    'title'      => '',
    'image_url'  => '',
    'target_url' => '',
    'is_active'  => 1,

    // Nouveaux champs
    'placement'  => 'faucet', // faucet | wheel | home
    'ad_type'    => 'url',    // url | image | html
    'weight'     => 10,
    'html_code'  => '',
    'starts_at'  => null,
    'ends_at'    => null,
];

$allowedPlacements = ['faucet' => 'Faucet (bouton BTC)', 'wheel' => 'Roue', 'home' => 'Accueil (optionnel)'];
$allowedTypes = ['url' => 'URL (Smartlink / WebView)', 'image' => 'Image + bouton', 'html' => 'HTML (web uniquement)'];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM ads WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $adDb = $stmt->fetch();

    if (!$adDb) {
        $isEdit  = false;
        $message = "Publicité introuvable, création d'une nouvelle.";
        $isError = true;
    } else {
        // Fusionner (pour ne pas casser si colonnes manquantes)
        $ad = array_merge($ad, $adDb);
    }
}

/**
 * Convertit une date HTML (YYYY-MM-DDTHH:MM ou YYYY-MM-DD HH:MM) en DATETIME MySQL (YYYY-MM-DD HH:MM:SS)
 */
function normalize_datetime(?string $s): ?string
{
    if ($s === null) return null;
    $s = trim($s);
    if ($s === '') return null;

    // Accepte "2025-12-14T12:30"
    $s = str_replace('T', ' ', $s);

    // Si pas de secondes, on ajoute ":00"
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $s)) {
        $s .= ':00';
    }

    // Validation simple
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $s)) {
        return null;
    }

    return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim((string)($_POST['title'] ?? ''));
    $imageUrl  = trim((string)($_POST['image_url'] ?? ''));
    $targetUrl = trim((string)($_POST['target_url'] ?? ''));
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    // Nouveaux champs
    $placement = trim((string)($_POST['placement'] ?? 'faucet'));
    $adType    = trim((string)($_POST['ad_type'] ?? 'url'));
    $weight    = (int)($_POST['weight'] ?? 10);
    $htmlCode  = (string)($_POST['html_code'] ?? '');
    $startsAt  = normalize_datetime($_POST['starts_at'] ?? null);
    $endsAt    = normalize_datetime($_POST['ends_at'] ?? null);

    if ($weight < 1) $weight = 1;

    if (!array_key_exists($placement, $allowedPlacements)) {
        $placement = 'faucet';
    }
    if (!array_key_exists($adType, $allowedTypes)) {
        $adType = 'url';
    }

    if ($title === '') {
        $isError = true;
        $message = 'Le titre est obligatoire.';
    } else {
        // Petite validation logique
        if ($adType === 'url' && $targetUrl === '') {
            $isError = true;
            $message = "Pour le type 'URL', l'URL de destination est recommandée (target_url).";
        } elseif ($adType === 'image' && $imageUrl === '') {
            $isError = true;
            $message = "Pour le type 'Image', l'URL de l'image est obligatoire (image_url).";
        } elseif ($adType === 'html' && trim($htmlCode) === '') {
            $isError = true;
            $message = "Pour le type 'HTML', le code HTML est obligatoire.";
        } else {
            if ($isEdit) {
                $upd = $pdo->prepare(
                    'UPDATE ads
                     SET title = :title,
                         image_url = :image_url,
                         target_url = :target_url,
                         is_active = :is_active,
                         placement = :placement,
                         ad_type = :ad_type,
                         weight = :weight,
                         html_code = :html_code,
                         starts_at = :starts_at,
                         ends_at = :ends_at,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $upd->execute([
                    'title'      => $title,
                    'image_url'  => $imageUrl !== '' ? $imageUrl : null,
                    'target_url' => $targetUrl !== '' ? $targetUrl : null,
                    'is_active'  => $isActive,
                    'placement'  => $placement,
                    'ad_type'    => $adType,
                    'weight'     => $weight,
                    'html_code'  => trim($htmlCode) !== '' ? $htmlCode : null,
                    'starts_at'  => $startsAt,
                    'ends_at'    => $endsAt,
                    'id'         => $id,
                ]);
                $message = 'Publicité mise à jour.';
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO ads (title, image_url, target_url, is_active, placement, ad_type, weight, html_code, starts_at, ends_at, created_at, updated_at)
                     VALUES (:title, :image_url, :target_url, :is_active, :placement, :ad_type, :weight, :html_code, :starts_at, :ends_at, NOW(), NOW())'
                );
                $ins->execute([
                    'title'      => $title,
                    'image_url'  => $imageUrl !== '' ? $imageUrl : null,
                    'target_url' => $targetUrl !== '' ? $targetUrl : null,
                    'is_active'  => $isActive,
                    'placement'  => $placement,
                    'ad_type'    => $adType,
                    'weight'     => $weight,
                    'html_code'  => trim($htmlCode) !== '' ? $htmlCode : null,
                    'starts_at'  => $startsAt,
                    'ends_at'    => $endsAt,
                ]);
                $id      = (int)$pdo->lastInsertId();
                $isEdit  = true;
                $message = 'Publicité créée.';
            }

            // Recharger les données
            $stmt = $pdo->prepare('SELECT * FROM ads WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $ad = array_merge($ad, (array)$stmt->fetch());
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2><?= $isEdit ? 'Modifier la publicité' : 'Nouvelle publicité' ?></h2>

    <?php if ($message): ?>
        <div class="alert <?= $isError ? 'alert-error' : 'alert-success' ?>">
            <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-2" style="display:grid; gap:12px;">
        <label for="title">Titre</label>
        <input type="text" id="title" name="title" required
               value="<?= htmlspecialchars((string)$ad['title'], ENT_QUOTES, 'UTF-8') ?>">

        <div style="display:grid; gap:12px; grid-template-columns: 1fr 1fr 1fr;">
            <div>
                <label for="placement">Emplacement</label>
                <select id="placement" name="placement">
                    <?php foreach ($allowedPlacements as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ((string)$ad['placement'] === $key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="ad_type">Type</label>
                <select id="ad_type" name="ad_type">
                    <?php foreach ($allowedTypes as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ((string)$ad['ad_type'] === $key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="weight">Poids (rotation)</label>
                <input type="number" id="weight" name="weight" min="1"
                       value="<?= (int)($ad['weight'] ?? 10) ?>">
                <small style="color:#6b7280;">Ex: 10 normal, 30 prioritaire</small>
            </div>
        </div>

        <label for="image_url">URL de l'image (obligatoire si type = Image)</label>
        <input type="url" id="image_url" name="image_url"
               value="<?= htmlspecialchars((string)($ad['image_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <label for="target_url">URL de destination (Smartlink / clic)</label>
        <input type="url" id="target_url" name="target_url"
               value="<?= htmlspecialchars((string)($ad['target_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <label for="html_code">Code HTML (uniquement si type = HTML)</label>
        <textarea id="html_code" name="html_code" rows="6"
                  placeholder="<script>...</script>"><?= htmlspecialchars((string)($ad['html_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <div style="display:grid; gap:12px; grid-template-columns: 1fr 1fr;">
            <div>
                <label for="starts_at">Début (optionnel)</label>
                <input type="datetime-local" id="starts_at" name="starts_at"
                       value="<?php
                            $v = (string)($ad['starts_at'] ?? '');
                            $v = str_replace(' ', 'T', substr($v, 0, 16));
                            echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                       ?>">
            </div>
            <div>
                <label for="ends_at">Fin (optionnel)</label>
                <input type="datetime-local" id="ends_at" name="ends_at"
                       value="<?php
                            $v = (string)($ad['ends_at'] ?? '');
                            $v = str_replace(' ', 'T', substr($v, 0, 16));
                            echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                       ?>">
            </div>
        </div>

        <label class="mt-1">
            <input type="checkbox" name="is_active" <?= (int)($ad['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
            Publicité active
        </label>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="ads_list.php" class="btn btn-secondary">Retour à la liste</a>
        </div>

        <div class="card" style="background:#0b1220;color:#e5e7eb;">
            <h3 style="margin-top:0;">Conseils rapides</h3>
            <ul style="margin:0; padding-left:18px; color:#cbd5e1;">
                <li><b>Type URL</b> : mets ton smartlink dans <code>target_url</code>.</li>
                <li><b>Type Image</b> : mets une image dans <code>image_url</code> + un lien dans <code>target_url</code>.</li>
                <li><b>Poids</b> : plus grand = affiché plus souvent.</li>
                <li><b>Dates</b> : utile pour programmer une campagne.</li>
            </ul>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

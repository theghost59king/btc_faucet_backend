<?php
// api/register.php
// Inscription d'un nouvel utilisateur (email + mot de passe + pseudo)
// ✅ Correction robuste pour hosting (InfinityFree) :
// - Normalisation email (trim + lowercase)
// - Validation de longueur (évite les troncatures MySQL)
// - Gestion propre des erreurs "duplicate key" (email déjà utilisé)

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$input = get_json_input();

$emailRaw = isset($input['email']) ? trim((string)$input['email']) : '';
$email    = mb_strtolower($emailRaw); // normalisation (évite Nico@ / nico@)
$password = isset($input['password']) ? (string)$input['password'] : '';
$pseudo   = isset($input['pseudo']) ? trim((string)$input['pseudo']) : '';

// Validations simples
if ($email === '' || $password === '' || $pseudo === '') {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Email, mot de passe et pseudo sont obligatoires.',
    ], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Email invalide.',
    ], 400);
}

// ⚠️ IMPORTANT : protège contre les colonnes trop courtes / troncature SQL
// Après migration, on vise VARCHAR(191). On bloque au-delà pour être sûr.
if (mb_strlen($email) > 191) {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Email trop long (max 191 caractères).',
    ], 400);
}

if (mb_strlen($pseudo) < 3 || mb_strlen($pseudo) > 30) {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Pseudo invalide (3 à 30 caractères).',
    ], 400);
}

if (mb_strlen($password) < 6) {
    json_response([
        'error'   => 'validation_error',
        'message' => 'Mot de passe trop court (min 6 caractères).',
    ], 400);
}

$pdo = get_pdo();

// Vérifier si email déjà utilisé (comparaison insensible à la casse)
$stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    json_response([
        'error'   => 'email_exists',
        'message' => 'Cet email est déjà enregistré.',
    ], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$apiToken     = generate_api_token();

$now = now_datetime();

$pdo->beginTransaction();

try {
    // Créer l'utilisateur
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, pseudo, api_token, created_at, updated_at)
         VALUES (:email, :password_hash, :pseudo, :api_token, :created_at, :updated_at)'
    );
    $stmt->execute([
        'email'         => $email,
        'password_hash' => $passwordHash,
        'pseudo'        => $pseudo,
        'api_token'     => $apiToken,
        'created_at'    => $now,
        'updated_at'    => $now,
    ]);

    $userId = (int)$pdo->lastInsertId();

    // Créer le wallet BTC par défaut
    $stmtWallet = $pdo->prepare(
        'INSERT INTO wallets (user_id, currency, balance_sat, total_earned_sat, created_at, updated_at)
         VALUES (:user_id, :currency, 0, 0, :created_at, :updated_at)'
    );
    $stmtWallet->execute([
        'user_id'    => $userId,
        'currency'   => 'BTC',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $pdo->commit();

    json_response([
        'id'     => $userId,
        'email'  => $email,
        'pseudo' => $pseudo,
        'token'  => $apiToken,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Gestion spécifique doublon (clé unique email)
    // Code MySQL typique : 1062 Duplicate entry
    $sqlState = $e->getCode();
    $msg = $e->getMessage();

    if (strpos($msg, '1062') !== false) {
        json_response([
            'error'   => 'email_exists',
            'message' => 'Cet email est déjà enregistré.',
        ], 409);
    }

    json_response([
        'error'   => 'server_error',
        'message' => 'Erreur lors de la création du compte.',
    ], 500);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'error'   => 'server_error',
        'message' => 'Erreur lors de la création du compte.',
    ], 500);
}

README backend BTC Faucet
=========================

1. Installation

- Copier le dossier `btc_faucet` dans :
  C:\wamp64\www\btc_faucet

- Démarrer WampServer.
- Ouvrir phpMyAdmin (http://localhost/phpmyadmin).

2. Base de données

- Exécuter d'abord le fichier `schema.sql` (crée la base `btc_faucet` et les tables).
- Exécuter ensuite `seed.sql` (insère les paramètres et un exemple de publicité).

3. Configuration

- Fichier : config/config.php

Adapter si besoin :
- DB_HOST, DB_NAME, DB_USER, DB_PASSWORD (connexion MySQL).
- BASE_URL (URL de base pour l'API).
- ADMIN_USERNAME, ADMIN_PASSWORD (identifiants de l'admin).

4. Interface d'administration

- URL : http://localhost/btc_faucet/admin/login.php
- Identifiants par défaut :
  - Utilisateur : admin
  - Mot de passe : admin123

Dans l'admin, tu peux :
- Gérer les paramètres globaux (intervalle entre deux gains, récompense min/max, temps du compte à rebours pub, seuil de retrait).
- Gérer les publicités (création, activation/désactivation).

5. API principale (pour l'app mobile)

Endpoints (tous en POST/GET JSON) :

- POST /btc_faucet/api/register.php
  body: { "email": "...", "password": "...", "pseudo": "..." }

- POST /btc_faucet/api/login.php
  body: { "email": "...", "password": "..." }

- POST /btc_faucet/api/login_google.php
  body: { "google_id": "...", "email": "...", "pseudo": "..." }

- GET /btc_faucet/api/me.php
  headers: Authorization: Bearer <token>

- POST /btc_faucet/api/update_profile.php
  headers: Authorization: Bearer <token>
  body: { "pseudo": "..." }

- GET /btc_faucet/api/faucet_status.php
  headers: Authorization: Bearer <token>

- GET /btc_faucet/api/ads_random.php

- POST /btc_faucet/api/faucet_claim.php
  headers: Authorization: Bearer <token>
  body: { "ad_id": <id de la pub vue> } (optionnel)

- GET /btc_faucet/api/wallet.php
  headers: Authorization: Bearer <token>

- POST /btc_faucet/api/withdraw.php
  headers: Authorization: Bearer <token>
  body: { "address": "adresse btc...", "amount_sat": 20000 }

6. Tests rapides

Tu peux tester avec Postman / Insomnia :
- Créer un utilisateur (register).
- Te connecter (login) → récupérer le token.
- Appeler faucet_status, ads_random, puis faucet_claim.
- Vérifier dans l'admin que les satoshis gagnés / retraits se mettent à jour.

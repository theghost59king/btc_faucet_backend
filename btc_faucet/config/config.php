<?php
// config/config.php
// Configuration globale de l'application backend (hors secrets sensibles)

declare(strict_types=1);

// Paramètres de connexion MySQL (adapter si besoin)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'btc_faucet');
define('DB_USER', 'root');
define('DB_PASSWORD', ''); // Par défaut sous Wamp, mot de passe souvent vide

// URL de base de l'API (à adapter selon ton réseau)
// Pour tests sur le même PC, ça peut être : http://localhost/btc_faucet
define('BASE_URL', 'http://localhost/btc_faucet');

// Configuration admin (interface de gestion des pubs et paramètres)
// ⚠️ Pour la démo, on stocke ça ici. À changer en prod.
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // À changer impérativement plus tard !

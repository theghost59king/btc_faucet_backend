<?php
// C:\wamp64\www\btc_faucet\api\cors.php
// Gère les en-têtes CORS pour autoriser l'app Flutter (web) à appeler l'API.

// Autoriser toutes les origines (pour le dev local)
// En production tu pourras restreindre à un domaine précis.
header('Access-Control-Allow-Origin: *');

// Autoriser certains en-têtes (JSON + Auth)
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Autoriser les méthodes HTTP utilisées par l'API
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Très important : répondre proprement aux requêtes "OPTIONS" (pré-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

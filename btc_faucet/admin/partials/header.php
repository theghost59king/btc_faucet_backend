<?php
// admin/partials/header.php

// On ne démarre la session que si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin BTC Faucet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            margin: 0;
        }
        header {
            background: linear-gradient(90deg, #f97316, #facc15);
            color: #111827;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        header h1 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        header a {
            color: #111827;
            text-decoration: none;
            font-weight: 600;
        }
        main {
            max-width: 960px;
            margin: 1.5rem auto;
            padding: 0 1rem;
        }
        .card {
            background: #111827;
            border-radius: 0.75rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        .card h2 {
            margin-top: 0;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }
        label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
            color: #9ca3af;
        }
        input[type="text"],
        input[type="number"],
        input[type="password"],
        input[type="url"] {
            width: 100%;
            padding: 0.5rem 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            box-sizing: border-box;
            margin-bottom: 0.75rem;
        }
        input[type="checkbox"] {
            transform: scale(1.1);
            margin-right: 0.4rem;
        }
        button,
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(90deg, #f97316, #facc15);
            color: #111827;
        }
        .btn-secondary {
            background: #111827;
            color: #e5e7eb;
            border: 1px solid #374151;
        }
        .btn-danger {
            background: #b91c1c;
            color: #fee2e2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        th, td {
            padding: 0.5rem 0.4rem;
            border-bottom: 1px solid #1f2937;
        }
        th {
            text-align: left;
            color: #9ca3af;
        }
        tr:hover td {
            background: #020617;
        }
        .tag {
            display: inline-block;
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
        }
        .tag-active {
            background: rgba(34,197,94,0.15);
            color: #bbf7d0;
        }
        .tag-inactive {
            background: rgba(239,68,68,0.15);
            color: #fecaca;
        }
        .alert {
            padding: 0.5rem 0.75rem;
            border-radius: 0.6rem;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
        }
        .alert-success {
            background: rgba(16,185,129,0.15);
            color: #6ee7b7;
        }
        .alert-error {
            background: rgba(239,68,68,0.15);
            color: #fecaca;
        }
        .flex {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 0.75rem; }
        .mt-4 { margin-top: 1rem; }
    </style>
</head>
<body>
<header>
    <h1>Admin BTC Faucet</h1>
    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
        <div>
            <a href="index.php">Dashboard</a> |
            <a href="settings.php">Paramètres</a> |
            <a href="ads_list.php">Publicités</a> |
            <a href="logout.php">Déconnexion</a>
        </div>
    <?php endif; ?>
</header>
<main>

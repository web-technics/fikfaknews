<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
    $wachtwoord = $_POST['wachtwoord'] ?? '';

    if (!$gebruikersnaam || !$wachtwoord) {
        echo 'Vul alle velden in.';
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, wachtwoord FROM users WHERE gebruikersnaam = ? OR email = ?');
    $stmt->execute([$gebruikersnaam, $gebruikersnaam]);
    $user = $stmt->fetch();

    if ($user && password_verify($wachtwoord, $user['wachtwoord'])) {
        $_SESSION['user_id'] = $user['id'];
        echo 'Succesvol ingelogd!';
    } else {
        echo 'Ongeldige inloggegevens.';
    }
}

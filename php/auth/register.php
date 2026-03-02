<?php
require 'config.php';

// Registratieformulier verwerken
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $wachtwoord = $_POST['wachtwoord'] ?? '';

    if (!$gebruikersnaam || !$email || !$wachtwoord) {
        echo 'Vul alle velden in.';
        exit;
    }

    // Controleren of gebruiker al bestaat
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR gebruikersnaam = ?');
    $stmt->execute([$email, $gebruikersnaam]);
    if ($stmt->fetch()) {
        echo 'Gebruiker bestaat al.';
        exit;
    }

    // Wachtwoord hashen
    $hash = password_hash($wachtwoord, PASSWORD_DEFAULT);

    // Gebruiker toevoegen
    $stmt = $pdo->prepare('INSERT INTO users (gebruikersnaam, email, wachtwoord) VALUES (?, ?, ?)');
    $stmt->execute([$gebruikersnaam, $email, $hash]);

    echo 'Registratie geslaagd! Je kan nu inloggen.';
}

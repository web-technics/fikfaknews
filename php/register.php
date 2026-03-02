<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$db   = 'ffgo';
$user = 'ffgo';
$pass = 'wN5eHEbwTuFxppi4KLpg';
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_error);
}

$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($username === '' || $email === '' || $password === '' || $password2 === '') {
        $error = 'Vul alle velden in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig e-mailadres.';
    } elseif ($password !== $password2) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email=? OR username=?');
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Gebruiker met dit e-mailadres of username bestaat al.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $now = date('Y-m-d H:i:s');
            $stmt2 = $mysqli->prepare('INSERT INTO users (username, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
            $stmt2->bind_param('sssss', $username, $email, $hash, $now, $now);
            $stmt2->execute();
            $stmt2->close();
            $success = true;
        }
        $stmt->close();
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Registreren - Fikfak News</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="../assets/account-shared.css">
</head>
<body>
<div class="account-box">
    <h2>Registreren</h2>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="success">Registratie gelukt! <a href="login.php">Inloggen</a></div>
    <?php else: ?>
    <form method="post">
        <input type="text" name="username" placeholder="Gebruikersnaam" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="password" placeholder="Wachtwoord" required>
        <input type="password" name="password2" placeholder="Herhaal wachtwoord" required>
        <button class="account-btn" type="submit">Registreren</button>
    </form>
    <a class="account-link" href="login.php">Al een account? Inloggen</a>
    <?php endif; ?>
</div>
</body>
</html>

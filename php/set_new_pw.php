<?php
// Set new password page for users with a valid reset token
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

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
$token = $_GET['token'] ?? '';
if ($token === '') {
    $error = 'Ongeldige of ontbrekende reset-token.';
} else {
    $stmt = $mysqli->prepare('SELECT id, reset_expires FROM users WHERE reset_token=?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($user_id, $reset_expires);
    if ($stmt->fetch()) {
        if (strtotime($reset_expires) < time()) {
            $error = 'Deze reset-link is verlopen.';
        }
    } else {
        $error = 'Ongeldige reset-link.';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($password === '' || $password2 === '') {
        $error = 'Vul beide wachtwoordvelden in.';
    } elseif ($password !== $password2) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt2 = $mysqli->prepare('UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?');
        $stmt2->bind_param('si', $hash, $user_id);
        $stmt2->execute();
        $stmt2->close();
        $success = true;
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Nieuw wachtwoord instellen - Fikfak News</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="../assets/account-shared.css">
</head>
<body>
<div class="account-box">
    <h2>Nieuw wachtwoord instellen</h2>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="success">Je wachtwoord is succesvol gewijzigd! <a href="login.php">Inloggen</a></div>
    <?php elseif ($error === '' && $token !== ''): ?>
    <form method="post">
        <input type="password" name="password" placeholder="Nieuw wachtwoord" required autofocus>
        <input type="password" name="password2" placeholder="Herhaal nieuw wachtwoord" required>
        <button class="account-btn" type="submit">Wachtwoord instellen</button>
    </form>
    <?php endif; ?>
    <a class="account-link" href="login.php">Terug naar inloggen</a>
</div>
</body>
</html>

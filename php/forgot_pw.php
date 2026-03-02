<?php
// Self-service password reset request page for users
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vul een geldig e-mailadres in.';
    } else {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email=?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $error = 'Geen gebruiker gevonden met dit e-mailadres.';
        } else {
            // Generate a reset token and expiry
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $stmt2 = $mysqli->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE email=?');
            $stmt2->bind_param('sss', $token, $expires, $email);
            $stmt2->execute();
            $stmt2->close();
            // Send email (for now, just display the link)
            $reset_link = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/set_new_pw.php?token=' . $token;
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
    <title>Wachtwoord vergeten - Fikfak News</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="../assets/account-shared.css">
    <style>
        .reset-link { word-break: break-all; color: var(--accent); font-size: 1.05em; }
    </style>
</head>
<body>
<div class="account-box">
    <h2>Wachtwoord vergeten?</h2>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="success">Er is een reset-link aangemaakt. (In productie zou deze per e-mail worden verzonden.)</div>
        <div class="reset-link">Reset-link: <a href="<?php echo htmlspecialchars($reset_link); ?>"><?php echo htmlspecialchars($reset_link); ?></a></div>
    <?php else: ?>
    <form method="post">
        <input type="email" name="email" placeholder="E-mail" required autofocus>
        <button class="account-btn" type="submit">Stuur reset-link</button>
    </form>
    <?php endif; ?>
    <a class="account-link" href="login.php">Terug naar inloggen</a>
</div>
</body>
</html>

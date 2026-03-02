<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'fikfak-admin') {
    header('Location: login.php');
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

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid user ID.');

// Generate a random password
function randomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $pw = '';
    for ($i = 0; $i < $length; $i++) {
        $pw .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pw;
}
$new_pw = randomPassword(12);
$hash = password_hash($new_pw, PASSWORD_BCRYPT);

$stmt = $mysqli->prepare('UPDATE users SET password=? WHERE id=?');
$stmt->bind_param('si', $hash, $id);
$stmt->execute();
$stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Wachtwoord Gereset - Fikfak News</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .pw-box { max-width: 420px; margin: 60px auto; background: #fff; padding: 32px; border-radius: 14px; box-shadow: 0 4px 24px #1c63cf22; }
        h2 { margin-bottom: 20px; color: #1c63cf; font-size: 2em; font-weight: 700; letter-spacing: 0.5px; }
        .pw { font-size: 1.2em; color: #1c63cf; margin: 20px 0; word-break: break-all; }
        .back-btn { background: #1c63cf; color: #fff; border: none; padding: 12px 28px; border-radius: 7px; font-size: 1.1em; cursor: pointer; margin-top: 18px; font-weight: 600; box-shadow: 0 2px 8px #1c63cf22; transition: background 0.2s; }
        .back-btn:hover { background: #174fa3; }
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 18px; }
        .nav-link { color: #1c63cf; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .nav-link:hover { color: #174fa3; text-decoration: underline; }
    </style>
</head>
<body>
<div class="pw-box">
    <div class="nav-links">
        <a class="nav-link" href="../index.html">← Terug naar website</a>
        <a class="nav-link" href="logout.php">Uitloggen</a>
    </div>
    <h2>Nieuw wachtwoord gegenereerd</h2>
    <div class="pw">Nieuw wachtwoord: <strong><?php echo htmlspecialchars($new_pw); ?></strong></div>
    <button class="back-btn" onclick="window.location.href='user_manager.php'">Terug naar beheer</button>
</div>
</body>
</html>

<?php
session_start();
$host = 'localhost';
$db   = 'ffgo';
$user = 'ffgo';
$pass = 'wN5eHEbwTuFxppi4KLpg';
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    $mysqli->close();
    header('Location: login.php');
    exit;
}

$roleStmt = $mysqli->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$roleStmt->bind_param('i', $_SESSION['user_id']);
$roleStmt->execute();
$roleStmt->bind_result($role);
$roleStmt->fetch();
$roleStmt->close();
if ($role !== 'admin') {
    $mysqli->close();
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid user ID.');

$lookupStmt = $mysqli->prepare('SELECT email, username FROM users WHERE id = ? LIMIT 1');
$lookupStmt->bind_param('i', $id);
$lookupStmt->execute();
$lookupStmt->bind_result($user_email, $username);
$lookupStmt->fetch();
$lookupStmt->close();

if (!$user_email) {
    $mysqli->close();
    die('Gebruiker niet gevonden.');
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 3600);
$stmt = $mysqli->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?');
$stmt->bind_param('ssi', $token, $expires, $id);
$stmt->execute();
$stmt->close();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
$hostName = $_SERVER['HTTP_HOST'] ?? 'go.fikfak.news';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
$reset_link = $scheme . '://' . $hostName . $basePath . '/set_new_pw.php?token=' . $token;

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Reset-link Aangemaakt - Fikfak News</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .pw-box { max-width: 420px; margin: 60px auto; background: #fff; padding: 32px; border-radius: 14px; box-shadow: 0 4px 24px #1c63cf22; }
        h2 { margin-bottom: 20px; color: #1c63cf; font-size: 2em; font-weight: 700; letter-spacing: 0.5px; }
        .pw { font-size: 1.05em; color: #1c63cf; margin: 20px 0; word-break: break-all; }
        .muted { color: #4b5563; line-height: 1.5; }
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
    <h2>Reset-link aangemaakt</h2>
    <div class="muted">Stuur deze link naar <strong><?php echo htmlspecialchars($username); ?></strong> via e-mail of bericht. De link verloopt over 1 uur.</div>
    <div class="pw"><strong><?php echo htmlspecialchars($reset_link); ?></strong></div>
    <button class="back-btn" onclick="window.location.href='user_manager.php'">Terug naar beheer</button>
</div>
</body>
</html>

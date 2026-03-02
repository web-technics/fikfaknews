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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $badges = trim($_POST['badges'] ?? '');
    $stmt = $mysqli->prepare('UPDATE users SET username=?, email=?, badges=? WHERE id=?');
    $stmt->bind_param('sssi', $username, $email, $badges, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: user_manager.php');
    exit;
}

$res = $mysqli->prepare('SELECT username, email, badges FROM users WHERE id=?');
$res->bind_param('i', $id);
$res->execute();
$res->bind_result($username, $email, $badges);
$res->fetch();
$res->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bewerk gebruiker - Fikfak News</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="../assets/account-shared.css">
    <style>
        .edit-box {
            max-width: 420px;
            margin: 60px auto;
            background: var(--card);
            padding: 32px;
            border-radius: 14px;
            box-shadow: 0 4px 24px #1c63cf22;
            color: #e6eef6;
        }
        h2 {
            margin-bottom: 20px;
            color: var(--accent);
            font-size: 2em;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        label {
            display: block;
            margin-top: 16px;
            font-weight: 600;
            color: var(--accent);
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border-radius: 7px;
            border: 1px solid #23304a;
            background: #101a2b;
            color: #e6eef6;
            font-size: 1.1em;
        }
        .save-btn {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 13px 0;
            border-radius: 7px;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            margin-top: 18px;
            cursor: pointer;
            box-shadow: 0 2px 8px #1c63cf22;
            transition: background 0.2s;
        }
        .save-btn:hover {
            background: #174fa3;
        }
        .back-btn {
            background: #888;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 7px;
            font-size: 1em;
            cursor: pointer;
            margin-top: 18px;
            margin-right: 10px;
            font-weight: 600;
        }
        .nav-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
            display: block;
            margin-top: 18px;
            text-align: center;
        }
        .nav-link:hover {
            color: #174fa3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="edit-box">
    <h2>Bewerk gebruiker</h2>
    <form method="post">
        <label for="username">Gebruikersnaam</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <label for="badges">Badges (JSON)</label>
        <textarea id="badges" name="badges" rows="3"><?php echo htmlspecialchars($badges); ?></textarea>
        <button class="save-btn" type="submit">Opslaan</button>
        <button class="back-btn" type="button" onclick="window.location.href='user_manager.php'">Annuleren</button>
    </form>
    <a class="nav-link" href="../index.html">← Terug naar website</a>
</div>
</body>
</html>

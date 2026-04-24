<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = (string) ($_SESSION['username'] ?? '');
$isAdmin = ($username === 'fikfak-admin');

// Optional DB role check; must never crash the page.
$host = 'localhost';
$db   = 'ffgo';
$user = 'ffgo';
$pass = 'wN5eHEbwTuFxppi4KLpg';
$mysqli = @new mysqli($host, $user, $pass, $db);
if (!$mysqli->connect_errno) {
    $stmt = @$mysqli->prepare('SELECT role, username FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($role, $dbUsername);
        if ($stmt->fetch()) {
            if (!empty($dbUsername)) {
                $username = (string) $dbUsername;
                $_SESSION['username'] = $username;
            }
            if ((string) $role === 'admin') {
                $isAdmin = true;
            }
        }
        $stmt->close();
    }
    $mysqli->close();
}

if (!$isAdmin) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/account-shared.css">
    <style>
        .admin-box {
            max-width: 700px;
            margin: 60px auto 0 auto;
            background: var(--card);
            padding: 36px 32px 32px 32px;
            border-radius: 18px;
            box-shadow: 0 4px 24px #1c63cf22;
            position: relative;
            color: #e6eef6;
        }
        h2 {
            margin-bottom: 24px;
            color: var(--accent);
            font-size: 2.1em;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .logout {
            position: absolute;
            top: 24px;
            right: 32px;
            color: #fff;
            background: var(--accent);
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            box-shadow: 0 2px 8px #1c63cf22;
            transition: background 0.2s;
        }
        .logout:hover {
            background: #174fa3;
        }
        .main-btn, .back-btn {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 1.15em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 32px;
            box-shadow: 0 2px 8px #1c63cf22;
            transition: background 0.2s;
        }
        .main-btn:hover, .back-btn:hover {
            background: #174fa3;
        }
        .back-btn {
            padding: 10px 22px;
            font-size: 1em;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .tools {
            margin-top: 36px;
        }
        .notice {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #101a2b;
            border: 1px solid #23304a;
            color: #c7d7ee;
            font-size: 1em;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<div class="admin-box">
    <button class="back-btn" style="background:#1c63cf;margin-bottom:20px;" onclick="window.location.href='../index.html'">&larr; Terug naar website</button>
    <button class="back-btn" style="background:#1c63cf;margin-bottom:20px;margin-left:10px;" onclick="window.location.href='dashboard.php'">
        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Dashboard'); ?> dashboard
    </button>
    <h2>Administrator Dashboard</h2>
    <div class="notice">
        Dashboard draait in herstelmodus om 500-fouten te vermijden.
        Basisbeheer blijft beschikbaar via de knop hieronder.
    </div>
    <div class="tools">
        <button class="main-btn" onclick="openUserManager()">Fikfakkers Beheer</button>
    </div>
    <script>
      function openUserManager() {
        window.location.href = 'user_manager.php';
      }
    </script>
</div>
</body>
</html>

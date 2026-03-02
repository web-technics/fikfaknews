<?php
session_start();
if (!isset($_SESSION['user_id'])) {
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

$stmt = $mysqli->prepare('SELECT role FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();
if ($role !== 'admin') {
    $mysqli->close();
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

// Get user stats
$res = $mysqli->query('SELECT COUNT(*) AS total FROM users');
$row = $res->fetch_assoc();
$total_users = $row['total'];
$res->free();
// Recent signups (last 7 days)
$res = $mysqli->query("SELECT COUNT(*) AS recent FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$row = $res->fetch_assoc();
$recent_signups = $row['recent'];
$res->free();
$mysqli->close();
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
        .stats {
            font-size: 1.25em;
            margin-bottom: 36px;
            color: #e6eef6;
        }
        .tools {
            margin-top: 36px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #23304a; }
        th { background: #101a2b; color: #e6eef6; }
    </style>
</head>
<body>
<div class="admin-box">
    <button class="back-btn" style="background:#1c63cf;margin-bottom:20px;" onclick="window.location.href='../index.html'">&larr; Terug naar website</button>
    <button class="back-btn" style="background:#1c63cf;margin-bottom:20px;margin-left:10px;" onclick="window.location.href='dashboard.php'">
        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Dashboard'); ?> dashboard
    </button>
    <h2>Administrator Dashboard</h2>
    <div class="stats">
        <div>Totaal aantal fikfakkers: <strong><?php echo $total_users; ?></strong></div>
        <div style="margin-top:10px;">Nieuwe aanmeldingen (7 dagen): <strong><?php echo $recent_signups; ?></strong></div>
    </div>
    <div class="tools">
        <button class="main-btn" onclick="openUserManager()">Fikfakkers Beheer</button>
    </div>
    <!-- Example table for consistency, update if you add user lists here -->
    <!--
    <table class="user-table">
      <tr><th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Lid sinds</th><th>Acties</th></tr>
      ...
    </table>
    -->
    <script>
      function openUserManager() {
        window.location.href = 'user_manager.php';
      }
    </script>
</div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$is_admin = ($username === 'fikfak-admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../assets/account-shared.css">
    <style>
        .dashboard {
            max-width: 500px;
            margin: 60px auto;
            background: var(--card);
            padding: 36px 32px 32px 32px;
            border-radius: 14px;
            box-shadow: 0 4px 24px #1c63cf22;
            position: relative;
            color: #e6eef6;
        }
        h2 {
            margin-bottom: 20px;
            color: var(--accent);
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
        .dashboard-btn, .dashboard-link {
            display:inline-block;
            margin-top:10px;
            margin-bottom:10px;
            padding:10px 20px;
            background: var(--accent);
            color:#fff;
            text-decoration:none;
            border-radius:6px;
            font-weight:600;
            transition: background 0.2s;
        }
        .dashboard-link {
            background: #0078d4;
            border-radius:4px;
        }
        .dashboard-btn:hover, .dashboard-link:hover {
            background: #174fa3;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <a class="logout" href="logout.php">Logout</a>
    <h2>Welkom, <?php echo htmlspecialchars($username); ?>!</h2>
    <p>Dit is je dashboard. Je kunt hieronder je profiel bewerken of terug naar de website gaan.</p>
    <a class="dashboard-btn" href="profile.php">Mijn Profiel</a>
    <?php if ($is_admin): ?>
        <a class="dashboard-btn" href="admin_dashboard.php">Admin Dashboard</a>
    <?php endif; ?>
    <a class="dashboard-link" href="/index.html">Terug naar website</a>
</div>
</body>
</html>

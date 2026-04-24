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

// Legacy sync health metrics
$legacy_wp_count = null;
$legacy_arm_count = null;
$legacy_total_unique = null;
$legacy_synced = null;
$legacy_missing = null;

$legacyWpExistsRes = $mysqli->query("SELECT 1 AS table_exists FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'FFNO2fb_users' LIMIT 1");
$legacyArmExistsRes = $mysqli->query("SELECT 1 AS table_exists FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'FFNO2fb_arm_members' LIMIT 1");
$legacyWpExists = $legacyWpExistsRes && $legacyWpExistsRes->fetch_assoc();
$legacyArmExists = $legacyArmExistsRes && $legacyArmExistsRes->fetch_assoc();
if ($legacyWpExistsRes) {
    $legacyWpExistsRes->free();
}
if ($legacyArmExistsRes) {
    $legacyArmExistsRes->free();
}

if ($legacyWpExists && $legacyArmExists) {
    $res = $mysqli->query("SELECT COUNT(DISTINCT LOWER(TRIM(user_email))) AS c FROM FFNO2fb_users WHERE user_email IS NOT NULL AND TRIM(user_email) <> ''");
    if ($res) {
        $legacy_wp_count = (int) ($res->fetch_assoc()['c'] ?? 0);
        $res->free();
    }

    $res = $mysqli->query("SELECT COUNT(DISTINCT LOWER(TRIM(arm_user_email))) AS c FROM FFNO2fb_arm_members WHERE arm_user_email IS NOT NULL AND TRIM(arm_user_email) <> ''");
    if ($res) {
        $legacy_arm_count = (int) ($res->fetch_assoc()['c'] ?? 0);
        $res->free();
    }

    $res = $mysqli->query("SELECT COUNT(*) AS c FROM (SELECT DISTINCT LOWER(TRIM(user_email)) AS email FROM FFNO2fb_users WHERE user_email IS NOT NULL AND TRIM(user_email) <> '' UNION SELECT DISTINCT LOWER(TRIM(arm_user_email)) AS email FROM FFNO2fb_arm_members WHERE arm_user_email IS NOT NULL AND TRIM(arm_user_email) <> '') legacy");
    if ($res) {
        $legacy_total_unique = (int) ($res->fetch_assoc()['c'] ?? 0);
        $res->free();
    }

    $res = $mysqli->query("SELECT COUNT(*) AS c FROM (SELECT DISTINCT LOWER(TRIM(user_email)) AS email FROM FFNO2fb_users WHERE user_email IS NOT NULL AND TRIM(user_email) <> '' UNION SELECT DISTINCT LOWER(TRIM(arm_user_email)) AS email FROM FFNO2fb_arm_members WHERE arm_user_email IS NOT NULL AND TRIM(arm_user_email) <> '') legacy INNER JOIN users u ON LOWER(TRIM(u.email)) = legacy.email");
    if ($res) {
        $legacy_synced = (int) ($res->fetch_assoc()['c'] ?? 0);
        $res->free();
    }

    if ($legacy_total_unique !== null && $legacy_synced !== null) {
        $legacy_missing = max(0, $legacy_total_unique - $legacy_synced);
    }
}

$res = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE password IS NULL OR password = ''");
$users_without_password = 0;
if ($res) {
    $users_without_password = (int) ($res->fetch_assoc()['c'] ?? 0);
    $res->free();
}

$res = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE password LIKE '$P$%' OR password LIKE '$H$%' OR password LIKE '$wp$2y$%'");
$users_wp_hash = 0;
if ($res) {
    $users_wp_hash = (int) ($res->fetch_assoc()['c'] ?? 0);
    $res->free();
}

$res = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE password REGEXP '^\\\\$2[aby]\\\\$'");
$users_bcrypt_hash = 0;
if ($res) {
    $users_bcrypt_hash = (int) ($res->fetch_assoc()['c'] ?? 0);
    $res->free();
}
$mysqli->close();

// Last sync metadata
$sync_meta = null;
$syncMetaFile = __DIR__ . '/sync_meta.json';
if (file_exists($syncMetaFile)) {
    $raw = @file_get_contents($syncMetaFile);
    if ($raw) {
        $sync_meta = @json_decode($raw, true);
    }
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
        <div style="margin-top:10px;">Accounts zonder wachtwoord: <strong><?php echo $users_without_password; ?></strong></div>
        <div style="margin-top:10px;">WP-compatibele hashes: <strong><?php echo $users_wp_hash; ?></strong></div>
        <div style="margin-top:10px;">Bcrypt hashes (nieuw platform): <strong><?php echo $users_bcrypt_hash; ?></strong></div>
        <?php if ($legacy_total_unique !== null): ?>
            <hr style="margin:18px 0;border:0;border-top:1px solid #23304a;">
            <div>Legacy WP unieke e-mails: <strong><?php echo $legacy_wp_count; ?></strong></div>
            <div style="margin-top:10px;">Legacy ARMember unieke e-mails: <strong><?php echo $legacy_arm_count; ?></strong></div>
            <div style="margin-top:10px;">Legacy totaal unieke e-mails: <strong><?php echo $legacy_total_unique; ?></strong></div>
            <div style="margin-top:10px;">Legacy e-mails gevonden in nieuw systeem: <strong><?php echo $legacy_synced; ?></strong></div>
            <div style="margin-top:10px;">Mogelijk nog niet gesynchroniseerd: <strong><?php echo $legacy_missing; ?></strong></div>
        <?php else: ?>
            <hr style="margin:18px 0;border:0;border-top:1px solid #23304a;">
            <div>Legacy sync-statistieken niet beschikbaar (legacy tabellen niet gevonden).</div>
        <?php endif; ?>
        <hr style="margin:18px 0;border:0;border-top:1px solid #23304a;">
        <?php if ($sync_meta): ?>
            <div>Laatste sync: <strong><?php echo htmlspecialchars($sync_meta['last_run'] ?? '—'); ?></strong></div>
            <div style="margin-top:8px;">Status: <strong style="color:<?php echo ($sync_meta['status'] ?? '') === 'ok' ? '#4caf50' : '#f44336'; ?>"><?php echo htmlspecialchars($sync_meta['status'] ?? '—'); ?></strong></div>
            <div style="margin-top:8px;">Ingevoegd: <strong><?php echo (int)($sync_meta['inserted'] ?? 0); ?></strong> &nbsp;|&nbsp; Bijgewerkt: <strong><?php echo (int)($sync_meta['updated'] ?? 0); ?></strong> &nbsp;|&nbsp; Overgeslagen: <strong><?php echo (int)($sync_meta['skipped'] ?? 0); ?></strong></div>
            <div style="margin-top:8px;">Duur: <strong><?php echo htmlspecialchars($sync_meta['elapsed_sec'] ?? '—'); ?>s</strong></div>
            <?php if (!empty($sync_meta['error'])): ?>
                <div style="margin-top:8px;color:#f44336;">Fout: <?php echo htmlspecialchars($sync_meta['error']); ?></div>
            <?php endif; ?>
        <?php else: ?>
            <div>Geen sync-gegevens beschikbaar. Voer sync_users.php uit via cron.</div>
        <?php endif; ?>
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

<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$host = 'localhost';
$db   = 'ffgo';
$user = 'ffgo';
$pass = 'wN5eHEbwTuFxppi4KLpg';
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_error);
}

// WordPress password check function
function wp_check_password($password, $hash) {
    // Support for $wp$2y$... (WordPress bcrypt plugin hashes)
    if (strpos($hash, '$wp$2y$') === 0) {
        $realHash = substr($hash, 3); // Remove the 'wp' prefix, keep $2y$...
        return password_verify($password, $realHash);
    }
    // Native bcrypt
    if (strlen($hash) == 60 && preg_match('/^\$2y\$/', $hash)) {
        return password_verify($password, $hash);
    }
    require_once __DIR__ . '/wp-password-compat.php';
    return wp_check_password_compat($password, $hash);
}

function should_upgrade_password_hash($hash) {
    if (!is_string($hash) || $hash === '') {
        return false;
    }

    if (strpos($hash, '$wp$2y$') === 0 || strpos($hash, '$P$') === 0 || strpos($hash, '$H$') === 0) {
        return true;
    }

    if (strlen($hash) == 60 && preg_match('/^\$2y\$/', $hash)) {
        return password_needs_rehash($hash, PASSWORD_BCRYPT);
    }

    return true;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $mysqli->prepare('SELECT id, username, email, password, role FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (empty($user['password'])) {
            $error = 'Je account is overgezet, maar heeft nog geen wachtwoord. Klik op "Wachtwoord vergeten?" om een nieuw wachtwoord in te stellen.';
        } elseif (wp_check_password($password, $user['password'])) {
            if (should_upgrade_password_hash($user['password'])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $upgradeStmt = $mysqli->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
                $upgradeStmt->bind_param('si', $newHash, $user['id']);
                $upgradeStmt->execute();
                $upgradeStmt->close();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            if (($user['role'] ?? '') === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid password.';
        }
    } else {
        $error = 'User not found.';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/account-shared.css">
    <style>
        .login-links { display: flex; justify-content: space-between; margin-top: 18px; }
        .login-social { margin-top: 24px; color: var(--muted); font-size: 0.98em; text-align: center; }
    </style>
</head>
<nav class="account-nav" style="width:100%;background:var(--card);padding:12px 20px 12px 20px;box-shadow:0 2px 8px #1c63cf22;display:flex;align-items:center;justify-content:center;gap:40px;position:fixed;top:0;left:0;z-index:1000;">
    <a href="/index.html" title="FikFak News" style="text-decoration:none;display:flex;align-items:center;">
        <picture>
            <source type="image/webp" srcset="../assets/images/logo-fikfak.webp">
            <img src="../assets/images/logo fikfak.png" alt="FikFak News Logo" style="height:44px;width:auto;display:block;transition:transform 0.3s ease,filter 0.3s ease;margin-right:12px;" onmouseover="this.style.transform='scale(1.05)';this.style.filter='brightness(1.1)';" onmouseout="this.style.transform='scale(1)';this.style.filter='brightness(1)';" loading="eager">
        </picture>
    </a>
    <div>
        <a href="/index.html" class="account-link">Terug naar site</a>
    </div>
</nav>
<body style="padding-top:68px;">
<div class="account-box">
    <h2>Inloggen</h2>
    <div class="login-social" style="margin-top:0;margin-bottom:18px;line-height:1.5;">
        Was je al lid op de oude website? Probeer eerst je bestaande e-mail en wachtwoord. Als je account nog geen wachtwoord heeft in het nieuwe systeem, gebruik dan <strong>Wachtwoord vergeten?</strong>
    </div>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
        <input type="text" name="login" placeholder="E-mail of gebruikersnaam" required autofocus>
        <input type="password" name="password" placeholder="Wachtwoord" required>
        <button class="account-btn" type="submit">Inloggen</button>
    </form>
    <div class="login-links">
        <a class="account-link" href="register.php">Registreren</a>
        <a class="account-link" href="forgot_pw.php">Wachtwoord vergeten?</a>
    </div>
    <div class="login-social">Lukt het niet? Vraag dan eenvoudig een nieuwe reset-link aan.</div>
</div>
</body>
</html>

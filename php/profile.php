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

$user_id = $_SESSION['user_id'];
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($username === '' || $email === '') {
        $error = 'Gebruikersnaam en e-mail zijn verplicht.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig e-mailadres.';
    } elseif ($password !== '' && $password !== $password2) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else {
        // Check for duplicate username/email (other users)
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE (email=? OR username=?) AND id != ?');
        $stmt->bind_param('ssi', $email, $username, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Gebruikersnaam of e-mail is al in gebruik.';
        } else {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt2 = $mysqli->prepare('UPDATE users SET username=?, email=?, password=?, updated_at=NOW() WHERE id=?');
                $stmt2->bind_param('sssi', $username, $email, $hash, $user_id);
            } else {
                $stmt2 = $mysqli->prepare('UPDATE users SET username=?, email=?, updated_at=NOW() WHERE id=?');
                $stmt2->bind_param('ssi', $username, $email, $user_id);
            }
            $stmt2->execute();
            $stmt2->close();
            $_SESSION['username'] = $username;
            $success = true;
        }
        $stmt->close();
    }
}

// Fetch user details
$stmt = $mysqli->prepare('SELECT username, email, created_at FROM users WHERE id=?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $created_at);
$stmt->fetch();
$stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn Profiel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="../assets/account-shared.css">
    <style>
        .logout-link { display: block; margin-top: 18px; text-align: center; }
    </style>
</head>
<body>
<div class="account-box">
    <h2>Mijn Profiel</h2>
    <div class="meta">Lid sinds: <?php echo htmlspecialchars($created_at); ?></div>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="success">Profiel bijgewerkt!</div>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Gebruikersnaam" value="<?php echo htmlspecialchars($username); ?>" required>
        <input type="email" name="email" placeholder="E-mail" value="<?php echo htmlspecialchars($email); ?>" required>
        <input type="password" name="password" placeholder="Nieuw wachtwoord (optioneel)">
        <input type="password" name="password2" placeholder="Herhaal nieuw wachtwoord">
        <button class="account-btn" type="submit">Opslaan</button>
    </form>
</form>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:18px;">
        <a class="account-btn" href="dashboard.php" style="text-align:center;width:100%;display:inline-block;">Dashboard</a>
    </div>
    <a class="logout-link account-link" href="logout.php">Uitloggen</a>
    <a class="account-link" style="margin-top:8px;display:block;text-align:center;" href="/index.html">Terug naar website</a>
</div>
</body>
</html>

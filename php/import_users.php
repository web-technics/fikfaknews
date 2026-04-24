<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Database connection settings
$host = 'localhost';
$db   = 'ffgo';
$user = 'ffgo';
$pass = 'wN5eHEbwTuFxppi4KLpg';

// Connect to MySQL
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_error);
}

function normalizeEmail($email)
{
    return strtolower(trim((string) $email));
}

function getUniqueUsername(mysqli $mysqli, $baseUsername, $email)
{
    $base = trim((string) $baseUsername);
    if ($base === '') {
        $localPart = explode('@', (string) $email)[0] ?? 'user';
        $base = preg_replace('/[^a-zA-Z0-9._-]/', '', $localPart);
    }

    if ($base === '') {
        $base = 'user';
    }

    $candidate = $base;
    $suffix = 1;
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');

    while (true) {
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = (bool) $result->fetch_assoc();
        $result->free();

        if (!$exists) {
            break;
        }

        $suffix++;
        $candidate = $base . '_' . $suffix;
    }

    $stmt->close();
    return $candidate;
}

// Fetch WordPress users
date_default_timezone_set('UTC');
$wp_users = [];
$res = $mysqli->query("SELECT ID, user_login, user_email, user_pass, user_registered FROM FFNO2fb_users");
while ($row = $res->fetch_assoc()) {
    $emailKey = normalizeEmail($row['user_email']);
    if ($emailKey === '') {
        continue;
    }

    $wp_users[$emailKey] = [
        'username' => $row['user_login'],
        'email' => $emailKey,
        'password' => $row['user_pass'],
        'created_at' => $row['user_registered'],
        'badges' => null,
        'social_provider' => null,
        'social_id' => null
    ];
}
$res->free();

// Fetch ARMember users (correct columns)
$res = $mysqli->query("SELECT arm_user_login, arm_user_email, arm_user_registered FROM FFNO2fb_arm_members");
while ($row = $res->fetch_assoc()) {
    $email = normalizeEmail($row['arm_user_email']);
    if ($email === '') {
        continue;
    }

    if (!isset($wp_users[$email])) {
        $wp_users[$email] = [
            'username' => $row['arm_user_login'],
            'email' => $email,
            'password' => null, // No password available
            'created_at' => $row['arm_user_registered'],
            'badges' => null,
            'social_provider' => null,
            'social_id' => null
        ];
    }
}
$res->free();

// Sync into new users table (idempotent by email)
$selectExisting = $mysqli->prepare('SELECT id, username, password FROM users WHERE email = ? LIMIT 1');
$insert = $mysqli->prepare("INSERT INTO users (username, email, password, badges, social_provider, social_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$update = $mysqli->prepare('UPDATE users SET username = ?, badges = ?, social_provider = ?, social_id = ?, updated_at = ?, created_at = COALESCE(created_at, ?) WHERE id = ?');
$updatePassword = $mysqli->prepare('UPDATE users SET username = ?, password = ?, badges = ?, social_provider = ?, social_id = ?, updated_at = ?, created_at = COALESCE(created_at, ?) WHERE id = ?');

$now = date('Y-m-d H:i:s');
$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($wp_users as $user) {
    $username = trim((string) $user['username']);
    $email = normalizeEmail($user['email']);
    if ($email === '') {
        $skipped++;
        continue;
    }

    $password = $user['password'] ? trim((string) $user['password']) : null;
    $badges = json_encode($user['badges']);
    $social_provider = $user['social_provider'];
    $social_id = $user['social_id'];
    $created_at = !empty($user['created_at']) ? $user['created_at'] : $now;
    $updated_at = $now;

    $selectExisting->bind_param('s', $email);
    $selectExisting->execute();
    $existingResult = $selectExisting->get_result();
    $existingUser = $existingResult->fetch_assoc();
    $existingResult->free();

    if ($existingUser) {
        $id = (int) $existingUser['id'];
        $resolvedUsername = trim((string) $existingUser['username']) !== '' ? $existingUser['username'] : getUniqueUsername($mysqli, $username, $email);
        $existingPassword = (string) ($existingUser['password'] ?? '');
        $shouldSetPassword = ($existingPassword === '' && !empty($password));

        if ($shouldSetPassword) {
            $updatePassword->bind_param(
                'sssssssi',
                $resolvedUsername,
                $password,
                $badges,
                $social_provider,
                $social_id,
                $updated_at,
                $created_at,
                $id
            );
            $updatePassword->execute();
        } else {
            $update->bind_param(
                'ssssssi',
                $resolvedUsername,
                $badges,
                $social_provider,
                $social_id,
                $updated_at,
                $created_at,
                $id
            );
            $update->execute();
        }
        $updated++;
        continue;
    }

    $resolvedUsername = getUniqueUsername($mysqli, $username, $email);
    $insert->bind_param(
        'ssssssss',
        $resolvedUsername,
        $email,
        $password,
        $badges,
        $social_provider,
        $social_id,
        $created_at,
        $updated_at
    );
    if ($insert->execute()) {
        $inserted++;
    } else {
        $skipped++;
    }
}

$selectExisting->close();
$update->close();
$updatePassword->close();
$insert->close();
$mysqli->close();
echo "User import and merge complete. Inserted: {$inserted}, Updated: {$updated}, Skipped: {$skipped}.\n";

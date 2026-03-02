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

// Fetch WordPress users
date_default_timezone_set('UTC');
$wp_users = [];
$res = $mysqli->query("SELECT ID, user_login, user_email, user_pass, user_registered FROM FFNO2fb_users");
while ($row = $res->fetch_assoc()) {
    $wp_users[$row['user_email']] = [
        'username' => $row['user_login'],
        'email' => $row['user_email'],
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
    $email = $row['arm_user_email'];
    if (!isset($wp_users[$email])) {
        $wp_users[$email] = [
            'username' => $row['arm_user_login'],
            'email' => $row['arm_user_email'],
            'password' => null, // No password available
            'created_at' => $row['arm_user_registered'],
            'badges' => null,
            'social_provider' => null,
            'social_id' => null
        ];
    }
}
$res->free();

// Insert into new users table
$insert = $mysqli->prepare("INSERT INTO users (username, email, password, badges, social_provider, social_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$now = date('Y-m-d H:i:s');
$now = date('Y-m-d H:i:s');
foreach ($wp_users as $user) {
    $username = $user['username'];
    $email = $user['email'];
    $password = $user['password'];
    $badges = json_encode($user['badges']);
    $social_provider = $user['social_provider'];
    $social_id = $user['social_id'];
    $created_at = $user['created_at'] ?? $now;
    $updated_at = $now;
    $insert->bind_param(
        'ssssssss',
        $username,
        $email,
        $password,
        $badges,
        $social_provider,
        $social_id,
        $created_at,
        $updated_at
    );
    $insert->execute();
}
$insert->close();
$mysqli->close();
echo "User import and merge complete.\n";

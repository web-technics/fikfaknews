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

$stmt = $mysqli->prepare('DELETE FROM users WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();
$mysqli->close();
header('Location: user_manager.php');
exit;

<?php
/**
 * sync_users.php — Idempotent legacy→new user sync
 *
 * Intended to run via cron (CLI) or a protected web call.
 * Writes sync metadata to sync_meta.json after each run.
 *
 * Cron example (every hour):
 *   0 * * * * /usr/bin/php /home/fikfak-go/htdocs/go.fikfak.news/php/sync_users.php >> /home/fikfak-go/htdocs/go.fikfak.news/php/sync_users.log 2>&1
 */

// --- Access guard -----------------------------------------------------------
$isCli = (PHP_SAPI === 'cli');

if (!$isCli) {
    // Allow web invocations only with a secret token set in the environment
    // Set SYNC_SECRET on your server: export SYNC_SECRET="your-long-random-string"
    $envSecret = (string) getenv('SYNC_SECRET');
    $providedToken = trim((string) ($_GET['token'] ?? ''));

    if ($envSecret === '' || !hash_equals($envSecret, $providedToken)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

$startTime = microtime(true);

// --- Database ---------------------------------------------------------------
$host = 'localhost';
$db   = 'ffgo';
$user = 'ffgo';
$pass = 'wN5eHEbwTuFxppi4KLpg';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    _finish(null, 0, 0, 0, 'DB connect error: ' . $mysqli->connect_error, $startTime);
    exit(1);
}

// --- Helpers ----------------------------------------------------------------
function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function getUniqueUsername(mysqli $db, string $baseUsername, string $email): string
{
    $base = trim($baseUsername);
    if ($base === '') {
        $localPart = explode('@', $email)[0] ?? 'user';
        $base = preg_replace('/[^a-zA-Z0-9._-]/', '', $localPart);
    }
    if ($base === '') {
        $base = 'user';
    }

    $candidate = $base;
    $suffix    = 1;
    $stmt      = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');

    while (true) {
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $res    = $stmt->get_result();
        $exists = (bool) $res->fetch_assoc();
        $res->free();
        if (!$exists) {
            break;
        }
        $suffix++;
        $candidate = $base . '_' . $suffix;
    }

    $stmt->close();
    return $candidate;
}

function _finish(?mysqli $db, int $inserted, int $updated, int $skipped, string $error, float $startTime): void
{
    $elapsed  = round(microtime(true) - $startTime, 2);
    $metaFile = __DIR__ . '/sync_meta.json';
    $status   = $error === '' ? 'ok' : 'error';

    $meta = [
        'last_run'    => date('c'),
        'status'      => $status,
        'inserted'    => $inserted,
        'updated'     => $updated,
        'skipped'     => $skipped,
        'elapsed_sec' => $elapsed,
        'error'       => $error,
    ];

    @file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $line = sprintf(
        "[%s] status=%s inserted=%d updated=%d skipped=%d elapsed=%.2fs%s\n",
        date('c'),
        $status,
        $inserted,
        $updated,
        $skipped,
        $elapsed,
        $error !== '' ? " error=$error" : ''
    );
    echo $line;

    if ($db) {
        $db->close();
    }
}

// --- Collect legacy users ---------------------------------------------------
$wp_users = [];

// Check if legacy tables exist before querying
$wpTableExists  = false;
$armTableExists = false;

$r = $mysqli->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'FFNO2fb_users' LIMIT 1");
if ($r && $r->fetch_assoc()) {
    $wpTableExists = true;
    $r->free();
}

$r = $mysqli->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'FFNO2fb_arm_members' LIMIT 1");
if ($r && $r->fetch_assoc()) {
    $armTableExists = true;
    $r->free();
}

if (!$wpTableExists && !$armTableExists) {
    _finish($mysqli, 0, 0, 0, 'Legacy tables not found — nothing to sync.', $startTime);
    exit(0);
}

if ($wpTableExists) {
    $res = $mysqli->query('SELECT user_login, user_email, user_pass, user_registered FROM FFNO2fb_users');
    while ($row = $res->fetch_assoc()) {
        $emailKey = normalizeEmail((string) $row['user_email']);
        if ($emailKey === '') {
            continue;
        }
        $wp_users[$emailKey] = [
            'username'   => (string) $row['user_login'],
            'email'      => $emailKey,
            'password'   => (string) $row['user_pass'],
            'created_at' => (string) $row['user_registered'],
        ];
    }
    $res->free();
}

if ($armTableExists) {
    $res = $mysqli->query('SELECT arm_user_login, arm_user_email, arm_user_registered FROM FFNO2fb_arm_members');
    while ($row = $res->fetch_assoc()) {
        $email = normalizeEmail((string) $row['arm_user_email']);
        if ($email === '' || isset($wp_users[$email])) {
            continue;
        }
        $wp_users[$email] = [
            'username'   => (string) $row['arm_user_login'],
            'email'      => $email,
            'password'   => null,
            'created_at' => (string) $row['arm_user_registered'],
        ];
    }
    $res->free();
}

if (empty($wp_users)) {
    _finish($mysqli, 0, 0, 0, '', $startTime);
    exit(0);
}

// --- Sync loop --------------------------------------------------------------
$selectExisting = $mysqli->prepare('SELECT id, username, password FROM users WHERE email = ? LIMIT 1');
$insert         = $mysqli->prepare('INSERT INTO users (username, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
$update         = $mysqli->prepare('UPDATE users SET username = ?, updated_at = ?, created_at = COALESCE(created_at, ?) WHERE id = ?');
$updatePassword = $mysqli->prepare('UPDATE users SET username = ?, password = ?, updated_at = ?, created_at = COALESCE(created_at, ?) WHERE id = ?');

if (!$selectExisting || !$insert || !$update || !$updatePassword) {
    _finish($mysqli, 0, 0, 0, 'Prepare failed: ' . $mysqli->error, $startTime);
    exit(1);
}

$now      = date('Y-m-d H:i:s');
$inserted = 0;
$updated  = 0;
$skipped  = 0;

foreach ($wp_users as $u) {
    $email    = $u['email'];
    $password = isset($u['password']) && $u['password'] !== '' ? trim($u['password']) : null;
    $created  = !empty($u['created_at']) ? $u['created_at'] : $now;

    $selectExisting->bind_param('s', $email);
    $selectExisting->execute();
    $existRes  = $selectExisting->get_result();
    $existing  = $existRes->fetch_assoc();
    $existRes->free();

    if ($existing) {
        $id              = (int) $existing['id'];
        $resolvedName    = trim((string) $existing['username']) !== ''
            ? $existing['username']
            : getUniqueUsername($mysqli, $u['username'], $email);
        $existingPw      = (string) ($existing['password'] ?? '');
        $shouldSetPw     = ($existingPw === '' && $password !== null);

        if ($shouldSetPw) {
            $updatePassword->bind_param('ssssi', $resolvedName, $password, $now, $created, $id);
            $updatePassword->execute();
        } else {
            $update->bind_param('sssi', $resolvedName, $now, $created, $id);
            $update->execute();
        }
        $updated++;
    } else {
        $resolvedName = getUniqueUsername($mysqli, $u['username'], $email);
        $insert->bind_param('sssss', $resolvedName, $email, $password, $created, $now);
        if ($insert->execute()) {
            $inserted++;
        } else {
            $skipped++;
        }
    }
}

$selectExisting->close();
$insert->close();
$update->close();
$updatePassword->close();

_finish($mysqli, $inserted, $updated, $skipped, '', $startTime);
exit(0);

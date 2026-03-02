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

// Filtering
$filter = trim($_GET['filter'] ?? '');
$where = '';
if ($filter !== '') {
  $filter_esc = $mysqli->real_escape_string($filter);
  $where = "WHERE username LIKE '%$filter_esc%' OR email LIKE '%$filter_esc%'";
}

// Pagination
$per_page = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_res = $mysqli->query("SELECT COUNT(*) AS total FROM users $where");
$total_users = $count_res->fetch_assoc()['total'];
$count_res->free();
$total_pages = max(1, ceil($total_users / $per_page));

// Get users for current page
$res = $mysqli->query("SELECT id, username, email, created_at FROM users $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$users = [];
while ($row = $res->fetch_assoc()) {
  $users[] = $row;
}
$res->free();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fikfakker Beheer</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="../assets/account-shared.css">
    <style>
      .manager-box {
        max-width: 1000px;
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
        margin-left: 0;
      }
      .logout:hover {
        background: #174fa3;
      }
      .back-btn {
        background: var(--accent);
        color: #fff;
        border: none;
        padding: 10px 22px;
        border-radius: 8px;
        font-size: 1em;
        cursor: pointer;
        margin-bottom: 20px;
        margin-top: 0;
        box-shadow: 0 2px 8px #1c63cf22;
        transition: background 0.2s;
      }
      .back-btn:hover {
        background: #174fa3;
      }
      .filter-form { margin-bottom: 20px; }
      .filter-input { padding: 8px; border-radius: 4px; border: 1px solid #23304a; width: 220px; background: #101a2b; color: #e6eef6; }
      .user-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
      th, td { padding: 8px 12px; border: 1px solid #23304a; }
      th { background: #101a2b; color: #e6eef6; }
      .action-btn { background: var(--accent); color: #fff; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; margin-right: 12px; margin-bottom: 6px; font-size: 1em; transition: background 0.2s; }
      .action-btn:hover { background: #174fa3; }
    </style>
    <script>
      function editUser(id) {
        window.location.href = 'edit_user.php?id=' + id;
      }
      function resetPw(id) {
        if(confirm('Force password reset for this user?')) {
          window.location.href = 'reset_pw.php?id=' + id;
        }
      }
      function delUser(id) {
        if(confirm('Delete this user?')) {
          window.location.href = 'delete_user.php?id=' + id;
        }
      }
    </script>
</head>
<body>
<div class="manager-box">
    <a class="logout" href="logout.php">Logout</a>
    <button class="back-btn" onclick="window.location.href='admin_dashboard.php'">← Terug naar dashboard</button>
    <button class="back-btn" style="background:#1c63cf;margin-left:10px;" onclick="window.location.href='/index.html'">← Terug naar website</button>
    <h2>Fikfakkers Beheer</h2>
    <form class="filter-form" method="get">
        <input class="filter-input" type="text" name="filter" placeholder="Zoek op gebruikersnaam of e-mail" value="<?php echo htmlspecialchars($filter); ?>">
        <button class="action-btn" type="submit">Filter</button>
    </form>
    <div style="margin-bottom:10px; color:var(--muted); font-size:15px;">
      <strong>Uitleg acties:</strong><br>
      <b>Bewerk</b>: Pas gebruikersnaam, e-mail of badges aan.<br>
      <b>Reset PW</b>: Forceer een wachtwoord reset voor deze gebruiker.<br>
      <b>Verwijder</b>: Verwijder deze gebruiker definitief uit het systeem.
    </div>
    <table class="user-table">
      <tr><th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Lid sinds</th><th>Acties</th></tr>
      <?php foreach ($users as $user): ?>
      <tr>
        <td><?php echo htmlspecialchars($user['id']); ?></td>
        <td><?php echo htmlspecialchars($user['username']); ?></td>
        <td><?php echo htmlspecialchars($user['email']); ?></td>
        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
        <td>
          <button class="action-btn" style="margin-bottom:6px;" onclick="editUser(<?php echo $user['id']; ?>)">Bewerk</button>
          <button class="action-btn" style="margin-bottom:6px;" onclick="resetPw(<?php echo $user['id']; ?>)">Reset PW</button>
          <button class="action-btn" style="margin-bottom:6px;" onclick="delUser(<?php echo $user['id']; ?>)">Verwijder</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="margin-top:24px;text-align:center;">
      <?php if ($total_pages > 1): ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <span style="background:#1c63cf;color:#fff;padding:7px 16px;border-radius:6px;margin:0 3px;font-weight:600;"> <?php echo $i; ?> </span>
          <?php else: ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" style="background:#eee;color:#1c63cf;padding:7px 16px;border-radius:6px;margin:0 3px;text-decoration:none;font-weight:600;"> <?php echo $i; ?> </a>
          <?php endif; ?>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
</div>
</body>
</html>

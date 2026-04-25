<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — SecureAuth</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php
require 'auth.php'; // redirects to login.php if not logged in
require 'config/db.php';

$stmt = $pdo->prepare("SELECT name, email, created_at FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

$initials = strtoupper(substr($user['name'], 0, 1));
$joined   = date('M Y', strtotime($user['created_at']));
?>

<div class="auth-wrapper">
  <div class="auth-card dashboard-card">

    <div class="dashboard-avatar"><?= htmlspecialchars($initials) ?></div>

    <h2>Hello, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>.</h2>
    <p><?= htmlspecialchars($user['email']) ?> &nbsp;·&nbsp; Member since <?= $joined ?></p>

    <div class="dashboard-stats">
      <div class="stat-box">
        <div class="stat-num">1</div>
        <div class="stat-label">Session</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">✓</div>
        <div class="stat-label">Verified</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">🔒</div>
        <div class="stat-label">Secured</div>
      </div>
    </div>

    <a href="logout.php" class="btn-logout">Sign out</a>

  </div>
</div>

</body>
</html>

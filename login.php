<?php
session_start();
require 'config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
  if (!$email) {
    $error = 'Please enter a valid email address.';
  } else {
    $stmt = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
      session_regenerate_id(true);
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['name']    = $user['name'];
      header('Location: dashboard.php');
      exit;
    } else {
      $error = 'Invalid email or password.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — SecureAuth</title>
  <link rel="stylesheet" href="css/style.css" />
</head>

<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">
          <div class="auth-logo-icon">
            <svg viewBox="0 0 24 24">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </div>
          <span class="auth-logo-name">SecureAuth</span>
        </div>
        <h1 class="auth-title">Welcome back.</h1>
        <p class="auth-subtitle">Sign in to your account to continue.</p>
      </div>
      <div class="auth-tabs">
        <a href="login.php" class="auth-tab active">Sign in</a>
        <a href="register.php" class="auth-tab">Register</a>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-error">
          <svg viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <form method="POST" action="login.php" novalidate>
        <div class="form-group">
          <label class="form-label" for="email">Email address</label>
          <div class="form-input-wrap">
            <input class="form-input" type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            <svg viewBox="0 0 24 24">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
              <polyline points="22,6 12,13 2,6" />
            </svg>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="form-input-wrap" style="position:relative;">
            <input class="form-input" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required />
            <svg viewBox="0 0 24 24">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
            <button type="button" class="pw-toggle" onclick="togglePw('password',this)">
              <svg viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round;">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
          <div class="auth-meta"><a href="forgot-password.php" class="auth-link">Forgot password?</a></div>
        </div>
        <button type="submit" class="btn-submit">Sign in</button>
      </form>
      <div class="security-badge">
        <svg viewBox="0 0 24 24">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>
        Protected by bcrypt &amp; prepared statements
      </div>
    </div>
  </div>
  <script>
    function togglePw(id, btn) {
      var el = document.getElementById(id);
      el.type = el.type === 'text' ? 'password' : 'text';
      btn.querySelector('svg').style.opacity = el.type === 'text' ? '0.5' : '1';
    }
  </script>
</body>

</html>
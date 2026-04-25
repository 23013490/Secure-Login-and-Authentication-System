<?php
session_start();
require 'config/db.php';

$error = '';
$success = '';
$tokenValid = false;
$token = $_GET['token'] ?? '';

if (!$token || !ctype_xdigit($token) || strlen($token) !== 64) {
  $error = 'Invalid or missing reset token.';
} else {
  $stmt = $pdo->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = :token LIMIT 1");
  $stmt->execute([':token' => $token]);
  $reset = $stmt->fetch();

  if (!$reset) {
    $error = 'Invalid or expired reset token.';
  } elseif ($reset['used']) {
    $error = 'This reset link has already been used.';
  } elseif (strtotime($reset['expires_at']) < time()) {
    $error = 'This reset link has expired. Please request a new one.';
  } else {
    $tokenValid = true;
  }
}

if ($tokenValid && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $pass = $_POST['password'] ?? '';
  $conf = $_POST['confirm']  ?? '';

  if (strlen($pass) < 8) {
    $error = 'Password must be at least 8 characters.';
  } elseif ($pass !== $conf) {
    $error = 'Passwords do not match.';
  } else {
    $hash = password_hash($pass, PASSWORD_BCRYPT);

    $upd = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
    $upd->execute([':hash' => $hash, ':email' => $reset['email']]);

    $mark = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
    $mark->execute([':token' => $token]);

    $success = 'Your password has been reset successfully. You can now sign in with your new password.';
    $tokenValid = false;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password — SecureAuth</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .back-link {
      display: block;
      text-align: center;
      margin-top: 1.25rem;
      font-size: 0.85rem;
      color: var(--text-muted);
      text-decoration: none;
      transition: color 0.2s;
    }

    .back-link:hover {
      color: var(--accent);
    }
  </style>
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
        <h1 class="auth-title">New password.</h1>
        <p class="auth-subtitle">Create a strong password for your account.</p>
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

      <?php if ($success): ?>
        <div class="alert alert-success">
          <svg viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <?= htmlspecialchars($success) ?> <a href="login.php" style="color:inherit;font-weight:500;">Sign in &rarr;</a>
        </div>
      <?php endif; ?>

      <?php if ($tokenValid): ?>
        <form method="POST" action="reset-password.php?token=<?= htmlspecialchars($token) ?>" novalidate>
          <div class="form-group">
            <label class="form-label" for="password">New password</label>
            <div class="form-input-wrap" style="position:relative;">
              <input class="form-input" type="password" id="password" name="password" placeholder="Min 8 characters" autocomplete="new-password" required oninput="checkStrength(this.value)" />
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
            <div class="strength-bar">
              <div class="strength-seg" id="s1"></div>
              <div class="strength-seg" id="s2"></div>
              <div class="strength-seg" id="s3"></div>
              <div class="strength-seg" id="s4"></div>
            </div>
            <div class="strength-label" id="strength-label"></div>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm">Confirm password</label>
            <div class="form-input-wrap">
              <input class="form-input" type="password" id="confirm" name="confirm" placeholder="Repeat password" autocomplete="new-password" required />
              <svg viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
            </div>
          </div>
          <button type="submit" class="btn-submit">Reset password</button>
        </form>
      <?php endif; ?>

      <a href="login.php" class="back-link">&larr; Back to sign in</a>

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

    function checkStrength(val) {
      var score = 0;
      if (val.length >= 8) score++;
      if (val.length >= 12) score++;
      if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
      if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) score++;
      var cls = ['', 'weak', 'medium', 'medium', 'strong'];
      var lbs = ['', 'Weak', 'Fair', 'Good', 'Strong'];
      var clrs = ['', '#e07070', '#e0a860', '#e0a860', '#63b496'];
      ['s1', 's2', 's3', 's4'].forEach(function(id, i) {
        var el = document.getElementById(id);
        el.className = 'strength-seg';
        if (i < score) el.classList.add(cls[score]);
      });
      var lbl = document.getElementById('strength-label');
      lbl.textContent = val.length > 0 ? lbs[score] : '';
      lbl.style.color = clrs[score];
    }
  </script>
</body>

</html>
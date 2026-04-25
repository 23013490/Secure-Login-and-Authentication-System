<?php
session_start();
require 'config/db.php';

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

  if (!$email) {
    $error = 'Please enter a valid email address.';
  } else {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
      // Don't reveal whether email exists
      $success = 'If an account with that email exists, a password reset link has been generated below.';
    } else {
      // Generate secure token
      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

      // Remove any existing unused tokens for this email
      $del = $pdo->prepare("DELETE FROM password_resets WHERE email = :email AND used = 0");
      $del->execute([':email' => $email]);

      // Insert new token
      $ins = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
      $ins->execute([
        ':email'  => $email,
        ':token'  => $token,
        ':expires' => $expires
      ]);

      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'];
      $path = dirname($_SERVER['PHP_SELF']);
      $path = rtrim($path, '/\\');
      $resetLink = $protocol . '://' . $host . $path . '/reset-password.php?token=' . $token;

      $success = 'A password reset link has been generated. Copy the link below and open it in your browser to reset your password. (Email delivery is not configured on this local server.)';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password — SecureAuth</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .reset-link-box {
      background: var(--bg-input);
      border: 1px solid var(--border);
      border-radius: 9px;
      padding: 12px;
      margin-top: 10px;
      word-break: break-all;
      font-family: monospace;
      font-size: 0.8rem;
      color: var(--accent);
    }

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
        <h1 class="auth-title">Reset password.</h1>
        <p class="auth-subtitle">Enter your email and we'll generate a reset link.</p>
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
          <?= htmlspecialchars($success) ?>
        </div>
        <?php if ($resetLink): ?>
          <div class="reset-link-box"><?= htmlspecialchars($resetLink) ?></div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (!$resetLink): ?>
        <form method="POST" action="forgot-password.php" novalidate>
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
          <button type="submit" class="btn-submit">Send reset link</button>
        </form>
      <?php endif; ?>

      <a href="login.php" class="back-link">&larr; Back to sign in</a>

      <div class="security-badge">
        <svg viewBox="0 0 24 24">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>
        Tokens expire in 1 hour — single use only
      </div>
    </div>
  </div>
</body>

</html>
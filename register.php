<?php
session_start();
require 'config/db.php';
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $pass  = $_POST['password'] ?? '';
    $conf  = $_POST['confirm']  ?? '';
    if (!$name)              $error = 'Full name is required.';
    elseif (!$email)         $error = 'Please enter a valid email address.';
    elseif (strlen($pass)<8) $error = 'Password must be at least 8 characters.';
    elseif ($pass !== $conf) $error = 'Passwords do not match.';
    else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $chk->execute([':email' => $email]);
        if ($chk->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)");
            $ins->execute([':name'=>$name, ':email'=>$email, ':hash'=>$hash]);
            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register — SecureAuth</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo">
        <div class="auth-logo-icon">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <span class="auth-logo-name">SecureAuth</span>
      </div>
      <h1 class="auth-title">Create account.</h1>
      <p class="auth-subtitle">Join securely. Your password is always hashed.</p>
    </div>
    <div class="auth-tabs">
      <a href="login.php" class="auth-tab">Sign in</a>
      <a href="register.php" class="auth-tab active">Register</a>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">
      <svg viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      <?= htmlspecialchars($success) ?> <a href="login.php" style="color:inherit;font-weight:500;">Sign in &rarr;</a>
    </div>
    <?php endif; ?>
    <form method="POST" action="register.php" novalidate>
      <div class="form-group">
        <label class="form-label" for="name">Full name</label>
        <div class="form-input-wrap">
          <input class="form-input" type="text" id="name" name="name" placeholder="Jane Smith" autocomplete="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"/>
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <div class="form-input-wrap">
          <input class="form-input" type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="form-input-wrap" style="position:relative;">
          <input class="form-input" type="password" id="password" name="password" placeholder="Min 8 characters" autocomplete="new-password" required oninput="checkStrength(this.value)"/>
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <button type="button" class="pw-toggle" onclick="togglePw('password',this)">
            <svg viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round;">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
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
          <input class="form-input" type="password" id="confirm" name="confirm" placeholder="Repeat password" autocomplete="new-password" required/>
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
      </div>
      <button type="submit" class="btn-submit">Create account</button>
    </form>
    <div class="security-badge">
      <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Passwords hashed with bcrypt — never stored in plain text
    </div>
  </div>
</div>
<script>
function togglePw(id,btn){var el=document.getElementById(id);el.type=el.type==='text'?'password':'text';btn.querySelector('svg').style.opacity=el.type==='text'?'0.5':'1';}
function checkStrength(val){
  var score=0;
  if(val.length>=8)score++;if(val.length>=12)score++;
  if(/[A-Z]/.test(val)&&/[a-z]/.test(val))score++;
  if(/[0-9]/.test(val)&&/[^A-Za-z0-9]/.test(val))score++;
  var cls=['','weak','medium','medium','strong'];
  var lbs=['','Weak','Fair','Good','Strong'];
  var clrs=['','#e07070','#e0a860','#e0a860','#63b496'];
  ['s1','s2','s3','s4'].forEach(function(id,i){
    var el=document.getElementById(id);el.className='strength-seg';
    if(i<score)el.classList.add(cls[score]);
  });
  var lbl=document.getElementById('strength-label');
  lbl.textContent=val.length>0?lbs[score]:'';lbl.style.color=clrs[score];
}
</script>
</body>
</html>

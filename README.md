# 🔐 Secure Login & Authentication System

A secure user authentication system built with **PHP** and **MySQL**, demonstrating best practices including password hashing, SQL injection prevention, and input validation.

---

## 📁 Project Structure

```
secure-login/
├── css/
│   └── style.css        # Styling for all pages
├── config/
│   └── db.php           # Database connection (PDO)
├── login.php            # Sign in page + PHP logic
├── register.php         # Registration page + PHP logic
├── dashboard.php        # Protected page (requires login)
├── auth.php             # Session guard
├── logout.php           # Destroys session and redirects
├── forgot-password.php  # Request a password reset link
├── reset-password.php   # Reset password using a valid token
└── setup.sql            # Database + table creation script
```

---

## ⚙️ Requirements

- [XAMPP](https://www.apachefriends.org/) (includes Apache, PHP, MySQL, phpMyAdmin)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- A modern web browser

---

## 🚀 Installation & Setup

### Step 1 — Download & Install XAMPP

Go to [https://www.apachefriends.org](https://www.apachefriends.org) and download XAMPP for your operating system (Windows, Mac, or Linux). Run the installer with default settings.

### Step 2 — Start Apache and MySQL

Open the **XAMPP Control Panel** and click **Start** next to both:
- `Apache`
- `MySQL`

Both status lights should turn green.

### Step 3 — Place the project files

Copy the entire `secure-login` folder into XAMPP's web root:

| OS      | Path                              |
|---------|-----------------------------------|
| Windows | `C:\xampp\htdocs\secure-login\`   |
| Mac     | `/Applications/XAMPP/htdocs/secure-login/` |
| Linux   | `/opt/lampp/htdocs/secure-login/` |

### Step 4 — Create the database

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click the **SQL** tab at the top
3. Paste the following SQL and click **Go**:

```sql
CREATE DATABASE IF NOT EXISTS secure_login;
USE secure_login;

CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(255) NOT NULL,
  token      VARCHAR(64)  NOT NULL UNIQUE,
  expires_at DATETIME     NOT NULL,
  used       TINYINT(1)   NOT NULL DEFAULT 0,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
```

> Alternatively, open the `setup.sql` file included in the project and paste its contents into the SQL tab.

### Step 5 — Configure the database connection

Open `config/db.php` in a text editor and update the credentials if needed:

```php
$host = 'localhost';
$db   = 'secure_login';
$user = 'root';
$pass = '';        // XAMPP default is blank — change if you set a password
```

### Step 6 — Run the project

Open your browser and visit:

```
http://localhost/secure-login/register.php
```

---

## 📖 How to Use

### Register a new account
1. Go to `http://localhost/secure-login/register.php`
2. Fill in your full name, email address, and a password (min 8 characters)
3. Click **Create account**

### Sign in
1. Go to `http://localhost/secure-login/login.php`
2. Enter your registered email and password
3. Click **Sign in** — you will be redirected to the dashboard

### Sign out
- Click the **Sign out** button on the dashboard, or visit:
  `http://localhost/secure-login/logout.php`

### Reset a forgotten password
1. On the login page click **Forgot password?** or go to `http://localhost/secure-login/forgot-password.php`
2. Enter your registered email address and click **Send reset link**
3. A unique reset link will be displayed on the page (since email delivery is not configured on a local server)
4. Copy the link and open it in your browser
5. Enter and confirm your new password, then click **Reset password**
6. You will be redirected to the login page — sign in with your new password

> **Note:** Reset tokens expire after **1 hour** and can only be used **once**. If the token expires, simply request a new one.

---

## 🛡️ Security Features

| Feature | Implementation |
|---|---|
| Password hashing | `password_hash()` with bcrypt algorithm |
| Password verification | `password_verify()` — safe constant-time comparison |
| SQL injection prevention | PDO prepared statements with bound parameters |
| Session fixation prevention | `session_regenerate_id(true)` after login |
| Input validation | `filter_var()` with `FILTER_VALIDATE_EMAIL` |
| XSS prevention | `htmlspecialchars()` on all output |
| Cookie protection | `session.cookie_httponly` and `session.cookie_secure` flags |
| Vague error messages | Generic "Invalid email or password" — prevents user enumeration |
| Secure reset tokens | `bin2hex(random_bytes(32))` — cryptographically secure 64-char token |
| Token expiry | Reset links expire after 1 hour via `expires_at` column |
| Single-use tokens | Token marked `used = 1` after first use — cannot be reused |
| Email enumeration prevention | Same success message shown whether email exists or not |

---

## 🔒 Key Code Concepts

### Password hashing on registration
```php
$hash = password_hash($password, PASSWORD_BCRYPT);
// Store $hash in the database — never the plain password
```

### Password verification on login
```php
if (password_verify($inputPassword, $storedHash)) {
    // Login successful
}
```

### SQL injection prevention
```php
// Safe — uses prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
```

### Session security
```php
session_start();
session_regenerate_id(true);   // Prevents session fixation attacks
$_SESSION['user_id'] = $user['id'];
```

### Generating a secure password reset token
```php
// Cryptographically secure random token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Store token in database with expiry
$ins = $pdo->prepare(
    "INSERT INTO password_resets (email, token, expires_at)
     VALUES (:email, :token, :expires)"
);
$ins->execute([':email' => $email, ':token' => $token, ':expires' => $expires]);
```

### Validating the reset token on use
```php
$stmt = $pdo->prepare(
    "SELECT * FROM password_resets
     WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1"
);
$stmt->execute([':token' => $token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = 'This reset link is invalid or has expired.';
}
```

### Marking the token as used after password reset
```php
// Update the password
$pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = :email")
    ->execute([':hash' => password_hash($newPassword, PASSWORD_BCRYPT), ':email' => $reset['email']]);

// Invalidate the token so it cannot be reused
$pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token")
    ->execute([':token' => $token]);
```

---

## 🌐 Page URLs

| Page | URL |
|---|---|
| Register | `http://localhost/secure-login/register.php` |
| Login | `http://localhost/secure-login/login.php` |
| Dashboard | `http://localhost/secure-login/dashboard.php` |
| Logout | `http://localhost/secure-login/logout.php` |
| Forgot Password | `http://localhost/secure-login/forgot-password.php` |
| Reset Password | `http://localhost/secure-login/reset-password.php?token=YOUR_TOKEN` |
| phpMyAdmin | `http://localhost/phpmyadmin` |

---

## 🐛 Common Issues

**Apache or MySQL won't start in XAMPP**
- Check if port 80 is already in use (Skype, IIS). Change Apache's port in XAMPP config or stop the conflicting app.

**"Database connection failed" error**
- Make sure MySQL is running in the XAMPP Control Panel.
- Double-check the credentials in `config/db.php`.

**Page not found (404)**
- Confirm the folder is named `secure-login` and placed directly inside `htdocs`.
- Verify Apache is running.

**"An account with that email already exists"**
- That email is already registered. Use a different email or sign in directly.

**"This reset link is invalid or has expired"**
- The token has either been used already or it has passed the 1-hour expiry window. Go back to `forgot-password.php` and request a fresh link.

---

## 🛠️ Built With

- **PHP** — server-side logic and authentication
- **MySQL** — user data storage
- **PDO** — secure database abstraction layer
- **HTML5 & CSS3** — front-end interface
- **phpMyAdmin** — database management GUI

---

## 📄 License

This project is open source and available for educational use.

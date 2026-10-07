<?php
/**
 * FIXMATE - Real PHP + MySQL Login Controller
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect straight to role dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    header("Location: " . getRoleDashboardUrl($user['role'], './'));
    exit;
}

$error = '';
$quickDemo = $_GET['quick_demo'] ?? '';
$defaultEmail = '';
$defaultPass = '';

if ($quickDemo === 'admin') {
    $defaultEmail = 'admin@fixmate.com';
    $defaultPass = 'admin123';
} elseif ($quickDemo === 'technician') {
    $defaultEmail = 'technician@fixmate.com';
    $defaultPass = 'tech123';
} elseif ($quickDemo === 'user') {
    $defaultEmail = 'user@fixmate.com';
    $defaultPass = 'user123';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'user');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Check status
            if ($user['status'] !== 'active') {
                $error = 'Your account has been deactivated. Please contact Facility Administration.';
            } else {
                $passwordMatch = false;

                // 1. Standard BCrypt verification
                if (password_verify($password, $user['password'])) {
                    $passwordMatch = true;
                }
                // 2. Demo seed password match / auto-upgrade
                elseif ($password === 'admin123' || $password === 'tech123' || $password === 'user123' || $password === $user['password']) {
                    $passwordMatch = true;
                    // Auto-upgrade password to fresh standard hash
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upStmt->execute([$newHash, $user['id']]);
                }

                if ($passwordMatch) {
                    // Check if role matches if provided
                    if (!empty($role) && $user['role'] !== $role) {
                        $error = "Account found, but role for this user is '{$user['role']}'. Please select the correct role tab.";
                    } else {
                        loginUser($user);
                        setFlash('success', "Welcome back, {$user['full_name']}!");
                        header("Location: " . getRoleDashboardUrl($user['role'], './'));
                        exit;
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        } else {
            $error = 'No account found with that email address.';
        }
    }
}

$page_title = "Sign In – FixMate";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/forms.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    body {
      background: var(--bg-main);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .auth-container {
      display: flex;
      width: 100%;
      max-width: 1000px;
      min-height: 620px;
      background: #FFFFFF;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      border: 1px solid var(--border-color);
      overflow: hidden;
    }
    .auth-visual {
      flex: 1.1;
      background: linear-gradient(145deg, #0F172A 0%, #1E293B 100%);
      color: #FFFFFF;
      padding: 3.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
    }
    .auth-visual::before {
      content: '';
      position: absolute;
      top: -60px;
      left: -60px;
      width: 260px;
      height: 260px;
      background: radial-gradient(circle, rgba(37, 99, 235, 0.3) 0%, transparent 70%);
      border-radius: 50%;
    }
    .auth-visual-content h2 {
      color: #FFFFFF;
      font-size: 1.95rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      line-height: 1.25;
    }
    .auth-visual-content p {
      color: #94A3B8;
      font-size: 0.95rem;
      margin-bottom: 2rem;
    }
    .feature-point {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      margin-bottom: 1.25rem;
      font-size: 0.9rem;
      color: #E2E8F0;
    }
    .feature-point i {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(37, 99, 235, 0.25);
      color: #93C5FD;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
    }
    .auth-form-side {
      flex: 1.2;
      padding: 3.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: #FFFFFF;
    }
    .demo-credentials-box {
      background: var(--bg-main);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 0.85rem 1rem;
      margin-top: 1.5rem;
      font-size: 0.8rem;
    }
    .quick-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      padding: 0.25rem 0.6rem;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      margin-right: 0.4rem;
      margin-top: 0.4rem;
      transition: var(--transition);
      color: var(--text-main);
      text-decoration: none;
    }
    .quick-chip:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
  </style>
</head>
<body>

  <div class="auth-container">
    <!-- Left Visual Column -->
    <div class="auth-visual">
      <div>
        <div class="brand-logo" style="margin-bottom: 2.5rem;">
          <div class="brand-icon"><i class="fas fa-wrench"></i></div>
          <span>FixMate</span>
        </div>
        <div class="auth-visual-content">
          <h2>Report. Track. Resolve.</h2>
          <p>Smart, institutional facility maintenance & issue resolution infrastructure with MySQL backend.</p>

          <div class="feature-point">
            <i class="fas fa-bolt"></i>
            <span>Real-time complaint tracking from submission to closure</span>
          </div>
          <div class="feature-point">
            <i class="fas fa-user-check"></i>
            <span>Instant qualified technician dispatching</span>
          </div>
          <div class="feature-point">
            <i class="fas fa-chart-pie"></i>
            <span>Comprehensive analytics and MySQL performance metrics</span>
          </div>
        </div>
      </div>

      <div style="font-size: 0.78rem; color: #64748B;">
        Web Technology Lab Project • KUMARESAN K (CS157)
      </div>
    </div>

    <!-- Right Login Form Column -->
    <div class="auth-form-side">
      <div style="margin-bottom: 1.75rem;">
        <h1 style="font-size: 1.65rem; font-weight: 700; margin-bottom: 0.35rem;">Sign In</h1>
        <p class="text-muted" style="font-size: 0.88rem;">Select your role and enter credentials to continue</p>
      </div>

      <?= displayFlash() ?>

      <?php if (!empty($error)): ?>
        <div class="toast toast-danger" style="position:relative; margin-bottom:1.25rem; animation:none; max-width:100%;">
          <div class="toast-icon"><i class="fas fa-triangle-exclamation text-danger"></i></div>
          <div class="toast-content"><div class="toast-msg"><?= e($error) ?></div></div>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST" id="loginForm">
        <!-- Role Selection Tabs -->
        <div class="form-group">
          <label class="form-label">Select Account Role <span class="required">*</span></label>
          <div class="priority-selector" style="grid-template-columns: repeat(3, 1fr);">
            <div class="priority-option">
              <input type="radio" name="role" id="roleUser" value="user" <?= ($quickDemo === 'user' || empty($quickDemo)) ? 'checked' : '' ?>>
              <label for="roleUser" class="priority-label" style="font-size:0.8rem;">
                <i class="fas fa-user"></i> User
              </label>
            </div>
            <div class="priority-option">
              <input type="radio" name="role" id="roleTech" value="technician" <?= ($quickDemo === 'technician') ? 'checked' : '' ?>>
              <label for="roleTech" class="priority-label" style="font-size:0.8rem;">
                <i class="fas fa-wrench"></i> Technician
              </label>
            </div>
            <div class="priority-option">
              <input type="radio" name="role" id="roleAdmin" value="admin" <?= ($quickDemo === 'admin') ? 'checked' : '' ?>>
              <label for="roleAdmin" class="priority-label" style="font-size:0.8rem;">
                <i class="fas fa-shield-halved"></i> Admin
              </label>
            </div>
          </div>
        </div>

        <!-- Username / Email Field -->
        <div class="form-group">
          <label class="form-label" for="loginEmail">Email Address <span class="required">*</span></label>
          <input type="email" id="loginEmail" name="email" class="form-control" placeholder="user@fixmate.com" value="<?= e($defaultEmail) ?>" required autofocus>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
            <label class="form-label" for="loginPassword" style="margin-bottom:0;">Password <span class="required">*</span></label>
            <span class="text-muted" style="font-size: 0.78rem;">Demo: user123 / tech123 / admin123</span>
          </div>
          <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" value="<?= e($defaultPass) ?>" required>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary w-100" style="width: 100%; padding: 0.8rem; margin-top:0.5rem;">
          <i class="fas fa-right-to-bracket"></i> Sign In to FixMate
        </button>
      </form>

      <!-- Demo Quick Fill Buttons -->
      <div class="demo-credentials-box">
        <div style="font-weight: 600; color: var(--text-main); margin-bottom: 0.35rem;">
          <i class="fas fa-key text-warning"></i> One-Click Demo Credentials:
        </div>
        <div>
          <a href="login.php?quick_demo=user" class="quick-chip">👤 User (user@fixmate.com)</a>
          <a href="login.php?quick_demo=technician" class="quick-chip">🔧 Tech (technician@fixmate.com)</a>
          <a href="login.php?quick_demo=admin" class="quick-chip">🛡️ Admin (admin@fixmate.com)</a>
        </div>
      </div>

      <div style="text-align: center; margin-top: 1.5rem; font-size: 0.88rem; color: var(--text-muted);">
        Don't have an account? <a href="register.php" style="font-weight: 600;">Create Account</a>
      </div>
    </div>
  </div>

</body>
</html>

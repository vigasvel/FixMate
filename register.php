<?php
/**
 * FIXMATE - Real PHP + MySQL User Registration Controller
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
$fullName = '';
$email = '';
$phone = '';
$empId = '';
$department = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $empId = trim($_POST['employee_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Field Validations
    if (empty($fullName) || empty($email) || empty($phone) || empty($empId) || empty($department) || empty($password)) {
        $error = 'All fields marked with an asterisk are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password and confirmation password do not match.';
    } else {
        $pdo = getDB();

        // Check for duplicate email
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'This email address is already registered. Please sign in instead.';
        } else {
            // Check for duplicate employee/student ID
            $checkIdStmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = ? LIMIT 1");
            $checkIdStmt->execute([$empId]);
            if ($checkIdStmt->fetch()) {
                $error = 'This Employee / Student ID is already registered.';
            } else {
                // Hash password securely with PHP standard BCrypt
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, employee_id, department, password, role, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'user', 'active')
                ");

                try {
                    $insertStmt->execute([$fullName, $email, $phone, $empId, $department, $passwordHash]);
                    setFlash('success', 'Registration successful! You can now sign in with your credentials.');
                    header("Location: login.php");
                    exit;
                } catch (PDOException $e) {
                    $error = 'Registration failed due to a database error. Please try again.';
                }
            }
        }
    }
}

$page_title = "Create Account – FixMate";
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
      padding: 2rem 1.5rem;
    }
    .register-card {
      width: 100%;
      max-width: 680px;
      background: #FFFFFF;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      border: 1px solid var(--border-color);
      padding: 3rem;
    }
  </style>
</head>
<body>

  <div class="register-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem;">
      <a href="index.php" class="brand-logo" style="color: var(--secondary);">
        <div class="brand-icon"><i class="fas fa-wrench"></i></div>
        <span style="color: var(--secondary);">FixMate</span>
      </a>
      <span class="badge badge-medium">Registration</span>
    </div>

    <div style="margin-bottom: 1.75rem;">
      <h1 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 0.35rem;">Create Your Account</h1>
      <p class="text-muted" style="font-size: 0.88rem;">Register to report issues and track maintenance across campus facilities</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="toast toast-danger" style="position:relative; margin-bottom:1.25rem; animation:none; max-width:100%;">
        <div class="toast-icon"><i class="fas fa-triangle-exclamation text-danger"></i></div>
        <div class="toast-content"><div class="toast-msg"><?= e($error) ?></div></div>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST" id="registerForm">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="regName">Full Name <span class="required">*</span></label>
          <input type="text" id="regName" name="full_name" class="form-control" placeholder="e.g. Kumaresan K" value="<?= e($fullName) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="regEmail">Institutional Email <span class="required">*</span></label>
          <input type="email" id="regEmail" name="email" class="form-control" placeholder="name@fixmate.com" value="<?= e($email) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="regPhone">Phone Number <span class="required">*</span></label>
          <input type="tel" id="regPhone" name="phone" class="form-control" placeholder="9876543210" value="<?= e($phone) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="regEmpId">Employee / Student ID <span class="required">*</span></label>
          <input type="text" id="regEmpId" name="employee_id" class="form-control" placeholder="e.g. CS157 or FAC-201" value="<?= e($empId) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="regDepartment">Department / Office <span class="required">*</span></label>
        <select id="regDepartment" name="department" class="form-control" required>
          <option value="">-- Select Your Department --</option>
          <option value="Computer Science & Engg" <?= ($department === 'Computer Science & Engg') ? 'selected' : '' ?>>Computer Science & Engg</option>
          <option value="Information Technology" <?= ($department === 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
          <option value="Electrical & Electronics" <?= ($department === 'Electrical & Electronics') ? 'selected' : '' ?>>Electrical & Electronics</option>
          <option value="Mechanical Engg" <?= ($department === 'Mechanical Engg') ? 'selected' : '' ?>>Mechanical Engg</option>
          <option value="Civil Engineering" <?= ($department === 'Civil Engineering') ? 'selected' : '' ?>>Civil Engineering</option>
          <option value="Biomedical Engg" <?= ($department === 'Biomedical Engg') ? 'selected' : '' ?>>Biomedical Engg</option>
          <option value="Administration & Accounts" <?= ($department === 'Administration & Accounts') ? 'selected' : '' ?>>Administration & Accounts</option>
          <option value="Library & Information Science" <?= ($department === 'Library & Information Science') ? 'selected' : '' ?>>Library & Information Science</option>
          <option value="Hostel & Facilities" <?= ($department === 'Hostel & Facilities') ? 'selected' : '' ?>>Hostel & Facilities</option>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="regPassword">Password <span class="required">*</span></label>
          <input type="password" id="regPassword" name="password" class="form-control" placeholder="At least 6 characters" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="regConfirmPassword">Confirm Password <span class="required">*</span></label>
          <input type="password" id="regConfirmPassword" name="confirm_password" class="form-control" placeholder="Repeat password" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100" style="width: 100%; padding: 0.8rem; margin-top:0.5rem;">
        <i class="fas fa-user-plus"></i> Complete Registration
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.5rem; font-size: 0.88rem; color: var(--text-muted);">
      Already have an account? <a href="login.php" style="font-weight: 600;">Sign In</a>
    </div>
  </div>

</body>
</html>

<?php
/**
 * FIXMATE - Main Landing Portal Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect straight to user's dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    header("Location: " . getRoleDashboardUrl($user['role'], './'));
    exit;
}

$page_title = "FixMate – Smart Complaint & Maintenance Management System";
$base_url = './';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="Enterprise Facility & Maintenance Management System for Colleges, Hospitals, Offices and Enterprises.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    .landing-nav {
      height: 75px;
      background: #FFFFFF;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 4rem;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .landing-hero {
      padding: 5rem 4rem 4rem;
      background: radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.08) 0%, transparent 50%), #FFFFFF;
      text-align: center;
      border-bottom: 1px solid var(--border-color);
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.35rem 0.95rem;
      border-radius: var(--radius-full);
      background: var(--primary-light);
      color: var(--primary);
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }
    .landing-hero h1 {
      font-size: 2.85rem;
      font-weight: 800;
      color: var(--secondary);
      max-width: 850px;
      margin: 0 auto 1.25rem;
      line-height: 1.2;
    }
    .landing-hero p {
      font-size: 1.15rem;
      color: var(--text-muted);
      max-width: 680px;
      margin: 0 auto 2.5rem;
    }
    .hero-actions {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 3rem;
      flex-wrap: wrap;
    }
    .feature-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      padding: 4rem;
      max-width: 1280px;
      margin: 0 auto;
    }
    .feature-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-xl);
      padding: 2rem;
      transition: var(--transition);
    }
    .portal-role-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      padding: 2rem 4rem 4rem;
      max-width: 1280px;
      margin: 0 auto;
    }
    .role-card {
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-xl);
      padding: 2rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: var(--shadow-sm);
    }
    @media (max-width: 768px) {
      .landing-nav, .landing-hero, .feature-grid, .portal-role-grid {
        padding: 1.5rem 1rem;
      }
      .landing-hero h1 { font-size: 2rem; }
    }
  </style>
</head>
<body>

  <!-- Landing Top Nav -->
  <header class="landing-nav">
    <a href="index.php" class="brand-logo" style="color:var(--secondary);">
      <div class="brand-icon"><i class="fas fa-wrench"></i></div>
      <span style="color:var(--secondary);">FixMate</span>
    </a>
    <div style="display:flex; align-items:center; gap:1.25rem;">
      <a href="login.php" class="btn btn-primary btn-sm">Sign In / Portal Access</a>
      <a href="register.php" class="btn btn-outline btn-sm">Create Account</a>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="landing-hero">
    <div class="hero-badge">
      <i class="fas fa-server"></i> Full-Stack PHP 8+ & MySQL Web Application
    </div>
    <h1>Smart Complaint & Maintenance Management System</h1>
    <p>Report facility breakdowns, automatically assign certified technicians, trace work timelines in real-time, and streamline institutional operations with a real MySQL relational database.</p>
    
    <div class="hero-actions">
      <a href="login.php" class="btn btn-primary btn-lg">
        <i class="fas fa-right-to-bracket"></i> Launch Portal Login
      </a>
      <a href="register.php" class="btn btn-outline btn-lg">
        <i class="fas fa-user-plus"></i> New User Registration
      </a>
      <a href="test-db.php" class="btn btn-secondary btn-lg">
        <i class="fas fa-database"></i> Verify MySQL Connection
      </a>
    </div>

    <!-- Quick Stats from Real Database -->
    <?php
    $pdo = getDB();
    $totComplaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    $totTechs = (int)$pdo->query("SELECT COUNT(*) FROM technicians WHERE status='active'")->fetchColumn();
    $totCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories WHERE status='active'")->fetchColumn();
    $resolvedComplaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Resolved', 'Closed')")->fetchColumn();
    $resolutionRate = $totComplaints > 0 ? round(($resolvedComplaints / $totComplaints) * 100) : 95;
    ?>
    <div class="stats-grid" style="max-width:960px; margin:0 auto; text-align:left;">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-bolt"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $totComplaints ?></div>
          <div class="stat-label">Complaints in Database</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-user-gear"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $totTechs ?></div>
          <div class="stat-label">Active Technicians</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-layer-group"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $totCategories ?></div>
          <div class="stat-label">Service Categories</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-gauge-high"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $resolutionRate ?>%</div>
          <div class="stat-label">Resolution Efficiency</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Demo Role Selector Section -->
  <section style="background:var(--bg-main); padding:3.5rem 0;">
    <div style="text-align:center; margin-bottom:2rem;">
      <h2 style="font-size:1.85rem; margin-bottom:0.5rem;">Select a User Role to Explore</h2>
      <p style="color:var(--text-muted);">Experience the custom dashboard workflows built for every stakeholder</p>
    </div>

    <div class="portal-role-grid">
      <!-- User Role -->
      <div class="role-card">
        <div>
          <div class="stat-icon blue" style="margin-bottom:1rem;"><i class="fas fa-user"></i></div>
          <h3 style="font-size:1.35rem; margin-bottom:0.5rem;">1. User / Employee</h3>
          <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1rem;">
            Submit maintenance complaints, monitor live 5-step status timelines, converse via comments, and submit 5-star ratings upon resolution.
          </p>
          <div style="font-size:0.8rem; background:var(--bg-main); padding:0.6rem; border-radius:6px; margin-bottom:1.5rem;">
            <strong>Login:</strong> user@fixmate.com / user123
          </div>
        </div>
        <a href="login.php?quick_demo=user" class="btn btn-primary w-100">
          <i class="fas fa-arrow-right"></i> Sign In as User
        </a>
      </div>

      <!-- Technician Role -->
      <div class="role-card">
        <div>
          <div class="stat-icon amber" style="margin-bottom:1rem;"><i class="fas fa-wrench"></i></div>
          <h3 style="font-size:1.35rem; margin-bottom:0.5rem;">2. Technician</h3>
          <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1rem;">
            Review task assignments, accept incoming work, update ticket status to IN PROGRESS, log parts used, and submit work completion summaries.
          </p>
          <div style="font-size:0.8rem; background:var(--bg-main); padding:0.6rem; border-radius:6px; margin-bottom:1.5rem;">
            <strong>Login:</strong> technician@fixmate.com / tech123
          </div>
        </div>
        <a href="login.php?quick_demo=technician" class="btn btn-warning w-100">
          <i class="fas fa-arrow-right"></i> Sign In as Technician
        </a>
      </div>

      <!-- Admin Role -->
      <div class="role-card">
        <div>
          <div class="stat-icon red" style="margin-bottom:1rem;"><i class="fas fa-shield-halved"></i></div>
          <h3 style="font-size:1.35rem; margin-bottom:0.5rem;">3. Administrator</h3>
          <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1rem;">
            Monitor live Chart.js analytics, assign technicians to unassigned tickets, manage categories & user accounts, and export CSV reports.
          </p>
          <div style="font-size:0.8rem; background:var(--bg-main); padding:0.6rem; border-radius:6px; margin-bottom:1.5rem;">
            <strong>Login:</strong> admin@fixmate.com / admin123
          </div>
        </div>
        <a href="login.php?quick_demo=admin" class="btn btn-danger w-100">
          <i class="fas fa-arrow-right"></i> Sign In as Admin
        </a>
      </div>
    </div>
  </section>

  <!-- Key Features -->
  <section class="feature-grid">
    <div class="feature-card">
      <div class="stat-icon cyan" style="margin-bottom:1rem;"><i class="fas fa-ticket-simple"></i></div>
      <h4>Automated Ticket ID Generation</h4>
      <p class="text-muted" style="font-size:0.88rem; margin-top:0.4rem;">
        Unique sequenced ticket IDs (e.g. <code>FM-2026-0001</code>) with automatic category tagging and priority weighting.
      </p>
    </div>

    <div class="feature-card">
      <div class="stat-icon green" style="margin-bottom:1rem;"><i class="fas fa-route"></i></div>
      <h4>5-Stage Visual Stepper</h4>
      <p class="text-muted" style="font-size:0.88rem; margin-top:0.4rem;">
        Clear status lifecycle: SUBMITTED &rarr; ASSIGNED &rarr; IN PROGRESS &rarr; RESOLVED &rarr; CLOSED.
      </p>
    </div>

    <div class="feature-card">
      <div class="stat-icon blue" style="margin-bottom:1rem;"><i class="fas fa-file-csv"></i></div>
      <h4>Analytics & CSV Export</h4>
      <p class="text-muted" style="font-size:0.88rem; margin-top:0.4rem;">
        Interactive Chart.js breakdowns, technician workload tracking, and instant client-side CSV spreadsheet exports.
      </p>
    </div>
  </section>

  <!-- Footer -->
  <footer style="background:var(--secondary); color:#94A3B8; padding:3rem 4rem; text-align:center; font-size:0.9rem; border-top:1px solid rgba(255,255,255,0.08);">
    <div style="display:flex; justify-content:center; align-items:center; gap:0.5rem; color:#FFFFFF; font-weight:700; font-size:1.2rem; margin-bottom:0.75rem;">
      <div class="brand-icon" style="width:30px; height:30px; font-size:0.9rem;"><i class="fas fa-wrench"></i></div> FixMate
    </div>
    <p>Web Technology Individual Academic Project • Submitted by <strong>KUMARESAN K (CS157)</strong></p>
    <div style="margin-top:1.5rem; display:flex; justify-content:center; gap:1.5rem; flex-wrap:wrap;">
      <a href="login.php" style="color:#93C5FD;">Login Portal</a>
      <a href="register.php" style="color:#93C5FD;">Create Account</a>
      <a href="test-db.php" style="color:#93C5FD;">Test MySQL DB Connection</a>
    </div>
  </footer>

</body>
</html>

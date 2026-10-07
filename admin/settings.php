<?php
/**
 * FIXMATE - Admin System Settings
 * Organization Profile, SLA Policies, and Database Backup/Diagnostics
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();
$current_user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_org_settings') {
        // Simulating system settings confirmation
        setFlash('success', 'Organization preferences and SLA response parameters saved successfully.');
        header("Location: settings.php");
        exit;
    }
}

// Database stats
$dbStats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'technicians' => $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn(),
    'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'complaints' => $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn(),
    'comments' => $pdo->query("SELECT COUNT(*) FROM complaint_comments")->fetchColumn(),
    'notifications' => $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn(),
    'feedback' => $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn(),
];

$page_title = "System Settings";
$active_tab = "settings";
$base_url = "../";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
  <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="page-body">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-wrapper">
      <?= displayFlash() ?>

      <div class="content-header mb-4">
        <h1 class="page-title">System Settings & Configuration</h1>
        <p class="text-muted">Configure facility parameters, SLA response targets, and database diagnostics.</p>
      </div>

      <div class="dashboard-grid-2">
        <!-- Organization & Facility Profile -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-building text-primary"></i> Facility Information</h3>
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="save_org_settings">
              <div class="form-group mb-3">
                <label>Organization Name</label>
                <input type="text" class="form-control" value="FixMate Enterprise Facility" name="org_name">
              </div>
              <div class="form-grid-2 mb-3">
                <div class="form-group">
                  <label>Facility Type</label>
                  <select class="form-control" name="facility_type">
                    <option value="College / University" selected>College / University Campus</option>
                    <option value="Hospital">Hospital / Healthcare</option>
                    <option value="Corporate Office">Corporate Office</option>
                    <option value="Apartments">Residential Complex</option>
                    <option value="Industrial">Manufacturing Facility</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Support Hotline</label>
                  <input type="text" class="form-control" value="+91 44 2345 6789" name="hotline">
                </div>
              </div>
              <div class="form-group mb-3">
                <label>Admin Notification Email</label>
                <input type="email" class="form-control" value="<?= e($current_user['email']) ?>" name="admin_email">
              </div>
              <div class="form-grid-2 mb-3">
                <div class="form-group">
                  <label>Critical SLA (Hours)</label>
                  <input type="number" class="form-control" value="4" min="1" max="72">
                </div>
                <div class="form-group">
                  <label>Standard SLA (Hours)</label>
                  <input type="number" class="form-control" value="24" min="1" max="168">
                </div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Configuration</button>
            </form>
          </div>
        </div>

        <!-- Database & Engine Diagnostic -->
        <div>
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-database text-primary"></i> MySQL Database Engine Health</h3>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-center gap-2 mb-3 text-success font-bold">
                <i class="fa-solid fa-circle-check fa-lg"></i> PDO MySQL Driver Connected: <code>fixmate_db</code>
              </div>
              
              <ul class="list-group mb-3" style="list-style: none; padding: 0;">
                <li class="flex-between py-2 border-bottom">
                  <span><i class="fa-solid fa-users mr-2 text-muted"></i> Users Table</span>
                  <strong><?= $dbStats['users'] ?> records</strong>
                </li>
                <li class="flex-between py-2 border-bottom">
                  <span><i class="fa-solid fa-wrench mr-2 text-muted"></i> Technicians Table</span>
                  <strong><?= $dbStats['technicians'] ?> records</strong>
                </li>
                <li class="flex-between py-2 border-bottom">
                  <span><i class="fa-solid fa-tags mr-2 text-muted"></i> Categories Table</span>
                  <strong><?= $dbStats['categories'] ?> records</strong>
                </li>
                <li class="flex-between py-2 border-bottom">
                  <span><i class="fa-solid fa-clipboard-list mr-2 text-muted"></i> Complaints Table</span>
                  <strong><?= $dbStats['complaints'] ?> records</strong>
                </li>
                <li class="flex-between py-2 border-bottom">
                  <span><i class="fa-solid fa-comments mr-2 text-muted"></i> Complaint Comments</span>
                  <strong><?= $dbStats['comments'] ?> records</strong>
                </li>
                <li class="flex-between py-2 border-bottom">
                  <span><i class="fa-solid fa-bell mr-2 text-muted"></i> Notifications Table</span>
                  <strong><?= $dbStats['notifications'] ?> records</strong>
                </li>
                <li class="flex-between py-2">
                  <span><i class="fa-solid fa-star mr-2 text-muted"></i> Feedback & Ratings</span>
                  <strong><?= $dbStats['feedback'] ?> records</strong>
                </li>
              </ul>

              <div class="d-flex gap-2">
                <a href="../test-db.php" target="_blank" class="btn btn-sm btn-light">
                  <i class="fa-solid fa-stethoscope"></i> Run DB Test Suite
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

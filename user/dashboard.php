<?php
/**
 * FIXMATE - User Dashboard Controller
 * Real SQL calculations from MySQL
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['user', 'admin'], '../');
$pdo = getDB();
$userId = $user['id'];

// Real SQL Statistics from MySQL
$stmtTot = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ?");
$stmtTot->execute([$userId]);
$totalComplaints = (int)$stmtTot->fetchColumn();

$stmtSubmitted = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'Submitted'");
$stmtSubmitted->execute([$userId]);
$pendingCount = (int)$stmtSubmitted->fetchColumn();

$stmtProgress = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status IN ('Assigned', 'In Progress')");
$stmtProgress->execute([$userId]);
$inProgressCount = (int)$stmtProgress->fetchColumn();

$stmtResolved = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status IN ('Resolved', 'Closed')");
$stmtResolved->execute([$userId]);
$resolvedCount = (int)$stmtResolved->fetchColumn();

// Active Ongoing Complaint for Stepper
$stmtActive = $pdo->prepare("
    SELECT c.*, cat.category_name, u_tech.full_name AS tech_name
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    LEFT JOIN users u_tech ON t.user_id = u_tech.id
    WHERE c.user_id = ? AND c.status IN ('Submitted', 'Assigned', 'In Progress')
    ORDER BY c.created_at DESC LIMIT 1
");
$stmtActive->execute([$userId]);
$activeComplaint = $stmtActive->fetch();

// Recent Complaints List
$stmtRecent = $pdo->prepare("
    SELECT c.*, cat.category_name, u_tech.full_name AS tech_name
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    LEFT JOIN users u_tech ON t.user_id = u_tech.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC LIMIT 5
");
$stmtRecent->execute([$userId]);
$recentComplaints = $stmtRecent->fetchAll();

$page_title = "User Dashboard – FixMate";
$active_nav = "dashboard";
$header_title = "User Dashboard";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <?= displayFlash() ?>

    <!-- Hero Banner with CTA -->
    <div class="dashboard-hero">
      <div class="hero-text">
        <h1>Good Day, <?= e($user['full_name']) ?>!</h1>
        <p>Manage and monitor all your campus facility complaints and service requests in real-time.</p>
      </div>
      <div>
        <a href="report-problem.php" class="btn btn-primary btn-lg" style="background:#FFFFFF; color:var(--primary); box-shadow:0 4px 14px rgba(0,0,0,0.15); font-weight:700;">
          <i class="fas fa-plus-circle"></i> + Report a Problem
        </a>
      </div>
    </div>

    <!-- Metric Cards Grid from MySQL -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $totalComplaints ?></div>
          <div class="stat-label">Total Complaints</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $pendingCount ?></div>
          <div class="stat-label">Pending Review</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-screwdriver-wrench"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $inProgressCount ?></div>
          <div class="stat-label">In Progress</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $resolvedCount ?></div>
          <div class="stat-label">Resolved / Closed</div>
        </div>
      </div>
    </div>

    <!-- Active Stepper Progress Banner -->
    <?php if ($activeComplaint): ?>
      <?php
      $steps = ['Submitted', 'Assigned', 'In Progress', 'Resolved', 'Closed'];
      $currentStepIdx = array_search($activeComplaint['status'], $steps);
      $progressPct = ($currentStepIdx !== false) ? ($currentStepIdx / 4) * 80 : 0;
      ?>
      <div class="card" style="margin-bottom:2rem;">
        <div class="card-header">
          <div class="card-title">
            <i class="fas fa-spinner fa-spin text-primary"></i>
            <span>Active Request: <strong><?= e($activeComplaint['complaint_code']) ?></strong></span>
          </div>
          <div style="font-size:0.85rem; color:var(--text-muted);">
            Assigned Specialist: <strong style="color:var(--text-main);"><?= e($activeComplaint['tech_name'] ?? 'Pending Dispatch') ?></strong>
          </div>
        </div>
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
            <h3 style="font-size:1.1rem;"><?= e($activeComplaint['title']) ?></h3>
            <span class="badge badge-medium"><i class="fas fa-location-dot"></i> <?= e($activeComplaint['location']) ?></span>
          </div>
          
          <div class="status-tracker">
            <div class="tracker-line">
              <div class="tracker-line-progress" style="width: <?= $progressPct ?>%;"></div>
            </div>
            <?php foreach ($steps as $idx => $step): ?>
              <?php
              $stepClass = '';
              $iconHtml = $idx + 1;
              if ($currentStepIdx !== false) {
                  if ($idx < $currentStepIdx) {
                      $stepClass = 'completed';
                      $iconHtml = '<i class="fas fa-check"></i>';
                  } elseif ($idx === $currentStepIdx) {
                      $stepClass = 'active';
                      $iconHtml = '<i class="fas fa-dot-circle"></i>';
                  }
              }
              ?>
              <div class="tracker-step <?= $stepClass ?>">
                <div class="tracker-icon"><?= $iconHtml ?></div>
                <div class="tracker-title"><?= strtoupper($step) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Recent Complaints Section -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <i class="fas fa-list-check text-primary"></i>
          <span>Recent Complaints</span>
        </div>
        <a href="my-complaints.php" class="btn btn-outline btn-sm">View All Complaints &rarr;</a>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Complaint ID</th>
              <th>Issue Summary</th>
              <th>Category</th>
              <th>Priority</th>
              <th>Assigned Technician</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentComplaints)): ?>
              <tr>
                <td colspan="7">
                  <div class="table-empty-state" style="padding:2rem;">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>No complaints submitted yet</h3>
                    <p>You haven't logged any maintenance requests. Click below to submit your first issue.</p>
                    <a href="report-problem.php" class="btn btn-primary btn-sm" style="margin-top:10px;">
                      <i class="fas fa-plus"></i> Submit a Problem
                    </a>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentComplaints as $c): ?>
                <tr>
                  <td class="cell-id"><?= e($c['complaint_code']) ?></td>
                  <td>
                    <div class="cell-title"><?= e($c['title']) ?></div>
                    <div class="cell-subtitle"><i class="fas fa-map-marker-alt"></i> <?= e($c['location']) ?></div>
                  </td>
                  <td><?= e($c['category_name']) ?></td>
                  <td><?= getPriorityBadge($c['priority']) ?></td>
                  <td><?= e($c['tech_name'] ?? 'Not Assigned') ?></td>
                  <td><?= getStatusBadge($c['status']) ?></td>
                  <td>
                    <a href="complaint-details.php?id=<?= urlencode($c['complaint_code']) ?>" class="btn btn-outline btn-sm">
                      <i class="fas fa-eye"></i> View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

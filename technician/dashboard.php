<?php
/**
 * FIXMATE - Technician Dashboard Controller (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['technician', 'admin'], '../');
$pdo = getDB();

// Find technician profile linked to this user
$techStmt = $pdo->prepare("SELECT * FROM technicians WHERE user_id = ?");
$techStmt->execute([$user['id']]);
$technician = $techStmt->fetch();
$techId = $technician ? (int)$technician['id'] : 0;

// Metric counts from MySQL
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE technician_id = ?");
$stmtTotal->execute([$techId]);
$totalAssigned = (int)$stmtTotal->fetchColumn();

$stmtPending = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE technician_id = ? AND status = 'Assigned'");
$stmtPending->execute([$techId]);
$pendingCount = (int)$stmtPending->fetchColumn();

$stmtProgress = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE technician_id = ? AND status = 'In Progress'");
$stmtProgress->execute([$techId]);
$inProgressCount = (int)$stmtProgress->fetchColumn();

$stmtResolved = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE technician_id = ? AND status IN ('Resolved', 'Closed')");
$stmtResolved->execute([$techId]);
$completedCount = (int)$stmtResolved->fetchColumn();

$stmtCritical = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE technician_id = ? AND priority = 'Critical' AND status NOT IN ('Resolved', 'Closed')");
$stmtCritical->execute([$techId]);
$criticalCount = (int)$stmtCritical->fetchColumn();

// Fetch Active Task Queue
$stmtTasks = $pdo->prepare("
    SELECT c.*, cat.category_name, u_req.full_name AS requester_name, u_req.phone AS requester_phone
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u_req ON c.user_id = u_req.id
    WHERE c.technician_id = ?
    ORDER BY FIELD(c.priority, 'Critical', 'High', 'Medium', 'Low'), c.created_at DESC
    LIMIT 10
");
$stmtTasks->execute([$techId]);
$tasks = $stmtTasks->fetchAll();

$page_title = "Technician Workspace – FixMate";
$active_nav = "tech-dashboard";
$header_title = "Technician Job Center";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <?= displayFlash() ?>

    <!-- Technician Hero Banner -->
    <div class="dashboard-hero">
      <div class="hero-text">
        <h1>Welcome, <?= e($user['full_name']) ?>!</h1>
        <p>Specialization: <strong><?= e($technician['specialization'] ?? 'General Maintenance') ?></strong> • Status: <strong><?= e($technician['availability'] ?? 'Available') ?></strong></p>
      </div>
      <div>
        <a href="assigned-tasks.php" class="btn btn-primary" style="background:#FFFFFF; color:var(--primary); font-weight:700;">
          <i class="fas fa-list-check"></i> View All Task Assignments
        </a>
      </div>
    </div>

    <!-- Technician Metrics Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $totalAssigned ?></div>
          <div class="stat-label">Assigned Tasks</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-hand-holding-hand"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $pendingCount ?></div>
          <div class="stat-label">Pending Start</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-spinner"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $inProgressCount ?></div>
          <div class="stat-label">In Progress</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $completedCount ?></div>
          <div class="stat-label">Completed / Closed</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-radiation"></i></div>
        <div class="stat-details">
          <div class="stat-value"><?= $criticalCount ?></div>
          <div class="stat-label">Critical Priority</div>
        </div>
      </div>
    </div>

    <!-- Assigned Tasks Table Card -->
    <div class="card" style="padding:0; overflow:hidden;">
      <div style="padding:1.25rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:600; font-size:1rem; color:var(--text-main);">
          <i class="fas fa-wrench text-primary"></i> Active Job Queue
        </div>
        <span class="badge badge-medium"><?= count($tasks) ?> Tasks in Queue</span>
      </div>

      <div class="table-responsive" style="border:none; border-radius:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Issue & Category</th>
              <th>Location & Requester</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tasks)): ?>
              <tr>
                <td colspan="7">
                  <div class="table-empty-state">
                    <i class="fas fa-check-double"></i>
                    <h3>No pending maintenance jobs</h3>
                    <p>You have zero outstanding repair tickets in your work queue.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($tasks as $t): ?>
                <tr>
                  <td class="cell-id"><?= e($t['complaint_code']) ?></td>
                  <td>
                    <div class="cell-title"><?= e($t['title']) ?></div>
                    <div class="cell-subtitle"><i class="fas fa-layer-group"></i> <?= e($t['category_name']) ?></div>
                  </td>
                  <td>
                    <div><i class="fas fa-map-marker-alt text-muted"></i> <?= e($t['location']) ?></div>
                    <div class="cell-subtitle">Requester: <?= e($t['requester_name']) ?> (<?= e($t['requester_phone']) ?>)</div>
                  </td>
                  <td><?= getPriorityBadge($t['priority']) ?></td>
                  <td><?= getStatusBadge($t['status']) ?></td>
                  <td><span style="font-size:0.8rem; color:var(--text-muted);"><?= formatDate($t['submitted_date'], 'd M Y') ?></span></td>
                  <td>
                    <a href="task-details.php?id=<?= urlencode($t['complaint_code']) ?>" class="btn btn-primary btn-sm">
                      <i class="fas fa-wrench"></i> Manage
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

<?php
/**
 * FIXMATE - Technician Assigned Tasks List & Filter (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['technician', 'admin'], '../');
$pdo = getDB();

$techStmt = $pdo->prepare("SELECT id FROM technicians WHERE user_id = ?");
$techStmt->execute([$user['id']]);
$techId = (int)$techStmt->fetchColumn();

// Filters
$status = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, cat.category_name, u_req.full_name AS requester_name, u_req.phone AS requester_phone
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u_req ON c.user_id = u_req.id
    WHERE c.technician_id = ?
";
$params = [$techId];

if (!empty($status)) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}

if (!empty($priority)) {
    $sql .= " AND c.priority = ?";
    $params[] = $priority;
}

if (!empty($search)) {
    $sql .= " AND (c.complaint_code LIKE ? OR c.title LIKE ? OR c.location LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$page_title = "Assigned Tasks – FixMate Technician";
$active_nav = "assigned-tasks";
$header_title = "Assigned Maintenance Tasks";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.5rem; font-weight:700;">Task Board & Assignments</h1>
        <p class="text-muted" style="font-size:0.88rem;">Filter and inspect all breakdown repair requests routed to your specialization.</p>
      </div>
    </div>

    <?= displayFlash() ?>

    <!-- Toolbar Filters -->
    <form method="GET" action="assigned-tasks.php" class="table-toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="form-control" placeholder="Search tasks by ID, room, or issue..." value="<?= e($search) ?>">
      </div>

      <div class="filters-group">
        <select name="status" class="filter-select" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="Assigned" <?= ($status === 'Assigned') ? 'selected' : '' ?>>Assigned (Pending Start)</option>
          <option value="In Progress" <?= ($status === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
          <option value="Resolved" <?= ($status === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
          <option value="Closed" <?= ($status === 'Closed') ? 'selected' : '' ?>>Closed</option>
        </select>

        <select name="priority" class="filter-select" onchange="this.form.submit()">
          <option value="">All Priorities</option>
          <option value="Low" <?= ($priority === 'Low') ? 'selected' : '' ?>>Low</option>
          <option value="Medium" <?= ($priority === 'Medium') ? 'selected' : '' ?>>Medium</option>
          <option value="High" <?= ($priority === 'High') ? 'selected' : '' ?>>High</option>
          <option value="Critical" <?= ($priority === 'Critical') ? 'selected' : '' ?>>Critical</option>
        </select>

        <a href="assigned-tasks.php" class="btn btn-outline btn-sm" title="Reset Filters"><i class="fas fa-rotate-left"></i></a>
      </div>
    </form>

    <div class="card" style="padding:0; overflow:hidden;">
      <div style="padding:1.25rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:600; font-size:1rem; color:var(--text-main);">
          <i class="fas fa-clipboard-check text-primary"></i> Work Orders Directory
        </div>
        <span class="badge badge-medium"><?= count($tasks) ?> Tasks</span>
      </div>

      <div class="table-responsive" style="border:none; border-radius:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Issue Description</th>
              <th>Category</th>
              <th>Site & Customer</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Reported Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tasks)): ?>
              <tr>
                <td colspan="8">
                  <div class="table-empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No tasks found</h3>
                    <p>No maintenance tasks match your filter criteria.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($tasks as $t): ?>
                <tr>
                  <td class="cell-id"><?= e($t['complaint_code']) ?></td>
                  <td>
                    <div class="cell-title"><?= e($t['title']) ?></div>
                    <div class="cell-subtitle"><i class="fas fa-location-dot"></i> <?= e($t['location']) ?></div>
                  </td>
                  <td><?= e($t['category_name']) ?></td>
                  <td>
                    <div><?= e($t['requester_name']) ?></div>
                    <div class="cell-subtitle"><i class="fas fa-phone"></i> <?= e($t['requester_phone']) ?></div>
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

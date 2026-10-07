<?php
/**
 * FIXMATE - User My Complaints List & Filter (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['user', 'admin'], '../');
$pdo = getDB();
$userId = $user['id'];

// Filter inputs
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$priority = trim($_GET['priority'] ?? '');

$sql = "
    SELECT c.*, cat.category_name, u_tech.full_name AS tech_name, f.rating AS user_rating
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    LEFT JOIN users u_tech ON t.user_id = u_tech.id
    LEFT JOIN feedback f ON c.id = f.complaint_id
    WHERE c.user_id = ?
";
$params = [$userId];

if (!empty($search)) {
    $sql .= " AND (c.complaint_code LIKE ? OR c.title LIKE ? OR c.location LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($status)) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}

if ($categoryId > 0) {
    $sql .= " AND c.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($priority)) {
    $sql .= " AND c.priority = ?";
    $params[] = $priority;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Fetch categories for filter dropdown
$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY category_name ASC")->fetchAll();

$page_title = "My Complaints – FixMate";
$active_nav = "my-complaints";
$header_title = "My Reported Issues";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.5rem; font-weight:700;">My Facility Complaints</h1>
        <p class="text-muted" style="font-size:0.88rem;">Track progress updates, review resolution notes, and confirm ticket completions.</p>
      </div>
      <a href="report-problem.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> + Report New Issue
      </a>
    </div>

    <?= displayFlash() ?>

    <!-- Filter & Search Toolbar -->
    <form method="GET" action="my-complaints.php" class="table-toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="form-control" placeholder="Search by ID, title, or location..." value="<?= e($search) ?>">
      </div>

      <div class="filters-group">
        <select name="status" class="filter-select" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="Submitted" <?= ($status === 'Submitted') ? 'selected' : '' ?>>Submitted</option>
          <option value="Assigned" <?= ($status === 'Assigned') ? 'selected' : '' ?>>Assigned</option>
          <option value="In Progress" <?= ($status === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
          <option value="Resolved" <?= ($status === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
          <option value="Closed" <?= ($status === 'Closed') ? 'selected' : '' ?>>Closed</option>
        </select>

        <select name="category_id" class="filter-select" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($categoryId === (int)$cat['id']) ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="priority" class="filter-select" onchange="this.form.submit()">
          <option value="">All Priorities</option>
          <option value="Low" <?= ($priority === 'Low') ? 'selected' : '' ?>>Low</option>
          <option value="Medium" <?= ($priority === 'Medium') ? 'selected' : '' ?>>Medium</option>
          <option value="High" <?= ($priority === 'High') ? 'selected' : '' ?>>High</option>
          <option value="Critical" <?= ($priority === 'Critical') ? 'selected' : '' ?>>Critical</option>
        </select>

        <a href="my-complaints.php" class="btn btn-outline btn-sm" title="Reset Filters"><i class="fas fa-rotate-left"></i></a>
      </div>
    </form>

    <!-- Table Container -->
    <div class="card" style="padding:0; overflow:hidden;">
      <div style="padding:1.25rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:600; font-size:1rem; color:var(--text-main);">
          <i class="fas fa-list text-primary"></i> Complaints Directory
        </div>
        <span class="badge badge-medium"><?= count($complaints) ?> Complaints Found</span>
      </div>

      <div class="table-responsive" style="border:none; border-radius:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Complaint ID</th>
              <th>Issue & Location</th>
              <th>Category</th>
              <th>Priority</th>
              <th>Assigned Technician</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($complaints)): ?>
              <tr>
                <td colspan="8">
                  <div class="table-empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No complaints found</h3>
                    <p>No complaints match your search or filter parameters in MySQL.</p>
                    <a href="report-problem.php" class="btn btn-primary btn-sm" style="margin-top:10px;">
                      <i class="fas fa-plus"></i> Submit a New Complaint
                    </a>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($complaints as $c): ?>
                <tr>
                  <td class="cell-id"><?= e($c['complaint_code']) ?></td>
                  <td>
                    <div class="cell-title" title="<?= e($c['title']) ?>"><?= e($c['title']) ?></div>
                    <div class="cell-subtitle"><i class="fas fa-map-marker-alt"></i> <?= e($c['location']) ?></div>
                  </td>
                  <td><span style="font-weight:500;"><?= e($c['category_name']) ?></span></td>
                  <td><?= getPriorityBadge($c['priority']) ?></td>
                  <td>
                    <?php if (!empty($c['tech_name'])): ?>
                      <i class="fas fa-user-gear text-primary"></i> <?= e($c['tech_name']) ?>
                    <?php else: ?>
                      <span class="text-muted"><i class="far fa-clock"></i> Not Assigned</span>
                    <?php endif; ?>
                  </td>
                  <td><?= getStatusBadge($c['status']) ?></td>
                  <td><span style="font-size:0.82rem; color:var(--text-muted);"><?= formatDate($c['submitted_date'], 'd M Y') ?></span></td>
                  <td>
                    <div class="cell-actions">
                      <a href="complaint-details.php?id=<?= urlencode($c['complaint_code']) ?>" class="btn btn-outline btn-sm" title="View Details">
                        <i class="fas fa-eye"></i> View
                      </a>
                      <?php if ($c['status'] === 'Resolved' && empty($c['user_rating'])): ?>
                        <a href="complaint-details.php?id=<?= urlencode($c['complaint_code']) ?>#confirmBox" class="btn btn-success btn-sm">
                          <i class="fas fa-check"></i> Verify
                        </a>
                      <?php endif; ?>
                    </div>
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

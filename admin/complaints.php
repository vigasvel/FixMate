<?php
/**
 * FIXMATE - Admin Master Complaints Directory (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole('admin', '../');
$pdo = getDB();

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['complaint_id'] ?? 0);
    if ($deleteId > 0) {
        $delStmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
        $delStmt->execute([$deleteId]);
        setFlash('success', 'Complaint ticket removed from MySQL database.');
        header("Location: complaints.php");
        exit;
    }
}

// Handle Status / Priority Override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_parameters') {
    $cId = (int)($_POST['complaint_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'Submitted');
    $newPriority = trim($_POST['priority'] ?? 'Medium');

    if ($cId > 0) {
        $upStmt = $pdo->prepare("UPDATE complaints SET status = ?, priority = ? WHERE id = ?");
        $upStmt->execute([$newStatus, $newPriority, $cId]);
        setFlash('success', 'Complaint parameters updated successfully.');
        header("Location: complaints.php");
        exit;
    }
}

// Filters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$priority = trim($_GET['priority'] ?? '');
$techId = (int)($_GET['technician_id'] ?? 0);

$sql = "
    SELECT c.*, cat.category_name, u_req.full_name AS requester_name, u_req.department AS requester_dept,
           u_tech.full_name AS tech_name
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u_req ON c.user_id = u_req.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    LEFT JOIN users u_tech ON t.user_id = u_tech.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.complaint_code LIKE ? OR c.title LIKE ? OR u_req.full_name LIKE ? OR c.location LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
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

if ($techId > 0) {
    $sql .= " AND c.technician_id = ?";
    $params[] = $techId;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Dropdown lists
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();
$technicians = $pdo->query("
    SELECT t.id, u.full_name, t.specialization, t.availability 
    FROM technicians t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status = 'active' 
    ORDER BY u.full_name ASC
")->fetchAll();

$page_title = "Manage Complaints – FixMate Administration";
$active_nav = "admin-complaints";
$header_title = "Facility Complaints Master Ledger";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.5rem; font-weight:700;">Facility Complaints Master Directory</h1>
        <p class="text-muted" style="font-size:0.88rem;">Review, assign technicians, adjust priority levels, and oversee lifecycle resolution.</p>
      </div>
      <div style="display:flex; gap:0.5rem;">
        <a href="reports.php" class="btn btn-outline">
          <i class="fas fa-file-export"></i> Analytics & Reports
        </a>
      </div>
    </div>

    <?= displayFlash() ?>

    <!-- Filter & Search Toolbar -->
    <form method="GET" action="complaints.php" class="table-toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="form-control" placeholder="Search by ticket ID, user, room or keyword..." value="<?= e($search) ?>">
      </div>

      <div class="filters-group">
        <select name="status" class="filter-select" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="Submitted" <?= ($status === 'Submitted') ? 'selected' : '' ?>>Submitted (Unassigned)</option>
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

        <select name="technician_id" class="filter-select" onchange="this.form.submit()">
          <option value="">All Technicians</option>
          <?php foreach ($technicians as $tech): ?>
            <option value="<?= $tech['id'] ?>" <?= ($techId === (int)$tech['id']) ? 'selected' : '' ?>><?= e($tech['full_name']) ?></option>
          <?php endforeach; ?>
        </select>

        <a href="complaints.php" class="btn btn-outline btn-sm" title="Reset Filters"><i class="fas fa-rotate-left"></i></a>
      </div>
    </form>

    <!-- Table Container -->
    <div class="card" style="padding:0; overflow:hidden;">
      <div style="padding:1.25rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:600; font-size:1rem; color:var(--text-main);">
          <i class="fas fa-table-list text-primary"></i> Complaints Database Records
        </div>
        <span class="badge badge-medium"><?= count($complaints) ?> Complaints</span>
      </div>

      <div class="table-responsive" style="border:none; border-radius:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Reported By</th>
              <th>Issue & Location</th>
              <th>Category</th>
              <th>Priority</th>
              <th>Assigned Tech</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($complaints)): ?>
              <tr>
                <td colspan="9">
                  <div class="table-empty-state">
                    <i class="fas fa-filter-circle-xmark"></i>
                    <h3>No complaints found</h3>
                    <p>No complaints match your active filter parameters.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($complaints as $c): ?>
                <tr>
                  <td class="cell-id"><?= e($c['complaint_code']) ?></td>
                  <td>
                    <div style="font-weight:600; color:var(--text-main);"><?= e($c['requester_name']) ?></div>
                    <div class="cell-subtitle"><?= e($c['requester_dept'] ?? 'Campus') ?></div>
                  </td>
                  <td>
                    <div class="cell-title" title="<?= e($c['title']) ?>"><?= e($c['title']) ?></div>
                    <div class="cell-subtitle"><i class="fas fa-location-dot"></i> <?= e($c['location']) ?></div>
                  </td>
                  <td><?= e($c['category_name']) ?></td>
                  <td><?= getPriorityBadge($c['priority']) ?></td>
                  <td>
                    <span style="font-weight:500;"><?= e($c['tech_name'] ?? 'Unassigned') ?></span>
                  </td>
                  <td><?= getStatusBadge($c['status']) ?></td>
                  <td><span style="font-size:0.8rem; color:var(--text-muted);"><?= formatDate($c['submitted_date'], 'd M Y') ?></span></td>
                  <td>
                    <div class="cell-actions">
                      <button type="button" class="btn btn-outline btn-sm" onclick="openAssignModal(<?= $c['id'] ?>, '<?= e($c['complaint_code']) ?>', '<?= e(addslashes($c['title'])) ?>', '<?= e($c['technician_id']) ?>')" title="Assign Technician">
                        <i class="fas fa-user-plus text-primary"></i> Assign
                      </button>
                      <button type="button" class="btn btn-outline btn-sm" onclick="openEditModal(<?= $c['id'] ?>, '<?= e($c['complaint_code']) ?>', '<?= e($c['status']) ?>', '<?= e($c['priority']) ?>')" title="Modify Status">
                        <i class="fas fa-pen-to-square"></i>
                      </button>
                      <a href="complaint-details.php?id=<?= urlencode($c['complaint_code']) ?>" class="btn btn-outline btn-sm" title="View Full File">
                        <i class="fas fa-eye"></i>
                      </a>
                      <form action="complaints.php" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Permanently delete complaint <?= e($c['complaint_code']) ?> from MySQL?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm text-danger" title="Delete">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </form>
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

<!-- Assign Technician Modal -->
<div class="modal-backdrop" id="assignModal">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-user-plus text-primary"></i> Assign Technician</div>
      <button class="modal-close" onclick="document.getElementById('assignModal').classList.remove('show')">&times;</button>
    </div>
    <form action="assign-technician.php" method="POST">
      <input type="hidden" name="complaint_id" id="modalAssignComplaintId">
      <div class="modal-body">
        <div style="background:var(--bg-main); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1rem; margin-bottom:1rem;">
          <div id="modalAssignCode" style="font-weight:800; font-family:monospace; color:var(--primary);">FM-2026-XXXX</div>
          <div id="modalAssignTitle" style="font-weight:600; font-size:0.95rem; margin-top:3px;">-</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="selectTechnician">Select Qualified Technician <span class="required">*</span></label>
          <select id="selectTechnician" name="technician_id" class="form-control" required>
            <option value="">-- Select Technician --</option>
            <?php foreach ($technicians as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?> (<?= e($t['specialization']) ?> - <?= e($t['availability']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('assignModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Confirm Assignment</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Status/Priority Modal -->
<div class="modal-backdrop" id="editModal">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-sliders text-primary"></i> Update Ticket Status & Priority</div>
      <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('show')">&times;</button>
    </div>
    <form action="complaints.php" method="POST">
      <input type="hidden" name="action" value="update_parameters">
      <input type="hidden" name="complaint_id" id="modalEditComplaintId">
      <div class="modal-body">
        <div style="margin-bottom:1rem;">
          <span class="text-muted" style="font-size:0.85rem;">Ticket:</span>
          <strong id="modalEditCode" style="font-family:monospace; color:var(--primary); margin-left:6px;">FM-2026-XXXX</strong>
        </div>

        <div class="form-group">
          <label class="form-label" for="modalEditStatus">Status</label>
          <select id="modalEditStatus" name="status" class="form-control">
            <option value="Submitted">Submitted</option>
            <option value="Assigned">Assigned</option>
            <option value="In Progress">In Progress</option>
            <option value="Resolved">Resolved</option>
            <option value="Closed">Closed</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="modalEditPriority">Priority Level</label>
          <select id="modalEditPriority" name="priority" class="form-control">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('editModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAssignModal(id, code, title, techId) {
  document.getElementById('modalAssignComplaintId').value = id;
  document.getElementById('modalAssignCode').textContent = code;
  document.getElementById('modalAssignTitle').textContent = title;
  document.getElementById('selectTechnician').value = techId || '';
  document.getElementById('assignModal').classList.add('show');
}

function openEditModal(id, code, status, priority) {
  document.getElementById('modalEditComplaintId').value = id;
  document.getElementById('modalEditCode').textContent = code;
  document.getElementById('modalEditStatus').value = status;
  document.getElementById('modalEditPriority').value = priority;
  document.getElementById('editModal').classList.add('show');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * FIXMATE - Admin Technician Management
 * View technicians with live SQL calculated active & completed workload counts, manage status & specialization
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();
$current_user = getCurrentUser();

// Handle Actions (Add, Edit, Update Availability, Delete/Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_availability') {
        $tech_id = intval($_POST['technician_id'] ?? 0);
        $availability = $_POST['availability'] ?? 'Available';
        $stmt = $pdo->prepare("UPDATE technicians SET availability = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$availability, $tech_id]);
        setFlash('success', 'Technician availability updated.');
        header("Location: technicians.php");
        exit;
    }

    if ($action === 'edit_technician') {
        $tech_id = intval($_POST['technician_id'] ?? 0);
        $specialization = trim($_POST['specialization'] ?? 'General Maintenance');
        $experience = trim($_POST['experience'] ?? '1 Year');
        $availability = $_POST['availability'] ?? 'Available';
        $status = $_POST['status'] ?? 'active';

        $stmt = $pdo->prepare("
            UPDATE technicians 
            SET specialization = ?, experience = ?, availability = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$specialization, $experience, $availability, $status, $tech_id]);
        setFlash('success', 'Technician profile updated successfully.');
        header("Location: technicians.php");
        exit;
    }
}

// Fetch technicians with LIVE SQL calculations of active vs completed tasks
$stmt = $pdo->query("
    SELECT 
        t.id AS technician_id,
        t.user_id,
        t.specialization,
        t.availability,
        t.experience,
        t.status AS tech_status,
        u.full_name,
        u.email,
        u.phone,
        u.department,
        COUNT(c.id) AS total_assigned_all_time,
        SUM(CASE WHEN c.status IN ('Assigned', 'In Progress') THEN 1 ELSE 0 END) AS active_tasks,
        SUM(CASE WHEN c.status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) AS completed_tasks,
        AVG(f.rating) AS avg_rating,
        COUNT(f.id) AS feedback_count
    FROM technicians t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN complaints c ON c.technician_id = t.id
    LEFT JOIN feedback f ON f.complaint_id = c.id
    GROUP BY t.id, t.user_id, t.specialization, t.availability, t.experience, t.status, u.full_name, u.email, u.phone, u.department
    ORDER BY t.id ASC
");
$technicians = $stmt->fetchAll();

// Availability counts
$avail_count = [
    'Available' => 0,
    'Busy' => 0,
    'On Leave' => 0
];
foreach ($technicians as $t) {
    if (isset($avail_count[$t['availability']])) {
        $avail_count[$t['availability']]++;
    }
}

$page_title = "Technicians Directory";
$active_tab = "technicians";
$base_url = "../";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
  <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="page-body">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-wrapper">
      <?= displayFlash() ?>

      <div class="content-header flex-between mb-4">
        <div>
          <h1 class="page-title">Technicians & Workload Management</h1>
          <p class="text-muted">Monitor technician availability, active workload queues, completed tickets, and ratings calculated directly from MySQL.</p>
        </div>
        <div>
          <a href="users.php" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Add New Staff / Technician
          </a>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="stats-grid mb-4">
        <div class="stat-card">
          <div class="stat-icon bg-blue-subtle text-primary">
            <i class="fa-solid fa-user-gear"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= count($technicians) ?></div>
            <div class="stat-label">Total Technicians</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-green-subtle text-success">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= $avail_count['Available'] ?></div>
            <div class="stat-label">Available for Dispatch</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-yellow-subtle text-warning">
            <i class="fa-solid fa-person-digging"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= $avail_count['Busy'] ?></div>
            <div class="stat-label">Currently Busy</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-red-subtle text-danger">
            <i class="fa-solid fa-calendar-xmark"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= $avail_count['On Leave'] ?></div>
            <div class="stat-label">On Leave</div>
          </div>
        </div>
      </div>

      <!-- Technicians Roster Grid & Table -->
      <div class="card">
        <div class="card-header flex-between">
          <h2 class="card-title"><i class="fa-solid fa-wrench"></i> Active Field Technicians</h2>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Technician</th>
                <th>Specialization</th>
                <th>Availability</th>
                <th>Active Tasks</th>
                <th>Completed</th>
                <th>Rating</th>
                <th>Contact</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($technicians)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    No technician records found. Create a technician from User Management.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($technicians as $t): ?>
                  <tr>
                    <td>
                      <div class="user-meta-cell">
                        <div class="avatar-circle">
                          <?= strtoupper(substr($t['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                          <div class="font-bold"><?= e($t['full_name']) ?></div>
                          <div class="text-xs text-muted">ID: TECH-<?= str_pad($t['technician_id'], 3, '0', STR_PAD_LEFT) ?> &bull; Exp: <?= e($t['experience']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-secondary-subtle">
                        <i class="fa-solid fa-toolbox mr-1"></i> <?= e($t['specialization']) ?>
                      </span>
                    </td>
                    <td>
                      <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="action" value="update_availability">
                        <input type="hidden" name="technician_id" value="<?= $t['technician_id'] ?>">
                        <select name="availability" class="form-control form-control-sm" onchange="this.form.submit()" style="font-size: 0.8rem; font-weight: 600;">
                          <option value="Available" <?= $t['availability'] === 'Available' ? 'selected' : '' ?>>🟢 Available</option>
                          <option value="Busy" <?= $t['availability'] === 'Busy' ? 'selected' : '' ?>>🟡 Busy</option>
                          <option value="On Leave" <?= $t['availability'] === 'On Leave' ? 'selected' : '' ?>>🔴 On Leave</option>
                        </select>
                      </form>
                    </td>
                    <td>
                      <span class="badge <?= ($t['active_tasks'] > 3) ? 'badge-danger' : (($t['active_tasks'] > 0) ? 'badge-warning' : 'badge-neutral') ?>">
                        <?= intval($t['active_tasks']) ?> Active
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-success">
                        <i class="fa-solid fa-check-double mr-1"></i> <?= intval($t['completed_tasks']) ?> Done
                      </span>
                    </td>
                    <td>
                      <?php if ($t['avg_rating']): ?>
                        <div class="rating-stars text-warning text-sm">
                          <i class="fa-solid fa-star"></i> <?= number_format($t['avg_rating'], 1) ?>
                          <span class="text-xs text-muted">(<?= $t['feedback_count'] ?>)</span>
                        </div>
                      <?php else: ?>
                        <span class="text-xs text-muted">No ratings yet</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="text-xs"><i class="fa-solid fa-phone text-muted mr-1"></i> <?= e($t['phone'] ?: 'N/A') ?></div>
                      <div class="text-xs text-muted"><i class="fa-solid fa-envelope mr-1"></i> <?= e($t['email']) ?></div>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-light" onclick='openEditTechModal(<?= json_encode($t) ?>)' title="Edit Profile & Specialization">
                        <i class="fa-solid fa-pen-to-square text-primary"></i> Edit
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Edit Technician Profile -->
<div id="editTechModal" class="modal-backdrop" style="display: none;">
  <div class="modal-box">
    <div class="modal-header flex-between">
      <h3 class="modal-title"><i class="fa-solid fa-user-gear text-primary"></i> Edit Technician Profile</h3>
      <button type="button" class="btn-close" onclick="closeModal('editTechModal')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_technician">
      <input type="hidden" name="technician_id" id="edit_tech_id">
      <div class="modal-body">
        <div class="form-group mb-3">
          <label>Technician Name</label>
          <input type="text" id="edit_tech_name" class="form-control" disabled>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>Specialization</label>
            <select name="specialization" id="edit_tech_spec" class="form-control">
              <option value="Electrical">Electrical</option>
              <option value="Plumbing">Plumbing</option>
              <option value="Networking">Networking</option>
              <option value="HVAC">HVAC / Air Conditioning</option>
              <option value="Carpentry">Carpentry</option>
              <option value="General Maintenance">General Maintenance</option>
            </select>
          </div>
          <div class="form-group">
            <label>Experience</label>
            <input type="text" name="experience" id="edit_tech_exp" class="form-control" placeholder="e.g., 4 Years">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>Availability</label>
            <select name="availability" id="edit_tech_avail" class="form-control">
              <option value="Available">Available</option>
              <option value="Busy">Busy</option>
              <option value="On Leave">On Leave</option>
            </select>
          </div>
          <div class="form-group">
            <label>Service Status</label>
            <select name="status" id="edit_tech_status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" onclick="closeModal('editTechModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Profile</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditTechModal(t) {
  document.getElementById('edit_tech_id').value = t.technician_id;
  document.getElementById('edit_tech_name').value = t.full_name;
  document.getElementById('edit_tech_spec').value = t.specialization;
  document.getElementById('edit_tech_exp').value = t.experience || '';
  document.getElementById('edit_tech_avail').value = t.availability;
  document.getElementById('edit_tech_status').value = t.tech_status || 'active';
  document.getElementById('editTechModal').style.display = 'flex';
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

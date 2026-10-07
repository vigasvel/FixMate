<?php
/**
 * FIXMATE - Admin User Management
 * Database-backed User Management (CRUD)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();
$current_user = getCurrentUser();

// Handle User Actions (Add, Edit, Toggle Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        
        if (empty($full_name) || empty($email) || empty($password)) {
            setFlash('danger', 'Please provide full name, email, and password.');
        } else {
            // Check duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                setFlash('danger', 'A user with this email address already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, employee_id, department, password, role, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$full_name, $email, $phone, $employee_id, $department, $hash, $role, $status]);
                $new_id = $pdo->lastInsertId();
                
                // If role is technician, also create technicians record
                if ($role === 'technician') {
                    $spec = $_POST['specialization'] ?? 'General Maintenance';
                    $exp = $_POST['experience'] ?? '1 Year';
                    $stmtTech = $pdo->prepare("
                        INSERT INTO technicians (user_id, specialization, availability, experience, status, created_at)
                        VALUES (?, ?, 'Available', ?, 'active', NOW())
                    ");
                    $stmtTech->execute([$new_id, $spec, $exp]);
                }
                
                setFlash('success', 'User account created successfully.');
            }
        }
        header("Location: users.php");
        exit;
    }
    
    if ($action === 'edit_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
        $new_password = $_POST['new_password'] ?? '';
        
        if ($user_id > 0) {
            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, phone = ?, employee_id = ?, department = ?, role = ?, status = ?, password = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $phone, $employee_id, $department, $role, $status, $hash, $user_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, phone = ?, employee_id = ?, department = ?, role = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $phone, $employee_id, $department, $role, $status, $user_id]);
            }
            setFlash('success', 'User details updated successfully.');
        }
        header("Location: users.php");
        exit;
    }
    
    if ($action === 'toggle_status') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $current_status = $_POST['current_status'] ?? 'active';
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
        
        if ($user_id === $current_user['id']) {
            setFlash('danger', 'You cannot deactivate your own administrative account.');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            setFlash('success', "User account status changed to " . ucfirst($new_status) . ".");
        }
        header("Location: users.php");
        exit;
    }
    
    if ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id === $current_user['id']) {
            setFlash('danger', 'You cannot delete your own active administrator account.');
        } else {
            try {
                // Delete technician profile if exists
                $stmt = $pdo->prepare("DELETE FROM technicians WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                setFlash('success', 'User deleted successfully.');
            } catch (PDOException $ex) {
                // In case of foreign key constraints (complaints logged)
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$user_id]);
                setFlash('warning', 'User has linked complaints/records and was deactivated instead of permanently deleted.');
            }
        }
        header("Location: users.php");
        exit;
    }
}

// Search and Filter
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR employee_id LIKE ? OR department LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($role_filter !== '') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get counts
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) AS total_users,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) AS role_users,
        SUM(CASE WHEN role = 'technician' THEN 1 ELSE 0 END) AS role_technicians,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS role_admins,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_users
    FROM users
");
$stats = $stats_stmt->fetch();

$page_title = "User Management";
$active_tab = "users";
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
          <h1 class="page-title">User Management</h1>
          <p class="text-muted">Manage system users, technicians, role permissions, and access states directly in MySQL.</p>
        </div>
        <div>
          <button class="btn btn-primary" onclick="openAddUserModal()">
            <i class="fa-solid fa-user-plus"></i> Add New User
          </button>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="stats-grid mb-4">
        <div class="stat-card">
          <div class="stat-icon bg-blue-subtle text-primary">
            <i class="fa-solid fa-users"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= number_format($stats['total_users'] ?? 0) ?></div>
            <div class="stat-label">Total Registered</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-green-subtle text-success">
            <i class="fa-solid fa-user-check"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= number_format($stats['active_users'] ?? 0) ?></div>
            <div class="stat-label">Active Accounts</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-yellow-subtle text-warning">
            <i class="fa-solid fa-wrench"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= number_format($stats['role_technicians'] ?? 0) ?></div>
            <div class="stat-label">Technicians</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-purple-subtle text-purple">
            <i class="fa-solid fa-user-shield"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= number_format($stats['role_admins'] ?? 0) ?></div>
            <div class="stat-label">Administrators</div>
          </div>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="card mb-4">
        <div class="card-body">
          <form method="GET" class="filter-row">
            <div class="form-group flex-1">
              <label><i class="fa-solid fa-magnifying-glass"></i> Search</label>
              <input type="text" name="search" class="form-control" placeholder="Search by name, email, employee ID, department..." value="<?= e($search) ?>">
            </div>
            <div class="form-group" style="min-width: 160px;">
              <label>Role</label>
              <select name="role" class="form-control">
                <option value="">All Roles</option>
                <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Staff / User</option>
                <option value="technician" <?= $role_filter === 'technician' ? 'selected' : '' ?>>Technician</option>
                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrator</option>
              </select>
            </div>
            <div class="form-group" style="min-width: 150px;">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
            <div class="form-group" style="align-self: flex-end;">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
              <a href="users.php" class="btn btn-light"><i class="fa-solid fa-rotate-right"></i> Reset</a>
            </div>
          </form>
        </div>
      </div>

      <!-- Users Table -->
      <div class="card">
        <div class="card-header flex-between">
          <h2 class="card-title"><i class="fa-solid fa-address-book"></i> System Users Ledger (<?= count($users) ?>)</h2>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>User Details</th>
                <th>Contact</th>
                <th>Department</th>
                <th>Role</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-user-slash fa-3x mb-3"></i>
                    <p>No user accounts matched your search criteria.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td><strong>#<?= $u['id'] ?></strong></td>
                    <td>
                      <div class="user-meta-cell">
                        <div class="avatar-circle">
                          <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                          <div class="font-bold"><?= e($u['full_name']) ?></div>
                          <div class="text-xs text-muted"><?= e($u['email']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="text-sm"><i class="fa-solid fa-phone text-muted mr-1"></i> <?= e($u['phone'] ?: 'N/A') ?></div>
                      <div class="text-xs text-muted"><i class="fa-solid fa-id-badge mr-1"></i> <?= e($u['employee_id'] ?: 'N/A') ?></div>
                    </td>
                    <td>
                      <span class="badge bg-secondary-subtle"><?= e($u['department'] ?: 'General') ?></span>
                    </td>
                    <td>
                      <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge bg-purple-subtle text-purple"><i class="fa-solid fa-shield"></i> Admin</span>
                      <?php elseif ($u['role'] === 'technician'): ?>
                        <span class="badge bg-yellow-subtle text-warning"><i class="fa-solid fa-wrench"></i> Technician</span>
                      <?php else: ?>
                        <span class="badge bg-blue-subtle text-primary"><i class="fa-solid fa-user"></i> User</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($u['status'] === 'active'): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Active</span>
                      <?php else: ?>
                        <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-sm text-muted">
                      <?= formatDate($u['created_at']) ?>
                    </td>
                    <td>
                      <div class="action-btn-group">
                        <button class="btn btn-sm btn-light" title="Edit User" onclick='openEditUserModal(<?= json_encode($u) ?>)'>
                          <i class="fa-solid fa-pen-to-square text-primary"></i>
                        </button>
                        
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Change status for <?= addslashes($u['full_name']) ?>?');">
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                          <button type="submit" class="btn btn-sm btn-light" title="<?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                            <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban text-warning' : 'fa-check text-success' ?>"></i>
                          </button>
                        </form>

                        <?php if ($u['id'] !== $current_user['id']): ?>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete this user account?');">
                          <input type="hidden" name="action" value="delete_user">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-light" title="Delete User">
                            <i class="fa-solid fa-trash-can text-danger"></i>
                          </button>
                        </form>
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
    </div>
  </div>
</div>

<!-- Modal: Add New User -->
<div id="addUserModal" class="modal-backdrop" style="display: none;">
  <div class="modal-box">
    <div class="modal-header flex-between">
      <h3 class="modal-title"><i class="fa-solid fa-user-plus text-primary"></i> Add New User</h3>
      <button type="button" class="btn-close" onclick="closeModal('addUserModal')">&times;</button>
    </div>
    <form method="POST" data-validate="true">
      <input type="hidden" name="action" value="add_user">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" class="form-control" required placeholder="e.g., Dr. Rajesh Kumar">
          </div>
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" class="form-control" required placeholder="name@fixmate.com">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="+91 9876543210">
          </div>
          <div class="form-group">
            <label>Employee / Student ID</label>
            <input type="text" name="employee_id" class="form-control" placeholder="EMP-2026-042">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" class="form-control" placeholder="e.g., Computer Science">
          </div>
          <div class="form-group">
            <label>System Role *</label>
            <select name="role" id="new_user_role" class="form-control" onchange="toggleTechSpecialization(this.value)">
              <option value="user">User / Facility Staff</option>
              <option value="technician">Technician</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
        </div>
        
        <div id="techFields" style="display: none;" class="p-3 bg-light rounded mb-3 border">
          <h4 class="text-sm font-bold mb-2 text-primary"><i class="fa-solid fa-screwdriver-wrench"></i> Technician Profile</h4>
          <div class="form-grid-2">
            <div class="form-group">
              <label>Specialization</label>
              <select name="specialization" class="form-control">
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
              <input type="text" name="experience" class="form-control" placeholder="e.g., 3 Years">
            </div>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label>Account Status</label>
            <select name="status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" class="form-control" required minlength="6" placeholder="Min 6 characters">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" onclick="closeModal('addUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Create User Account</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit User -->
<div id="editUserModal" class="modal-backdrop" style="display: none;">
  <div class="modal-box">
    <div class="modal-header flex-between">
      <h3 class="modal-title"><i class="fa-solid fa-user-pen text-primary"></i> Edit User Details</h3>
      <button type="button" class="btn-close" onclick="closeModal('editUserModal')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_user">
      <input type="hidden" name="user_id" id="edit_user_id">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="edit_email" class="form-control" disabled>
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" id="edit_phone" class="form-control">
          </div>
          <div class="form-group">
            <label>Employee / Student ID</label>
            <input type="text" name="employee_id" id="edit_employee_id" class="form-control">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" id="edit_department" class="form-control">
          </div>
          <div class="form-group">
            <label>System Role *</label>
            <select name="role" id="edit_role" class="form-control">
              <option value="user">User / Facility Staff</option>
              <option value="technician">Technician</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>Account Status</label>
            <select name="status" id="edit_status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label>Change Password (Leave blank to keep current)</label>
            <input type="password" name="new_password" class="form-control" minlength="6" placeholder="Enter new password">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" onclick="closeModal('editUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddUserModal() {
  document.getElementById('addUserModal').style.display = 'flex';
}

function openEditUserModal(user) {
  document.getElementById('edit_user_id').value = user.id;
  document.getElementById('edit_full_name').value = user.full_name;
  document.getElementById('edit_email').value = user.email;
  document.getElementById('edit_phone').value = user.phone || '';
  document.getElementById('edit_employee_id').value = user.employee_id || '';
  document.getElementById('edit_department').value = user.department || '';
  document.getElementById('edit_role').value = user.role;
  document.getElementById('edit_status').value = user.status;
  document.getElementById('editUserModal').style.display = 'flex';
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}

function toggleTechSpecialization(val) {
  document.getElementById('techFields').style.display = (val === 'technician') ? 'block' : 'none';
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

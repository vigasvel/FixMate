<?php
/**
 * FIXMATE - Admin Category Management
 * CRUD for categories table, tracks total active complaints linked to each category
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();
$current_user = getCurrentUser();

// Handle Actions (Add, Edit, Toggle, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $category_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (!empty($category_name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, description, status, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$category_name, $description, $status]);
            setFlash('success', 'Maintenance category added successfully.');
        } else {
            setFlash('danger', 'Category name cannot be blank.');
        }
        header("Location: categories.php");
        exit;
    }

    if ($action === 'edit_category') {
        $cat_id = intval($_POST['category_id'] ?? 0);
        $category_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($cat_id > 0 && !empty($category_name)) {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$category_name, $description, $status, $cat_id]);
            setFlash('success', 'Category updated successfully.');
        }
        header("Location: categories.php");
        exit;
    }

    if ($action === 'toggle_status') {
        $cat_id = intval($_POST['category_id'] ?? 0);
        $current_status = $_POST['current_status'] ?? 'active';
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';

        $stmt = $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $cat_id]);
        setFlash('success', 'Category status updated.');
        header("Location: categories.php");
        exit;
    }

    if ($action === 'delete_category') {
        $cat_id = intval($_POST['category_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$cat_id]);
            setFlash('success', 'Category deleted successfully.');
        } catch (PDOException $ex) {
            $stmt = $pdo->prepare("UPDATE categories SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$cat_id]);
            setFlash('warning', 'Category has associated complaints and was set to Inactive instead of deletion.');
        }
        header("Location: categories.php");
        exit;
    }
}

// Fetch categories with live complaint counts from MySQL
$stmt = $pdo->query("
    SELECT 
        cat.id,
        cat.category_name,
        cat.description,
        cat.status,
        cat.created_at,
        COUNT(c.id) AS total_complaints,
        SUM(CASE WHEN c.status IN ('Submitted', 'Assigned', 'In Progress') THEN 1 ELSE 0 END) AS pending_complaints,
        SUM(CASE WHEN c.status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) AS resolved_complaints
    FROM categories cat
    LEFT JOIN complaints c ON c.category_id = cat.id
    GROUP BY cat.id, cat.category_name, cat.description, cat.status, cat.created_at
    ORDER BY cat.id ASC
");
$categories = $stmt->fetchAll();

$page_title = "Category Management";
$active_tab = "categories";
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
          <h1 class="page-title">Maintenance Categories</h1>
          <p class="text-muted">Organize service categories, track incident frequencies, and manage reporting classifications.</p>
        </div>
        <div>
          <button class="btn btn-primary" onclick="openAddCatModal()">
            <i class="fa-solid fa-plus"></i> Add New Category
          </button>
        </div>
      </div>

      <!-- Categories Table -->
      <div class="card">
        <div class="card-header flex-between">
          <h2 class="card-title"><i class="fa-solid fa-tags"></i> Facility Service Categories (<?= count($categories) ?>)</h2>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Total Tickets</th>
                <th>Active / In Queue</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $cat): ?>
                <tr>
                  <td><strong>#<?= $cat['id'] ?></strong></td>
                  <td>
                    <div class="font-bold text-primary">
                      <i class="fa-solid fa-folder-open mr-1"></i> <?= e($cat['category_name']) ?>
                    </div>
                  </td>
                  <td class="text-sm text-muted">
                    <?= e($cat['description'] ?: 'Standard facility maintenance domain.') ?>
                  </td>
                  <td>
                    <span class="badge bg-secondary-subtle">
                      <?= intval($cat['total_complaints']) ?> tickets
                    </span>
                  </td>
                  <td>
                    <?php if ($cat['pending_complaints'] > 0): ?>
                      <span class="badge badge-warning"><?= intval($cat['pending_complaints']) ?> pending</span>
                    <?php else: ?>
                      <span class="badge badge-success">Clear</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($cat['status'] === 'active'): ?>
                      <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Active</span>
                    <?php else: ?>
                      <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="action-btn-group">
                      <button class="btn btn-sm btn-light" onclick='openEditCatModal(<?= json_encode($cat) ?>)' title="Edit Category">
                        <i class="fa-solid fa-pen-to-square text-primary"></i>
                      </button>

                      <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <input type="hidden" name="current_status" value="<?= $cat['status'] ?>">
                        <button type="submit" class="btn btn-sm btn-light" title="<?= $cat['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                          <i class="fa-solid <?= $cat['status'] === 'active' ? 'fa-ban text-warning' : 'fa-check text-success' ?>"></i>
                        </button>
                      </form>

                      <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete category <?= addslashes($cat['category_name']) ?>?');">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-light" title="Delete">
                          <i class="fa-solid fa-trash-can text-danger"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Add Category -->
<div id="addCatModal" class="modal-backdrop" style="display: none;">
  <div class="modal-box">
    <div class="modal-header flex-between">
      <h3 class="modal-title"><i class="fa-solid fa-plus-circle text-primary"></i> Add Category</h3>
      <button type="button" class="btn-close" onclick="closeModal('addCatModal')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_category">
      <div class="modal-body">
        <div class="form-group mb-3">
          <label>Category Name *</label>
          <input type="text" name="category_name" class="form-control" required placeholder="e.g., HVAC & Climate Control">
        </div>
        <div class="form-group mb-3">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief scope of issues covered under this department..."></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" onclick="closeModal('addCatModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Category -->
<div id="editCatModal" class="modal-backdrop" style="display: none;">
  <div class="modal-box">
    <div class="modal-header flex-between">
      <h3 class="modal-title"><i class="fa-solid fa-pen text-primary"></i> Edit Category</h3>
      <button type="button" class="btn-close" onclick="closeModal('editCatModal')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_category">
      <input type="hidden" name="category_id" id="edit_cat_id">
      <div class="modal-body">
        <div class="form-group mb-3">
          <label>Category Name *</label>
          <input type="text" name="category_name" id="edit_cat_name" class="form-control" required>
        </div>
        <div class="form-group mb-3">
          <label>Description</label>
          <textarea name="description" id="edit_cat_desc" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="edit_cat_status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" onclick="closeModal('editCatModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Category</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddCatModal() {
  document.getElementById('addCatModal').style.display = 'flex';
}

function openEditCatModal(cat) {
  document.getElementById('edit_cat_id').value = cat.id;
  document.getElementById('edit_cat_name').value = cat.category_name;
  document.getElementById('edit_cat_desc').value = cat.description || '';
  document.getElementById('edit_cat_status').value = cat.status;
  document.getElementById('editCatModal').style.display = 'flex';
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * FIXMATE - Technician Profile & Availability Controller (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['technician', 'admin'], '../');
$pdo = getDB();

$techStmt = $pdo->prepare("
    SELECT t.*, u.full_name, u.email, u.phone, u.employee_id, u.department
    FROM technicians t
    JOIN users u ON t.user_id = u.id
    WHERE t.user_id = ?
");
$techStmt->execute([$user['id']]);
$tech = $techStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_availability') {
    $availability = trim($_POST['availability'] ?? 'Available');
    $upStmt = $pdo->prepare("UPDATE technicians SET availability = ? WHERE user_id = ?");
    $upStmt->execute([$availability, $user['id']]);
    setFlash('success', "Your duty availability updated to '{$availability}'.");
    header("Location: profile.php");
    exit;
}

$page_title = "Technician Profile – FixMate";
$active_nav = "profile";
$header_title = "Technician Staff Profile";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="max-width: 860px; margin: 0 auto;">
      <?= displayFlash() ?>

      <div class="card" style="margin-bottom: 2rem;">
        <div style="display:flex; align-items:center; gap:1.75rem; flex-wrap:wrap;">
          <div style="width:90px; height:90px; border-radius:50%; background:linear-gradient(135deg, #F59E0B, #D97706); color:#FFFFFF; font-size:2.2rem; font-weight:700; display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow-md);">
            <?= strtoupper(substr($tech['full_name'] ?? 'Tech', 0, 2)) ?>
          </div>
          <div style="flex:1;">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.25rem;">
              <h1 style="font-size:1.6rem; font-weight:700;"><?= e($tech['full_name'] ?? $user['full_name']) ?></h1>
              <span class="role-pill technician">TECHNICIAN</span>
              <span class="badge <?= ($tech['availability'] === 'Available') ? 'badge-low' : 'badge-high' ?>"><?= e($tech['availability'] ?? 'Available') ?></span>
            </div>
            <div style="font-size:0.95rem; color:var(--text-muted); margin-bottom:0.5rem;">
              Specialization: <strong><?= e($tech['specialization'] ?? 'General') ?></strong> • Department: <?= e($tech['department'] ?? 'Maintenance') ?>
            </div>
            <div style="display:flex; gap:1.25rem; font-size:0.85rem; color:var(--text-muted); flex-wrap:wrap;">
              <span><i class="fas fa-id-badge text-primary"></i> <?= e($tech['employee_id'] ?? 'TECH') ?></span>
              <span><i class="fas fa-envelope text-primary"></i> <?= e($tech['email'] ?? $user['email']) ?></span>
              <span><i class="fas fa-phone text-primary"></i> <?= e($tech['phone'] ?? '') ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Update Duty Availability -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="fas fa-arrows-rotate text-primary"></i> Duty Availability Status
          </div>
        </div>

        <form action="profile.php" method="POST">
          <input type="hidden" name="action" value="update_availability">
          <div class="form-group">
            <label class="form-label" for="availabilitySelect">Current Availability State <span class="required">*</span></label>
            <select id="availabilitySelect" name="availability" class="form-control" style="max-width:320px;">
              <option value="Available" <?= (($tech['availability'] ?? '') === 'Available') ? 'selected' : '' ?>>Available (Ready for Dispatch)</option>
              <option value="Busy" <?= (($tech['availability'] ?? '') === 'Busy') ? 'selected' : '' ?>>Busy (Handling Active Job)</option>
              <option value="On Leave" <?= (($tech['availability'] ?? '') === 'On Leave') ? 'selected' : '' ?>>On Leave (Off Duty)</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Availability Status
          </button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

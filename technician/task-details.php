<?php
/**
 * FIXMATE - Technician Task Workspace Details (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['technician', 'admin'], '../');
$pdo = getDB();

$techStmt = $pdo->prepare("SELECT id FROM technicians WHERE user_id = ?");
$techStmt->execute([$user['id']]);
$techId = (int)$techStmt->fetchColumn();

$complaintIdOrCode = trim($_GET['id'] ?? '');

if (empty($complaintIdOrCode)) {
    header("Location: assigned-tasks.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.*, cat.category_name, u_req.full_name AS requester_name, u_req.email AS requester_email,
           u_req.phone AS requester_phone, u_req.department AS requester_dept, u_req.employee_id AS requester_empid
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u_req ON c.user_id = u_req.id
    WHERE (c.complaint_code = ? OR c.id = ?) AND (c.technician_id = ? OR ? = 'admin')
    LIMIT 1
");
$stmt->execute([$complaintIdOrCode, $complaintIdOrCode, $techId, $user['role']]);
$task = $stmt->fetch();

if (!$task) {
    setFlash('danger', 'Task record not found or not assigned to your account.');
    header("Location: assigned-tasks.php");
    exit;
}

$page_title = "Task " . $task['complaint_code'] . " – FixMate Technician";
$active_nav = "assigned-tasks";
$header_title = "Maintenance Job Workspace";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
      <a href="assigned-tasks.php" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Task Board
      </a>

      <!-- Action Workflow Buttons -->
      <div style="display:flex; gap:0.75rem;">
        <?php if ($task['status'] === 'Assigned'): ?>
          <form action="start-task.php" method="POST" style="margin:0;">
            <input type="hidden" name="complaint_id" value="<?= $task['id'] ?>">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-play"></i> Start On-Site Work
            </button>
          </form>
        <?php elseif ($task['status'] === 'In Progress'): ?>
          <button type="button" class="btn btn-success" onclick="document.getElementById('resolveTaskModal').classList.add('show')">
            <i class="fas fa-check-circle"></i> Complete & Mark as Resolved
          </button>
        <?php endif; ?>
      </div>
    </div>

    <?= displayFlash() ?>

    <!-- Task Core Summary Card -->
    <div class="card">
      <div class="card-header">
        <div>
          <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.35rem;">
            <span style="font-size:1.4rem; font-weight:800; font-family:monospace; color:var(--primary);"><?= e($task['complaint_code']) ?></span>
            <?= getStatusBadge($task['status']) ?>
            <?= getPriorityBadge($task['priority']) ?>
          </div>
          <h1 style="font-size:1.35rem; font-weight:700; color:var(--text-main);"><?= e($task['title']) ?></h1>
        </div>
        <div style="text-align:right;">
          <div style="font-size:0.78rem; color:var(--text-muted);">Assigned Date</div>
          <div style="font-size:0.9rem; font-weight:600; color:var(--text-main);"><?= formatDate($task['assigned_date'] ?? $task['submitted_date']) ?></div>
        </div>
      </div>

      <!-- 2-Column Info Grid -->
      <div class="form-row" style="margin-bottom:1.5rem;">
        <!-- Requester Info -->
        <div style="background:var(--bg-main); padding:1.25rem; border-radius:var(--radius-lg); border:1px solid var(--border-color);">
          <h4 style="font-size:0.95rem; margin-bottom:0.75rem; color:var(--secondary);">
            <i class="fas fa-user-circle text-primary"></i> Customer / Requester Information
          </h4>
          <div style="font-size:0.88rem; line-height:1.7;">
            <div><span class="text-muted">Reported By:</span> <strong><?= e($task['requester_name']) ?></strong> (<?= e($task['requester_empid']) ?>)</div>
            <div><span class="text-muted">Department:</span> <?= e($task['requester_dept']) ?></div>
            <div><span class="text-muted">Direct Phone:</span> <a href="tel:<?= e($task['contact_number']) ?>" style="font-weight:600;"><?= e($task['contact_number']) ?></a></div>
            <div><span class="text-muted">Preferred Visit:</span> <?= formatDate($task['preferred_date'], 'd M Y') ?></div>
          </div>
        </div>

        <!-- Site & Location Info -->
        <div style="background:var(--bg-main); padding:1.25rem; border-radius:var(--radius-lg); border:1px solid var(--border-color);">
          <h4 style="font-size:0.95rem; margin-bottom:0.75rem; color:var(--secondary);">
            <i class="fas fa-location-dot text-danger"></i> Site Location & Category
          </h4>
          <div style="font-size:0.88rem; line-height:1.7;">
            <div><span class="text-muted">Problem Category:</span> <strong><?= e($task['category_name']) ?></strong></div>
            <div><span class="text-muted">Campus Block:</span> <strong><?= e($task['building']) ?></strong></div>
            <div><span class="text-muted">Floor & Room:</span> <?= e($task['floor']) ?> (<?= e($task['room_number'] ?? 'General Area') ?>)</div>
            <div><span class="text-muted">Safety Clearance:</span> <span class="badge badge-low">Verified Clean</span></div>
          </div>
        </div>
      </div>

      <!-- Problem Description Reported -->
      <div style="margin-bottom:1.5rem;">
        <h4 style="font-size:0.95rem; margin-bottom:0.5rem; color:var(--text-main);"><i class="fas fa-file-lines text-muted"></i> Reported Issue Details</h4>
        <div style="padding:1.25rem; background:#FFFFFF; border:1px solid var(--border-color); border-radius:var(--radius-md); font-size:0.95rem; line-height:1.6;">
          <?= nl2br(e($task['description'])) ?>
        </div>
      </div>

      <!-- Recorded Resolution Notes (If Resolved / Closed) -->
      <?php if (!empty($task['resolution_notes'])): ?>
        <div class="card" style="background:var(--success-bg); border-color:#BBF7D0;">
          <h4 style="color:var(--success-text); margin-bottom:0.5rem;"><i class="fas fa-check-circle"></i> Submitted Resolution Summary</h4>
          <p style="font-size:0.9rem; margin-bottom:0.4rem;"><strong>Work Done:</strong> <?= e($task['resolution_notes']) ?></p>
          <?php if (!empty($task['parts_used'])): ?>
            <p style="font-size:0.85rem; color:var(--text-muted);"><strong>Parts Replaced:</strong> <?= e($task['parts_used']) ?></p>
          <?php endif; ?>
          <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.4rem;">Resolved on: <?= formatDate($task['resolved_date']) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Resolution Modal Component -->
<div class="modal-backdrop" id="resolveTaskModal">
  <div class="modal-card">
    <div class="modal-header" style="background:var(--success-bg); border-bottom-color:#BBF7D0;">
      <div class="modal-title" style="color:var(--success-text); display:flex; align-items:center; gap:0.5rem;">
        <i class="fas fa-screwdriver-wrench"></i> Submit Work Completion Report
      </div>
      <button class="modal-close" onclick="document.getElementById('resolveTaskModal').classList.remove('show')">&times;</button>
    </div>
    <form action="resolve-task.php" method="POST">
      <input type="hidden" name="complaint_id" value="<?= $task['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="workPerformed">Work Performed / Fix Implemented <span class="required">*</span></label>
          <textarea id="workPerformed" name="work_performed" class="form-control" rows="3" placeholder="e.g. Replaced burnt capacitor on split AC compressor and replenished R410A refrigerant gas..." required></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="partsUsed">Replacement Parts / Consumables Used</label>
          <input type="text" id="partsUsed" name="parts_used" class="form-control" placeholder="e.g. 1x 50uF Capacitor, 500g Refrigerant Gas">
        </div>

        <div class="form-group">
          <label class="form-label" for="additionalNotes">Technician Observations / Preventive Notes</label>
          <textarea id="additionalNotes" name="additional_notes" class="form-control" rows="2" placeholder="e.g. Advised user to keep filter clean on monthly schedule..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('resolveTaskModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-check"></i> Submit Resolution & Mark Resolved
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

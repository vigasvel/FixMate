<?php
/**
 * FIXMATE - Admin Complaint Ticket Inspection View (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole('admin', '../');
$pdo = getDB();

$complaintIdOrCode = trim($_GET['id'] ?? '');

if (empty($complaintIdOrCode)) {
    header("Location: complaints.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.*, cat.category_name, u_user.full_name AS submitter_name, u_user.department AS submitter_dept, u_user.phone AS submitter_phone,
           u_tech.full_name AS tech_name, u_tech.phone AS tech_phone, f.rating AS feedback_rating, f.comment AS feedback_comment
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u_user ON c.user_id = u_user.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    LEFT JOIN users u_tech ON t.user_id = u_tech.id
    LEFT JOIN feedback f ON c.id = f.complaint_id
    WHERE c.complaint_code = ? OR c.id = ?
    LIMIT 1
");
$stmt->execute([$complaintIdOrCode, $complaintIdOrCode]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint record not found.');
    header("Location: complaints.php");
    exit;
}

// Fetch comments
$stmtComments = $pdo->prepare("
    SELECT cc.*, u.full_name, u.role
    FROM complaint_comments cc
    JOIN users u ON cc.user_id = u.id
    WHERE cc.complaint_id = ?
    ORDER BY cc.created_at ASC
");
$stmtComments->execute([$complaint['id']]);
$comments = $stmtComments->fetchAll();

// 5-Stage Stepper calculation
$steps = ['Submitted', 'Assigned', 'In Progress', 'Resolved', 'Closed'];
$currentStepIdx = array_search($complaint['status'], $steps);
$progressPct = ($currentStepIdx !== false) ? ($currentStepIdx / 4) * 80 : 0;

$page_title = "Admin Ticket Inspection " . $complaint['complaint_code'] . " – FixMate";
$active_nav = "admin-complaints";
$header_title = "Administrative Ticket Audit";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <a href="complaints.php" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Complaints Ledger
      </a>
    </div>

    <?= displayFlash() ?>

    <div class="card">
      <div class="card-header">
        <div>
          <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.35rem;">
            <span style="font-size:1.4rem; font-weight:800; font-family:monospace; color:var(--primary);"><?= e($complaint['complaint_code']) ?></span>
            <?= getStatusBadge($complaint['status']) ?>
            <?= getPriorityBadge($complaint['priority']) ?>
          </div>
          <h1 style="font-size:1.35rem; font-weight:700; color:var(--text-main);"><?= e($complaint['title']) ?></h1>
        </div>
        <div style="text-align:right;">
          <div style="font-size:0.78rem; color:var(--text-muted);">Reported On</div>
          <div style="font-size:0.9rem; font-weight:600; color:var(--text-main);"><?= formatDate($complaint['submitted_date']) ?></div>
        </div>
      </div>

      <!-- 5-Step Stepper -->
      <div style="padding:1rem 0; border-bottom:1px solid var(--border-color); margin-bottom:1.5rem;">
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

      <!-- Details Grid -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; background:var(--bg-main); padding:1.25rem; border-radius:var(--radius-lg); margin-bottom:1.5rem;">
        <div>
          <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase;">Category</div>
          <div style="font-weight:600; font-size:0.95rem; margin-top:2px;"><?= e($complaint['category_name']) ?></div>
        </div>

        <div>
          <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase;">Location & Room</div>
          <div style="font-weight:600; font-size:0.95rem; margin-top:2px;"><?= e($complaint['location']) ?></div>
        </div>

        <div>
          <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase;">Reported By</div>
          <div style="font-weight:600; font-size:0.95rem; margin-top:2px;"><?= e($complaint['submitter_name']) ?> (<?= e($complaint['submitter_dept']) ?>)</div>
        </div>

        <div>
          <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase;">Assigned Technician</div>
          <div style="font-weight:600; font-size:0.95rem; margin-top:2px; color:var(--primary);"><?= e($complaint['tech_name'] ?? 'Unassigned') ?></div>
        </div>
      </div>

      <!-- Problem Description -->
      <div style="margin-bottom:1.5rem;">
        <h3 style="font-size:1rem; margin-bottom:0.5rem; color:var(--text-main);"><i class="fas fa-align-left text-muted"></i> Problem Description</h3>
        <p style="font-size:0.95rem; line-height:1.6; color:var(--text-main); background:#FFFFFF; border:1px solid var(--border-color); padding:1.25rem; border-radius:var(--radius-md);">
          <?= nl2br(e($complaint['description'])) ?>
        </p>
      </div>

      <!-- Resolution Summary -->
      <?php if (!empty($complaint['resolution_notes'])): ?>
        <div style="background:var(--success-bg); border:1px solid #BBF7D0; border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1.5rem;">
          <h4 style="font-size:0.95rem; color:var(--success-text); margin-bottom:0.5rem;"><i class="fas fa-check-circle"></i> Technician Resolution Notes</h4>
          <p style="font-size:0.9rem; color:var(--text-main); margin-bottom:0.4rem;"><strong>Work Done:</strong> <?= e($complaint['resolution_notes']) ?></p>
          <?php if (!empty($complaint['parts_used'])): ?>
            <p style="font-size:0.85rem; color:var(--text-muted);"><strong>Parts Replaced:</strong> <?= e($complaint['parts_used']) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Feedback Review if Closed -->
      <?php if (!empty($complaint['feedback_rating'])): ?>
        <div style="background:var(--bg-main); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1rem 1.25rem;">
          <div style="font-weight:600; font-size:0.9rem; color:var(--text-main);"><i class="fas fa-star text-warning"></i> Customer Feedback: <?= (int)$complaint['feedback_rating'] ?> / 5 Stars</div>
          <?php if (!empty($complaint['feedback_comment'])): ?>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">"<?= e($complaint['feedback_comment']) ?>"</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 2-Column: Activity Log & Comments Thread -->
    <div class="form-row" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left text-primary"></i> Lifecycle Timeline</div></div>
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-header"><span class="timeline-title">Submitted</span><span class="timeline-time"><?= formatDate($complaint['submitted_date']) ?></span></div>
            <div class="timeline-desc">Logged by <?= e($complaint['submitter_name']) ?>.</div>
          </div>
          <?php if (!empty($complaint['assigned_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-header"><span class="timeline-title">Assigned</span><span class="timeline-time"><?= formatDate($complaint['assigned_date']) ?></span></div>
              <div class="timeline-desc">Assigned to <?= e($complaint['tech_name'] ?? 'Staff') ?>.</div>
            </div>
          <?php endif; ?>
          <?php if (!empty($complaint['started_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-header"><span class="timeline-title">In Progress</span><span class="timeline-time"><?= formatDate($complaint['started_date']) ?></span></div>
              <div class="timeline-desc">Work commenced on-site.</div>
            </div>
          <?php endif; ?>
          <?php if (!empty($complaint['resolved_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot success"></div>
              <div class="timeline-header"><span class="timeline-title">Resolved</span><span class="timeline-time"><?= formatDate($complaint['resolved_date']) ?></span></div>
              <div class="timeline-desc">Work completed by technician.</div>
            </div>
          <?php endif; ?>
          <?php if (!empty($complaint['closed_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot success"></div>
              <div class="timeline-header"><span class="timeline-title">Closed</span><span class="timeline-time"><?= formatDate($complaint['closed_date']) ?></span></div>
              <div class="timeline-desc">Ticket closed. Feedback received.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-comments text-primary"></i> Comments Thread</div></div>
        <div style="max-height:280px; overflow-y:auto; margin-bottom:1.25rem;">
          <?php if (empty($comments)): ?>
            <div style="text-align:center; padding:1.5rem; color:var(--text-muted); font-size:0.85rem;">No comments recorded.</div>
          <?php else: ?>
            <?php foreach ($comments as $cm): ?>
              <div style="background:var(--bg-main); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.85rem 1rem; margin-bottom:0.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                  <div style="font-weight:600; font-size:0.85rem; color:var(--text-main);">
                    <?= e($cm['full_name']) ?> <span class="role-pill <?= e($cm['role']) ?>" style="font-size:0.65rem;"><?= e($cm['role']) ?></span>
                  </div>
                  <div style="font-size:0.72rem; color:var(--text-muted);"><?= formatDate($cm['created_at']) ?></div>
                </div>
                <div style="font-size:0.875rem; color:var(--text-main);"><?= nl2br(e($cm['comment'])) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <form action="../user/add-comment.php" method="POST">
          <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
          <input type="hidden" name="complaint_code" value="<?= e($complaint['complaint_code']) ?>">
          <div class="form-group" style="margin-bottom:0.75rem;">
            <textarea name="comment" class="form-control" rows="2" placeholder="Write an administrative note..." required></textarea>
          </div>
          <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm">Post Admin Note</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * FIXMATE - Complaint Details & Timeline Tracker (User View)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['user', 'admin'], '../');
$pdo = getDB();

$complaintIdOrCode = trim($_GET['id'] ?? '');

if (empty($complaintIdOrCode)) {
    header("Location: my-complaints.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.*, cat.category_name, u_user.full_name AS submitter_name, u_user.department AS submitter_dept,
           u_tech.full_name AS tech_name, u_tech.phone AS tech_phone, f.rating AS feedback_rating, f.comment AS feedback_comment
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u_user ON c.user_id = u_user.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    LEFT JOIN users u_tech ON t.user_id = u_tech.id
    LEFT JOIN feedback f ON c.id = f.complaint_id
    WHERE (c.complaint_code = ? OR c.id = ?) AND (c.user_id = ? OR ? = 'admin')
    LIMIT 1
");
$stmt->execute([$complaintIdOrCode, $complaintIdOrCode, $user['id'], $user['role']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint record not found or access denied.');
    header("Location: my-complaints.php");
    exit;
}

// Fetch comments for this complaint
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

$page_title = "Complaint " . $complaint['complaint_code'] . " – FixMate";
$active_nav = "my-complaints";
$header_title = "Complaint Ticket Details";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <a href="my-complaints.php" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Listing
      </a>
    </div>

    <?= displayFlash() ?>

    <!-- Main Complaint Card -->
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

      <!-- 5-Step Visual Status Tracker Stepper -->
      <div style="padding:1rem 0; border-bottom:1px solid var(--border-color); margin-bottom:1.5rem;">
        <div style="font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.5rem;">
          <i class="fas fa-route text-primary"></i> Lifecycle Progress Tracker
        </div>
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
          <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase;">Submitted By</div>
          <div style="font-weight:600; font-size:0.95rem; margin-top:2px;"><?= e($complaint['submitter_name']) ?> (<?= e($complaint['submitter_dept']) ?>)</div>
        </div>

        <div>
          <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase;">Assigned Technician</div>
          <div style="font-weight:600; font-size:0.95rem; margin-top:2px; color:var(--primary);">
            <?= e($complaint['tech_name'] ?? 'Unassigned') ?>
          </div>
        </div>
      </div>

      <!-- Problem Description -->
      <div style="margin-bottom:1.5rem;">
        <h3 style="font-size:1rem; margin-bottom:0.5rem; color:var(--text-main);"><i class="fas fa-align-left text-muted"></i> Problem Description</h3>
        <p style="font-size:0.95rem; line-height:1.6; color:var(--text-main); background:#FFFFFF; border:1px solid var(--border-color); padding:1.25rem; border-radius:var(--radius-md);">
          <?= nl2br(e($complaint['description'])) ?>
        </p>
      </div>

      <!-- Resolution Notes (if completed) -->
      <?php if (!empty($complaint['resolution_notes'])): ?>
        <div style="background:var(--success-bg); border:1px solid #BBF7D0; border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1.5rem;">
          <h4 style="font-size:0.95rem; color:var(--success-text); margin-bottom:0.5rem;">
            <i class="fas fa-circle-check"></i> Technician Resolution Summary
          </h4>
          <p style="font-size:0.9rem; color:var(--text-main); margin-bottom:0.5rem;">
            <strong>Work Done:</strong> <?= e($complaint['resolution_notes']) ?>
          </p>
          <?php if (!empty($complaint['parts_used'])): ?>
            <p style="font-size:0.85rem; color:var(--text-muted);"><strong>Parts Replaced:</strong> <?= e($complaint['parts_used']) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- User Confirmation & Feedback Box (If Resolved) -->
      <?php if ($complaint['status'] === 'Resolved'): ?>
        <div id="confirmBox" style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:var(--radius-md); padding:1.5rem; margin-bottom:1.5rem;">
          <h4 style="color:#B45309; font-size:1.05rem; margin-bottom:0.4rem;">
            <i class="fas fa-star text-warning"></i> Technician Marked Issue as Resolved
          </h4>
          <p style="font-size:0.9rem; color:#92400E; margin-bottom:1.25rem;">
            Please verify the physical fix on-site and submit your satisfaction feedback to close this ticket.
          </p>

          <form action="feedback.php" method="POST">
            <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
            <input type="hidden" name="complaint_code" value="<?= e($complaint['complaint_code']) ?>">

            <div class="form-group">
              <label class="form-label">Service Rating (1 to 5 Stars) <span class="required">*</span></label>
              <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" checked><label for="star5"><i class="fas fa-star"></i></label>
                <input type="radio" id="star4" name="rating" value="4"><label for="star4"><i class="fas fa-star"></i></label>
                <input type="radio" id="star3" name="rating" value="3"><label for="star3"><i class="fas fa-star"></i></label>
                <input type="radio" id="star2" name="rating" value="2"><label for="star2"><i class="fas fa-star"></i></label>
                <input type="radio" id="star1" name="rating" value="1"><label for="star1"><i class="fas fa-star"></i></label>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="feedbackComment">Feedback / Verification Comments</label>
              <textarea id="feedbackComment" name="comment" class="form-control" rows="2" placeholder="Confirm the equipment is working properly or provide comments..."></textarea>
            </div>

            <button type="submit" class="btn btn-success">
              <i class="fas fa-check-double"></i> Confirm Resolution & Close Ticket
            </button>
          </form>
        </div>
      <?php elseif ($complaint['status'] === 'Closed' && !empty($complaint['feedback_rating'])): ?>
        <div style="background:var(--bg-main); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1rem 1.25rem; margin-bottom:1.5rem;">
          <div style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-star text-warning"></i> User Service Review: <?= (int)$complaint['feedback_rating'] ?> / 5 Stars
          </div>
          <?php if (!empty($complaint['feedback_comment'])): ?>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">"<?= e($complaint['feedback_comment']) ?>"</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 2-Column Grid: Timeline & Comments -->
    <div class="form-row" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
      <!-- Activity Timeline -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="fas fa-clock-rotate-left text-primary"></i>
            <span>Activity Audit Log</span>
          </div>
        </div>
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-header">
              <span class="timeline-title">Submitted</span>
              <span class="timeline-time"><?= formatDate($complaint['submitted_date']) ?></span>
            </div>
            <div class="timeline-desc">Logged into MySQL database by <?= e($complaint['submitter_name']) ?>.</div>
          </div>

          <?php if (!empty($complaint['assigned_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-header">
                <span class="timeline-title">Assigned</span>
                <span class="timeline-time"><?= formatDate($complaint['assigned_date']) ?></span>
              </div>
              <div class="timeline-desc">Dispatched to technician <?= e($complaint['tech_name'] ?? 'Specialist') ?>.</div>
            </div>
          <?php endif; ?>

          <?php if (!empty($complaint['started_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-header">
                <span class="timeline-title">In Progress</span>
                <span class="timeline-time"><?= formatDate($complaint['started_date']) ?></span>
              </div>
              <div class="timeline-desc">On-site troubleshooting started by technician.</div>
            </div>
          <?php endif; ?>

          <?php if (!empty($complaint['resolved_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot success"></div>
              <div class="timeline-header">
                <span class="timeline-title">Resolved</span>
                <span class="timeline-time"><?= formatDate($complaint['resolved_date']) ?></span>
              </div>
              <div class="timeline-desc">Repairs completed. Resolution report submitted.</div>
            </div>
          <?php endif; ?>

          <?php if (!empty($complaint['closed_date'])): ?>
            <div class="timeline-item">
              <div class="timeline-dot success"></div>
              <div class="timeline-header">
                <span class="timeline-title">Closed</span>
                <span class="timeline-time"><?= formatDate($complaint['closed_date']) ?></span>
              </div>
              <div class="timeline-desc">User confirmed resolution and rated service.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Comments Thread -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="fas fa-comments text-primary"></i>
            <span>Ticket Conversation</span>
          </div>
        </div>

        <div style="max-height:280px; overflow-y:auto; margin-bottom:1.25rem;">
          <?php if (empty($comments)): ?>
            <div style="text-align:center; padding:1.5rem; color:var(--text-muted); font-size:0.85rem;">
              No comments posted yet.
            </div>
          <?php else: ?>
            <?php foreach ($comments as $cm): ?>
              <div style="background:var(--bg-main); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.85rem 1rem; margin-bottom:0.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                  <div style="font-weight:600; font-size:0.85rem; color:var(--text-main);">
                    <i class="fas fa-user-circle text-primary"></i> <?= e($cm['full_name']) ?>
                    <span class="role-pill <?= e($cm['role']) ?>" style="font-size:0.65rem; margin-left:4px;"><?= e($cm['role']) ?></span>
                  </div>
                  <div style="font-size:0.72rem; color:var(--text-muted);"><?= formatDate($cm['created_at']) ?></div>
                </div>
                <div style="font-size:0.875rem; color:var(--text-main); line-height:1.4;">
                  <?= nl2br(e($cm['comment'])) ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Add Comment Form -->
        <form action="add-comment.php" method="POST">
          <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
          <input type="hidden" name="complaint_code" value="<?= e($complaint['complaint_code']) ?>">
          <div class="form-group" style="margin-bottom:0.75rem;">
            <textarea name="comment" class="form-control" rows="2" placeholder="Write a comment or reply to technician..." required></textarea>
          </div>
          <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="fas fa-paper-plane"></i> Post Comment
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

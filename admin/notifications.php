<?php
/**
 * FIXMATE - Admin Notifications Center
 * View system-wide and administrative maintenance notifications from MySQL
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();
$current_user = getCurrentUser();

// Handle Mark as Read / Clear Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $notif_id = intval($_POST['notification_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, $current_user['id']]);
        setFlash('success', 'Notification marked as read.');
    } elseif ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$current_user['id']]);
        setFlash('success', 'All notifications marked as read.');
    }
    header("Location: notifications.php");
    exit;
}

$filter = $_GET['filter'] ?? 'all';

$query = "
    SELECT n.*, c.complaint_code 
    FROM notifications n
    LEFT JOIN complaints c ON n.complaint_id = c.id
    WHERE n.user_id = ?
";

if ($filter === 'unread') {
    $query .= " AND n.is_read = 0";
} elseif ($filter === 'read') {
    $query .= " AND n.is_read = 1";
}

$query .= " ORDER BY n.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute([$current_user['id']]);
$notifications = $stmt->fetchAll();

// Counts
$countStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM notifications
    WHERE user_id = ?
");
$countStmt->execute([$current_user['id']]);
$notifCounts = $countStmt->fetch();

$page_title = "Notifications Hub";
$active_tab = "notifications";
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
          <h1 class="page-title">System & Dispatch Alerts</h1>
          <p class="text-muted">Live event triggers, ticket submissions, and technician resolution alerts from MySQL.</p>
        </div>
        <div>
          <?php if (($notifCounts['unread_count'] ?? 0) > 0): ?>
            <form method="POST" style="display: inline-block;">
              <input type="hidden" name="action" value="mark_all_read">
              <button type="submit" class="btn btn-light">
                <i class="fa-solid fa-check-double"></i> Mark All as Read
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="card mb-4">
        <div class="card-body p-2 flex-between">
          <div class="d-flex gap-2">
            <a href="notifications.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-light' ?>">
              All Alerts (<?= $notifCounts['total'] ?? 0 ?>)
            </a>
            <a href="notifications.php?filter=unread" class="btn btn-sm <?= $filter === 'unread' ? 'btn-primary' : 'btn-light' ?>">
              Unread (<?= $notifCounts['unread_count'] ?? 0 ?>)
            </a>
            <a href="notifications.php?filter=read" class="btn btn-sm <?= $filter === 'read' ? 'btn-primary' : 'btn-light' ?>">
              Read History
            </a>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="card">
        <div class="card-body p-0">
          <?php if (empty($notifications)): ?>
            <div class="text-center py-5 text-muted">
              <i class="fa-regular fa-bell-slash fa-3x mb-3"></i>
              <p>No notifications found in this view.</p>
            </div>
          <?php else: ?>
            <div class="notification-list">
              <?php foreach ($notifications as $n): ?>
                <div class="notification-item flex-between p-3 border-bottom <?= $n['is_read'] ? 'opacity-75' : 'bg-light-subtle' ?>">
                  <div class="d-flex align-items-start gap-3">
                    <div class="stat-icon-sm <?= $n['is_read'] ? 'bg-secondary-subtle text-muted' : 'bg-blue-subtle text-primary' ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                      <i class="fa-solid <?= $n['complaint_id'] ? 'fa-screwdriver-wrench' : 'fa-bell' ?>"></i>
                    </div>
                    <div>
                      <div class="font-medium text-dark">
                        <?= e($n['message']) ?>
                      </div>
                      <div class="text-xs text-muted mt-1">
                        <i class="fa-regular fa-clock mr-1"></i> <?= formatDate($n['created_at']) ?>
                        <?php if ($n['complaint_code']): ?>
                          &bull; <a href="complaint-details.php?id=<?= $n['complaint_id'] ?>" class="text-primary font-bold">View Ticket <?= e($n['complaint_code']) ?></a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <div>
                    <?php if (!$n['is_read']): ?>
                      <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-light" title="Mark as Read">
                          <i class="fa-solid fa-check text-primary"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * FIXMATE - Top Header / Navigation Bar Component
 */
$user = getCurrentUser();
$header_title = $header_title ?? "FixMate Facility Management";
$pdo = getDB();

// Fetch unread notifications for currently logged in user
$unreadNotifs = [];
$unreadCount = 0;
if ($user) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $unreadNotifs = $stmt->fetchAll();
    $unreadCount = count($unreadNotifs);
}
?>
<!-- Top Navigation Header -->
<header class="top-header">
  <div class="header-left">
    <button class="menu-toggle" id="mobileMenuBtn" aria-label="Toggle Sidebar">
      <i class="fas fa-bars"></i>
    </button>
    <h2 class="page-title"><?= e($header_title) ?></h2>
  </div>

  <div class="header-right">
    <!-- Quick Role Switcher for Lab Demo Convenience -->
    <div class="quick-demo-select" style="display:inline-block;">
      <select class="form-control" style="padding:0.4rem 0.6rem; font-size:0.8rem; background:var(--bg-main); border:1px solid var(--border-color); border-radius:6px;" onchange="window.location.href='<?= $base_url ?>login.php?quick_demo=' + this.value;">
        <option value="" disabled selected>⚡ Switch Demo Role...</option>
        <option value="user" <?= ($user && $user['role'] === 'user') ? 'selected' : '' ?>>👤 User (user@fixmate.com)</option>
        <option value="technician" <?= ($user && $user['role'] === 'technician') ? 'selected' : '' ?>>🔧 Tech (technician@fixmate.com)</option>
        <option value="admin" <?= ($user && $user['role'] === 'admin') ? 'selected' : '' ?>>🛡️ Admin (admin@fixmate.com)</option>
      </select>
    </div>

    <!-- Notification Bell & Dropdown -->
    <div style="position:relative;">
      <button class="header-action-btn" id="notifBellBtn" title="Notifications">
        <i class="far fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
          <span class="notif-badge-count"><?= $unreadCount ?></span>
        <?php endif; ?>
      </button>
      
      <div class="dropdown-menu" id="notifDropdown">
        <div class="dropdown-header">
          <span>Notifications (<?= $unreadCount ?> unread)</span>
        </div>
        <div class="dropdown-list">
          <?php if (empty($unreadNotifs)): ?>
            <div style="padding:1.5rem; text-align:center; color:var(--text-muted); font-size:0.85rem;">
              No unread notifications right now.
            </div>
          <?php else: ?>
            <?php foreach ($unreadNotifs as $n): ?>
              <div class="dropdown-item unread" style="padding:0.85rem 1rem; border-bottom:1px solid var(--border-color); font-size:0.85rem;">
                <div style="color:var(--<?= e($n['type']) ?>); font-size:1rem; margin-right:8px;">
                  <i class="fas fa-circle-info"></i>
                </div>
                <div>
                  <div style="font-weight:600; color:var(--text-main);"><?= e($n['message']) ?></div>
                  <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;"><?= formatDate($n['created_at']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Profile Link -->
    <a href="<?= $base_url . ($user['role'] === 'admin' ? 'admin/dashboard.php' : ($user['role'] === 'technician' ? 'technician/profile.php' : 'user/profile.php')) ?>" class="header-action-btn" title="My Profile">
      <i class="far fa-user"></i>
    </a>
  </div>
</header>

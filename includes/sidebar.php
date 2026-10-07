<?php
/**
 * FIXMATE - Sidebar Navigation Component
 */
$user = getCurrentUser();
$role = $user ? $user['role'] : 'user';
$userName = $user ? $user['full_name'] : 'Guest';
$empId = $user ? $user['employee_id'] : '';
$initials = '';
if ($user) {
    $parts = explode(' ', trim($userName));
    foreach ($parts as $p) {
        if (!empty($p)) $initials .= strtoupper($p[0]);
    }
    $initials = substr($initials, 0, 2);
}

$active_nav = $active_nav ?? '';
$pdo = getDB();
$unreadNotifCount = $user ? getUnreadNotificationCount($pdo, $user['id']) : 0;
?>
<!-- Sidebar Navigation -->
<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-header">
    <a href="<?= $base_url ?>index.php" class="brand-logo">
      <div class="brand-icon"><i class="fas fa-wrench"></i></div>
      <span>FixMate</span>
    </a>
    <span class="brand-badge"><?= e($role) ?></span>
  </div>

  <div class="sidebar-user">
    <div class="user-avatar-sm"><?= e($initials) ?></div>
    <div class="user-info-text">
      <div class="user-info-name"><?= e($userName) ?></div>
      <div class="user-info-role">
        <span class="role-pill <?= e($role) ?>"><?= e($role) ?></span>
        <span><?= e($empId) ?></span>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php if ($role === 'user'): ?>
      <div class="nav-section-title">User Menu</div>
      <a href="<?= $base_url ?>user/dashboard.php" class="nav-item <?= $active_nav === 'dashboard' ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> <span>Dashboard</span>
      </a>
      <a href="<?= $base_url ?>user/report-problem.php" class="nav-item <?= $active_nav === 'report-problem' ? 'active' : '' ?>">
        <i class="fas fa-plus-circle"></i> <span>Report Problem</span>
      </a>
      <a href="<?= $base_url ?>user/my-complaints.php" class="nav-item <?= $active_nav === 'my-complaints' ? 'active' : '' ?>">
        <i class="fas fa-list-check"></i> <span>My Complaints</span>
      </a>

      <div class="nav-section-title">Account</div>
      <a href="<?= $base_url ?>user/profile.php" class="nav-item <?= $active_nav === 'profile' ? 'active' : '' ?>">
        <i class="fas fa-user-circle"></i> <span>My Profile</span>
      </a>

    <?php elseif ($role === 'technician'): ?>
      <div class="nav-section-title">Technician Center</div>
      <a href="<?= $base_url ?>technician/dashboard.php" class="nav-item <?= $active_nav === 'tech-dashboard' ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i> <span>Task Dashboard</span>
      </a>
      <a href="<?= $base_url ?>technician/assigned-tasks.php" class="nav-item <?= $active_nav === 'assigned-tasks' ? 'active' : '' ?>">
        <i class="fas fa-tasks"></i> <span>Assigned Tasks</span>
      </a>

      <div class="nav-section-title">Account</div>
      <a href="<?= $base_url ?>technician/profile.php" class="nav-item <?= $active_nav === 'profile' ? 'active' : '' ?>">
        <i class="fas fa-user-circle"></i> <span>Technician Profile</span>
      </a>

    <?php elseif ($role === 'admin'): ?>
      <div class="nav-section-title">Administration</div>
      <a href="<?= $base_url ?>admin/dashboard.php" class="nav-item <?= $active_nav === 'admin-dashboard' ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i> <span>Admin Overview</span>
      </a>
      <a href="<?= $base_url ?>admin/complaints.php" class="nav-item <?= $active_nav === 'admin-complaints' ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> <span>All Complaints</span>
      </a>
      <a href="<?= $base_url ?>admin/users.php" class="nav-item <?= $active_nav === 'users' ? 'active' : '' ?>">
        <i class="fas fa-users"></i> <span>User Management</span>
      </a>
      <a href="<?= $base_url ?>admin/technicians.php" class="nav-item <?= $active_nav === 'technicians' ? 'active' : '' ?>">
        <i class="fas fa-user-gear"></i> <span>Technicians</span>
      </a>
      <a href="<?= $base_url ?>admin/categories.php" class="nav-item <?= $active_nav === 'categories' ? 'active' : '' ?>">
        <i class="fas fa-tags"></i> <span>Categories</span>
      </a>
      <a href="<?= $base_url ?>admin/reports.php" class="nav-item <?= $active_nav === 'reports' ? 'active' : '' ?>">
        <i class="fas fa-file-invoice"></i> <span>Reports & Analytics</span>
      </a>
      <a href="<?= $base_url ?>admin/notifications.php" class="nav-item <?= $active_nav === 'notifications' ? 'active' : '' ?>">
        <i class="fas fa-bell"></i> <span>System Alerts</span>
        <?php if ($unreadNotifCount > 0): ?>
          <span class="nav-badge danger"><?= $unreadNotifCount ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section-title">System</div>
      <a href="<?= $base_url ?>admin/settings.php" class="nav-item <?= $active_nav === 'settings' ? 'active' : '' ?>">
        <i class="fas fa-sliders"></i> <span>Settings</span>
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= $base_url ?>logout.php" class="btn btn-outline btn-sm w-100" style="width:100%; border-color:rgba(255,255,255,0.2); color:#F8FAFC;">
      <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
    </a>
  </div>
</aside>

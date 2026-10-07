<?php
/**
 * FIXMATE - Database Connectivity & Diagnostics Verification
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Database Test – FixMate";
$base_url = './';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body style="background:var(--bg-main); padding: 3rem 1.5rem;">

  <div style="max-width: 720px; margin: 0 auto;">
    <div class="card" style="padding: 2.5rem;">
      <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <div class="stat-icon green" style="width: 50px; height: 50px; font-size: 1.5rem;">
          <i class="fas fa-database"></i>
        </div>
        <div>
          <h2 style="font-size: 1.4rem; color: var(--success-text); margin-bottom: 0.2rem;">
            FixMate Database Connected Successfully
          </h2>
          <div style="font-size: 0.85rem; color: var(--text-muted);">
            Engine: PHP PDO MySQL Driver • Host: <?= DB_HOST ?>:<?= DB_PORT ?>
          </div>
        </div>
      </div>

      <?php
      try {
          $pdo = getDB();
          
          $tables = ['users', 'technicians', 'categories', 'complaints', 'complaint_comments', 'notifications', 'feedback'];
          $tableStats = [];

          foreach ($tables as $tbl) {
              $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`");
              $count = (int)$stmt->fetchColumn();
              $tableStats[$tbl] = $count;
          }
      ?>
        <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
          <h4 style="font-size: 1rem; margin-bottom: 0.75rem; color: var(--text-main);">
            <i class="fas fa-table-list text-primary"></i> Active Database Tables in <code><?= DB_NAME ?></code>:
          </h4>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
            <?php foreach ($tableStats as $table => $rows): ?>
              <div style="background: #FFFFFF; border: 1px solid var(--border-color); padding: 0.6rem 0.85rem; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 500; font-size: 0.88rem;"><code><?= $table ?></code></span>
                <span class="badge badge-medium"><?= $rows ?> rows</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="display: flex; gap: 1rem;">
          <a href="login.php" class="btn btn-primary">
            <i class="fas fa-right-to-bracket"></i> Go to FixMate Login
          </a>
          <a href="index.php" class="btn btn-outline">
            <i class="fas fa-home"></i> Landing Page
          </a>
        </div>

      <?php
      } catch (Exception $e) {
          echo "<div class='toast toast-danger'><div class='toast-msg'>" . e($e->getMessage()) . "</div></div>";
      }
      ?>
    </div>
  </div>

</body>
</html>

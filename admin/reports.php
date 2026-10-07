<?php
/**
 * FIXMATE - Admin Analytics & Maintenance Reports
 * SQL Aggregation Engine with Chart.js, CSV export and printable audit reports
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();
$current_user = getCurrentUser();

// Date range filters
$start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('-3 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$cat_filter = $_GET['category_id'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

// 1. Overall Aggregates
$stmtOverall = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_tickets,
        SUM(CASE WHEN status = 'Submitted' THEN 1 ELSE 0 END) AS count_submitted,
        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) AS count_assigned,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS count_in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS count_resolved,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS count_closed,
        SUM(CASE WHEN priority = 'Critical' THEN 1 ELSE 0 END) AS count_critical,
        AVG(CASE WHEN resolved_date IS NOT NULL AND submitted_date IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, submitted_date, resolved_date) ELSE NULL END) AS avg_resolution_hours
    FROM complaints
    WHERE DATE(submitted_date) BETWEEN ? AND ?
");
$stmtOverall->execute([$start_date, $end_date]);
$overall = $stmtOverall->fetch();

// 2. Complaints by Category
$stmtCategory = $pdo->prepare("
    SELECT 
        cat.category_name,
        COUNT(c.id) AS count,
        SUM(CASE WHEN c.status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) AS resolved_count
    FROM categories cat
    LEFT JOIN complaints c ON c.category_id = cat.id AND DATE(c.submitted_date) BETWEEN ? AND ?
    GROUP BY cat.id, cat.category_name
    ORDER BY count DESC
");
$stmtCategory->execute([$start_date, $end_date]);
$categories_data = $stmtCategory->fetchAll();

// 3. Complaints by Priority
$stmtPriority = $pdo->prepare("
    SELECT 
        priority,
        COUNT(id) AS count
    FROM complaints
    WHERE DATE(submitted_date) BETWEEN ? AND ?
    GROUP BY priority
    ORDER BY FIELD(priority, 'Critical', 'High', 'Medium', 'Low')
");
$stmtPriority->execute([$start_date, $end_date]);
$priority_data = $stmtPriority->fetchAll();

// 4. Technician Performance & SLA
$stmtTechPerf = $pdo->prepare("
    SELECT 
        u.full_name AS tech_name,
        t.specialization,
        COUNT(c.id) AS total_assigned,
        SUM(CASE WHEN c.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_count,
        SUM(CASE WHEN c.status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) AS resolved_count,
        AVG(CASE WHEN c.resolved_date IS NOT NULL AND c.assigned_date IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, c.assigned_date, c.resolved_date) ELSE NULL END) AS avg_turnaround_hrs,
        AVG(f.rating) AS avg_rating
    FROM technicians t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN complaints c ON c.technician_id = t.id AND DATE(c.submitted_date) BETWEEN ? AND ?
    LEFT JOIN feedback f ON f.complaint_id = c.id
    GROUP BY t.id, u.full_name, t.specialization
    ORDER BY resolved_count DESC
");
$stmtTechPerf->execute([$start_date, $end_date]);
$tech_perf = $stmtTechPerf->fetchAll();

// 5. Monthly Trend
$stmtMonthly = $pdo->query("
    SELECT 
        DATE_FORMAT(submitted_date, '%b %Y') AS month_label,
        COUNT(id) AS total,
        SUM(CASE WHEN status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) AS resolved
    FROM complaints
    WHERE submitted_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(submitted_date, '%Y-%m'), DATE_FORMAT(submitted_date, '%b %Y')
    ORDER BY DATE_FORMAT(submitted_date, '%Y-%m') ASC
");
$monthly_data = $stmtMonthly->fetchAll();

// Category selector list
$all_cats = $pdo->query("SELECT id, category_name FROM categories WHERE status = 'active'")->fetchAll();

$page_title = "Reports & Analytics";
$active_tab = "reports";
$base_url = "../";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
  <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="page-body">
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-wrapper">
      <?= displayFlash() ?>

      <div class="content-header flex-between mb-4 no-print">
        <div>
          <h1 class="page-title">Facility Performance & Incident Analytics</h1>
          <p class="text-muted">Aggregated SQL metrics, turnaround SLA benchmarks, and technician productivity audit.</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-light" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Report
          </button>
          <a href="export-csv.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success">
            <i class="fa-solid fa-file-csv"></i> Export CSV
          </a>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="card mb-4 no-print">
        <div class="card-body">
          <form method="GET" class="filter-row">
            <div class="form-group">
              <label>Date From</label>
              <input type="date" name="start_date" class="form-control" value="<?= e($start_date) ?>">
            </div>
            <div class="form-group">
              <label>Date To</label>
              <input type="date" name="end_date" class="form-control" value="<?= e($end_date) ?>">
            </div>
            <div class="form-group" style="align-self: flex-end;">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply Period</button>
              <a href="reports.php" class="btn btn-light"><i class="fa-solid fa-rotate-right"></i> Reset</a>
            </div>
          </form>
        </div>
      </div>

      <!-- Executive Metric Summary -->
      <div class="stats-grid mb-4">
        <div class="stat-card">
          <div class="stat-icon bg-blue-subtle text-primary">
            <i class="fa-solid fa-clipboard-list"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= number_format($overall['total_tickets'] ?? 0) ?></div>
            <div class="stat-label">Total Reported in Period</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-green-subtle text-success">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= number_format(($overall['count_resolved'] ?? 0) + ($overall['count_closed'] ?? 0)) ?></div>
            <div class="stat-label">Resolved & Closed</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-yellow-subtle text-warning">
            <i class="fa-solid fa-clock-rotate-left"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value">
              <?= $overall['avg_resolution_hours'] ? round($overall['avg_resolution_hours'], 1) . ' hrs' : 'N/A' ?>
            </div>
            <div class="stat-label">Avg. Resolution Time</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-red-subtle text-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div class="stat-data">
            <div class="stat-value"><?= number_format($overall['count_critical'] ?? 0) ?></div>
            <div class="stat-label">Critical Incidents</div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="dashboard-grid-2 mb-4">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-column text-primary"></i> Complaints by Category</h3>
          </div>
          <div class="card-body">
            <canvas id="categoryChart" style="max-height: 280px;"></canvas>
          </div>
        </div>
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-pie text-primary"></i> Complaints by Priority</h3>
          </div>
          <div class="card-body">
            <canvas id="priorityChart" style="max-height: 280px;"></canvas>
          </div>
        </div>
      </div>

      <!-- Technician Performance Table -->
      <div class="card mb-4">
        <div class="card-header flex-between">
          <h3 class="card-title"><i class="fa-solid fa-users-gear text-primary"></i> Technician Productivity & Resolution Benchmark</h3>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Technician</th>
                <th>Specialization</th>
                <th>Total Assigned</th>
                <th>In Progress</th>
                <th>Resolved / Closed</th>
                <th>Completion Rate</th>
                <th>Avg SLA Turnaround</th>
                <th>Avg Rating</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tech_perf)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No technician activity logged in this period.</td></tr>
              <?php else: ?>
                <?php foreach ($tech_perf as $tp): 
                  $rate = ($tp['total_assigned'] > 0) ? round(($tp['resolved_count'] / $tp['total_assigned']) * 100) : 0;
                ?>
                  <tr>
                    <td><strong><?= e($tp['tech_name']) ?></strong></td>
                    <td><span class="badge bg-secondary-subtle"><?= e($tp['specialization']) ?></span></td>
                    <td><?= intval($tp['total_assigned']) ?></td>
                    <td><span class="badge badge-warning"><?= intval($tp['in_progress_count']) ?></span></td>
                    <td><span class="badge badge-success"><?= intval($tp['resolved_count']) ?></span></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress" style="height: 6px; width: 80px; background: var(--border-color); border-radius: 3px;">
                          <div class="progress-bar bg-success" style="width: <?= $rate ?>%; height: 100%; border-radius: 3px;"></div>
                        </div>
                        <span class="text-xs font-bold"><?= $rate ?>%</span>
                      </div>
                    </td>
                    <td>
                      <?= $tp['avg_turnaround_hrs'] ? round($tp['avg_turnaround_hrs'], 1) . ' hrs' : 'N/A' ?>
                    </td>
                    <td>
                      <?php if ($tp['avg_rating']): ?>
                        <span class="text-warning font-bold"><i class="fa-solid fa-star"></i> <?= number_format($tp['avg_rating'], 1) ?> / 5</span>
                      <?php else: ?>
                        <span class="text-xs text-muted">No ratings</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Breakdown by Category Detail Table -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fa-solid fa-list-check text-primary"></i> Category Resolution Ledger</h3>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Reported Incidents</th>
                <th>Resolved / Closed</th>
                <th>Pending Active</th>
                <th>Resolution Ratio</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories_data as $cd): 
                $pending = $cd['count'] - $cd['resolved_count'];
                $ratio = ($cd['count'] > 0) ? round(($cd['resolved_count'] / $cd['count']) * 100) : 0;
              ?>
                <tr>
                  <td><strong><i class="fa-solid fa-folder text-primary mr-1"></i> <?= e($cd['category_name']) ?></strong></td>
                  <td><?= intval($cd['count']) ?></td>
                  <td><span class="badge badge-success"><?= intval($cd['resolved_count']) ?></span></td>
                  <td>
                    <?php if ($pending > 0): ?>
                      <span class="badge badge-warning"><?= $pending ?></span>
                    <?php else: ?>
                      <span class="badge badge-neutral">0</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?= $ratio ?>%</strong>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Category Breakdown Chart
    const catLabels = <?= json_encode(array_column($categories_data, 'category_name')) ?>;
    const catCounts = <?= json_encode(array_column($categories_data, 'count')) ?>;
    
    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Complaints',
                data: catCounts,
                backgroundColor: '#2563EB',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 2. Priority Chart
    const prioLabels = <?= json_encode(array_column($priority_data, 'priority')) ?>;
    const prioCounts = <?= json_encode(array_column($priority_data, 'count')) ?>;
    const prioColors = {
        'Critical': '#DC2626',
        'High': '#EA580C',
        'Medium': '#F59E0B',
        'Low': '#16A34A'
    };
    const bgPrio = prioLabels.map(l => prioColors[l] || '#64748B');

    new Chart(document.getElementById('priorityChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: prioLabels,
            datasets: [{
                data: prioCounts,
                backgroundColor: bgPrio
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

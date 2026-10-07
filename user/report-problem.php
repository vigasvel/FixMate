<?php
/**
 * FIXMATE - Report a Problem Form (User)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['user', 'admin'], '../');
$pdo = getDB();

// Fetch categories from MySQL
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();

// Generate prospective next code
$nextCode = generateComplaintCode($pdo);

$page_title = "Report a Problem – FixMate";
$active_nav = "report-problem";
$header_title = "Submit Maintenance Request";
$base_url = "../";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main class="page-content">
    <div style="max-width: 880px; margin: 0 auto;">
      <?= displayFlash() ?>

      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="fas fa-file-circle-plus text-primary"></i>
            <span>Submit New Facility Maintenance Complaint</span>
          </div>
          <span class="badge badge-submitted">Step 1 of 5: Submission</span>
        </div>

        <form action="submit-complaint.php" method="POST" id="reportProblemForm">
          <!-- Auto Generated ID & Category -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="autoComplaintId">Assigned Complaint Reference ID</label>
              <input type="text" id="autoComplaintId" class="form-control font-semibold" style="color:var(--primary); background:var(--bg-main);" readonly value="<?= e($nextCode) ?>">
              <div class="form-text">System auto-assigned tracking code for MySQL indexation.</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="categorySelect">Problem Category <span class="required">*</span></label>
              <select id="categorySelect" name="category_id" class="form-control" required>
                <option value="">-- Select Problem Category --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['category_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Complaint Title -->
          <div class="form-group">
            <label class="form-label" for="complaintTitle">Complaint Title / Summary <span class="required">*</span></label>
            <input type="text" id="complaintTitle" name="title" class="form-control" placeholder="e.g. Split AC not cooling in Computer Lab 3" required>
          </div>

          <!-- Location Grid -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="buildingSelect">Campus Building / Block <span class="required">*</span></label>
              <select id="buildingSelect" name="building" class="form-control" required>
                <option value="">-- Select Building --</option>
                <option value="Block A">Block A (Academic & CSE)</option>
                <option value="Block B">Block B (Mechanical & EEE)</option>
                <option value="Block C">Block C (Civil & Sciences)</option>
                <option value="Administrative Block">Administrative Block</option>
                <option value="Central Library">Central Library</option>
                <option value="Seminar Hall">Seminar Hall Complex</option>
                <option value="Auditorium">Main Auditorium</option>
                <option value="Hostel Block A">Hostel Block A</option>
                <option value="Hostel Block B">Hostel Block B</option>
                <option value="Cafeteria">Food Court / Cafeteria</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="floorSelect">Floor Level <span class="required">*</span></label>
              <select id="floorSelect" name="floor" class="form-control" required>
                <option value="">-- Select Floor --</option>
                <option value="Ground Floor">Ground Floor</option>
                <option value="1st Floor">1st Floor</option>
                <option value="2nd Floor">2nd Floor</option>
                <option value="3rd Floor">3rd Floor</option>
                <option value="4th Floor">4th Floor</option>
                <option value="Terrace / Roof">Terrace / Roof</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="roomNumber">Room / Lab Number</label>
              <input type="text" id="roomNumber" name="room_number" class="form-control" placeholder="e.g. Room 302 or Lab 04">
            </div>
          </div>

          <!-- Priority Selector Radio Buttons -->
          <div class="form-group">
            <label class="form-label">Urgency & Priority Level <span class="required">*</span></label>
            <div class="priority-selector">
              <div class="priority-option priority-low">
                <input type="radio" name="priority" id="priLow" value="Low">
                <label for="priLow" class="priority-label">
                  <i class="fas fa-arrow-down"></i> LOW
                </label>
              </div>
              <div class="priority-option priority-medium">
                <input type="radio" name="priority" id="priMed" value="Medium" checked>
                <label for="priMed" class="priority-label">
                  <i class="fas fa-minus"></i> MEDIUM
                </label>
              </div>
              <div class="priority-option priority-high">
                <input type="radio" name="priority" id="priHigh" value="High">
                <label for="priHigh" class="priority-label">
                  <i class="fas fa-arrow-up"></i> HIGH
                </label>
              </div>
              <div class="priority-option priority-critical">
                <input type="radio" name="priority" id="priCrit" value="Critical">
                <label for="priCrit" class="priority-label">
                  <i class="fas fa-radiation"></i> CRITICAL
                </label>
              </div>
            </div>
          </div>

          <!-- Detailed Problem Description -->
          <div class="form-group">
            <label class="form-label" for="description">Detailed Problem Description <span class="required">*</span></label>
            <textarea id="description" name="description" class="form-control" rows="4" placeholder="Describe symptoms, noise, water leak, power cut, equipment error code, or exact nature of damage..." required></textarea>
          </div>

          <!-- Date & Contact -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="preferredDate">Preferred Technician Inspection Date</label>
              <input type="date" id="preferredDate" name="preferred_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
              <label class="form-label" for="contactNumber">Contact Phone Number <span class="required">*</span></label>
              <input type="tel" id="contactNumber" name="contact_number" class="form-control" placeholder="9876543210" value="<?= e($user['phone'] ?? '9876543220') ?>" required>
            </div>
          </div>

          <!-- Form Action Buttons -->
          <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
            <button type="reset" class="btn btn-outline">
              <i class="fas fa-rotate-left"></i> Reset Form
            </button>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-paper-plane"></i> Submit Maintenance Ticket
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

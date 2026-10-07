/**
 * FIXMATE - User Dashboard Controller
 */

const UserDashboard = {
  init() {
    Auth.checkAccess(['user', 'admin', 'technician']);
    const user = Auth.getCurrentUser();
    this.renderGreeting(user);
    this.renderUserMetrics(user);
    this.renderRecentComplaints(user);
    this.renderActiveProgressTracker(user);
  },

  renderGreeting(user) {
    const greetingEl = document.getElementById("dashboardUserGreeting");
    const subtextEl = document.getElementById("dashboardUserSubtext");
    if (!greetingEl) return;

    const hour = new Date().getHours();
    let timeGreeting = "Good Morning";
    if (hour >= 12 && hour < 17) timeGreeting = "Good Afternoon";
    else if (hour >= 17) timeGreeting = "Good Evening";

    const name = user ? user.name : "Authorized User";
    greetingEl.textContent = `${timeGreeting}, ${name}!`;
    if (subtextEl) {
      subtextEl.textContent = `Welcome to FixMate Facility Portal. Track your maintenance requests or submit new issues below.`;
    }
  },

  renderUserMetrics(user) {
    const complaints = StorageManager.getComplaints();
    let myComplaints = complaints;
    if (user && user.role === 'user') {
      myComplaints = complaints.filter(c => c.user_id === user.user_id || c.user_name === user.name);
    }

    const total = myComplaints.length;
    const pending = myComplaints.filter(c => c.status === 'SUBMITTED').length;
    const inProgress = myComplaints.filter(c => c.status === 'ASSIGNED' || c.status === 'IN PROGRESS').length;
    const resolved = myComplaints.filter(c => c.status === 'RESOLVED' || c.status === 'CLOSED').length;

    const elTotal = document.getElementById("userStatTotal");
    const elPending = document.getElementById("userStatPending");
    const elInProgress = document.getElementById("userStatInProgress");
    const elResolved = document.getElementById("userStatResolved");

    if (elTotal) elTotal.textContent = total;
    if (elPending) elPending.textContent = pending;
    if (elInProgress) elInProgress.textContent = inProgress;
    if (elResolved) elResolved.textContent = resolved;
  },

  renderRecentComplaints(user) {
    const tbody = document.getElementById("userRecentComplaintsTable");
    if (!tbody) return;

    const complaints = StorageManager.getComplaints();
    let myComplaints = complaints;
    if (user && user.role === 'user') {
      myComplaints = complaints.filter(c => c.user_id === user.user_id || c.user_name === user.name);
    }

    const recent = myComplaints.slice(0, 5);

    if (recent.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="table-empty-state" style="padding:2rem;">
              <i class="fas fa-clipboard-check"></i>
              <h3>No complaints yet</h3>
              <p>You haven't submitted any complaints. Use the button above to report a problem.</p>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = recent.map(c => `
      <tr>
        <td class="cell-id">${c.complaint_code}</td>
        <td>
          <div class="cell-title">${c.title}</div>
          <div class="cell-subtitle">${c.location}</div>
        </td>
        <td>${c.category_name}</td>
        <td>${FixMateApp.getPriorityBadge(c.priority)}</td>
        <td>${c.technician_name}</td>
        <td>${FixMateApp.getStatusBadge(c.status)}</td>
        <td>
          <a href="complaint-details.html?id=${c.complaint_code}" class="btn btn-outline btn-sm">
            <i class="fas fa-eye"></i> View
          </a>
        </td>
      </tr>
    `).join('');
  },

  renderActiveProgressTracker(user) {
    const container = document.getElementById("userActiveTrackerCard");
    if (!container) return;

    const complaints = StorageManager.getComplaints();
    let myComplaints = complaints;
    if (user && user.role === 'user') {
      myComplaints = complaints.filter(c => c.user_id === user.user_id || c.user_name === user.name);
    }

    const activeComplaint = myComplaints.find(c => c.status === 'ASSIGNED' || c.status === 'IN PROGRESS' || c.status === 'SUBMITTED');

    if (!activeComplaint) {
      container.style.display = 'none';
      return;
    }

    container.style.display = 'block';
    document.getElementById("activeTrackerCode").textContent = activeComplaint.complaint_code;
    document.getElementById("activeTrackerTitle").textContent = activeComplaint.title;
    document.getElementById("activeTrackerLocation").textContent = activeComplaint.location;
    document.getElementById("activeTrackerTech").textContent = activeComplaint.technician_name;

    const steps = ["SUBMITTED", "ASSIGNED", "IN PROGRESS", "RESOLVED", "CLOSED"];
    const currentIdx = steps.indexOf(activeComplaint.status.toUpperCase());
    const progressPct = currentIdx >= 0 ? (currentIdx / (steps.length - 1)) * 80 : 0;

    const trackerBox = document.getElementById("activeStepperBox");
    if (trackerBox) {
      trackerBox.innerHTML = `
        <div class="tracker-line">
          <div class="tracker-line-progress" style="width: ${progressPct}%;"></div>
        </div>
        ${steps.map((step, idx) => {
          let stepClass = "";
          let iconHtml = idx + 1;
          if (idx < currentIdx) {
            stepClass = "completed";
            iconHtml = '<i class="fas fa-check"></i>';
          } else if (idx === currentIdx) {
            stepClass = "active";
            iconHtml = '<i class="fas fa-dot-circle"></i>';
          }

          return `
            <div class="tracker-step ${stepClass}">
              <div class="tracker-icon">${iconHtml}</div>
              <div class="tracker-title">${step}</div>
            </div>
          `;
        }).join('')}
      `;
    }
  }
};

/**
 * FIXMATE - Admin Management Controller
 * Handles Admin Dashboard, Charts (Chart.js), Technician Assignment, All Complaints Filtering, Priority/Status Overrides
 */

const AdminController = {
  // 1. Admin Dashboard Initializer
  initDashboard() {
    Auth.checkAccess(['admin']);
    this.renderMetrics();
    this.renderCharts();
    this.renderCriticalAlerts();
    this.renderTechnicianWorkload();
    this.renderRecentComplaintsTable();
  },

  renderMetrics() {
    const complaints = StorageManager.getComplaints();
    const technicians = StorageManager.getTechnicians();

    const total = complaints.length;
    const pending = complaints.filter(c => c.status === 'SUBMITTED').length;
    const inProgress = complaints.filter(c => c.status === 'ASSIGNED' || c.status === 'IN PROGRESS').length;
    const resolved = complaints.filter(c => c.status === 'RESOLVED' || c.status === 'CLOSED').length;
    const critical = complaints.filter(c => c.priority === 'CRITICAL' && c.status !== 'CLOSED').length;
    const activeTechs = technicians.filter(t => t.status === 'active').length;

    const elTotal = document.getElementById("adminStatTotal");
    const elPending = document.getElementById("adminStatPending");
    const elInProgress = document.getElementById("adminStatInProgress");
    const elResolved = document.getElementById("adminStatResolved");
    const elCritical = document.getElementById("adminStatCritical");
    const elTechs = document.getElementById("adminStatTechs");

    if (elTotal) elTotal.textContent = total;
    if (elPending) elPending.textContent = pending;
    if (elInProgress) elInProgress.textContent = inProgress;
    if (elResolved) elResolved.textContent = resolved;
    if (elCritical) elCritical.textContent = critical;
    if (elTechs) elTechs.textContent = activeTechs;
  },

  renderCharts() {
    if (typeof Chart === 'undefined') return;

    const complaints = StorageManager.getComplaints();

    // Chart 1: Status Distribution (Doughnut)
    const statusCtx = document.getElementById("chartComplaintsStatus")?.getContext("2d");
    if (statusCtx) {
      const counts = {
        'Submitted': complaints.filter(c => c.status === 'SUBMITTED').length,
        'Assigned': complaints.filter(c => c.status === 'ASSIGNED').length,
        'In Progress': complaints.filter(c => c.status === 'IN PROGRESS').length,
        'Resolved': complaints.filter(c => c.status === 'RESOLVED').length,
        'Closed': complaints.filter(c => c.status === 'CLOSED').length
      };

      new Chart(statusCtx, {
        type: 'doughnut',
        data: {
          labels: Object.keys(counts),
          datasets: [{
            data: Object.values(counts),
            backgroundColor: ['#6366F1', '#F59E0B', '#2563EB', '#16A34A', '#64748B'],
            borderWidth: 2,
            borderColor: '#FFFFFF'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
          cutout: '70%'
        }
      });
    }

    // Chart 2: Category Breakdown (Bar)
    const categoryCtx = document.getElementById("chartComplaintsCategory")?.getContext("2d");
    if (categoryCtx) {
      const categories = StorageManager.getCategories();
      const catLabels = categories.map(c => c.name);
      const catData = categories.map(cat => complaints.filter(c => c.category_name.toLowerCase() === cat.name.toLowerCase()).length);

      new Chart(categoryCtx, {
        type: 'bar',
        data: {
          labels: catLabels,
          datasets: [{
            label: 'Complaints',
            data: catData,
            backgroundColor: '#3B82F6',
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
          }
        }
      });
    }

    // Chart 3: Priority Breakdown (Pie)
    const priorityCtx = document.getElementById("chartComplaintsPriority")?.getContext("2d");
    if (priorityCtx) {
      const pCounts = {
        'Low': complaints.filter(c => c.priority === 'LOW').length,
        'Medium': complaints.filter(c => c.priority === 'MEDIUM').length,
        'High': complaints.filter(c => c.priority === 'HIGH').length,
        'Critical': complaints.filter(c => c.priority === 'CRITICAL').length
      };

      new Chart(priorityCtx, {
        type: 'pie',
        data: {
          labels: Object.keys(pCounts),
          datasets: [{
            data: Object.values(pCounts),
            backgroundColor: ['#16A34A', '#2563EB', '#EA580C', '#DC2626'],
            borderWidth: 2,
            borderColor: '#FFFFFF'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
      });
    }

    // Chart 4: Monthly Trend (Line)
    const trendCtx = document.getElementById("chartComplaintsTrend")?.getContext("2d");
    if (trendCtx) {
      new Chart(trendCtx, {
        type: 'line',
        data: {
          labels: ['Oct 2025', 'Nov 2025', 'Dec 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026'],
          datasets: [
            {
              label: 'Reported Issues',
              data: [18, 24, 20, 29, 35, complaints.length],
              borderColor: '#2563EB',
              backgroundColor: 'rgba(37, 99, 235, 0.08)',
              fill: true,
              tension: 0.35,
              pointRadius: 4
            },
            {
              label: 'Resolved Issues',
              data: [15, 22, 19, 26, 31, complaints.filter(c => c.status === 'RESOLVED' || c.status === 'CLOSED').length],
              borderColor: '#16A34A',
              backgroundColor: 'transparent',
              borderDash: [5, 5],
              tension: 0.35,
              pointRadius: 4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
          scales: {
            y: { beginAtZero: true, grid: { color: '#F1F5F9' } },
            x: { grid: { display: false } }
          }
        }
      });
    }
  },

  renderCriticalAlerts() {
    const container = document.getElementById("adminCriticalAlertsContainer");
    if (!container) return;

    const complaints = StorageManager.getComplaints();
    const criticals = complaints.filter(c => c.priority === 'CRITICAL' && c.status !== 'CLOSED');

    if (criticals.length === 0) {
      container.innerHTML = `
        <div style="background:var(--success-bg); border:1px solid #BBF7D0; color:var(--success-text); padding:1rem; border-radius:var(--radius-md); font-size:0.88rem; display:flex; align-items:center; gap:0.5rem;">
          <i class="fas fa-check-circle"></i> <span>Zero active critical facility bottlenecks. All systems operating normally.</span>
        </div>
      `;
      return;
    }

    container.innerHTML = criticals.map(c => `
      <div class="alert-banner" style="margin-bottom:0.75rem;">
        <div class="alert-content">
          <i class="fas fa-radiation-alt fa-lg"></i>
          <div>
            <strong>${c.complaint_code}: ${c.title}</strong>
            <div style="font-size:0.78rem; opacity:0.9;">Location: ${c.location} | Tech: ${c.technician_name} | Status: ${c.status}</div>
          </div>
        </div>
        <div>
          <a href="complaint-details.html?id=${c.complaint_code}" class="btn btn-danger btn-sm" style="font-size:0.78rem;">
            Inspect Now
          </a>
        </div>
      </div>
    `).join('');
  },

  renderTechnicianWorkload() {
    const container = document.getElementById("technicianWorkloadGrid");
    if (!container) return;

    const technicians = StorageManager.getTechnicians();
    const complaints = StorageManager.getComplaints();

    container.innerHTML = technicians.slice(0, 4).map(t => {
      const activeCount = complaints.filter(c => (c.technician_name === t.name || c.technician_id === t.id) && (c.status === 'ASSIGNED' || c.status === 'IN PROGRESS')).length;
      const completedCount = complaints.filter(c => (c.technician_name === t.name || c.technician_id === t.id) && (c.status === 'RESOLVED' || c.status === 'CLOSED')).length;
      const progressPct = Math.min(100, Math.round((activeCount / 6) * 100));

      return `
        <div class="tech-card">
          <div class="tech-card-header">
            <div class="tech-avatar">${t.name.split(' ').map(n=>n[0]).join('')}</div>
            <div class="tech-info">
              <h4>${t.name}</h4>
              <div class="tech-specialty"><i class="fas fa-screwdriver-wrench"></i> ${t.specialization}</div>
            </div>
            <span class="badge ${t.availability === 'Available' ? 'badge-low' : 'badge-high'}" style="margin-left:auto; font-size:0.7rem;">${t.availability}</span>
          </div>
          <div>
            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:4px;">
              <span>Active Workload</span>
              <strong>${activeCount} Active / ${completedCount} Completed</strong>
            </div>
            <div class="tech-progress-bar">
              <div class="tech-progress-fill" style="width:${progressPct}%; background:${progressPct > 70 ? 'var(--danger)' : 'var(--primary)'};"></div>
            </div>
          </div>
        </div>
      `;
    }).join('');
  },

  renderRecentComplaintsTable() {
    const tbody = document.getElementById("adminRecentComplaintsTable");
    if (!tbody) return;

    const complaints = StorageManager.getComplaints().slice(0, 6);

    tbody.innerHTML = complaints.map(c => `
      <tr>
        <td class="cell-id">${c.complaint_code}</td>
        <td>
          <div class="cell-title">${c.title}</div>
          <div class="cell-subtitle">${c.user_name} (${c.user_dept || 'Campus'})</div>
        </td>
        <td>${c.category_name}</td>
        <td>${FixMateApp.getPriorityBadge(c.priority)}</td>
        <td>${c.technician_name}</td>
        <td>${FixMateApp.getStatusBadge(c.status)}</td>
        <td>
          <a href="complaint-details.html?id=${c.complaint_code}" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-right"></i>
          </a>
        </td>
      </tr>
    `).join('');
  },

  // 2. Admin All Complaints Page Initializer
  initAdminComplaints() {
    Auth.checkAccess(['admin']);
    this.populateAdminFilters();
    this.renderAdminComplaintsTable();

    // Event listeners
    const searchInput = document.getElementById("adminComplaintSearch");
    const statusFilter = document.getElementById("adminStatusFilter");
    const categoryFilter = document.getElementById("adminCategoryFilter");
    const priorityFilter = document.getElementById("adminPriorityFilter");
    const techFilter = document.getElementById("adminTechFilter");

    const handler = () => this.renderAdminComplaintsTable();
    if (searchInput) searchInput.addEventListener("input", handler);
    if (statusFilter) statusFilter.addEventListener("change", handler);
    if (categoryFilter) categoryFilter.addEventListener("change", handler);
    if (priorityFilter) priorityFilter.addEventListener("change", handler);
    if (techFilter) techFilter.addEventListener("change", handler);
  },

  populateAdminFilters() {
    const catSelect = document.getElementById("adminCategoryFilter");
    const techSelect = document.getElementById("adminTechFilter");

    if (catSelect) {
      const categories = StorageManager.getCategories();
      catSelect.innerHTML = `<option value="">All Categories</option>` +
        categories.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
    }

    if (techSelect) {
      const techs = StorageManager.getTechnicians();
      techSelect.innerHTML = `<option value="">All Technicians</option>` +
        techs.map(t => `<option value="${t.name}">${t.name}</option>`).join('');
    }
  },

  renderAdminComplaintsTable() {
    const tbody = document.getElementById("adminComplaintsTableBody");
    const countBadge = document.getElementById("adminComplaintsCountBadge");
    if (!tbody) return;

    const complaints = StorageManager.getComplaints();

    const searchVal = (document.getElementById("adminComplaintSearch")?.value || "").toLowerCase().trim();
    const statusVal = document.getElementById("adminStatusFilter")?.value || "";
    const catVal = document.getElementById("adminCategoryFilter")?.value || "";
    const priorityVal = document.getElementById("adminPriorityFilter")?.value || "";
    const techVal = document.getElementById("adminTechFilter")?.value || "";

    const filtered = complaints.filter(c => {
      const matchSearch = !searchVal ||
        c.complaint_code.toLowerCase().includes(searchVal) ||
        c.title.toLowerCase().includes(searchVal) ||
        c.user_name.toLowerCase().includes(searchVal) ||
        c.location.toLowerCase().includes(searchVal);

      const matchStatus = !statusVal || c.status.toUpperCase() === statusVal.toUpperCase();
      const matchCat = !catVal || c.category_name.toLowerCase() === catVal.toLowerCase();
      const matchPriority = !priorityVal || c.priority.toUpperCase() === priorityVal.toUpperCase();
      const matchTech = !techVal || c.technician_name === techVal;

      return matchSearch && matchStatus && matchCat && matchPriority && matchTech;
    });

    if (countBadge) countBadge.textContent = `${filtered.length} Total Complaints`;

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="9">
            <div class="table-empty-state">
              <i class="fas fa-filter-circle-xmark"></i>
              <h3>No complaints found</h3>
              <p>Try resetting your filter parameters to view complaints.</p>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = filtered.map(c => `
      <tr>
        <td class="cell-id">${c.complaint_code}</td>
        <td>
          <div style="font-weight:600; color:var(--text-main);">${c.user_name}</div>
          <div class="cell-subtitle">${c.user_dept || 'Campus'}</div>
        </td>
        <td>
          <div class="cell-title" title="${c.title}">${c.title}</div>
          <div class="cell-subtitle"><i class="fas fa-location-dot"></i> ${c.location}</div>
        </td>
        <td>${c.category_name}</td>
        <td>${FixMateApp.getPriorityBadge(c.priority)}</td>
        <td>
          <span style="font-weight:500;">${c.technician_name}</span>
        </td>
        <td>${FixMateApp.getStatusBadge(c.status)}</td>
        <td><span style="font-size:0.8rem; color:var(--text-muted);">${c.submitted_date.split(' ')[0]}</span></td>
        <td>
          <div class="cell-actions">
            <button onclick="AdminController.openAssignModal('${c.complaint_code}')" class="btn btn-outline btn-sm" title="Assign / Reassign Technician">
              <i class="fas fa-user-plus text-primary"></i> Assign
            </button>
            <button onclick="AdminController.openStatusModal('${c.complaint_code}')" class="btn btn-outline btn-sm" title="Change Status / Priority">
              <i class="fas fa-pen-to-square"></i> Edit
            </button>
            <button onclick="AdminController.deleteComplaint('${c.complaint_code}')" class="btn btn-outline btn-sm text-danger" title="Delete Complaint">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  },

  // 3. Assignment Modal
  openAssignModal(complaintCode) {
    const complaint = StorageManager.getComplaintById(complaintCode);
    if (!complaint) return;

    document.getElementById("assignModalComplaintCode").textContent = complaint.complaint_code;
    document.getElementById("assignModalTitle").textContent = complaint.title;
    document.getElementById("assignModalLocation").textContent = complaint.location;
    document.getElementById("assignModalPriority").innerHTML = FixMateApp.getPriorityBadge(complaint.priority);
    document.getElementById("assignComplaintIdHidden").value = complaint.complaint_code;

    const select = document.getElementById("assignTechnicianSelect");
    if (select) {
      const techs = StorageManager.getTechnicians();
      select.innerHTML = `<option value="">-- Choose Qualified Technician --</option>` +
        techs.map(t => `<option value="${t.id}" data-name="${t.name}" ${complaint.technician_id === t.id ? 'selected' : ''}>${t.name} (${t.specialization} - ${t.availability})</option>`).join('');
    }

    const modal = document.getElementById("assignTechnicianModal");
    if (modal) modal.classList.add("show");
  },

  closeAssignModal() {
    const modal = document.getElementById("assignTechnicianModal");
    if (modal) modal.classList.remove("show");
  },

  submitTechnicianAssignment() {
    const code = document.getElementById("assignComplaintIdHidden").value;
    const select = document.getElementById("assignTechnicianSelect");
    const techId = select.value;
    const techName = select.options[select.selectedIndex]?.getAttribute("data-name");

    if (!techId) {
      FixMateApp.showToast("Selection Required", "Please select a technician.", "warning");
      return;
    }

    const now = new Date();
    const dateStr = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const complaint = StorageManager.getComplaintById(code);
    const newStatus = complaint.status === 'SUBMITTED' ? 'ASSIGNED' : complaint.status;

    StorageManager.updateComplaint(code, {
      technician_id: Number(techId),
      technician_name: techName,
      status: newStatus,
      assigned_date: dateStr
    });

    StorageManager.addTimelineStep(code, "ASSIGNED", `Assigned to technician ${techName} by Facility Admin`);

    // Notify User and Technician
    StorageManager.addNotification({
      user_id: complaint.user_id,
      complaint_code: complaint.complaint_code,
      message: `Your complaint ${complaint.complaint_code} has been assigned to ${techName}.`,
      type: "info"
    });

    StorageManager.addNotification({
      user_id: Number(techId),
      complaint_code: complaint.complaint_code,
      message: `New task assigned: ${complaint.title} (${complaint.location}).`,
      type: "warning"
    });

    this.closeAssignModal();
    FixMateApp.showToast("Assignment Complete", `Complaint ${code} assigned to ${techName}.`, "success");
    this.renderAdminComplaintsTable();
  },

  // 4. Status & Priority Override Modal
  openStatusModal(complaintCode) {
    const complaint = StorageManager.getComplaintById(complaintCode);
    if (!complaint) return;

    document.getElementById("editModalCode").textContent = complaint.complaint_code;
    document.getElementById("editComplaintIdHidden").value = complaint.complaint_code;
    document.getElementById("editStatusSelect").value = complaint.status;
    document.getElementById("editPrioritySelect").value = complaint.priority;

    const modal = document.getElementById("editStatusPriorityModal");
    if (modal) modal.classList.add("show");
  },

  closeStatusModal() {
    const modal = document.getElementById("editStatusPriorityModal");
    if (modal) modal.classList.remove("show");
  },

  submitStatusPriorityEdit() {
    const code = document.getElementById("editComplaintIdHidden").value;
    const newStatus = document.getElementById("editStatusSelect").value;
    const newPriority = document.getElementById("editPrioritySelect").value;

    StorageManager.updateComplaint(code, {
      status: newStatus,
      priority: newPriority
    });

    StorageManager.addTimelineStep(code, newStatus, `Administrator updated ticket status to ${newStatus} & priority to ${newPriority}`);

    this.closeStatusModal();
    FixMateApp.showToast("Updated", `Complaint ${code} parameters updated.`, "success");
    this.renderAdminComplaintsTable();
  },

  deleteComplaint(complaintCode) {
    if (confirm(`Are you sure you want to permanently delete complaint ${complaintCode}?`)) {
      StorageManager.deleteComplaint(complaintCode);
      FixMateApp.showToast("Deleted", `Complaint ${complaintCode} was removed.`, "danger");
      this.renderAdminComplaintsTable();
    }
  }
};

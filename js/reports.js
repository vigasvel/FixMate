/**
 * FIXMATE - Reports & Analytics Controller
 * Handles Complaint Summaries, Category/Priority Analysis, Resolution Metrics, Print & CSV Export
 */

const ReportsController = {
  init() {
    Auth.checkAccess(['admin', 'technician']);
    this.initDateFilters();
    this.generateReports();
  },

  initDateFilters() {
    const fromInput = document.getElementById("reportFromDate");
    const toInput = document.getElementById("reportToDate");
    const catSelect = document.getElementById("reportCategorySelect");

    if (fromInput) fromInput.value = "2026-01-01";
    if (toInput) toInput.value = new Date().toISOString().split('T')[0];

    if (catSelect) {
      const categories = StorageManager.getCategories();
      catSelect.innerHTML = `<option value="">All Categories</option>` +
        categories.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
    }
  },

  generateReports() {
    const complaints = StorageManager.getComplaints();
    const technicians = StorageManager.getTechnicians();
    const feedback = StorageManager.getFeedback();

    const catFilter = document.getElementById("reportCategorySelect")?.value || "";
    const priorityFilter = document.getElementById("reportPrioritySelect")?.value || "";
    const statusFilter = document.getElementById("reportStatusSelect")?.value || "";

    const filtered = complaints.filter(c => {
      const matchCat = !catFilter || c.category_name === catFilter;
      const matchPri = !priorityFilter || c.priority === priorityFilter;
      const matchStat = !statusFilter || c.status === statusFilter;
      return matchCat && matchPri && matchStat;
    });

    // Summary Metrics
    const total = filtered.length;
    const resolved = filtered.filter(c => c.status === 'RESOLVED' || c.status === 'CLOSED').length;
    const pending = filtered.filter(c => c.status === 'SUBMITTED' || c.status === 'ASSIGNED').length;
    const inProgress = filtered.filter(c => c.status === 'IN PROGRESS').length;
    const resolutionRate = total > 0 ? Math.round((resolved / total) * 100) : 0;

    const elTotal = document.getElementById("repTotalComplaints");
    const elResolved = document.getElementById("repResolvedComplaints");
    const elPending = document.getElementById("repPendingComplaints");
    const elRate = document.getElementById("repResolutionRate");

    if (elTotal) elTotal.textContent = total;
    if (elResolved) elResolved.textContent = resolved;
    if (elPending) elPending.textContent = pending + inProgress;
    if (elRate) elRate.textContent = `${resolutionRate}%`;

    // Render Detailed Table
    this.renderReportsTable(filtered);
    this.renderTechnicianPerformanceTable(technicians, complaints);
  },

  renderReportsTable(complaints) {
    const tbody = document.getElementById("reportDetailTableBody");
    const countBadge = document.getElementById("reportRowsCount");
    if (!tbody) return;

    if (countBadge) countBadge.textContent = `${complaints.length} Records`;

    if (complaints.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8">
            <div class="table-empty-state">
              <i class="fas fa-file-excel"></i>
              <h3>No data found for this report</h3>
              <p>Adjust your filter settings to generate statistical records.</p>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = complaints.map(c => `
      <tr>
        <td class="cell-id">${c.complaint_code}</td>
        <td>
          <div style="font-weight:600;">${c.title}</div>
          <div class="cell-subtitle">${c.location}</div>
        </td>
        <td>${c.category_name}</td>
        <td>${c.user_name} (${c.user_dept || 'Campus'})</td>
        <td>${FixMateApp.getPriorityBadge(c.priority)}</td>
        <td>${c.technician_name}</td>
        <td>${FixMateApp.getStatusBadge(c.status)}</td>
        <td><span style="font-size:0.8rem; color:var(--text-muted);">${c.submitted_date.split(' ')[0]}</span></td>
      </tr>
    `).join('');
  },

  renderTechnicianPerformanceTable(technicians, complaints) {
    const tbody = document.getElementById("reportTechPerfTable");
    if (!tbody) return;

    tbody.innerHTML = technicians.map(t => {
      const assigned = complaints.filter(c => c.technician_name === t.name || c.technician_id === t.id);
      const done = assigned.filter(c => c.status === 'RESOLVED' || c.status === 'CLOSED');
      const rate = assigned.length > 0 ? Math.round((done.length / assigned.length) * 100) : 100;

      return `
        <tr>
          <td>
            <strong>${t.name}</strong>
            <div class="cell-subtitle">${t.specialization}</div>
          </td>
          <td>${assigned.length}</td>
          <td>${done.length}</td>
          <td>${assigned.length - done.length}</td>
          <td>
            <div style="display:flex; align-items:center; gap:0.5rem;">
              <div class="tech-progress-bar" style="width:100px;">
                <div class="tech-progress-fill" style="width:${rate}%; background:${rate >= 80 ? 'var(--success)' : 'var(--warning)'};"></div>
              </div>
              <span style="font-size:0.8rem; font-weight:600;">${rate}%</span>
            </div>
          </td>
          <td>
            <span style="color:#F59E0B; font-weight:600;"><i class="fas fa-star"></i> ${t.rating ? t.rating.toFixed(1) : '5.0'}</span>
          </td>
        </tr>
      `;
    }).join('');
  },

  // Export Table Data as CSV File
  exportToCSV() {
    const complaints = StorageManager.getComplaints();
    if (complaints.length === 0) {
      FixMateApp.showToast("Export Notice", "No complaint records available to export.", "warning");
      return;
    }

    const headers = ["Complaint Code", "Title", "Category", "Reported By", "Department", "Location", "Priority", "Technician", "Status", "Reported Date", "Resolved Date", "Resolution Notes"];

    const rows = complaints.map(c => [
      `"${c.complaint_code}"`,
      `"${c.title.replace(/"/g, '""')}"`,
      `"${c.category_name}"`,
      `"${c.user_name}"`,
      `"${c.user_dept || ''}"`,
      `"${c.location.replace(/"/g, '""')}"`,
      `"${c.priority}"`,
      `"${c.technician_name}"`,
      `"${c.status}"`,
      `"${c.submitted_date}"`,
      `"${c.resolved_date || 'N/A'}"`,
      `"${(c.resolution_notes || '').replace(/"/g, '""')}"`
    ]);

    const csvContent = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `FixMate_Complaints_Report_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    FixMateApp.showToast("CSV Downloaded", "FixMate report exported successfully.", "success");
  },

  printReport() {
    window.print();
  }
};

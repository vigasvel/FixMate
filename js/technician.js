/**
 * FIXMATE - Technician Controller
 * Handles Technician Dashboard, Task Filters, Task Acceptance, Work Progress, and Resolution Notes
 */

const TechnicianController = {
  // 1. Technician Dashboard Initializer
  initDashboard() {
    Auth.checkAccess(['technician', 'admin']);
    const user = Auth.getCurrentUser();
    this.renderMetrics(user);
    this.renderTasksTable(user);

    // Filter listeners
    const searchInput = document.getElementById("techTaskSearch");
    const statusFilter = document.getElementById("techStatusFilter");
    const priorityFilter = document.getElementById("techPriorityFilter");

    const filterHandler = () => this.renderTasksTable(user);
    if (searchInput) searchInput.addEventListener("input", filterHandler);
    if (statusFilter) statusFilter.addEventListener("change", filterHandler);
    if (priorityFilter) priorityFilter.addEventListener("change", filterHandler);

    // Check URL query params for quick filters
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get("filter");
    if (filterParam && statusFilter) {
      if (filterParam === 'assigned') statusFilter.value = 'ASSIGNED';
      else if (filterParam === 'in_progress') statusFilter.value = 'IN PROGRESS';
      else if (filterParam === 'completed') statusFilter.value = 'RESOLVED';
      this.renderTasksTable(user);
    }
  },

  renderMetrics(user) {
    const complaints = StorageManager.getComplaints();
    const techName = user ? user.name : "Arun Kumar";
    const myTasks = complaints.filter(c => c.technician_name === techName || c.technician_id === (user ? user.user_id : 2));

    const total = myTasks.length;
    const pending = myTasks.filter(c => c.status === 'ASSIGNED').length;
    const inProgress = myTasks.filter(c => c.status === 'IN PROGRESS').length;
    const completed = myTasks.filter(c => c.status === 'RESOLVED' || c.status === 'CLOSED').length;
    const critical = myTasks.filter(c => c.priority === 'CRITICAL' && c.status !== 'CLOSED').length;

    const elTotal = document.getElementById("metricTechTotal");
    const elPending = document.getElementById("metricTechPending");
    const elProgress = document.getElementById("metricTechProgress");
    const elCompleted = document.getElementById("metricTechCompleted");
    const elCritical = document.getElementById("metricTechCritical");

    if (elTotal) elTotal.textContent = total;
    if (elPending) elPending.textContent = pending;
    if (elProgress) elProgress.textContent = inProgress;
    if (elCompleted) elCompleted.textContent = completed;
    if (elCritical) elCritical.textContent = critical;
  },

  renderTasksTable(user) {
    const tbody = document.getElementById("technicianTasksTableBody");
    const countEl = document.getElementById("techTasksCountBadge");
    if (!tbody) return;

    const complaints = StorageManager.getComplaints();
    const techName = user ? user.name : "Arun Kumar";
    let myTasks = complaints.filter(c => c.technician_name === techName || c.technician_id === (user ? user.user_id : 2));

    // If no assigned tasks found for this specific technician account, show all assigned for demo purposes
    if (myTasks.length === 0 && user && user.role === 'technician') {
      myTasks = complaints.filter(c => c.technician_name !== 'Unassigned');
    }

    const searchVal = (document.getElementById("techTaskSearch")?.value || "").toLowerCase().trim();
    const statusVal = document.getElementById("techStatusFilter")?.value || "";
    const priorityVal = document.getElementById("techPriorityFilter")?.value || "";

    const filtered = myTasks.filter(c => {
      const matchSearch = !searchVal ||
        c.complaint_code.toLowerCase().includes(searchVal) ||
        c.title.toLowerCase().includes(searchVal) ||
        c.location.toLowerCase().includes(searchVal);

      const matchStatus = !statusVal || (statusVal === 'RESOLVED' ? (c.status === 'RESOLVED' || c.status === 'CLOSED') : c.status === statusVal);
      const matchPriority = !priorityVal || c.priority === priorityVal;

      return matchSearch && matchStatus && matchPriority;
    });

    if (countEl) countEl.textContent = `${filtered.length} Tasks`;

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="table-empty-state">
              <i class="fas fa-check-double"></i>
              <h3>No tasks in this queue</h3>
              <p>You have no pending maintenance requests matching these filters.</p>
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
          <div class="cell-title" title="${c.title}">${c.title}</div>
          <div class="cell-subtitle"><i class="fas fa-layer-group"></i> ${c.category_name}</div>
        </td>
        <td>
          <div><i class="fas fa-map-marker-alt text-muted"></i> ${c.location}</div>
          <div class="cell-subtitle">Requested by: ${c.user_name}</div>
        </td>
        <td>${FixMateApp.getPriorityBadge(c.priority)}</td>
        <td>${FixMateApp.getStatusBadge(c.status)}</td>
        <td><span style="font-size:0.82rem; color:var(--text-muted);">${c.submitted_date.split(' ')[0]}</span></td>
        <td>
          <div class="cell-actions">
            <a href="technician-task.html?id=${c.complaint_code}" class="btn btn-primary btn-sm" title="Open Task Workspace">
              <i class="fas fa-wrench"></i> Manage
            </a>
          </div>
        </td>
      </tr>
    `).join('');
  },

  // 2. Technician Task Detail Page Initializer
  initTaskPage() {
    Auth.checkAccess(['technician', 'admin']);
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get("id") || "FM-2026-0001";
    const complaint = StorageManager.getComplaintById(code);

    if (!complaint) {
      FixMateApp.showToast("Task Not Found", `Complaint ${code} does not exist.`, "danger");
      setTimeout(() => window.location.href = "technician-dashboard.html", 1500);
      return;
    }

    this.renderTaskDetailView(complaint);
    this.initActionButtons(complaint);
  },

  renderTaskDetailView(c) {
    document.title = `Task ${c.complaint_code} - FixMate`;

    document.getElementById("taskComplaintCode").textContent = c.complaint_code;
    document.getElementById("taskTitle").textContent = c.title;
    document.getElementById("taskStatusBadge").innerHTML = FixMateApp.getStatusBadge(c.status);
    document.getElementById("taskPriorityBadge").innerHTML = FixMateApp.getPriorityBadge(c.priority);

    document.getElementById("taskCategory").textContent = c.category_name;
    document.getElementById("taskLocation").textContent = `${c.building} - ${c.floor} (${c.room_number || 'Room N/A'})`;
    document.getElementById("taskCustomer").textContent = `${c.user_name} (${c.user_dept || 'Department'})`;
    document.getElementById("taskContact").textContent = c.contact_number || "9876543200";
    document.getElementById("taskAssignedDate").textContent = c.assigned_date || c.submitted_date;
    document.getElementById("taskPreferredDate").textContent = c.preferred_date || "Immediate";
    document.getElementById("taskDescription").textContent = c.description;

    // Update Action Buttons States based on current status
    const btnAccept = document.getElementById("btnAcceptTask");
    const btnStart = document.getElementById("btnStartWork");
    const btnResolveModal = document.getElementById("btnOpenResolveModal");

    if (c.status === 'SUBMITTED') {
      if (btnAccept) btnAccept.style.display = 'inline-flex';
      if (btnStart) btnStart.style.display = 'none';
      if (btnResolveModal) btnResolveModal.style.display = 'none';
    } else if (c.status === 'ASSIGNED') {
      if (btnAccept) btnAccept.style.display = 'none';
      if (btnStart) btnStart.style.display = 'inline-flex';
      if (btnResolveModal) btnResolveModal.style.display = 'none';
    } else if (c.status === 'IN PROGRESS') {
      if (btnAccept) btnAccept.style.display = 'none';
      if (btnStart) btnStart.style.display = 'none';
      if (btnResolveModal) btnResolveModal.style.display = 'inline-flex';
    } else {
      if (btnAccept) btnAccept.style.display = 'none';
      if (btnStart) btnStart.style.display = 'none';
      if (btnResolveModal) btnResolveModal.style.display = 'none';
    }

    // Resolution Notes Display if already resolved
    const resCard = document.getElementById("completedResolutionCard");
    if (resCard) {
      if (c.status === 'RESOLVED' || c.status === 'CLOSED') {
        resCard.style.display = 'block';
        document.getElementById("recordedWorkDone").textContent = c.resolution_notes || "Completed.";
        document.getElementById("recordedPartsUsed").textContent = c.parts_used || "None";
      } else {
        resCard.style.display = 'none';
      }
    }
  },

  initActionButtons(complaint) {
    const btnAccept = document.getElementById("btnAcceptTask");
    const btnStart = document.getElementById("btnStartWork");
    const user = Auth.getCurrentUser();
    const techName = user ? user.name : "Arun Kumar";

    if (btnAccept) {
      btnAccept.onclick = () => {
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        StorageManager.updateComplaint(complaint.id, {
          status: "ASSIGNED",
          technician_id: user ? user.user_id : 2,
          technician_name: techName,
          assigned_date: dateStr
        });
        StorageManager.addTimelineStep(complaint.id, "ASSIGNED", `Task accepted by technician ${techName}`);
        StorageManager.addNotification({
          user_id: complaint.user_id,
          complaint_code: complaint.complaint_code,
          message: `Technician ${techName} accepted your complaint ${complaint.complaint_code}.`,
          type: "info"
        });
        FixMateApp.showToast("Task Accepted", `You have accepted task ${complaint.complaint_code}`, "success");
        setTimeout(() => window.location.reload(), 600);
      };
    }

    if (btnStart) {
      btnStart.onclick = () => {
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        StorageManager.updateComplaint(complaint.id, {
          status: "IN PROGRESS",
          started_date: dateStr
        });
        StorageManager.addTimelineStep(complaint.id, "IN PROGRESS", `Maintenance work commenced on-site by ${techName}`);
        StorageManager.addNotification({
          user_id: complaint.user_id,
          complaint_code: complaint.complaint_code,
          message: `Technician ${techName} has started working on complaint ${complaint.complaint_code}.`,
          type: "info"
        });
        FixMateApp.showToast("Work Started", "Complaint status updated to IN PROGRESS.", "info");
        setTimeout(() => window.location.reload(), 600);
      };
    }

    // Resolution Form Submission
    const resolveForm = document.getElementById("resolveTaskForm");
    if (resolveForm) {
      resolveForm.onsubmit = (e) => {
        e.preventDefault();
        const workDone = resolveForm.workPerformed.value.trim();
        const partsUsed = resolveForm.partsUsed.value.trim();
        const addNotes = resolveForm.additionalNotes.value.trim();

        if (!workDone) {
          FixMateApp.showToast("Required", "Please describe the work performed.", "warning");
          return;
        }

        const now = new Date();
        const dateStr = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const fullNotes = `${workDone}${addNotes ? ' | Note: ' + addNotes : ''}`;

        StorageManager.updateComplaint(complaint.id, {
          status: "RESOLVED",
          resolved_date: dateStr,
          resolution_notes: fullNotes,
          parts_used: partsUsed || "None"
        });

        StorageManager.addTimelineStep(complaint.id, "RESOLVED", `Issue resolved by ${techName}: "${workDone}"`);
        StorageManager.addNotification({
          user_id: complaint.user_id,
          complaint_code: complaint.complaint_code,
          message: `Your complaint ${complaint.complaint_code} was resolved. Please inspect and rate the service.`,
          type: "success"
        });

        const modal = document.getElementById("resolveTaskModal");
        if (modal) modal.classList.remove("show");

        FixMateApp.showToast("Task Resolved", `Task ${complaint.complaint_code} marked as RESOLVED!`, "success");
        setTimeout(() => window.location.reload(), 600);
      };
    }
  },

  openResolveModal() {
    const modal = document.getElementById("resolveTaskModal");
    if (modal) modal.classList.add("show");
  },

  closeResolveModal() {
    const modal = document.getElementById("resolveTaskModal");
    if (modal) modal.classList.remove("show");
  }
};

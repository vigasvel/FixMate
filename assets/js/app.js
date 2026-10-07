/**
 * FIXMATE - Global Application Utilities & UI Engine
 */

const FixMateApp = {
  initLayout(activeNavId = "") {
    const user = Auth.getCurrentUser();
    this.renderSidebar(user, activeNavId);
    this.renderTopHeader(user);
    this.initNotificationsDropdown(user);
    this.initTheme();
    this.initMobileSidebar();
  },

  renderSidebar(user, activeNavId) {
    const sidebarEl = document.getElementById("mainSidebar");
    if (!sidebarEl) return;

    const role = user ? user.role : 'user';
    const userName = user ? user.name : 'Guest User';
    const userDept = user ? (user.department || user.role) : 'Visitor';
    const initials = userName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

    let navItemsHtml = '';

    if (role === 'user') {
      navItemsHtml = `
        <div class="nav-section-title">User Menu</div>
        <a href="dashboard.html" class="nav-item ${activeNavId === 'dashboard' ? 'active' : ''}">
          <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>
        <a href="report-problem.html" class="nav-item ${activeNavId === 'report-problem' ? 'active' : ''}">
          <i class="fas fa-plus-circle"></i> <span>Report Problem</span>
        </a>
        <a href="my-complaints.html" class="nav-item ${activeNavId === 'my-complaints' ? 'active' : ''}">
          <i class="fas fa-list-check"></i> <span>My Complaints</span>
        </a>
        <a href="notifications.html" class="nav-item ${activeNavId === 'notifications' ? 'active' : ''}">
          <i class="fas fa-bell"></i> <span>Notifications</span>
          <span class="nav-badge" id="sidebarNotifBadge">0</span>
        </a>
        
        <div class="nav-section-title">Account</div>
        <a href="profile.html" class="nav-item ${activeNavId === 'profile' ? 'active' : ''}">
          <i class="fas fa-user-circle"></i> <span>My Profile</span>
        </a>
        <a href="settings.html" class="nav-item ${activeNavId === 'settings' ? 'active' : ''}">
          <i class="fas fa-cog"></i> <span>Settings</span>
        </a>
        <a href="about.html" class="nav-item ${activeNavId === 'about' ? 'active' : ''}">
          <i class="fas fa-info-circle"></i> <span>About Project</span>
        </a>
      `;
    } else if (role === 'technician') {
      navItemsHtml = `
        <div class="nav-section-title">Technician Center</div>
        <a href="technician-dashboard.html" class="nav-item ${activeNavId === 'tech-dashboard' ? 'active' : ''}">
          <i class="fas fa-chart-line"></i> <span>Task Dashboard</span>
        </a>
        <a href="technician-dashboard.html?filter=assigned" class="nav-item ${activeNavId === 'assigned-tasks' ? 'active' : ''}">
          <i class="fas fa-tasks"></i> <span>Assigned Tasks</span>
        </a>
        <a href="technician-dashboard.html?filter=in_progress" class="nav-item ${activeNavId === 'in-progress-tasks' ? 'active' : ''}">
          <i class="fas fa-spinner"></i> <span>In Progress</span>
        </a>
        <a href="technician-dashboard.html?filter=completed" class="nav-item ${activeNavId === 'completed-tasks' ? 'active' : ''}">
          <i class="fas fa-check-circle"></i> <span>Completed Tasks</span>
        </a>
        <a href="notifications.html" class="nav-item ${activeNavId === 'notifications' ? 'active' : ''}">
          <i class="fas fa-bell"></i> <span>Notifications</span>
          <span class="nav-badge" id="sidebarNotifBadge">0</span>
        </a>

        <div class="nav-section-title">Account</div>
        <a href="profile.html" class="nav-item ${activeNavId === 'profile' ? 'active' : ''}">
          <i class="fas fa-user-circle"></i> <span>Technician Profile</span>
        </a>
        <a href="settings.html" class="nav-item ${activeNavId === 'settings' ? 'active' : ''}">
          <i class="fas fa-cog"></i> <span>Settings</span>
        </a>
        <a href="about.html" class="nav-item ${activeNavId === 'about' ? 'active' : ''}">
          <i class="fas fa-info-circle"></i> <span>About Project</span>
        </a>
      `;
    } else if (role === 'admin') {
      navItemsHtml = `
        <div class="nav-section-title">Administration</div>
        <a href="admin-dashboard.html" class="nav-item ${activeNavId === 'admin-dashboard' ? 'active' : ''}">
          <i class="fas fa-chart-pie"></i> <span>Admin Overview</span>
        </a>
        <a href="admin-complaints.html" class="nav-item ${activeNavId === 'admin-complaints' ? 'active' : ''}">
          <i class="fas fa-clipboard-list"></i> <span>All Complaints</span>
        </a>
        <a href="users.html" class="nav-item ${activeNavId === 'users' ? 'active' : ''}">
          <i class="fas fa-users"></i> <span>User Management</span>
        </a>
        <a href="technicians.html" class="nav-item ${activeNavId === 'technicians' ? 'active' : ''}">
          <i class="fas fa-user-gear"></i> <span>Technicians</span>
        </a>
        <a href="categories.html" class="nav-item ${activeNavId === 'categories' ? 'active' : ''}">
          <i class="fas fa-tags"></i> <span>Categories</span>
        </a>
        <a href="reports.html" class="nav-item ${activeNavId === 'reports' ? 'active' : ''}">
          <i class="fas fa-file-invoice"></i> <span>Reports & Analytics</span>
        </a>
        <a href="notifications.html" class="nav-item ${activeNavId === 'notifications' ? 'active' : ''}">
          <i class="fas fa-bell"></i> <span>System Alerts</span>
          <span class="nav-badge" id="sidebarNotifBadge">0</span>
        </a>

        <div class="nav-section-title">System</div>
        <a href="profile.html" class="nav-item ${activeNavId === 'profile' ? 'active' : ''}">
          <i class="fas fa-user-shield"></i> <span>Admin Profile</span>
        </a>
        <a href="settings.html" class="nav-item ${activeNavId === 'settings' ? 'active' : ''}">
          <i class="fas fa-sliders"></i> <span>Settings</span>
        </a>
        <a href="about.html" class="nav-item ${activeNavId === 'about' ? 'active' : ''}">
          <i class="fas fa-info-circle"></i> <span>About Project</span>
        </a>
      `;
    }

    sidebarEl.innerHTML = `
      <div class="sidebar-header">
        <a href="index.html" class="brand-logo">
          <div class="brand-icon"><i class="fas fa-wrench"></i></div>
          <span>FixMate</span>
        </a>
        <span class="brand-badge">${role}</span>
      </div>

      <div class="sidebar-user">
        <div class="user-avatar-sm">${initials}</div>
        <div class="user-info-text">
          <div class="user-info-name">${userName}</div>
          <div class="user-info-role">
            <span class="role-pill ${role}">${role}</span>
            <span>${user ? user.employee_id : ''}</span>
          </div>
        </div>
      </div>

      <nav class="sidebar-nav">
        ${navItemsHtml}
      </nav>

      <div class="sidebar-footer">
        <button onclick="Auth.logout()" class="btn btn-outline btn-sm w-100" style="width:100%; border-color:rgba(255,255,255,0.2); color:#F8FAFC;">
          <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
        </button>
      </div>
    `;

    this.updateNotificationCounters(user);
  },

  renderTopHeader(user) {
    const headerEl = document.getElementById("mainHeader");
    if (!headerEl) return;

    const pageTitle = headerEl.getAttribute("data-page-title") || "FixMate Maintenance System";

    headerEl.innerHTML = `
      <div class="header-left">
        <button class="menu-toggle" id="mobileMenuBtn" aria-label="Toggle Sidebar">
          <i class="fas fa-bars"></i>
        </button>
        <h2 class="page-title">${pageTitle}</h2>
      </div>

      <div class="header-right">
        <!-- Quick Role Switcher / Demo Helper -->
        <div class="quick-demo-select" style="display:inline-block;">
          <select id="quickRoleSwitcher" class="form-control" style="padding:0.4rem 0.6rem; font-size:0.8rem; background:var(--bg-main); border:1px solid var(--border-color); border-radius:6px;" onchange="FixMateApp.switchDemoRole(this.value)">
            <option value="user" ${user && user.role === 'user' ? 'selected' : ''}>👤 User (Kumaresan K)</option>
            <option value="technician" ${user && user.role === 'technician' ? 'selected' : ''}>🔧 Tech (Arun Kumar)</option>
            <option value="admin" ${user && user.role === 'admin' ? 'selected' : ''}>🛡️ Admin (System)</option>
          </select>
        </div>

        <!-- Notification Bell & Dropdown -->
        <div style="position:relative;">
          <button class="header-action-btn" id="notifBellBtn" title="Notifications">
            <i class="far fa-bell"></i>
            <span class="notif-badge-count" id="headerNotifBadge" style="display:none;">0</span>
          </button>
          
          <div class="dropdown-menu" id="notifDropdown">
            <div class="dropdown-header">
              <span>Notifications</span>
              <button onclick="FixMateApp.markAllAsRead()" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:0.2rem 0.5rem;">Mark All Read</button>
            </div>
            <div class="dropdown-list" id="notifListContainer">
              <!-- Rendered via JS -->
            </div>
            <div class="dropdown-footer">
              <a href="notifications.html" style="font-weight:600;">View All Notifications →</a>
            </div>
          </div>
        </div>

        <!-- Profile Quick Link -->
        <a href="profile.html" class="header-action-btn" title="My Profile">
          <i class="far fa-user"></i>
        </a>
      </div>
    `;
  },

  switchDemoRole(targetRole) {
    const demo = DEMO_ACCOUNTS[targetRole];
    if (demo) {
      Auth.setCurrentUser(demo, true);
      FixMateApp.showToast("Role Switched", `Active role switched to ${demo.name} (${targetRole.toUpperCase()})`, "info");
      setTimeout(() => {
        window.location.href = Auth.getRedirectUrlForRole(targetRole);
      }, 500);
    }
  },

  initNotificationsDropdown(user) {
    const bellBtn = document.getElementById("notifBellBtn");
    const dropdown = document.getElementById("notifDropdown");
    if (!bellBtn || !dropdown) return;

    bellBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      dropdown.classList.toggle("show");
      this.populateNotifDropdown(user);
    });

    document.addEventListener("click", (e) => {
      if (!dropdown.contains(e.target) && e.target !== bellBtn) {
        dropdown.classList.remove("show");
      }
    });
  },

  populateNotifDropdown(user) {
    const container = document.getElementById("notifListContainer");
    if (!container) return;

    const notifs = StorageManager.getNotifications(user ? (user.role === 'admin' ? null : user.user_id) : null);
    const recent = notifs.slice(0, 5);

    if (recent.length === 0) {
      container.innerHTML = `<div style="padding:1.5rem; text-align:center; color:var(--text-muted); font-size:0.85rem;">No notifications right now.</div>`;
      return;
    }

    container.innerHTML = recent.map(n => `
      <div class="dropdown-item ${!n.is_read ? 'unread' : ''}" onclick="FixMateApp.handleNotifClick(${n.id}, '${n.complaint_code}')">
        <div style="font-size:1.1rem; color:var(--${n.type || 'info'});">
          <i class="fas fa-circle-info"></i>
        </div>
        <div>
          <div style="font-size:0.85rem; font-weight:${n.is_read ? '400' : '600'}; color:var(--text-main);">${n.message}</div>
          <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">${n.created_at}</div>
        </div>
      </div>
    `).join('');
  },

  handleNotifClick(notifId, complaintCode) {
    StorageManager.markNotificationRead(notifId);
    if (complaintCode) {
      window.location.href = `complaint-details.html?id=${complaintCode}`;
    } else {
      window.location.href = `notifications.html`;
    }
  },

  markAllAsRead() {
    const user = Auth.getCurrentUser();
    StorageManager.markAllNotificationsRead(user && user.role !== 'admin' ? user.user_id : null);
    this.updateNotificationCounters(user);
    this.populateNotifDropdown(user);
    this.showToast("Notifications", "All notifications marked as read.", "success");
  },

  updateNotificationCounters(user) {
    const notifs = StorageManager.getNotifications(user ? (user.role === 'admin' ? null : user.user_id) : null);
    const unreadCount = notifs.filter(n => !n.is_read).length;

    const headerBadge = document.getElementById("headerNotifBadge");
    if (headerBadge) {
      if (unreadCount > 0) {
        headerBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        headerBadge.style.display = 'flex';
      } else {
        headerBadge.style.display = 'none';
      }
    }

    const sidebarBadge = document.getElementById("sidebarNotifBadge");
    if (sidebarBadge) {
      sidebarBadge.textContent = unreadCount;
      if (unreadCount > 0) sidebarBadge.classList.add("danger");
      else sidebarBadge.classList.remove("danger");
    }
  },

  initTheme() {
    const isDark = localStorage.getItem("fixmate_dark_mode") === "true";
    if (isDark) {
      document.body.classList.add("dark-mode");
    } else {
      document.body.classList.remove("dark-mode");
    }
  },

  toggleTheme() {
    document.body.classList.toggle("dark-mode");
    const isDark = document.body.classList.contains("dark-mode");
    localStorage.setItem("fixmate_dark_mode", isDark);
    return isDark;
  },

  initMobileSidebar() {
    const btn = document.getElementById("mobileMenuBtn");
    const sidebar = document.getElementById("mainSidebar");
    let backdrop = document.querySelector(".sidebar-backdrop");

    if (!backdrop) {
      backdrop = document.createElement("div");
      backdrop.className = "sidebar-backdrop";
      document.body.appendChild(backdrop);
    }

    if (btn && sidebar) {
      btn.addEventListener("click", () => {
        sidebar.classList.toggle("show");
        backdrop.classList.toggle("show");
      });

      backdrop.addEventListener("click", () => {
        sidebar.classList.remove("show");
        backdrop.classList.remove("show");
      });
    }
  },

  // Toast System
  showToast(title, message, type = "info") {
    let container = document.querySelector(".toast-container");
    if (!container) {
      container = document.createElement("div");
      container.className = "toast-container";
      document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;

    let iconHtml = '<i class="fas fa-circle-info text-info"></i>';
    if (type === 'success') iconHtml = '<i class="fas fa-check-circle text-success"></i>';
    if (type === 'danger') iconHtml = '<i class="fas fa-triangle-exclamation text-danger"></i>';
    if (type === 'warning') iconHtml = '<i class="fas fa-bell text-warning"></i>';

    toast.innerHTML = `
      <div class="toast-icon">${iconHtml}</div>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
      if (toast.parentElement) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        toast.style.transition = '0.3s ease';
        setTimeout(() => toast.remove(), 300);
      }
    }, 4500);
  },

  // Status & Priority Badges Formatter
  getStatusBadge(status) {
    const s = (status || 'SUBMITTED').toUpperCase();
    let badgeClass = 'badge-submitted';
    let icon = 'fa-paper-plane';

    if (s === 'ASSIGNED') { badgeClass = 'badge-assigned'; icon = 'fa-user-check'; }
    else if (s === 'IN PROGRESS') { badgeClass = 'badge-in-progress'; icon = 'fa-screwdriver-wrench'; }
    else if (s === 'RESOLVED') { badgeClass = 'badge-resolved'; icon = 'fa-circle-check'; }
    else if (s === 'CLOSED') { badgeClass = 'badge-closed'; icon = 'fa-lock'; }

    return `<span class="badge ${badgeClass}"><i class="fas ${icon}"></i> ${s}</span>`;
  },

  getPriorityBadge(priority) {
    const p = (priority || 'MEDIUM').toUpperCase();
    let badgeClass = 'badge-medium';
    let icon = 'fa-circle';

    if (p === 'LOW') { badgeClass = 'badge-low'; icon = 'fa-arrow-down'; }
    else if (p === 'HIGH') { badgeClass = 'badge-high'; icon = 'fa-arrow-up'; }
    else if (p === 'CRITICAL') { badgeClass = 'badge-critical'; icon = 'fa-radiation'; }

    return `<span class="badge ${badgeClass}"><i class="fas ${icon}"></i> ${p}</span>`;
  }
};

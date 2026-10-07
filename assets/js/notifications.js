/**
 * FIXMATE - Notifications Page Controller
 */

const NotificationsController = {
  init() {
    Auth.checkAccess(['user', 'technician', 'admin']);
    const user = Auth.getCurrentUser();
    this.renderNotificationsList(user);

    const filter = document.getElementById("notifFilterSelect");
    if (filter) {
      filter.addEventListener("change", () => this.renderNotificationsList(user));
    }
  },

  renderNotificationsList(user) {
    const container = document.getElementById("notificationsListContainer");
    const countBadge = document.getElementById("notificationsTotalCount");
    if (!container) return;

    let notifs = StorageManager.getNotifications(user && user.role !== 'admin' ? user.user_id : null);
    const filterVal = document.getElementById("notifFilterSelect")?.value || "all";

    if (filterVal === 'unread') {
      notifs = notifs.filter(n => !n.is_read);
    } else if (filterVal === 'read') {
      notifs = notifs.filter(n => n.is_read);
    }

    if (countBadge) countBadge.textContent = `${notifs.length} Alerts`;

    if (notifs.length === 0) {
      container.innerHTML = `
        <div class="table-empty-state" style="padding:3rem 1rem;">
          <i class="fas fa-bell-slash"></i>
          <h3>No notifications</h3>
          <p>You have no notifications matching this view.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = notifs.map(n => `
      <div class="card" style="padding:1.2rem 1.5rem; margin-bottom:1rem; border-left:4px solid var(--${n.type || 'info'}); background:${n.is_read ? 'var(--bg-card)' : 'var(--primary-light)'}; transition:var(--transition);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;">
          <div style="display:flex; align-items:flex-start; gap:1rem;">
            <div class="stat-icon ${n.type === 'danger' ? 'red' : (n.type === 'warning' ? 'amber' : (n.type === 'success' ? 'green' : 'blue'))}" style="width:42px; height:42px; font-size:1.15rem;">
              <i class="fas ${n.type === 'danger' ? 'fa-triangle-exclamation' : (n.type === 'warning' ? 'fa-bell' : (n.type === 'success' ? 'fa-check' : 'fa-info-circle'))}"></i>
            </div>
            <div>
              <div style="font-size:0.95rem; font-weight:${n.is_read ? '500' : '700'}; color:var(--text-main); margin-bottom:0.25rem;">
                ${n.message}
              </div>
              <div style="font-size:0.78rem; color:var(--text-muted); display:flex; gap:1rem;">
                <span><i class="far fa-clock"></i> ${n.created_at}</span>
                ${n.complaint_code ? `<span><i class="fas fa-ticket"></i> Ticket: <a href="complaint-details.html?id=${n.complaint_code}" style="font-weight:600;">${n.complaint_code}</a></span>` : ''}
              </div>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:0.5rem; flex-shrink:0;">
            ${!n.is_read ? `
              <button onclick="NotificationsController.markRead(${n.id})" class="btn btn-outline btn-sm" title="Mark as read">
                <i class="fas fa-check"></i>
              </button>
            ` : ''}
            ${n.complaint_code ? `
              <a href="complaint-details.html?id=${n.complaint_code}" class="btn btn-primary btn-sm">
                View Ticket
              </a>
            ` : ''}
          </div>
        </div>
      </div>
    `).join('');
  },

  markRead(id) {
    StorageManager.markNotificationRead(id);
    const user = Auth.getCurrentUser();
    this.renderNotificationsList(user);
    FixMateApp.updateNotificationCounters(user);
  },

  markAllRead() {
    const user = Auth.getCurrentUser();
    StorageManager.markAllNotificationsRead(user && user.role !== 'admin' ? user.user_id : null);
    this.renderNotificationsList(user);
    FixMateApp.updateNotificationCounters(user);
    FixMateApp.showToast("Updated", "All alerts marked as read.", "success");
  }
};

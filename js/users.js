/**
 * FIXMATE - User Management Controller (Admin)
 */

const UsersController = {
  init() {
    Auth.checkAccess(['admin']);
    this.renderUsersTable();
    this.initFilters();
    this.initAddUserForm();
  },

  initFilters() {
    const searchInput = document.getElementById("userSearchInput");
    const roleFilter = document.getElementById("userRoleFilter");
    const deptFilter = document.getElementById("userDeptFilter");
    const statusFilter = document.getElementById("userStatusFilter");

    const handler = () => this.renderUsersTable();
    if (searchInput) searchInput.addEventListener("input", handler);
    if (roleFilter) roleFilter.addEventListener("change", handler);
    if (deptFilter) deptFilter.addEventListener("change", handler);
    if (statusFilter) statusFilter.addEventListener("change", handler);
  },

  renderUsersTable() {
    const tbody = document.getElementById("usersTableBody");
    const countBadge = document.getElementById("usersCountBadge");
    if (!tbody) return;

    const users = StorageManager.getUsers();

    const searchVal = (document.getElementById("userSearchInput")?.value || "").toLowerCase().trim();
    const roleVal = document.getElementById("userRoleFilter")?.value || "";
    const deptVal = document.getElementById("userDeptFilter")?.value || "";
    const statusVal = document.getElementById("userStatusFilter")?.value || "";

    const filtered = users.filter(u => {
      const matchSearch = !searchVal ||
        u.name.toLowerCase().includes(searchVal) ||
        u.email.toLowerCase().includes(searchVal) ||
        u.employee_id.toLowerCase().includes(searchVal);

      const matchRole = !roleVal || u.role.toLowerCase() === roleVal.toLowerCase();
      const matchDept = !deptVal || u.department.toLowerCase().includes(deptVal.toLowerCase());
      const matchStatus = !statusVal || u.status === statusVal;

      return matchSearch && matchRole && matchDept && matchStatus;
    });

    if (countBadge) countBadge.textContent = `${filtered.length} Users`;

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="table-empty-state">
              <i class="fas fa-users-slash"></i>
              <h3>No users found</h3>
              <p>No user records match your search criteria.</p>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = filtered.map(u => `
      <tr>
        <td class="cell-id">#${u.id}</td>
        <td>
          <div style="font-weight:600; color:var(--text-main);">${u.name}</div>
          <div class="cell-subtitle"><i class="fas fa-id-badge"></i> ${u.employee_id}</div>
        </td>
        <td>
          <div>${u.email}</div>
          <div class="cell-subtitle"><i class="fas fa-phone"></i> ${u.phone || 'N/A'}</div>
        </td>
        <td><span style="font-weight:500;">${u.department}</span></td>
        <td><span class="role-pill ${u.role}">${u.role}</span></td>
        <td>
          <span class="badge ${u.status === 'active' ? 'badge-low' : 'badge-closed'}">
            ${u.status === 'active' ? 'Active' : 'Inactive'}
          </span>
        </td>
        <td>
          <div class="cell-actions">
            <button onclick="UsersController.openEditModal(${u.id})" class="btn btn-outline btn-sm" title="Edit User">
              <i class="fas fa-edit text-primary"></i> Edit
            </button>
            <button onclick="UsersController.toggleUserStatus(${u.id})" class="btn btn-outline btn-sm ${u.status === 'active' ? 'text-warning' : 'text-success'}" title="Toggle Active Status">
              <i class="fas ${u.status === 'active' ? 'fa-ban' : 'fa-check'}"></i> ${u.status === 'active' ? 'Deactivate' : 'Activate'}
            </button>
            <button onclick="UsersController.deleteUser(${u.id})" class="btn btn-outline btn-sm text-danger" title="Delete User">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  },

  openAddModal() {
    const modal = document.getElementById("addUserModal");
    const form = document.getElementById("addUserForm");
    if (form) form.reset();
    document.getElementById("userModalMode").value = "create";
    document.getElementById("userModalTitle").textContent = "Add New User";
    if (modal) modal.classList.add("show");
  },

  openEditModal(userId) {
    const user = StorageManager.getUserById(userId);
    if (!user) return;

    document.getElementById("userModalMode").value = "edit";
    document.getElementById("userEditIdHidden").value = user.id;
    document.getElementById("userModalTitle").textContent = "Edit User Details";

    const form = document.getElementById("addUserForm");
    form.userName.value = user.name;
    form.userEmail.value = user.email;
    form.userPhone.value = user.phone;
    form.userEmpId.value = user.employee_id;
    form.userDepartment.value = user.department;
    form.userRole.value = user.role;

    const modal = document.getElementById("addUserModal");
    if (modal) modal.classList.add("show");
  },

  closeModal() {
    const modal = document.getElementById("addUserModal");
    if (modal) modal.classList.remove("show");
  },

  initAddUserForm() {
    const form = document.getElementById("addUserForm");
    if (!form) return;

    form.onsubmit = (e) => {
      e.preventDefault();
      const mode = document.getElementById("userModalMode").value;
      const name = form.userName.value.trim();
      const email = form.userEmail.value.trim();
      const phone = form.userPhone.value.trim();
      const empId = form.userEmpId.value.trim();
      const department = form.userDepartment.value.trim();
      const role = form.userRole.value;

      if (!name || !email || !department) {
        FixMateApp.showToast("Validation", "Please fill in all mandatory fields.", "warning");
        return;
      }

      if (mode === "create") {
        const added = StorageManager.addUser({ name, email, phone, employee_id: empId, department, role });
        FixMateApp.showToast("User Created", `Added user ${added.name} successfully.`, "success");
      } else {
        const editId = document.getElementById("userEditIdHidden").value;
        StorageManager.updateUser(editId, { name, email, phone, employee_id: empId, department, role });
        FixMateApp.showToast("User Updated", `Updated user details for ${name}.`, "success");
      }

      this.closeModal();
      this.renderUsersTable();
    };
  },

  toggleUserStatus(userId) {
    const user = StorageManager.getUserById(userId);
    if (!user) return;
    const newStatus = user.status === 'active' ? 'inactive' : 'active';
    StorageManager.updateUser(userId, { status: newStatus });
    FixMateApp.showToast("Status Changed", `${user.name} is now ${newStatus}.`, "info");
    this.renderUsersTable();
  },

  deleteUser(userId) {
    const user = StorageManager.getUserById(userId);
    if (!user) return;
    if (confirm(`Are you sure you want to delete user "${user.name}"?`)) {
      StorageManager.deleteUser(userId);
      FixMateApp.showToast("Deleted", "User record removed.", "danger");
      this.renderUsersTable();
    }
  }
};

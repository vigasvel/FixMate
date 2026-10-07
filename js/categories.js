/**
 * FIXMATE - Categories & Technicians Controllers (Admin)
 */

const CategoriesController = {
  init() {
    Auth.checkAccess(['admin']);
    this.renderCategoryCards();
    this.initCategoryForm();
  },

  renderCategoryCards() {
    const container = document.getElementById("categoriesCardsGrid");
    const countBadge = document.getElementById("categoriesCountBadge");
    if (!container) return;

    const categories = StorageManager.getCategories();
    const complaints = StorageManager.getComplaints();

    if (countBadge) countBadge.textContent = `${categories.length} Active Categories`;

    container.innerHTML = categories.map(cat => {
      const catComplaints = complaints.filter(c => c.category_name.toLowerCase() === cat.name.toLowerCase());
      const total = catComplaints.length;
      const pending = catComplaints.filter(c => c.status === 'SUBMITTED' || c.status === 'ASSIGNED' || c.status === 'IN PROGRESS').length;
      const resolved = catComplaints.filter(c => c.status === 'RESOLVED' || c.status === 'CLOSED').length;

      return `
        <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
          <div>
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
              <div class="stat-icon blue" style="width:46px; height:46px; font-size:1.25rem;">
                <i class="fas ${cat.icon || 'fa-tools'}"></i>
              </div>
              <span class="badge ${cat.status === 'active' ? 'badge-low' : 'badge-closed'}">${cat.status}</span>
            </div>
            <h3 style="font-size:1.15rem; margin-bottom:0.35rem;">${cat.name}</h3>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.25rem; min-height:40px;">${cat.description || 'Facility maintenance category.'}</p>
          </div>

          <div>
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); background:var(--bg-main); padding:0.75rem; border-radius:var(--radius-md); text-align:center; margin-bottom:1rem;">
              <div>
                <div style="font-size:1.15rem; font-weight:700;">${total}</div>
                <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Total</div>
              </div>
              <div>
                <div style="font-size:1.15rem; font-weight:700; color:var(--warning);">${pending}</div>
                <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Pending</div>
              </div>
              <div>
                <div style="font-size:1.15rem; font-weight:700; color:var(--success);">${resolved}</div>
                <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Resolved</div>
              </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
              <button onclick="CategoriesController.openEditModal(${cat.id})" class="btn btn-outline btn-sm">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button onclick="CategoriesController.deleteCategory(${cat.id})" class="btn btn-outline btn-sm text-danger">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  },

  openAddModal() {
    const modal = document.getElementById("categoryModal");
    const form = document.getElementById("categoryForm");
    if (form) form.reset();
    document.getElementById("categoryModalMode").value = "create";
    document.getElementById("categoryModalTitle").textContent = "Add Problem Category";
    if (modal) modal.classList.add("show");
  },

  openEditModal(catId) {
    const categories = StorageManager.getCategories();
    const cat = categories.find(c => c.id === Number(catId));
    if (!cat) return;

    document.getElementById("categoryModalMode").value = "edit";
    document.getElementById("categoryEditIdHidden").value = cat.id;
    document.getElementById("categoryModalTitle").textContent = "Edit Category";

    const form = document.getElementById("categoryForm");
    form.categoryName.value = cat.name;
    form.categoryIcon.value = cat.icon || "fa-tools";
    form.categoryDesc.value = cat.description || "";

    const modal = document.getElementById("categoryModal");
    if (modal) modal.classList.add("show");
  },

  closeModal() {
    const modal = document.getElementById("categoryModal");
    if (modal) modal.classList.remove("show");
  },

  initCategoryForm() {
    const form = document.getElementById("categoryForm");
    if (!form) return;

    form.onsubmit = (e) => {
      e.preventDefault();
      const mode = document.getElementById("categoryModalMode").value;
      const name = form.categoryName.value.trim();
      const icon = form.categoryIcon.value.trim();
      const description = form.categoryDesc.value.trim();

      if (!name) {
        FixMateApp.showToast("Required", "Please provide a category name.", "warning");
        return;
      }

      if (mode === "create") {
        StorageManager.addCategory({ name, icon, description });
        FixMateApp.showToast("Category Added", `Added category "${name}".`, "success");
      } else {
        const id = document.getElementById("categoryEditIdHidden").value;
        StorageManager.updateCategory(id, { name, icon, description });
        FixMateApp.showToast("Category Updated", `Updated category "${name}".`, "success");
      }

      this.closeModal();
      this.renderCategoryCards();
    };
  },

  deleteCategory(catId) {
    if (confirm("Are you sure you want to delete this complaint category?")) {
      StorageManager.deleteCategory(catId);
      FixMateApp.showToast("Category Removed", "Category has been deleted.", "danger");
      this.renderCategoryCards();
    }
  }
};

const TechniciansAdminController = {
  init() {
    Auth.checkAccess(['admin']);
    this.renderTechniciansTable();
    this.initTechnicianForm();
  },

  renderTechniciansTable() {
    const tbody = document.getElementById("adminTechniciansTableBody");
    const countBadge = document.getElementById("techCountBadge");
    if (!tbody) return;

    const techs = StorageManager.getTechnicians();
    const complaints = StorageManager.getComplaints();

    if (countBadge) countBadge.textContent = `${techs.length} Technicians`;

    tbody.innerHTML = techs.map(t => {
      const activeCount = complaints.filter(c => (c.technician_name === t.name || c.technician_id === t.id) && (c.status === 'ASSIGNED' || c.status === 'IN PROGRESS')).length;
      const completedCount = complaints.filter(c => (c.technician_name === t.name || c.technician_id === t.id) && (c.status === 'RESOLVED' || c.status === 'CLOSED')).length;

      return `
        <tr>
          <td class="cell-id">TECH-${String(t.id).padStart(3, '0')}</td>
          <td>
            <div style="font-weight:600; color:var(--text-main);">${t.name}</div>
            <div class="cell-subtitle"><i class="fas fa-envelope"></i> ${t.email}</div>
          </td>
          <td>
            <span class="badge badge-medium"><i class="fas fa-wrench"></i> ${t.specialization}</span>
          </td>
          <td><i class="fas fa-phone text-muted"></i> ${t.phone}</td>
          <td>
            <span class="badge ${t.availability === 'Available' ? 'badge-low' : (t.availability === 'Busy' ? 'badge-high' : 'badge-closed')}">
              ${t.availability}
            </span>
          </td>
          <td>
            <strong>${activeCount} active</strong>
            <div class="cell-subtitle">${completedCount} completed</div>
          </td>
          <td>
            <div style="color:#F59E0B; font-weight:600; font-size:0.85rem;">
              <i class="fas fa-star"></i> ${t.rating ? t.rating.toFixed(1) : '5.0'} / 5.0
            </div>
          </td>
          <td>
            <div class="cell-actions">
              <button onclick="TechniciansAdminController.openEditModal(${t.id})" class="btn btn-outline btn-sm">
                <i class="fas fa-edit text-primary"></i> Edit
              </button>
              <button onclick="TechniciansAdminController.toggleAvailability(${t.id})" class="btn btn-outline btn-sm">
                <i class="fas fa-arrows-rotate"></i> Status
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  },

  openAddModal() {
    const modal = document.getElementById("technicianModal");
    const form = document.getElementById("technicianForm");
    if (form) form.reset();
    document.getElementById("techModalMode").value = "create";
    document.getElementById("techModalTitle").textContent = "Register New Technician";
    if (modal) modal.classList.add("show");
  },

  openEditModal(techId) {
    const techs = StorageManager.getTechnicians();
    const tech = techs.find(t => t.id === Number(techId));
    if (!tech) return;

    document.getElementById("techModalMode").value = "edit";
    document.getElementById("techEditIdHidden").value = tech.id;
    document.getElementById("techModalTitle").textContent = "Edit Technician Details";

    const form = document.getElementById("technicianForm");
    form.techName.value = tech.name;
    form.techEmail.value = tech.email;
    form.techPhone.value = tech.phone;
    form.techSpecialty.value = tech.specialization;
    form.techAvailability.value = tech.availability;

    const modal = document.getElementById("technicianModal");
    if (modal) modal.classList.add("show");
  },

  closeModal() {
    const modal = document.getElementById("technicianModal");
    if (modal) modal.classList.remove("show");
  },

  initTechnicianForm() {
    const form = document.getElementById("technicianForm");
    if (!form) return;

    form.onsubmit = (e) => {
      e.preventDefault();
      const mode = document.getElementById("techModalMode").value;
      const name = form.techName.value.trim();
      const email = form.techEmail.value.trim();
      const phone = form.techPhone.value.trim();
      const specialization = form.techSpecialty.value;
      const availability = form.techAvailability.value;

      if (!name || !email || !phone || !specialization) {
        FixMateApp.showToast("Required", "Please fill in all technician details.", "warning");
        return;
      }

      if (mode === "create") {
        StorageManager.addTechnician({ name, email, phone, specialization, availability });
        FixMateApp.showToast("Technician Added", `${name} added to staff roster.`, "success");
      } else {
        const id = document.getElementById("techEditIdHidden").value;
        StorageManager.updateTechnician(id, { name, email, phone, specialization, availability });
        FixMateApp.showToast("Technician Updated", `Details updated for ${name}.`, "success");
      }

      this.closeModal();
      this.renderTechniciansTable();
    };
  },

  toggleAvailability(techId) {
    const tech = StorageManager.getTechnicianById(techId);
    if (!tech) return;
    const nextState = tech.availability === 'Available' ? 'Busy' : (tech.availability === 'Busy' ? 'On Leave' : 'Available');
    StorageManager.updateTechnician(techId, { availability: nextState });
    FixMateApp.showToast("Availability Updated", `${tech.name} is now marked as "${nextState}".`, "info");
    this.renderTechniciansTable();
  }
};

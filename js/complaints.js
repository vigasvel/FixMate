/**
 * FIXMATE - Complaints Controller
 * Handles Complaint Submission, Listing, Filtering, Detail Views, Timelines, Comments & Feedback
 */

const ComplaintsController = {
  // 1. Report Problem Page Initializer
  initReportForm() {
    Auth.checkAccess(['user', 'admin']);
    const form = document.getElementById("reportProblemForm");
    const nextCodeEl = document.getElementById("autoComplaintId");
    const catSelect = document.getElementById("categorySelect");

    if (nextCodeEl) {
      nextCodeEl.value = StorageManager.generateNextComplaintCode();
    }

    // Populate categories dynamically
    if (catSelect) {
      const categories = StorageManager.getCategories();
      catSelect.innerHTML = `<option value="">-- Select Problem Category --</option>` +
        categories.map(c => `<option value="${c.id}" data-name="${c.name}">${c.name}</option>`).join('');
    }

    // Set default preferred date to today
    const dateInput = document.getElementById("preferredDate");
    if (dateInput) {
      dateInput.value = new Date().toISOString().split('T')[0];
    }

    // Handle Image Preview
    const imageInput = document.getElementById("complaintImageInput");
    const previewContainer = document.getElementById("imagePreviewBox");
    let base64Image = null;

    if (imageInput && previewContainer) {
      imageInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
          if (file.size > 2 * 1024 * 1024) {
            FixMateApp.showToast("File Too Large", "Please select an image smaller than 2MB.", "warning");
            imageInput.value = "";
            return;
          }
          const reader = new FileReader();
          reader.onload = (re) => {
            base64Image = re.target.result;
            previewContainer.innerHTML = `<div style="margin-top:10px; color:var(--success);"><i class="fas fa-check-circle"></i> Image attached: ${file.name}</div>`;
          };
          reader.readAsDataURL(file);
        }
      });
    }

    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        this.handleComplaintSubmission(form, base64Image);
      });
    }
  },

  handleComplaintSubmission(form, base64Image) {
    const user = Auth.getCurrentUser();
    const title = form.complaintTitle.value.trim();
    const categoryId = form.categorySelect.value;
    const catSelectedOption = form.categorySelect.options[form.categorySelect.selectedIndex];
    const categoryName = catSelectedOption ? catSelectedOption.getAttribute("data-name") : "";
    const building = form.buildingSelect.value;
    const floor = form.floorSelect.value;
    const roomNumber = form.roomNumber.value.trim();
    const description = form.description.value.trim();
    const priority = form.querySelector('input[name="priority"]:checked')?.value || "MEDIUM";
    const preferredDate = form.preferredDate.value;
    const contactNumber = form.contactNumber.value.trim();

    // Field Validations
    let isValid = true;

    if (!title) {
      this.setInvalid(form.complaintTitle, "Please enter a descriptive complaint title.");
      isValid = false;
    } else {
      this.setValid(form.complaintTitle);
    }

    if (!categoryId) {
      this.setInvalid(form.categorySelect, "Please choose a category.");
      isValid = false;
    } else {
      this.setValid(form.categorySelect);
    }

    if (!building) {
      this.setInvalid(form.buildingSelect, "Please select the campus building/block.");
      isValid = false;
    } else {
      this.setValid(form.buildingSelect);
    }

    if (!floor) {
      this.setInvalid(form.floorSelect, "Please select the floor.");
      isValid = false;
    } else {
      this.setValid(form.floorSelect);
    }

    if (!description || description.length < 10) {
      this.setInvalid(form.description, "Please provide more details (at least 10 characters).");
      isValid = false;
    } else {
      this.setValid(form.description);
    }

    if (!contactNumber || contactNumber.length < 10) {
      this.setInvalid(form.contactNumber, "Please provide a valid 10-digit contact number.");
      isValid = false;
    } else {
      this.setValid(form.contactNumber);
    }

    if (!isValid) {
      FixMateApp.showToast("Validation Error", "Please fill in all required fields accurately.", "danger");
      return;
    }

    // Store Complaint in LocalStorage
    const newComplaint = StorageManager.addComplaint({
      user_id: user ? user.user_id : 6,
      user_name: user ? user.name : "Kumaresan K",
      user_dept: user ? user.department : "Computer Science & Engg",
      category_id: categoryId,
      category_name: categoryName,
      title: title,
      description: description,
      building: building,
      floor: floor,
      room_number: roomNumber,
      priority: priority,
      preferred_date: preferredDate,
      contact_number: contactNumber,
      image_data: base64Image
    });

    // Show Success Modal
    this.showSuccessModal(newComplaint);
    form.reset();
    document.getElementById("autoComplaintId").value = StorageManager.generateNextComplaintCode();
  },

  setInvalid(el, msg) {
    el.classList.add("is-invalid");
    let feedback = el.nextElementSibling;
    if (feedback && feedback.classList.contains("invalid-feedback")) {
      feedback.textContent = msg;
    }
  },

  setValid(el) {
    el.classList.remove("is-invalid");
  },

  showSuccessModal(complaint) {
    const modal = document.getElementById("submissionSuccessModal");
    const codeEl = document.getElementById("modalComplaintCode");
    const titleEl = document.getElementById("modalComplaintTitle");
    const viewBtn = document.getElementById("modalViewComplaintBtn");

    if (codeEl) codeEl.textContent = complaint.complaint_code;
    if (titleEl) titleEl.textContent = complaint.title;
    if (viewBtn) {
      viewBtn.onclick = () => {
        window.location.href = `complaint-details.html?id=${complaint.complaint_code}`;
      };
    }

    if (modal) {
      modal.classList.add("show");
    } else {
      FixMateApp.showToast("Success", `Complaint ${complaint.complaint_code} submitted successfully!`, "success");
      setTimeout(() => {
        window.location.href = "my-complaints.html";
      }, 1500);
    }
  },

  // 2. User My Complaints Listing & Filtering
  initMyComplaints() {
    Auth.checkAccess(['user', 'admin']);
    const user = Auth.getCurrentUser();
    this.populateCategoryFilter();
    this.renderUserComplaintsTable(user);

    // Event Listeners for Live Filtering
    const searchInput = document.getElementById("complaintSearchInput");
    const statusFilter = document.getElementById("statusFilter");
    const categoryFilter = document.getElementById("categoryFilter");
    const priorityFilter = document.getElementById("priorityFilter");

    const filterHandler = () => this.renderUserComplaintsTable(user);

    if (searchInput) searchInput.addEventListener("input", filterHandler);
    if (statusFilter) statusFilter.addEventListener("change", filterHandler);
    if (categoryFilter) categoryFilter.addEventListener("change", filterHandler);
    if (priorityFilter) priorityFilter.addEventListener("change", filterHandler);
  },

  populateCategoryFilter() {
    const catSelect = document.getElementById("categoryFilter");
    if (!catSelect) return;
    const categories = StorageManager.getCategories();
    catSelect.innerHTML = `<option value="">All Categories</option>` +
      categories.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
  },

  renderUserComplaintsTable(user) {
    const tbody = document.getElementById("userComplaintsTableBody");
    const countEl = document.getElementById("complaintsCountBadge");
    if (!tbody) return;

    let complaints = StorageManager.getComplaints();

    // If regular user, filter only their complaints (or show demo user's if admin testing)
    if (user && user.role === 'user') {
      complaints = complaints.filter(c => c.user_id === user.user_id || c.user_name === user.name);
    }

    // Apply Filters
    const searchVal = (document.getElementById("complaintSearchInput")?.value || "").toLowerCase().trim();
    const statusVal = document.getElementById("statusFilter")?.value || "";
    const catVal = document.getElementById("categoryFilter")?.value || "";
    const priorityVal = document.getElementById("priorityFilter")?.value || "";

    const filtered = complaints.filter(c => {
      const matchSearch = !searchVal || 
        c.complaint_code.toLowerCase().includes(searchVal) ||
        c.title.toLowerCase().includes(searchVal) ||
        c.location.toLowerCase().includes(searchVal) ||
        (c.technician_name && c.technician_name.toLowerCase().includes(searchVal));

      const matchStatus = !statusVal || c.status.toUpperCase() === statusVal.toUpperCase();
      const matchCat = !catVal || c.category_name.toLowerCase() === catVal.toLowerCase();
      const matchPriority = !priorityVal || c.priority.toUpperCase() === priorityVal.toUpperCase();

      return matchSearch && matchStatus && matchCat && matchPriority;
    });

    if (countEl) countEl.textContent = `${filtered.length} Items`;

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8">
            <div class="table-empty-state">
              <i class="fas fa-folder-open"></i>
              <h3>No complaints found</h3>
              <p>No complaints match your search or filter criteria.</p>
              <a href="report-problem.html" class="btn btn-primary btn-sm" style="margin-top:10px;">
                <i class="fas fa-plus"></i> Submit a New Complaint
              </a>
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
          <div class="cell-subtitle"><i class="fas fa-map-marker-alt"></i> ${c.location}</div>
        </td>
        <td><span style="font-weight:500;">${c.category_name}</span></td>
        <td>${FixMateApp.getPriorityBadge(c.priority)}</td>
        <td>
          ${c.technician_name !== 'Unassigned' ? `<i class="fas fa-user-gear text-primary"></i> ${c.technician_name}` : `<span class="text-muted"><i class="far fa-clock"></i> Not Assigned</span>`}
        </td>
        <td>${FixMateApp.getStatusBadge(c.status)}</td>
        <td><span style="font-size:0.82rem; color:var(--text-muted);">${c.submitted_date.split(' ')[0]}</span></td>
        <td>
          <div class="cell-actions">
            <a href="complaint-details.html?id=${c.complaint_code}" class="btn btn-outline btn-sm" title="View Details">
              <i class="fas fa-eye"></i> View
            </a>
            ${c.status === 'RESOLVED' ? `
              <button onclick="ComplaintsController.openFeedbackModal('${c.complaint_code}')" class="btn btn-success btn-sm" title="Provide Feedback">
                <i class="fas fa-star"></i> Feedback
              </button>
            ` : ''}
          </div>
        </td>
      </tr>
    `).join('');
  },

  // 3. Complaint Details Page Initializer
  initComplaintDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const complaintCode = urlParams.get("id") || "FM-2026-0001";
    const complaint = StorageManager.getComplaintById(complaintCode);

    if (!complaint) {
      FixMateApp.showToast("Not Found", `Complaint ${complaintCode} does not exist.`, "danger");
      setTimeout(() => window.location.href = "my-complaints.html", 1500);
      return;
    }

    this.renderComplaintDetailsView(complaint);
    this.initCommentForm(complaint);
  },

  renderComplaintDetailsView(complaint) {
    document.title = `${complaint.complaint_code} - FixMate Details`;

    // Basic Header
    const codeEl = document.getElementById("detailComplaintCode");
    const titleEl = document.getElementById("detailTitle");
    const statusBadgeEl = document.getElementById("detailStatusBadge");
    const priorityBadgeEl = document.getElementById("detailPriorityBadge");

    if (codeEl) codeEl.textContent = complaint.complaint_code;
    if (titleEl) titleEl.textContent = complaint.title;
    if (statusBadgeEl) statusBadgeEl.innerHTML = FixMateApp.getStatusBadge(complaint.status);
    if (priorityBadgeEl) priorityBadgeEl.innerHTML = FixMateApp.getPriorityBadge(complaint.priority);

    // Meta details
    const catEl = document.getElementById("detailCategory");
    const locEl = document.getElementById("detailLocation");
    const submitterEl = document.getElementById("detailSubmitter");
    const dateEl = document.getElementById("detailDate");
    const techEl = document.getElementById("detailTechnician");
    const descEl = document.getElementById("detailDescription");
    const resNotesBox = document.getElementById("detailResolutionBox");

    if (catEl) catEl.textContent = complaint.category_name;
    if (locEl) locEl.textContent = `${complaint.building} - ${complaint.floor}, ${complaint.room_number || 'Main area'}`;
    if (submitterEl) submitterEl.textContent = `${complaint.user_name} (${complaint.user_dept || 'Campus'})`;
    if (dateEl) dateEl.textContent = complaint.submitted_date;
    if (techEl) techEl.textContent = complaint.technician_name || "Unassigned";
    if (descEl) descEl.textContent = complaint.description;

    if (resNotesBox) {
      if (complaint.status === 'RESOLVED' || complaint.status === 'CLOSED') {
        resNotesBox.style.display = 'block';
        resNotesBox.innerHTML = `
          <h4 style="font-size:0.95rem; color:var(--success-text); margin-bottom:0.5rem;">
            <i class="fas fa-circle-check"></i> Resolution Summary
          </h4>
          <p style="font-size:0.88rem; color:var(--text-main); margin-bottom:0.5rem;">
            <strong>Work Done:</strong> ${complaint.resolution_notes || 'Resolved according to facility specifications.'}
          </p>
          ${complaint.parts_used ? `<p style="font-size:0.85rem; color:var(--text-muted);"><strong>Parts Replaced:</strong> ${complaint.parts_used}</p>` : ''}
        `;
      } else {
        resNotesBox.style.display = 'none';
      }
    }

    // Feedback Prompt for User
    const feedbackBanner = document.getElementById("userFeedbackBanner");
    if (feedbackBanner) {
      if (complaint.status === 'RESOLVED') {
        feedbackBanner.style.display = 'block';
      } else {
        feedbackBanner.style.display = 'none';
      }
    }

    // Render Stepper Tracker
    this.renderProgressTracker(complaint.status);

    // Render Activity Timeline
    this.renderActivityTimeline(complaint.timeline || []);

    // Render Comments
    this.renderCommentsList(complaint.comments || []);
  },

  renderProgressTracker(status) {
    const steps = ["SUBMITTED", "ASSIGNED", "IN PROGRESS", "RESOLVED", "CLOSED"];
    const currentIdx = steps.indexOf(status.toUpperCase());
    const trackerContainer = document.getElementById("statusTrackerContainer");
    if (!trackerContainer) return;

    const progressPct = currentIdx >= 0 ? (currentIdx / (steps.length - 1)) * 80 : 0;

    trackerContainer.innerHTML = `
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
  },

  renderActivityTimeline(timeline) {
    const container = document.getElementById("activityTimelineContainer");
    if (!container) return;

    if (timeline.length === 0) {
      container.innerHTML = `<p class="text-muted" style="font-size:0.85rem;">No activity recorded yet.</p>`;
      return;
    }

    container.innerHTML = timeline.map(item => `
      <div class="timeline-item">
        <div class="timeline-dot ${item.status === 'RESOLVED' || item.status === 'CLOSED' ? 'success' : ''}"></div>
        <div class="timeline-header">
          <span class="timeline-title">${item.status}</span>
          <span class="timeline-time">${item.time}</span>
        </div>
        <div class="timeline-desc">${item.note}</div>
      </div>
    `).join('');
  },

  renderCommentsList(comments) {
    const container = document.getElementById("commentsListContainer");
    if (!container) return;

    if (comments.length === 0) {
      container.innerHTML = `<div style="text-align:center; padding:1.5rem; color:var(--text-muted); font-size:0.85rem;">No comments yet. Start the conversation below.</div>`;
      return;
    }

    container.innerHTML = comments.map(c => `
      <div style="background:var(--bg-main); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1rem; margin-bottom:0.75rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
          <div style="font-weight:600; font-size:0.88rem; color:var(--text-main);">
            <i class="fas fa-user-circle text-primary"></i> ${c.user_name}
            <span class="role-pill ${c.user_role}" style="margin-left:6px; font-size:0.65rem;">${c.user_role}</span>
          </div>
          <div style="font-size:0.75rem; color:var(--text-muted);">${c.time}</div>
        </div>
        <div style="font-size:0.875rem; color:var(--text-main); line-height:1.4;">${c.comment}</div>
      </div>
    `).join('');
  },

  initCommentForm(complaint) {
    const form = document.getElementById("addCommentForm");
    if (!form) return;

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const input = form.commentText;
      const text = input.value.trim();
      const user = Auth.getCurrentUser();

      if (!text) return;

      const newComment = StorageManager.addCommentToComplaint(complaint.id, {
        user_name: user ? user.name : "Authorized User",
        user_role: user ? user.role : "user",
        comment: text
      });

      input.value = "";
      const updated = StorageManager.getComplaintById(complaint.id);
      this.renderCommentsList(updated.comments);
      FixMateApp.showToast("Comment Added", "Your message was posted successfully.", "info");
    });
  },

  // 4. User Feedback Modal
  openFeedbackModal(complaintCode) {
    const modal = document.getElementById("feedbackModal");
    const codeEl = document.getElementById("feedbackComplaintCode");
    const idInput = document.getElementById("feedbackComplaintId");

    if (codeEl) codeEl.textContent = complaintCode;
    if (idInput) idInput.value = complaintCode;

    if (modal) {
      modal.classList.add("show");
    }
  },

  closeFeedbackModal() {
    const modal = document.getElementById("feedbackModal");
    if (modal) modal.classList.remove("show");
  },

  submitFeedback() {
    const idInput = document.getElementById("feedbackComplaintId");
    const complaintCode = idInput.value;
    const complaint = StorageManager.getComplaintById(complaintCode);
    const user = Auth.getCurrentUser();

    const ratingInput = document.querySelector('input[name="rating"]:checked');
    const rating = ratingInput ? ratingInput.value : 5;
    const comment = document.getElementById("feedbackCommentText")?.value.trim() || "";

    if (!complaint) return;

    StorageManager.addFeedback({
      complaint_id: complaint.id,
      complaint_code: complaint.complaint_code,
      user_id: user ? user.user_id : 6,
      user_name: user ? user.name : "Kumaresan K",
      rating: rating,
      comment: comment
    });

    this.closeFeedbackModal();
    FixMateApp.showToast("Feedback Submitted", "Thank you for rating our maintenance service!", "success");

    // Refresh view
    const updated = StorageManager.getComplaintById(complaint.id);
    if (window.location.pathname.includes("complaint-details.html")) {
      this.renderComplaintDetailsView(updated);
    } else if (window.location.pathname.includes("my-complaints.html")) {
      this.renderUserComplaintsTable(user);
    }
  }
};

# FixMate – Smart Complaint & Maintenance Management System

> **Tagline:** *"Report. Track. Resolve."*  
> **Course:** Web Technology Lab (CS157)  
> **Architecture:** Full-Stack Web Application (PHP 8+, MySQL PDO, HTML5, Vanilla CSS3, JavaScript)

---

## Project Overview

**FixMate** is a full-stack complaint and maintenance management web application designed to manage and streamline maintenance requests in organizations such as colleges, offices, hospitals, hotels, residential complexes, and industrial facilities.

---

## Industrial Use Case

**Facility Management / Maintenance Management**

---

## Problem Statement

Organizations often rely on manual complaint reporting through phone calls, unmonitored messaging apps, or paper-based registers. This results in:
- Delays in reporting critical electrical, plumbing, or infrastructure failures.
- Lack of accountability and visibility in technician dispatch.
- Absence of real-time status tracking, historical audit logs, and verified resolution feedback.

---

## Proposed Solution

FixMate provides a centralized, automated web platform for submitting, assigning, tracking, resolving, and rating facility maintenance complaints with strict role-based access control and persistent MySQL data management.

---

## Features

- **User Registration & Secure Authentication:** Encrypted passwords using `password_hash()` and `password_verify()`.
- **Role-Based Access Control (RBAC):** Dedicated, secure dashboards for Users, Technicians, and Administrators.
- **Complaint Submission & Structured Codes:** Automatic code generation (`FM-YYYY-XXXX`) with building, floor, room, category, and priority tagging.
- **5-Stage Visual Stepper Tracking:** Real-time visibility across *Submitted* → *Assigned* → *In Progress* → *Resolved* → *Closed*.
- **Technician Dispatch & Workload Management:** Live calculation of active vs completed jobs per technician.
- **Interactive Discussion Thread:** Dynamic communication on each complaint between requester and technician.
- **Resolution Verification & Feedback:** Users confirm ticket closure and submit 1–5 star ratings and reviews.
- **Live Notifications:** Automated alerts on assignment, start of work, and resolution.
- **Administrative Analytics & Reports:** Dynamic **Chart.js** charts, SLA turnaround calculations, printable summaries, and direct RFC 4180 CSV export.
- **Responsive Modern UI:** Clean enterprise design system built with Vanilla CSS3.

---

## User Roles

### 👤 User (Staff / Student / Faculty)
- Submit maintenance complaints with location and priority.
- View and track personal complaint status in real-time.
- Post comments and collaborate on active tickets.
- Confirm resolution when work is completed.
- Submit ratings (1–5 stars) and feedback reviews.

### 🔧 Technician (Field Maintenance Specialist)
- View assigned tasks in a prioritized queue.
- Accept tasks and schedule inspection.
- Start maintenance work (automatically timestamps `started_date = NOW()`).
- Mark tasks as resolved with resolution notes and parts used.
- Update availability status (`Available`, `Busy`, `On Leave`).

### 🛡️ Admin (Facility Manager)
- Monitor real-time dashboard analytics and KPI metrics.
- Assign specialized technicians to pending complaints.
- Manage users (Add, Edit, Deactivate, Delete).
- Manage technicians and track workload capacity.
- Manage maintenance categories.
- Generate analytical reports, print summaries, and export data as CSV.
- View feedback and system notification logs.

---

## Technology Stack

- **Frontend:** HTML5, Vanilla CSS3, Vanilla JavaScript (ES6+), Chart.js
- **Backend:** PHP 8+ (PDO Database Layer, Sessions, Prepared Statements)
- **Database:** MySQL 8+ (InnoDB, Foreign Key Constraints, Indexes)
- **Server Environment:** Apache / XAMPP

---

## Database

- **Database Name:** `fixmate_db`
- **SQL Schema File:** `database/fixmate_db.sql`

### Tables (7 Tables):
1. `users` – User credentials, departments, roles, and account status.
2. `technicians` – Technician specializations, availability, experience, and status.
3. `categories` – Service categories (Electrical, Plumbing, HVAC, Networking, etc.).
4. `complaints` – Master maintenance tickets with location, timestamps, and status.
5. `complaint_comments` – Activity logs and communication thread on tickets.
6. `notifications` – System alert notifications with read/unread tracking.
7. `feedback` – User star ratings (1–5) and review comments.

---

## Project Structure

```
FixMate/
├── index.php                 # Landing page with live database statistics
├── login.php                 # PHP/MySQL authentication with password_verify()
├── register.php              # User registration with server-side validation
├── logout.php                # Session termination
├── test-db.php               # Diagnostic database connection test tool
│
├── config/
│   └── database.php          # Central PDO database connection settings
│
├── includes/
│   ├── header.php            # Global HTML header with meta tags and styles
│   ├── footer.php            # Global footer component
│   ├── sidebar.php           # Dynamic role-based navigation sidebar
│   ├── navbar.php            # Top navigation bar with notification badge
│   ├── auth.php              # Session checks, role-based protection (RBAC)
│   └── functions.php         # Sanitization, ticket code generator, helpers
│
├── user/                     # End-User Module
│   ├── dashboard.php         # User metrics and recent tickets
│   ├── report-problem.php    # Complaint submission form
│   ├── submit-complaint.php  # POST processing & database insertion
│   ├── my-complaints.php     # Filterable complaints list
│   ├── complaint-details.php # Stepper progress, comments & feedback
│   ├── add-comment.php       # Comment submission endpoint
│   ├── confirm-resolution.php# Ticket closure action handler
│   ├── feedback.php          # 1-5 Star rating submission
│   └── profile.php           # User profile and password management
│
├── technician/               # Technician Module
│   ├── dashboard.php         # Task queue and workload overview
│   ├── assigned-tasks.php    # Filterable task ledger
│   ├── task-details.php      # Action modals (Accept, Start, Resolve)
│   ├── accept-task.php       # Accept task endpoint
│   ├── start-task.php        # Start work endpoint (started_date = NOW())
│   ├── resolve-task.php      # Resolve work endpoint with notes
│   └── profile.php           # Availability toggle & credentials
│
├── admin/                    # Administrator Module
│   ├── dashboard.php         # Chart.js analytics and system KPI cards
│   ├── complaints.php        # Master complaint dispatch ledger
│   ├── complaint-details.php # Admin ticket audit view
│   ├── assign-technician.php # Technician assignment handler
│   ├── users.php             # User CRUD management
│   ├── technicians.php       # Technician workload & capacity management
│   ├── categories.php        # Maintenance categories CRUD
│   ├── reports.php           # Statistical analysis & SLA reports
│   ├── export-csv.php        # Direct RFC 4180 CSV export stream
│   ├── notifications.php     # System alerts manager
│   └── settings.php          # Organization profile & database diagnostics
│
├── assets/
│   ├── css/                  # Design system styles
│   │   ├── style.css
│   │   ├── dashboard.css
│   │   ├── forms.css
│   │   ├── tables.css
│   │   └── responsive.css
│   └── js/                   # Vanilla JavaScript client scripts
│       ├── app.js
│       ├── validation.js
│       ├── dashboard.js
│       ├── complaints.js
│       └── notifications.js
│
├── database/
│   └── fixmate_db.sql        # Complete executable SQL schema with demo data
└── README.md
```

---

## Installation & Setup Guide

1. **Install XAMPP:** Download and install XAMPP (PHP 8.0+ & MySQL) from [apachefriends.org](https://www.apachefriends.org).
2. **Start Services:** Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. **Copy Project Folder:** Copy this repository folder to:
   ```
   C:/xampp/htdocs/FixMate/
   ```
4. **Import Database in phpMyAdmin:**
   - Open your browser and go to `http://localhost/phpmyadmin/`.
   - Click the **Import** tab.
   - Choose `database/fixmate_db.sql` and click **Import** (or **Go**).
5. **Verify Database:** Ensure `fixmate_db` appears with all 7 tables populated.
6. **Launch Application:** Open:
   ```
   http://localhost/FixMate/
   ```
   *(Or test DB connection at `http://localhost/FixMate/test-db.php`)*

---

## Demo Credentials

| Role | Email | Password | Access Level |
|---|---|---|---|
| **Admin** | `admin@fixmate.com` | `admin123` | Full administrative control, reports, user CRUD, dispatch |
| **Technician** | `technician@fixmate.com` | `tech123` | Assigned electrical and facility maintenance tickets |
| **User** | `user@fixmate.com` | `user123` | Facility staff/student complaint submission and tracking |

---

## Project Architecture

```
Browser
   ↓  (HTTP / Form Submissions / Fetch)
HTML5 + CSS3 + Vanilla JavaScript
   ↓
PHP 8+ Backend (Auth / RBAC / Business Logic / PDO)
   ↓
MySQL Database (fixmate_db)
```

---

## Academic Information

- **Student:** KUMARESAN K
- **Register Number:** CS157
- **Subject:** Web Technology Lab
- **Project Type:** Individual Project

---

## Future Enhancements

- Automated Email & SMS notification alerts.
- Progressive Web App (PWA) / Mobile App support.
- Real-time WebSocket notifications.
- QR-code based facility asset identification for instant complaint reporting.
- AI-powered complaint category and priority classification.

/**
 * FIXMATE - Authentication & Session Management
 */

const DEMO_ACCOUNTS = {
  admin: {
    username: "admin",
    email: "admin@fixmate.edu",
    password: "admin123",
    role: "admin",
    user_id: 1,
    name: "Admin Manager",
    department: "Facility Management",
    employee_id: "ADM-101"
  },
  technician: {
    username: "technician",
    email: "technician@fixmate.edu",
    password: "tech123",
    role: "technician",
    user_id: 2,
    name: "Arun Kumar",
    department: "Electrical Maintenance",
    employee_id: "TECH-201"
  },
  user: {
    username: "user",
    email: "user@fixmate.edu",
    password: "user123",
    role: "user",
    user_id: 6,
    name: "Kumaresan K",
    department: "Computer Science & Engg",
    employee_id: "CS157"
  }
};

const Auth = {
  getCurrentUser() {
    const sessionStr = sessionStorage.getItem(STORAGE_KEYS.CURRENT_USER) || localStorage.getItem(STORAGE_KEYS.CURRENT_USER);
    if (!sessionStr) return null;
    try {
      return JSON.parse(sessionStr);
    } catch (e) {
      return null;
    }
  },

  setCurrentUser(user, rememberMe = false) {
    const str = JSON.stringify(user);
    sessionStorage.setItem(STORAGE_KEYS.CURRENT_USER, str);
    if (rememberMe) {
      localStorage.setItem(STORAGE_KEYS.CURRENT_USER, str);
    }
  },

  login(identifier, password, role, rememberMe = false) {
    const cleanId = identifier.trim().toLowerCase();
    const cleanPass = password.trim();

    // 1. Check Demo Hardcoded Accounts
    for (const key in DEMO_ACCOUNTS) {
      const demo = DEMO_ACCOUNTS[key];
      if ((cleanId === demo.username || cleanId === demo.email.toLowerCase()) && cleanPass === demo.password) {
        if (role && role !== demo.role) {
          return { success: false, message: `Account exists, but role should be selected as "${demo.role.toUpperCase()}".` };
        }
        this.setCurrentUser(demo, rememberMe);
        return { success: true, user: demo };
      }
    }

    // 2. Check LocalStorage registered users
    const users = StorageManager.getUsers();
    const matchedUser = users.find(u => u.email.toLowerCase() === cleanId || u.employee_id.toLowerCase() === cleanId);

    if (matchedUser) {
      // For registered demo users default pass is user123 or check if passed
      if (cleanPass === "user123" || cleanPass === "password" || cleanPass.length >= 6) {
        if (role && role !== matchedUser.role) {
          return { success: false, message: `Please select role "${matchedUser.role.toUpperCase()}".` };
        }
        const sessionUser = {
          user_id: matchedUser.id,
          name: matchedUser.name,
          email: matchedUser.email,
          role: matchedUser.role,
          department: matchedUser.department,
          employee_id: matchedUser.employee_id
        };
        this.setCurrentUser(sessionUser, rememberMe);
        return { success: true, user: sessionUser };
      } else {
        return { success: false, message: "Invalid password for this account." };
      }
    }

    return { success: false, message: "Invalid username/email or password." };
  },

  register(userData) {
    const existing = StorageManager.getUserByEmail(userData.email);
    if (existing) {
      return { success: false, message: "Email address is already registered." };
    }

    const newUser = StorageManager.addUser({
      name: userData.name,
      email: userData.email,
      phone: userData.phone,
      employee_id: userData.employee_id,
      department: userData.department,
      role: "user"
    });

    return { success: true, user: newUser };
  },

  logout() {
    sessionStorage.removeItem(STORAGE_KEYS.CURRENT_USER);
    localStorage.removeItem(STORAGE_KEYS.CURRENT_USER);
    window.location.href = "login.html";
  },

  checkAccess(requiredRoles = []) {
    const user = this.getCurrentUser();
    const currentPage = window.location.pathname.split("/").pop() || "index.html";

    // Public pages
    const publicPages = ["login.html", "register.html", "index.html", "about.html"];
    if (publicPages.includes(currentPage)) {
      return;
    }

    if (!user) {
      window.location.href = "login.html?redirect=" + encodeURIComponent(currentPage);
      return;
    }

    if (requiredRoles.length > 0 && !requiredRoles.includes(user.role)) {
      alert(`Access Restricted: This page requires [${requiredRoles.join(", ").toUpperCase()}] privileges.`);
      if (user.role === 'admin') window.location.href = "admin-dashboard.html";
      else if (user.role === 'technician') window.location.href = "technician-dashboard.html";
      else window.location.href = "dashboard.html";
    }
  },

  getRedirectUrlForRole(role) {
    switch (role) {
      case 'admin':
        return 'admin-dashboard.html';
      case 'technician':
        return 'technician-dashboard.html';
      case 'user':
      default:
        return 'dashboard.html';
    }
  }
};

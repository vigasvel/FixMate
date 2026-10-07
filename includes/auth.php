<?php
/**
 * FIXMATE - Authentication & Role-Based Access Control (RBAC)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently authenticated user details from session
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'          => (int)$_SESSION['user_id'],
        'full_name'   => $_SESSION['full_name'] ?? 'User',
        'email'       => $_SESSION['email'] ?? '',
        'role'        => $_SESSION['role'] ?? 'user',
        'employee_id' => $_SESSION['employee_id'] ?? '',
        'department'  => $_SESSION['department'] ?? ''
    ];
}

/**
 * Store authenticated user in session
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']     = (int)$user['id'];
    $_SESSION['full_name']   = $user['full_name'];
    $_SESSION['email']       = $user['email'];
    $_SESSION['role']        = $user['role'];
    $_SESSION['employee_id'] = $user['employee_id'] ?? '';
    $_SESSION['department']  = $user['department'] ?? '';
}

/**
 * Destroy user session and log out
 */
function logoutUser(string $basePath = ''): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: " . $basePath . "login.php");
    exit;
}

/**
 * Require Login Middleware
 */
function requireLogin(string $basePath = ''): array {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access this facility resource.');
        header("Location: " . $basePath . "login.php");
        exit;
    }
    return getCurrentUser();
}

/**
 * Require Specific Role Middleware
 * @param array|string $allowedRoles
 */
function requireRole(array|string $allowedRoles, string $basePath = ''): array {
    $user = requireLogin($basePath);
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

    if (!in_array($user['role'], $roles, true)) {
        setFlash('danger', 'Access Restricted: You do not have authorization to view that administrative workspace.');
        $redirectUrl = getRoleDashboardUrl($user['role'], $basePath);
        header("Location: " . $redirectUrl);
        exit;
    }

    return $user;
}

/**
 * Get the target dashboard URL according to role
 */
function getRoleDashboardUrl(string $role, string $basePath = ''): string {
    return match($role) {
        'admin'      => $basePath . 'admin/dashboard.php',
        'technician' => $basePath . 'technician/dashboard.php',
        default      => $basePath . 'user/dashboard.php',
    };
}

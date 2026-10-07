<?php
/**
 * FIXMATE - Global Utility Functions & Database Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * HTML Output Escaper for XSS Prevention
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate Next Unique Sequential Complaint Code in format FM-YYYY-XXXX
 */
function generateComplaintCode(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf("FM-%s-%04d", $year, $count);
}

/**
 * Set Flash Message in Session
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and Clear Flash Message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render Flash Message Alert HTML
 */
function displayFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';

    $type = e($flash['type']);
    $msg = e($flash['message']);
    $icon = match($type) {
        'success' => 'fa-circle-check',
        'danger'  => 'fa-triangle-exclamation',
        'warning' => 'fa-bell',
        default   => 'fa-circle-info'
    };

    return "
    <div class='toast toast-{$type}' style='position:relative; margin-bottom:1.5rem; animation:none; max-width:100%;'>
        <div class='toast-icon'><i class='fas {$icon} text-{$type}'></i></div>
        <div class='toast-content'>
            <div class='toast-title'>" . ucfirst($type) . "</div>
            <div class='toast-msg'>{$msg}</div>
        </div>
        <button class='toast-close' onclick='this.parentElement.remove()'>&times;</button>
    </div>";
}

/**
 * Create a new notification record in MySQL
 */
function createNotification(PDO $pdo, int $userId, ?int $complaintId, string $message, string $type = 'info'): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, complaint_id, message, type, is_read) VALUES (?, ?, ?, ?, 0)");
        return $stmt->execute([$userId, $complaintId, $message, $type]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get Unread Notifications Count for a user
 */
function getUnreadNotificationCount(PDO $pdo, int $userId): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Status Badge HTML Formatter
 */
function getStatusBadge(string $status): string {
    $s = trim($status);
    $class = match(strtolower($s)) {
        'submitted'   => 'badge-submitted',
        'assigned'    => 'badge-assigned',
        'in progress' => 'badge-in-progress',
        'resolved'    => 'badge-resolved',
        'closed'      => 'badge-closed',
        default       => 'badge-submitted'
    };
    $icon = match(strtolower($s)) {
        'submitted'   => 'fa-paper-plane',
        'assigned'    => 'fa-user-check',
        'in progress' => 'fa-screwdriver-wrench',
        'resolved'    => 'fa-circle-check',
        'closed'      => 'fa-lock',
        default       => 'fa-circle'
    };
    return "<span class='badge {$class}'><i class='fas {$icon}'></i> " . e($s) . "</span>";
}

/**
 * Priority Badge HTML Formatter
 */
function getPriorityBadge(string $priority): string {
    $p = trim($priority);
    $class = match(strtolower($p)) {
        'low'      => 'badge-low',
        'medium'   => 'badge-medium',
        'high'     => 'badge-high',
        'critical' => 'badge-critical',
        default    => 'badge-medium'
    };
    $icon = match(strtolower($p)) {
        'low'      => 'fa-arrow-down',
        'medium'   => 'fa-minus',
        'high'     => 'fa-arrow-up',
        'critical' => 'fa-radiation',
        default    => 'fa-circle'
    };
    return "<span class='badge {$class}'><i class='fas {$icon}'></i> " . e($p) . "</span>";
}

/**
 * Format Date String
 */
function formatDate(?string $datetime, string $format = 'd M Y, h:i A'): string {
    if (!$datetime) return 'N/A';
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

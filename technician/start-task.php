<?php
/**
 * FIXMATE - Technician Start Task Endpoint (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['technician', 'admin'], '../');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = (int)($_POST['complaint_id'] ?? 0);

    if ($complaintId > 0) {
        $stmt = $pdo->prepare("
            UPDATE complaints
            SET status = 'In Progress', started_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$complaintId]);

        // Notify User
        $cStmt = $pdo->prepare("SELECT user_id, complaint_code FROM complaints WHERE id = ?");
        $cStmt->execute([$complaintId]);
        $comp = $cStmt->fetch();

        if ($comp) {
            createNotification($pdo, $comp['user_id'], $complaintId, "Technician {$user['full_name']} has started working on complaint {$comp['complaint_code']}.", 'info');
        }

        setFlash('success', 'Work started! Ticket status updated to IN PROGRESS.');
        header("Location: task-details.php?id=" . $complaintId);
        exit;
    }
}

header("Location: assigned-tasks.php");
exit;

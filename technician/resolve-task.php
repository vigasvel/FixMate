<?php
/**
 * FIXMATE - Technician Resolve Task Endpoint (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['technician', 'admin'], '../');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = (int)($_POST['complaint_id'] ?? 0);
    $workPerformed = trim($_POST['work_performed'] ?? '');
    $partsUsed = trim($_POST['parts_used'] ?? '');
    $additionalNotes = trim($_POST['additional_notes'] ?? '');

    if ($complaintId > 0 && !empty($workPerformed)) {
        $notesCombined = $workPerformed . (!empty($additionalNotes) ? " | Note: " . $additionalNotes : "");

        $stmt = $pdo->prepare("
            UPDATE complaints
            SET status = 'Resolved', resolved_date = NOW(), resolution_notes = ?, parts_used = ?
            WHERE id = ?
        ");
        $stmt->execute([$notesCombined, $partsUsed, $complaintId]);

        // Notify User to verify and close
        $cStmt = $pdo->prepare("SELECT user_id, complaint_code FROM complaints WHERE id = ?");
        $cStmt->execute([$complaintId]);
        $comp = $cStmt->fetch();

        if ($comp) {
            createNotification($pdo, $comp['user_id'], $complaintId, "Your complaint {$comp['complaint_code']} was marked as RESOLVED. Please inspect and rate service.", 'success');
        }

        setFlash('success', 'Task marked as RESOLVED! Resolution report saved.');
        header("Location: task-details.php?id=" . $complaintId);
        exit;
    }
}

header("Location: assigned-tasks.php");
exit;

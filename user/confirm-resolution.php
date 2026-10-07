<?php
/**
 * FIXMATE - User Confirm Resolution Endpoint
 * Updates status to 'Closed', records closed_date in MySQL, triggers notifications
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$pdo = getDBConnection();
$current_user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);

    // Verify ownership and status is 'Resolved'
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ? AND user_id = ?");
    $stmt->execute([$complaint_id, $current_user['id']]);
    $complaint = $stmt->fetch();

    if ($complaint && $complaint['status'] === 'Resolved') {
        $stmtUpdate = $pdo->prepare("
            UPDATE complaints 
            SET status = 'Closed', closed_date = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$complaint_id]);

        // Insert system comment
        $stmtComment = $pdo->prepare("
            INSERT INTO complaint_comments (complaint_id, user_id, comment, created_at)
            VALUES (?, ?, 'User confirmed resolution. Complaint is now officially Closed.', NOW())
        ");
        $stmtComment->execute([$complaint_id, $current_user['id']]);

        // Notify technician if assigned
        if ($complaint['technician_id']) {
            $stmtTech = $pdo->prepare("SELECT user_id FROM technicians WHERE id = ?");
            $stmtTech->execute([$complaint['technician_id']]);
            $techUserId = $stmtTech->fetchColumn();
            if ($techUserId) {
                createNotification($pdo, $techUserId, "User confirmed resolution for ticket #{$complaint['complaint_code']}. Ticket marked Closed.", $complaint_id);
            }
        }

        setFlash('success', "Resolution confirmed! Ticket #{$complaint['complaint_code']} has been closed. Please leave your rating and feedback below.");
        header("Location: complaint-details.php?id=" . $complaint_id . "#feedback-section");
        exit;
    } else {
        setFlash('danger', 'Invalid request or complaint is not in Resolved state.');
        header("Location: my-complaints.php");
        exit;
    }
}

header("Location: my-complaints.php");
exit;

<?php
/**
 * FIXMATE - Technician Accept Task Endpoint
 * Acknowledges assigned ticket, logs activity comment, notifies requester
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('technician');

$pdo = getDBConnection();
$current_user = getCurrentUser();

// Get technician ID
$stmtTech = $pdo->prepare("SELECT id FROM technicians WHERE user_id = ?");
$stmtTech->execute([$current_user['id']]);
$tech_id = $stmtTech->fetchColumn();

if (!$tech_id) {
    setFlash('danger', 'Technician profile not found.');
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);

    // Verify task is assigned to this technician
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ? AND technician_id = ?");
    $stmt->execute([$complaint_id, $tech_id]);
    $complaint = $stmt->fetch();

    if ($complaint) {
        $stmtUpdate = $pdo->prepare("
            UPDATE complaints 
            SET status = 'Assigned', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$complaint_id]);

        // Add progress comment
        $commentText = "Technician " . $current_user['full_name'] . " accepted the assignment and scheduled maintenance inspection.";
        $stmtComment = $pdo->prepare("
            INSERT INTO complaint_comments (complaint_id, user_id, comment, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmtComment->execute([$complaint_id, $current_user['id'], $commentText]);

        // Notify User
        createNotification(
            $pdo, 
            $complaint['user_id'], 
            "Technician {$current_user['full_name']} has accepted your maintenance complaint #{$complaint['complaint_code']}.", 
            $complaint_id
        );

        setFlash('success', "Task #{$complaint['complaint_code']} accepted and queued for execution.");
        header("Location: task-details.php?id=" . $complaint_id);
        exit;
    } else {
        setFlash('danger', 'Task not found or not assigned to your account.');
        header("Location: assigned-tasks.php");
        exit;
    }
}

header("Location: assigned-tasks.php");
exit;

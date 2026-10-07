<?php
/**
 * FIXMATE - Admin Assign Technician Endpoint (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole('admin', '../');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = (int)($_POST['complaint_id'] ?? 0);
    $technicianId = (int)($_POST['technician_id'] ?? 0);

    if ($complaintId > 0 && $technicianId > 0) {
        // Fetch technician user details
        $tStmt = $pdo->prepare("SELECT t.user_id, u.full_name FROM technicians t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $tStmt->execute([$technicianId]);
        $tech = $tStmt->fetch();

        // Fetch complaint details
        $cStmt = $pdo->prepare("SELECT user_id, complaint_code, title, status FROM complaints WHERE id = ?");
        $cStmt->execute([$complaintId]);
        $comp = $cStmt->fetch();

        if ($tech && $comp) {
            $newStatus = ($comp['status'] === 'Submitted') ? 'Assigned' : $comp['status'];

            $upStmt = $pdo->prepare("
                UPDATE complaints 
                SET technician_id = ?, status = ?, assigned_date = NOW()
                WHERE id = ?
            ");
            $upStmt->execute([$technicianId, $newStatus, $complaintId]);

            // Notify User
            createNotification($pdo, $comp['user_id'], $complaintId, "Your complaint {$comp['complaint_code']} has been assigned to technician {$tech['full_name']}.", 'info');

            // Notify Technician
            createNotification($pdo, $tech['user_id'], $complaintId, "New task assignment: {$comp['complaint_code']} - {$comp['title']}.", 'warning');

            setFlash('success', "Complaint {$comp['complaint_code']} assigned to {$tech['full_name']} successfully.");
        }
    }
}

header("Location: complaints.php");
exit;

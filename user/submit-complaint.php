<?php
/**
 * FIXMATE - Complaint Submission POST Handler (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['user', 'admin'], '../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: report-problem.php");
    exit;
}

$title = trim($_POST['title'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$building = trim($_POST['building'] ?? '');
$floor = trim($_POST['floor'] ?? '');
$roomNumber = trim($_POST['room_number'] ?? '');
$priority = trim($_POST['priority'] ?? 'Medium');
$description = trim($_POST['description'] ?? '');
$preferredDate = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : date('Y-m-d');
$contactNumber = trim($_POST['contact_number'] ?? '');

// Validation
if (empty($title) || empty($categoryId) || empty($building) || empty($floor) || empty($description) || empty($contactNumber)) {
    setFlash('danger', 'Please fill in all mandatory complaint fields.');
    header("Location: report-problem.php");
    exit;
}

$location = "{$building} - {$floor}" . (!empty($roomNumber) ? " ({$roomNumber})" : "");

$pdo = getDB();

try {
    $pdo->beginTransaction();

    $complaintCode = generateComplaintCode($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO complaints 
        (complaint_code, user_id, category_id, title, description, location, building, floor, room_number, priority, status, preferred_date, contact_number, submitted_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?, ?, NOW())
    ");

    $stmt->execute([
        $complaintCode,
        $user['id'],
        $categoryId,
        $title,
        $description,
        $location,
        $building,
        $floor,
        $roomNumber,
        $priority,
        $preferredDate,
        $contactNumber
    ]);

    $complaintId = (int)$pdo->lastInsertId();

    // Create Notification for Admin
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $adminId = (int)$adminStmt->fetchColumn() ?: 1;
    createNotification($pdo, $adminId, $complaintId, "New {$priority} ticket {$complaintCode} submitted by {$user['full_name']} in {$location}.", $priority === 'Critical' ? 'danger' : 'info');

    $pdo->commit();

    setFlash('success', "Complaint {$complaintCode} submitted successfully! Our facility staff will inspect it shortly.");
    header("Location: complaint-details.php?id=" . urlencode($complaintCode));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Failed to save complaint to MySQL database: ' . $e->getMessage());
    header("Location: report-problem.php");
    exit;
}

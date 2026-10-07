<?php
/**
 * FIXMATE - Add Comment Endpoint (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireLogin('../');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = (int)($_POST['complaint_id'] ?? 0);
    $complaintCode = trim($_POST['complaint_code'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($complaintId > 0 && !empty($comment)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO complaint_comments (complaint_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$complaintId, $user['id'], $comment]);
        setFlash('success', 'Your comment has been posted.');
    }
    
    header("Location: complaint-details.php?id=" . urlencode($complaintCode));
    exit;
}

header("Location: my-complaints.php");
exit;

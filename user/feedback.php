<?php
/**
 * FIXMATE - Feedback & Ticket Closure Handler (PHP + MySQL)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(['user', 'admin'], '../');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = (int)($_POST['complaint_id'] ?? 0);
    $complaintCode = trim($_POST['complaint_code'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) $rating = 5;

    if ($complaintId > 0) {
        $pdo = getDB();

        try {
            $pdo->beginTransaction();

            // 1. Update Complaint Status to Closed
            $updateStmt = $pdo->prepare("
                UPDATE complaints 
                SET status = 'Closed', closed_date = NOW()
                WHERE id = ? AND (user_id = ? OR ? = 'admin')
            ");
            $updateStmt->execute([$complaintId, $user['id'], $user['role']]);

            // 2. Insert Feedback Record (prevent duplicate)
            $fbStmt = $pdo->prepare("
                INSERT INTO feedback (complaint_id, user_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)
            ");
            $fbStmt->execute([$complaintId, $user['id'], $rating, $comment]);

            // 3. Notify Technician & Admin
            $cStmt = $pdo->prepare("SELECT technician_id, title FROM complaints WHERE id = ?");
            $cStmt->execute([$complaintId]);
            $comp = $cStmt->fetch();

            if ($comp && !empty($comp['technician_id'])) {
                // Get technician user_id
                $tStmt = $pdo->prepare("SELECT user_id FROM technicians WHERE id = ?");
                $tStmt->execute([$comp['technician_id']]);
                $techUserId = (int)$tStmt->fetchColumn();

                if ($techUserId > 0) {
                    createNotification($pdo, $techUserId, $complaintId, "User gave a {$rating}-star rating for your resolution on {$complaintCode}.", 'success');
                }
            }

            $pdo->commit();
            setFlash('success', 'Thank you! Your feedback has been recorded and ticket ' . $complaintCode . ' is now closed.');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('danger', 'Failed to save feedback: ' . $e->getMessage());
        }
    }

    header("Location: my-complaints.php");
    exit;
}

header("Location: my-complaints.php");
exit;

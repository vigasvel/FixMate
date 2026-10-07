<?php
/**
 * FIXMATE - Admin CSV Export Endpoint
 * Streams live database complaint records directly to standard RFC 4180 CSV
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$pdo = getDBConnection();

$start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('-6 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        c.complaint_code AS `Complaint ID`,
        u.full_name AS `Requester Name`,
        u.email AS `Requester Email`,
        u.department AS `Department`,
        c.title AS `Title`,
        cat.category_name AS `Category`,
        c.location AS `Location`,
        c.building AS `Building`,
        c.floor AS `Floor`,
        c.room_number AS `Room`,
        c.priority AS `Priority`,
        c.status AS `Status`,
        tech_user.full_name AS `Assigned Technician`,
        c.submitted_date AS `Submitted Date`,
        c.assigned_date AS `Assigned Date`,
        c.started_date AS `Started Date`,
        c.resolved_date AS `Resolved Date`,
        c.closed_date AS `Closed Date`,
        c.resolution_notes AS `Resolution Notes`
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    LEFT JOIN users tech_user ON t.user_id = tech_user.id
    WHERE DATE(c.submitted_date) BETWEEN ? AND ?
    ORDER BY c.id DESC
");
$stmt->execute([$start_date, $end_date]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output CSV headers
$filename = "fixmate_complaints_report_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Output UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if (!empty($rows)) {
    // Write headers
    fputcsv($output, array_keys($rows[0]));
    
    // Write data rows
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No records found for the selected period']);
}

fclose($output);
exit;

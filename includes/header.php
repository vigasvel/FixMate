<?php
/**
 * FIXMATE - Global HTML Header Component
 */
if (!isset($base_url)) {
    $currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
    $base_url = in_array($currentDir, ['user', 'technician', 'admin']) ? '../' : './';
}

$page_title = isset($page_title) ? $page_title . " – FixMate" : "FixMate – Smart Complaint & Maintenance Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="Enterprise Facility & Maintenance Management System for Colleges, Hospitals, Offices and Enterprises.">
  
  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Core Design System CSS -->
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/dashboard.css">
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/forms.css">
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/tables.css">
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/responsive.css">

  <!-- Chart.js CDN for Analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-layout">

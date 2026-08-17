<?php
// adminlogin/export_reports.php - Export Municipal Complaint Redressal Reports (CSV/Excel)
session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$whereClauses = ["1=1"];

if (!empty($statusFilter) && $statusFilter !== 'All') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $whereClauses[] = "p.status = '$safeStatus'";
}

if (!empty($categoryFilter) && $categoryFilter !== 'All') {
    $safeCat = mysqli_real_escape_string($conn, $categoryFilter);
    $whereClauses[] = "p.category = '$safeCat'";
}

if (!empty($fromDate)) {
    $safeFrom = mysqli_real_escape_string($conn, $fromDate);
    $whereClauses[] = "DATE(p.created_at) >= '$safeFrom'";
}

if (!empty($toDate)) {
    $safeTo = mysqli_real_escape_string($conn, $toDate);
    $whereClauses[] = "DATE(p.created_at) <= '$safeTo'";
}

$whereSql = implode(" AND ", $whereClauses);

$query = "SELECT p.id, p.category, p.description, p.street, p.area, p.city, p.pincode, 
                 p.status, p.ai_severity, p.created_at,
                 u.name as citizen_name, u.email as citizen_email, u.mobile as citizen_mobile,
                 w.name as worker_name, w.mobile as worker_mobile
          FROM problems p
          LEFT JOIN people u ON p.user_id = u.id
          LEFT JOIN workers w ON p.worker_id = w.id
          WHERE $whereSql
          ORDER BY p.id DESC";

$result = mysqli_query($conn, $query);

$filename = "CivicConnect_Grievance_Report_" . date('Y-m-d_His') . ".csv";

// Set CSV download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Write CSV Headers
fputcsv($output, [
    'Grievance ID',
    'Category',
    'Description',
    'Street / Landmark',
    'Area / Ward',
    'City',
    'Pincode',
    'Citizen Name',
    'Citizen Mobile',
    'Assigned Field Officer',
    'Officer Contact',
    'Severity Level',
    'Current Status',
    'Date Reported'
]);

// Write Rows
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            '#' . $row['id'],
            $row['category'],
            $row['description'],
            $row['street'],
            $row['area'],
            $row['city'],
            $row['pincode'],
            $row['citizen_name'] ?: 'N/A',
            $row['citizen_mobile'] ?: 'N/A',
            $row['worker_name'] ?: 'Unassigned',
            $row['worker_mobile'] ?: 'N/A',
            $row['ai_severity'] ?: 'Medium',
            $row['status'],
            $row['created_at']
        ]);
    }
}

fclose($output);
exit();

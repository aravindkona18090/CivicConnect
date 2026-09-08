<?php
// api/get_notifications.php - Real-time Citizen Notification Center API
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'notifications' => []]);
    exit();
}

$userId = intval($_SESSION['user_id']);

$query = "SELECT p.id, p.category, p.status, p.created_at, p.after_photo,
                 w.name as worker_name, w.mobile as worker_mobile
          FROM problems p
          LEFT JOIN workers w ON p.worker_id = w.id
          WHERE p.user_id = '$userId'
          ORDER BY p.id DESC
          LIMIT 6";

$res = mysqli_query($conn, $query);
$notifications = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $type = 'info';
        $title = "Grievance #{$row['id']}";
        $msg = "";
        $timeAgo = date('M d, H:i', strtotime($row['created_at']));

        if ($row['status'] === 'Completed') {
            $type = 'success';
            $title = "✅ Grievance Resolved #{$row['id']}";
            $msg = "Your reported issue in {$row['category']} has been successfully fixed! (+150 Karma XP)";
        } elseif ($row['status'] === 'In Progress') {
            $type = 'warning';
            $title = "👷 Field Officer Dispatched #{$row['id']}";
            $officer = $row['worker_name'] ?: 'Municipal Field Worker';
            $msg = "Officer {$officer} is currently working on the site.";
        } else {
            $type = 'pending';
            $title = "📋 Complaint Registered #{$row['id']}";
            $msg = "Grievance logged under {$row['category']} and queued for field allocation.";
        }

        $notifications[] = [
            'id' => $row['id'],
            'type' => $type,
            'title' => $title,
            'message' => $msg,
            'status' => $row['status'],
            'time' => $timeAgo
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications
]);

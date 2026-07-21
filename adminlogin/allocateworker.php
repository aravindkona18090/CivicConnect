<?php
session_start();
include('../db/connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: adminlogin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem_id = intval($_POST['problem_id']);
    $worker_id = intval($_POST['worker_id']);

    if ($worker_id > 0 && $problem_id > 0) {
        $allocated_at = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE problems SET worker_id = ?, allocated_at = ? WHERE id = ?");
        $stmt->bind_param("isi", $worker_id, $allocated_at, $problem_id);

        if ($stmt->execute()) {
            header("Location: admindashboard.php?success=Worker+assigned");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Invalid worker or problem ID.";
    }
} else {
    header("Location: admindashboard.php");
    exit();
}
?>

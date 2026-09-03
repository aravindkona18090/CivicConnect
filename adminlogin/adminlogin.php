<?php
session_start();
include('../db/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = trim($_POST['username'] ?? $_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $table = "admin1";
    $test = mysqli_query($conn, "SHOW TABLES LIKE 'admin1'");
    if (!$test || mysqli_num_rows($test) === 0) {
        $table = "admin";
    }

    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $input, $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['adminname'] = $user['username'] ?? $user['adminname'] ?? 'Admin';
            $_SESSION['admin_email'] = $user['email'];

            header("Location: admindashboard.php");
            exit();
        } else {
            header("Location: login.php?error=Invalid+password");
            exit();
        }
    } else {
        header("Location: login.php?error=No+admin+account+found");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}

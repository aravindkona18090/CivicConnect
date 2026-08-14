<?php
session_start();
include('../db/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM workers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['worker_id'] = $user['id'];
            $_SESSION['workername'] = $user['name'];
            $_SESSION['worker_email'] = $user['email'];

            header("Location: workerdashboard.php"); // redirect to dashboard
            exit();
        } else {
            header("Location: login.php?error=Invalid+password");
            exit();
        }
    } else {
        header("Location: login.php?error=No+user+found");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>

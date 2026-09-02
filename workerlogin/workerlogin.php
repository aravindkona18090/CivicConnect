<?php
session_start();
include('../db/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_id = trim($_POST['login_id'] ?? $_POST['email'] ?? $_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($login_id) || empty($password)) {
        header("Location: login.php?error=" . urlencode("Please enter both login identifier and password."));
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM workers WHERE email = ? OR name = ? OR id = ?");
    $stmt->bind_param("sss", $login_id, $login_id, $login_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['worker_id'] = $user['id'];
            $_SESSION['workername'] = $user['name'];
            $_SESSION['worker_email'] = $user['email'];
            $_SESSION['worker_category'] = $user['category'] ?? '';

            header("Location: workerdashboard.php");
            exit();
        } else {
            header("Location: login.php?error=" . urlencode("Incorrect password entered. Please try again."));
            exit();
        }
    } else {
        header("Location: login.php?error=" . urlencode("No Field Officer account found with these details."));
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>

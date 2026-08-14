<?php
include('../db/connection.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['emailSign']);
    $mobile     = trim($_POST['mobile']);
    $password   = trim($_POST['createPass']);
    $confirmpwd = trim($_POST['confirmPass']);

    // Check password match
    if ($password !== $confirmpwd) {
        header("Location: login.php?error=Passwords+do+not+match");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM people WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error_message2 = "User with this email already exists.";
        header("Location: login.php?error2=" . urlencode($error_message2));
        exit();
    } else {
        // Redirect to OTP verification with safe values
        header("Location: otp_verify.php?email=" . urlencode($email) . "&name=" . urlencode($username) . "&password=" . urlencode($hashedPassword) . "&phone=" . urlencode($mobile));
        exit();
    }
}
?>

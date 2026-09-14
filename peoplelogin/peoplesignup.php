<?php
include('../db/connection.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['emailSign'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $password   = trim($_POST['createPass'] ?? '');
    $confirmpwd = isset($_POST['confirmPass']) ? trim($_POST['confirmPass']) : $password;

    if (empty($username) || empty($email) || empty($password)) {
        header("Location: login.php?error2=" . urlencode("Please fill in all required fields."));
        exit();
    }

    // Check password match
    if ($password !== $confirmpwd) {
        header("Location: login.php?error2=" . urlencode("Passwords do not match."));
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
        $error_message2 = "A citizen with this email address already exists. Please log in.";
        header("Location: login.php?error2=" . urlencode($error_message2));
        exit();
    } else {
        // Securely store pending signup data in session (zero URL credential leakage)
        $_SESSION['pending_signup'] = [
            'username' => $username,
            'email'    => $email,
            'mobile'   => $mobile,
            'password' => $hashedPassword
        ];
        
        // Reset previous OTP
        unset($_SESSION['otp'], $_SESSION['otp_timestamp']);

        header("Location: otp_verify.php");
        exit();
    }
}
?>

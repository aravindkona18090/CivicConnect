<?php
include('../db/connection.php');

$email = "civicconnect24@gmail.com";
$username = "admin";
$mobile = "0000000000";
$password = password_hash("admin@123", PASSWORD_DEFAULT); // HASH the password

// Delete old admin row if exists
$conn->query("DELETE FROM admin1 WHERE email='$email'");

$stmt = $conn->prepare("INSERT INTO admin1 (username, email, mobile, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $mobile, $password);
$stmt->execute();

echo "Admin inserted successfully!";
?>

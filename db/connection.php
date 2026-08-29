<?php
// Railway environment variables (if available), otherwise use local XAMPP values
$servername = getenv("MYSQLHOST") ?: "localhost";
$username   = getenv("MYSQLUSER") ?: "root";
$password   = getenv("MYSQLPASSWORD") ?: "";
$dbname     = getenv("MYSQLDATABASE") ?: "civicconnect";
$port       = getenv("MYSQLPORT") ?: 3306;

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname, $port);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

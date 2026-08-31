<?php
require_once __DIR__ . '/../config.php';

// Environment variables or central config loader
$servername = civic_config("MYSQLHOST", "localhost");
$username   = civic_config("MYSQLUSER", "root");
$password   = civic_config("MYSQLPASSWORD", "");
$dbname     = civic_config("MYSQLDATABASE", "test");
$port       = intval(civic_config("MYSQLPORT", 3306));

// Initialize MySQLi
$conn = mysqli_init();

if (!$conn) {
    die("mysqli_init failed");
}

// Enable SSL for cloud database hosts (TiDB Cloud, Aiven, Railway)
if ($servername !== 'localhost' && $servername !== '127.0.0.1') {
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    $connResult = @mysqli_real_connect($conn, $servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    $connResult = @mysqli_real_connect($conn, $servername, $username, $password, $dbname, $port);
}

// Check connection
if (!$connResult) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>

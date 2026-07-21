<?php
$servername = "localhost";
$username = "root";       // default WAMP username
$password = "";           // default WAMP password is empty
$dbname = "gov_problems";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

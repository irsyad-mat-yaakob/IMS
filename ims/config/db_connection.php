<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = ""; // Update with your database password if needed
$database = "db_ims";

// Create database connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
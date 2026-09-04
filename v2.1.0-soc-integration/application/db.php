<?php
// Shared database connection for TechSecure v2.0
$conn = mysqli_connect("localhost", "pengelola", "password_db_123", "techsecure_db");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>

<?php
// Includes_dynamics/dataB.php

// Enable error reporting for debugging during development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Default XAMPP password is empty
$db_name = 'edulend_db';

try {
    // Establish a secure connection using MySQLi object-oriented style
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    // Set charset to match your HTML form encoding
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Guard clause: If connection fails, halt execution safely without revealing passwords
    die("Database Connection Failure: System could not authenticate secure data channels.");
}
?>
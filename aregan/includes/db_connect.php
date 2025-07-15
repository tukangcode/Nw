<?php
// /aregan/includes/db_connect.php

// --- Database Configuration ---
// Replace these with your actual database details 
define('DB_SERVER', ''); // Usually 'localhost' or a specific server name like 
define('DB_USERNAME', ''); // Your database username
define('DB_PASSWORD', ''); // Your database password
define('DB_NAME', '');       // The name of the database you created

// --- Create Database Connection ---
try {
    // We use PDO (PHP Data Objects) for a secure, modern connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);

    // Set PDO error mode to exception to catch any database errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set the default fetch mode to associative array for easier handling
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If connection fails, stop everything and show an error.
    // In a live production environment, you might want to log this error instead of showing it to the user.
    http_response_code(500); // Internal Server Error
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}

// The $pdo variable is now available for any script that includes this file.
?>
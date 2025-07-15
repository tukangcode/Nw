<?php
// /aregan/admin/auth.php

// We need to work with sessions
session_start();

// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not, redirect to login page
    header('Location: login.php');
    exit;
}

// Include our database connection
require_once '../includes/db_connect.php';

$username = $_POST['username'];
$password = $_POST['password'];

// Basic validation
if (empty($username) || empty($password)) {
    header('Location: login.php?error=empty');
    exit;
}

try {
    // Prepare SQL to find the user by username
    $sql = "SELECT id, username, password_hash FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the user
    $user = $stmt->fetch();

    // Verify the user exists and the password is correct
    // password_verify() is the secure way to check a hashed password
    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct!
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user information in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Redirect to the admin dashboard
        header('Location: index.php');
        exit;
    } else {
        // Password is not correct or user does not exist
        header('Location: login.php?error=invalid');
        exit;
    }

} catch (PDOException $e) {
    // Database error
    // In a real app, you'd log this error instead of showing it
    die("Database error: " . $e->getMessage());
}
?>
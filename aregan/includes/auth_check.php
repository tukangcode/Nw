<?php

// /aregan/includes/auth_check.php



// Start session management

session_start();



// Check if the user_id session variable is not set

if (!isset($_SESSION['user_id'])) {

    // If not logged in, redirect them to the login page

    header('Location: /aregan/admin/login.php');  // Change and adjust based on you directory or 
                                                    //  Path name

    exit; // Stop executing the script

}



// You can also add other checks here, e.g., check user roles, last activity time, etc.

?>
<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page with the correct path
header("Location: /t2_t3_assessment/admin/authentication/login.php"); // Adjust this path to your login page
exit();
?>
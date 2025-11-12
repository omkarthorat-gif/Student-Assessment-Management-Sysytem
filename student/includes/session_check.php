<?php
// student/includes/session_check.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and has the Student role
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true || 
    !isset($_SESSION['reg_no']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    // Redirect to login page if not logged in or not a student
    header("Location: /t2_t3_assessment/student/authentication/login.php");
    exit();
}

// Optional: Session timeout (e.g., 30 minutes)
$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: /t2_t3_assessment/student/authentication/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();
?>
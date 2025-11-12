<?php
// t2_t3_assessment/faculty/includes/session_check.php
session_start();

// Define session timeout duration (e.g., 30 minutes = 1800 seconds)
define('SESSION_TIMEOUT', 1800);

// Function to check if the session has timed out
function isSessionTimedOut() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        return true;
    }
    return false;
}

// Update last activity time for the current role
$_SESSION['last_activity'] = time();

// Determine the current directory context (faculty, student, or potentially admin)
$current_dir = basename(dirname(dirname(__FILE__))); // Gets 'faculty' or 'student' or 'admin'

// Role-specific session checks
if ($current_dir === 'faculty') {
    // Faculty-specific session check
    if (!isset($_SESSION['faculty_logged_in']) || $_SESSION['faculty_logged_in'] !== true) {
        // User is not logged in as faculty
        session_unset();
        session_destroy();
        header("Location: /t2_t3_assessment/faculty/authentication/login.php?error=not_logged_in");
        exit();
    } elseif (isSessionTimedOut()) {
        // Session has timed out
        unset($_SESSION['faculty_logged_in']);
        session_destroy();
        header("Location: /t2_t3_assessment/faculty/authentication/login.php?error=session_timeout");
        exit();
    }
} elseif ($current_dir === 'student') {
    // Student-specific session check (for reference; update in student/includes/session_check.php)
    if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
        // User is not logged in as student
        session_unset();
        session_destroy();
        header("Location: /t2_t3_assessment/student/authentication/login.php?error=not_logged_in");
        exit();
    } elseif (isSessionTimedOut()) {
        // Session has timed out
        unset($_SESSION['student_logged_in']);
        session_destroy();
        header("Location: /t2_t3_assessment/student/authentication/login.php?error=session_timeout");
        exit();
    }
} elseif ($current_dir === 'admin') {
    // Admin-specific session check (for reference; update in admin/includes/session_check.php if applicable)
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // User is not logged in as admin
        session_unset();
        session_destroy();
        header("Location: /t2_t3_assessment/admin/authentication/login.php?error=not_logged_in");
        exit();
    } elseif (isSessionTimedOut()) {
        // Session has timed out
        unset($_SESSION['admin_logged_in']);
        session_destroy();
        header("Location: /t2_t3_assessment/admin/authentication/login.php?error=session_timeout");
        exit();
    }
} else {
    // Invalid directory context (neither faculty, student, nor admin)
    session_unset();
    session_destroy();
    header("Location: /t2_t3_assessment/index.php?error=invalid_access");
    exit();
}

// Additional security: Prevent session fixation by regenerating session ID periodically for the current role
$session_key = '';
switch ($current_dir) {
    case 'faculty':
        $session_key = 'faculty_created';
        break;
    case 'student':
        $session_key = 'student_created';
        break;
    case 'admin':
        $session_key = 'admin_created';
        break;
    default:
        $session_key = 'created';
}

if (!isset($_SESSION[$session_key]) || (time() - $_SESSION[$session_key] > 3600)) {
    session_regenerate_id(true);
    $_SESSION[$session_key] = time();
}
?>
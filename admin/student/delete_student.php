<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Initialize variables
$reg_no = "";
$error = "";
$success = "";

// Check if reg_no is provided in URL
if (isset($_GET['reg_no']) && !empty($_GET['reg_no'])) {
    $reg_no = trim($_GET['reg_no']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, check if the student exists
        $check_stmt = $conn->prepare("SELECT reg_no FROM Students WHERE reg_no = ?");
        $check_stmt->bind_param("s", $reg_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            throw new Exception("Student with registration number $reg_no not found.");
        }
        
        // Delete from T2_T3_Submissions - this will cascade delete from Marks table
        $delete_submissions_stmt = $conn->prepare("DELETE FROM T2_T3_Submissions WHERE reg_no = ?");
        $delete_submissions_stmt->bind_param("s", $reg_no);
        if (!$delete_submissions_stmt->execute()) {
            throw new Exception("Failed to delete submissions for $reg_no: " . $conn->error);
        }
        
        // Delete from Students table
        $delete_student_stmt = $conn->prepare("DELETE FROM Students WHERE reg_no = ?");
        $delete_student_stmt->bind_param("s", $reg_no);
        if (!$delete_student_stmt->execute()) {
            throw new Exception("Failed to delete student $reg_no: " . $conn->error);
        }
        
        // Delete from Users table
        $delete_user_stmt = $conn->prepare("DELETE FROM Users WHERE username = ? AND role = 'Student'");
        $delete_user_stmt->bind_param("s", $reg_no);
        if (!$delete_user_stmt->execute()) {
            throw new Exception("Failed to delete user $reg_no: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Student with registration number $reg_no has been deleted successfully.";
        
        // Redirect back to manage_students.php (assuming this is where you want to redirect)
        header("Location: view_students.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Set error message
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        
        // Redirect back to manage_students.php
        header("Location: view_students.php");
        exit();
    }
    
} else {
    // No reg_no provided, redirect back to manage_students.php
    $_SESSION['error_message'] = "Error: No registration number provided.";
    header("Location: view_students.php");
    exit();
}
?>

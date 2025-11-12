<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Check if dept_id is provided
if (!isset($_GET['dept_id']) || empty($_GET['dept_id'])) {
    $_SESSION['error_message'] = "No department selected for deletion.";
    header("Location: view_departments.php");
    exit;
}

$dept_id = trim($_GET['dept_id']);

// Check if department exists
$stmt = $conn->prepare("SELECT dept_id FROM Departments WHERE dept_id = ?");
$stmt->bind_param("s", $dept_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Department not found.";
    header("Location: view_departments.php");
    exit;
}

// Check for related data in dependent tables
$tables_to_check = [
    'Students' => "SELECT COUNT(*) as count FROM Students WHERE dept_id = ?",
    'Faculty' => "SELECT COUNT(*) as count FROM Faculty WHERE dept_id = ?",
    'Sections' => "SELECT COUNT(*) as count FROM Sections WHERE dept_id = ?",
    'Subjects' => "SELECT COUNT(*) as count FROM Subjects WHERE dept_id = ?",
];

$has_related_data = false;
foreach ($tables_to_check as $table => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $has_related_data = true;
        break;
    }
}

if ($has_related_data) {
    $_SESSION['error_message'] = "Cannot delete department because it is associated with students, faculty, sections, or subjects.";
    header("Location: view_departments.php");
    exit;
}

// Delete the department
$stmt = $conn->prepare("DELETE FROM Departments WHERE dept_id = ?");
$stmt->bind_param("s", $dept_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Department deleted successfully.";
} else {
    $_SESSION['error_message'] = "Error deleting department: " . $stmt->error;
}

header("Location: view_departments.php");
exit;
?>
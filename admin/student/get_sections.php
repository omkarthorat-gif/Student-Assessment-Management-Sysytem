<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Check if required parameters are provided
if (!isset($_POST['year']) || !isset($_POST['dept_id'])) {
    echo json_encode([]);
    exit;
}

$year = intval($_POST['year']);
$dept_id = $_POST['dept_id'];

// Get available sections for the department and year
$query = "SELECT DISTINCT section_name FROM Students 
          WHERE dept_id = ? AND year = ? 
          ORDER BY section_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $dept_id, $year);
$stmt->execute();
$result = $stmt->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = $row;
}

// If no sections found, return default sections
if (empty($sections)) {
    foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $section) {
        $sections[] = ['section_name' => $section];
    }
}

// Return sections as JSON
header('Content-Type: application/json');
echo json_encode($sections);
exit;
?>
<?php
// t2_t3_assessment/student/download_submission.php
require_once 'includes/session_check.php';  // Ensure student is logged in
require_once '../config.php';              // Database connection

$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$reg_no = $_SESSION['reg_no'];

if ($submission_id <= 0) {
    die("Invalid submission ID.");
}

// Fetch file path
$query = "SELECT file_path FROM t2_t3_submissions WHERE submission_id = ? AND reg_no = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $submission_id, $reg_no);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();

if (!$submission || !file_exists($submission['file_path'])) {
    die("File not found or you don't have permission to access it.");
}

$file_path = $submission['file_path'];
$file_name = basename($file_path);

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache');
header('Pragma: no-cache');

// Output file content
readfile($file_path);
exit;
?>
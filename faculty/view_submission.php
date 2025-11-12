<?php
// t2_t3_assessment/faculty/view_submission.php
require_once 'includes/session_check.php';
require_once '../config.php';

$submission_id = isset($_GET['submission_id']) ? $_GET['submission_id'] : 0;

$query = "SELECT sub.file_path, sub.document_type, sub.reg_no, sub.subject_id
          FROM t2_t3_submissions sub
          WHERE sub.submission_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();

if ($submission) {
    $file_path = $submission['file_path'];
    $file_name = basename($file_path);
    
    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        die("File not found.");
    }
} else {
    die("Submission not found.");
}

$stmt->close();
mysqli_close($conn);
?>
<?php
// t2_t3_assessment/student/upload_submission.php
require_once 'includes/session_check.php';  // Ensure student is logged in
require_once '../config.php';              // Database connection

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_no = $_SESSION['reg_no'];
    $subject_id = $_POST['subject_id'] ?? '';
    $document_type = $_POST['document_type'] ?? '';
    
    // Get student details to validate year, semester, and dept_id
    $student_query = "SELECT year, semester, dept_id FROM students WHERE reg_no = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("s", $reg_no);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        die("Student not found.");
    }

    $year = $student['year'];
    $semester = $student['semester'];
    $dept_id = $student['dept_id'];

    // Validate subject allocation
    $subject_check_query = "SELECT * FROM subject_allocation WHERE subject_id = ? AND year = ? AND semester = ? AND dept_id = ?";
    $stmt = $conn->prepare($subject_check_query);
    $stmt->bind_param("siis", $subject_id, $year, $semester, $dept_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        die("Invalid subject or allocation.");
    }

    // File upload handling
    $file_field = $document_type === 'T2' ? 't2_file' : 't3_file';
    if (!isset($_FILES[$file_field]) || $_FILES[$file_field]['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['error'] = "No file uploaded.";
        header("Location: dashboard/index.php");
        exit;
    }

    $file = $_FILES[$file_field];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    // Define allowed file types and max size (e.g., 5MB)
    $allowed_types = $document_type === 'T2' ? ['docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'] : 
                                              ['ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validate file
    if ($file_error !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Error uploading file.";
        header("Location: dashboard/index.php");
        exit;
    }

    if (!array_key_exists($file_ext, $allowed_types)) {
        $_SESSION['error'] = "Invalid file type. Allowed types: " . implode(", ", array_keys($allowed_types));
        header("Location: dashboard/index.php");
        exit;
    }

    if ($file_size > $max_size) {
        $_SESSION['error'] = "File size exceeds 5MB limit.";
        header("Location: dashboard/index.php");
        exit;
    }

    // Verify MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        $_SESSION['error'] = "Invalid file format detected.";
        header("Location: dashboard/index.php");
        exit;
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/submissions/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $unique_name = $reg_no . '_' . $subject_id . '_' . $document_type . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_name;

    // Move file to uploads directory
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Insert into t2_t3_submissions table
        $insert_query = "INSERT INTO t2_t3_submissions (reg_no, subject_id, year, semester, document_type, file_path, upload_status, upload_date) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Uploaded', CURDATE())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssiiss", $reg_no, $subject_id, $year, $semester, $document_type, $file_path);
        
        if ($stmt->execute()) {
            // Insert into marks table with default values
            $submission_id = $conn->insert_id;
            $marks_query = "INSERT INTO marks (submission_id, assessed_status) VALUES (?, 'No')";
            $stmt = $conn->prepare($marks_query);
            $stmt->bind_param("i", $submission_id);
            $stmt->execute();

            $_SESSION['success'] = "$document_type submission uploaded successfully!";
        } else {
            unlink($file_path); // Remove file if DB insertion fails
            $_SESSION['error'] = "Failed to save submission to database.";
        }
    } else {
        $_SESSION['error'] = "Failed to upload file.";
    }

    $stmt->close();
    mysqli_close($conn);
    header("Location: dashboard/index.php");
    exit;
} else {
    // If not POST request, redirect to dashboard
    header("Location: dashboard/index.php");
    exit;
}
?>
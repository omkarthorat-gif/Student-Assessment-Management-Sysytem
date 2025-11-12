<?php
// get_students.php
require_once '../../config.php';

header('Content-Type: application/json');

$year = $_GET['year'];
$section = $_GET['section'];
$subject_id = $_GET['subject'];
$faculty_id = $_GET['faculty_id'];

$query = "
    SELECT 
        s.reg_no,
        s.name,
        t2.submission_id AS t2_submission_id,
        t2.file_path AS t2_file_path,
        t3.submission_id AS t3_submission_id,
        t3.file_path AS t3_file_path,
        m.t2_mark1,
        m.t2_mark2,
        m.t3_mark1,
        m.t3_mark2
    FROM students s
    LEFT JOIN t2_t3_submissions t2 ON s.reg_no = t2.reg_no AND t2.subject_id = ? AND t2.document_type = 'T2'
    LEFT JOIN t2_t3_submissions t3 ON s.reg_no = t3.reg_no AND t3.subject_id = ? AND t3.document_type = 'T3'
    LEFT JOIN marks m ON (m.submission_id = t2.submission_id OR m.submission_id = t3.submission_id)
    WHERE s.year = ? AND s.section_name = ? AND s.dept_id = (
        SELECT dept_id FROM faculty WHERE faculty_id = ?
    )";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssiss", $subject_id, $subject_id, $year, $section, $faculty_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = [
        'reg_no' => $row['reg_no'],
        'name' => $row['name'],
        't2_submission' => $row['t2_file_path'] ? [
            'submission_id' => $row['t2_submission_id'],
            'file_path' => $row['t2_file_path']
        ] : null,
        't3_submission' => $row['t3_file_path'] ? [
            'submission_id' => $row['t3_submission_id'],
            'file_path' => $row['t3_file_path']
        ] : null,
        'marks' => [
            't2_mark1' => $row['t2_mark1'],
            't2_mark2' => $row['t2_mark2'],
            't3_mark1' => $row['t3_mark1'],
            't3_mark2' => $row['t3_mark2']
        ]
    ];
}

echo json_encode($students);
mysqli_close($conn);
?>
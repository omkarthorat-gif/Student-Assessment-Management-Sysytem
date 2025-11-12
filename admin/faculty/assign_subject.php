<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php'; // Adjust path to your config.php

// Check if admin is logged in (assuming session_check.php handles this)
include '../includes/session_check.php';

// Initialize variables
$faculty_id = isset($_GET['faculty_id']) ? $_GET['faculty_id'] : '';
$faculty_name = '';
$faculty_dept = '';
$error = '';
$success = '';

// Fetch faculty information if faculty_id is provided
if (!empty($faculty_id)) {
    $faculty_query = "SELECT f.name, f.dept_id, d.dept_name
                      FROM Faculty f
                      JOIN Departments d ON f.dept_id = d.dept_id
                      WHERE f.faculty_id = ?";
    $stmt = $conn->prepare($faculty_query);
    $stmt->bind_param("s", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $faculty = $result->fetch_assoc();
        $faculty_name = $faculty['name'];
        $faculty_dept = $faculty['dept_id'];
        $faculty_dept_name = $faculty['dept_name'];
    } else {
        $_SESSION['error'] = "Faculty not found.";
        header("Location: view_faculty.php");
        exit();
    }
}

// Process form submission for assigning subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_subject'])) {
    $faculty_id = trim($_POST['faculty_id']);
    $subject_id = trim($_POST['subject_id']);
    $year = intval($_POST['year']);
    $semester = intval($_POST['semester']);
    $section_name = trim($_POST['section_name']);
    $dept_id = trim($_POST['dept_id']);

    // Validation
    if (empty($faculty_id) || empty($subject_id) || empty($year) || empty($semester) || empty($section_name) || empty($dept_id)) {
        $error = "All fields are required";
    } else {
        // Check if the section exists
        $section_check = "SELECT COUNT(*) as count
                          FROM Sections
                          WHERE section_name = ? AND year = ? AND dept_id = ?";
        $stmt = $conn->prepare($section_check);
        $stmt->bind_param("sis", $section_name, $year, $dept_id);
        $stmt->execute();
        $section_result = $stmt->get_result();
        $section_exists = $section_result->fetch_assoc()['count'] > 0;

        if (!$section_exists) {
            $error = "Selected section does not exist for this department and year.";
        } else {
            // Check if the subject is allocated to the section
            $check_allocation = "SELECT * FROM Subject_Allocation
                                WHERE subject_id = ? AND year = ? AND semester = ? AND section_name = ? AND dept_id = ?";
            $stmt = $conn->prepare($check_allocation);
            $stmt->bind_param("siiss", $subject_id, $year, $semester, $section_name, $dept_id);
            $stmt->execute();
            $allocation_result = $stmt->get_result();

            if ($allocation_result->num_rows == 0) {
                $error = "Subject is not allocated to this section. Please allocate it first.";
            } else {
                // Check if assignment already exists
                $check_assignment = "SELECT * FROM Faculty_Subject_Assign
                                    WHERE faculty_id = ? AND subject_id = ? AND year = ? AND semester = ? AND section_name = ? AND dept_id = ?";
                $stmt = $conn->prepare($check_assignment);
                $stmt->bind_param("ssiiss", $faculty_id, $subject_id, $year, $semester, $section_name, $dept_id);
                $stmt->execute();
                $assignment_result = $stmt->get_result();

                if ($assignment_result->num_rows > 0) {
                    $error = "This subject is already assigned to this faculty for this section.";
                } else {
                    // Check if another faculty is assigned
                    $check_other_faculty = "SELECT f.faculty_id, f.name
                                            FROM Faculty_Subject_Assign fsa
                                            JOIN Faculty f ON fsa.faculty_id = f.faculty_id
                                            WHERE fsa.subject_id = ? AND fsa.year = ? AND fsa.semester = ?
                                            AND fsa.section_name = ? AND fsa.dept_id = ?";
                    $stmt = $conn->prepare($check_other_faculty);
                    $stmt->bind_param("siiss", $subject_id, $year, $semester, $section_name, $dept_id);
                    $stmt->execute();
                    $other_faculty_result = $stmt->get_result();

                    if ($other_faculty_result->num_rows > 0) {
                        $other_faculty = $other_faculty_result->fetch_assoc();
                        $error = "This subject is already assigned to faculty " . $other_faculty['name'] . " for this section.";
                    } else {
                        // Insert the assignment
                        $insert_query = "INSERT INTO Faculty_Subject_Assign
                                        (faculty_id, subject_id, year, semester, section_name, dept_id)
                                        VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($insert_query);
                        $stmt->bind_param("ssiiss", $faculty_id, $subject_id, $year, $semester, $section_name, $dept_id);

                        if ($stmt->execute()) {
                            $subject_query = "SELECT subject_name FROM Subjects
                                            WHERE subject_id = ? AND year = ? AND semester = ?";
                            $stmt = $conn->prepare($subject_query);
                            $stmt->bind_param("sii", $subject_id, $year, $semester);
                            $stmt->execute();
                            $subject_result = $stmt->get_result();
                            $subject = $subject_result->fetch_assoc();

                            $success = "Subject '" . $subject['subject_name'] . "' assigned successfully to faculty $faculty_name.";
                        } else {
                            $error = "Error assigning subject: " . $stmt->error;
                        }
                    }
                }
            }
        }
    }
}

// Process removal of subject assignment
if (isset($_GET['remove']) && isset($_GET['faculty_id']) && isset($_GET['subject_id']) &&
    isset($_GET['year']) && isset($_GET['semester']) && isset($_GET['section']) && isset($_GET['dept'])) {

    $faculty_id = $_GET['faculty_id'];
    $subject_id = $_GET['subject_id'];
    $year = intval($_GET['year']);
    $semester = intval($_GET['semester']);
    $section_name = $_GET['section'];
    $dept_id = $_GET['dept'];

    $subject_query = "SELECT subject_name FROM Subjects
                     WHERE subject_id = ? AND year = ? AND semester = ?";
    $stmt = $conn->prepare($subject_query);
    $stmt->bind_param("sii", $subject_id, $year, $semester);
    $stmt->execute();
    $subject_result = $stmt->get_result();
    $subject = $subject_result->fetch_assoc();

    $delete_query = "DELETE FROM Faculty_Subject_Assign
                    WHERE faculty_id = ? AND subject_id = ? AND year = ? AND semester = ? AND section_name = ? AND dept_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ssiiss", $faculty_id, $subject_id, $year, $semester, $section_name, $dept_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Subject '" . $subject['subject_name'] . "' successfully removed from faculty $faculty_name.";
    } else {
        $_SESSION['error'] = "Error removing subject assignment: " . $stmt->error;
    }

    header("Location: assign_subject.php?faculty_id=" . urlencode($faculty_id));
    exit();
}

// Fetch departments, sections, and current assignments
$dept_query = "SELECT dept_id, dept_name FROM Departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Fetch all sections
$sections_query = "SELECT DISTINCT section_name FROM Sections ORDER BY section_name";
$sections_result = $conn->query($sections_query);
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row['section_name'];
}

// Fetch current assignments for this faculty
$assignments_query = "SELECT fs.*, s.subject_name, d.dept_name
                     FROM Faculty_Subject_Assign fs
                     JOIN Subjects s ON fs.subject_id = s.subject_id AND fs.year = s.year AND fs.semester = s.semester
                     JOIN Departments d ON fs.dept_id = d.dept_id
                     WHERE fs.faculty_id = ?
                     ORDER BY fs.year, fs.semester, fs.dept_id, fs.section_name, s.subject_name";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$assignments_result = $stmt->get_result();
$current_assignments = [];
while ($row = $assignments_result->fetch_assoc()) {
    $current_assignments[] = $row;
}

// Calculate workload
$total_subjects = count($current_assignments);
$workload_by_semester = [];
foreach ($current_assignments as $assignment) {
    $key = "Year {$assignment['year']}, Semester {$assignment['semester']}";
    $workload_by_semester[$key] = ($workload_by_semester[$key] ?? 0) + 1;
}

// Get filter parameters - these should come from the form submission
$selected_dept_id = isset($_POST['dept_id']) ? $_POST['dept_id'] : ($faculty_dept ?? '');
$selected_year = isset($_POST['year']) ? $_POST['year'] : '';
$selected_semester = isset($_POST['semester']) ? $_POST['semester'] : '';
$selected_section = isset($_POST['section_name']) ? $_POST['section_name'] : '';
$available_subjects = [];

// Flag to determine if we're submitting to load subjects
$is_filter_request = ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter'])); // Changed this line

// Fetch available subjects - modified query to correctly fetch unassigned subjects
if ($is_filter_request && !empty($selected_dept_id) && !empty($selected_year) && !empty($selected_semester) && !empty($selected_section)) {
    // Improved query to get subjects that:
    // 1. Are allocated to the selected section, department, year, semester
    // 2. Are not already assigned to any faculty for that section
    $subjects_query = "
        SELECT s.subject_id, s.subject_name
        FROM Subjects s
        JOIN Subject_Allocation sa ON s.subject_id = sa.subject_id
                                   AND s.year = sa.year
                                   AND s.semester = sa.semester
        WHERE sa.dept_id = ?
          AND sa.year = ?
          AND sa.semester = ?
          AND sa.section_name = ?
          AND NOT EXISTS (
              SELECT 1 FROM Faculty_Subject_Assign fsa
              WHERE fsa.subject_id = sa.subject_id
                AND fsa.year = sa.year
                AND fsa.semester = sa.semester
                AND fsa.section_name = sa.section_name
                AND fsa.dept_id = sa.dept_id
          )
        ORDER BY s.subject_name";

    $stmt = $conn->prepare($subjects_query);
    $stmt->bind_param("siis", $selected_dept_id, $selected_year, $selected_semester, $selected_section);
    $stmt->execute();
    $subjects_result = $stmt->get_result();

    while ($row = $subjects_result->fetch_assoc()) {
        $available_subjects[] = $row;
    }
}

// Get available sections for selected department and year (for dynamic section dropdown)
$available_sections = [];

// Fetch sections on initial page load and whenever department or year changes
if (!empty($selected_dept_id) && !empty($selected_year)) {
    $sections_query = "SELECT section_name FROM Sections
                      WHERE dept_id = ? AND year = ?
                      ORDER BY section_name";
    $stmt = $conn->prepare($sections_query);
    $stmt->bind_param("si", $selected_dept_id, $selected_year);
    $stmt->execute();
    $sections_result = $stmt->get_result();

    while ($row = $sections_result->fetch_assoc()) {
        $available_sections[] = $row['section_name'];
    }
}

include '../includes/header.php'; // Adjust path as needed
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; // Adjust path as needed ?>

        <main class="col-md-10 offset-md-1">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Assign Subjects to Faculty</h1>
                <a href="view_faculty.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Faculty List
                </a>
            </div>

            <?php if (!empty($faculty_id) && !empty($faculty_name)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Faculty Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?= htmlspecialchars($faculty_name) ?></p>
                                <p><strong>ID:</strong> <?= htmlspecialchars($faculty_id) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Department:</strong> <?= htmlspecialchars($faculty_dept_name) ?></p>
                                <p><strong>Current Workload:</strong> <?= $total_subjects ?> subject(s)</p>
                            </div>
                        </div>
                        <?php if (!empty($workload_by_semester)): ?>
                            <div class="mt-3">
                                <h6>Workload Distribution:</h6>
                                <ul class="list-group">
                                    <?php foreach ($workload_by_semester as $semester => $count): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= $semester ?>
                                            <span class="badge bg-primary rounded-pill"><?= $count ?> subject(s)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($faculty_id)): ?>
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Assign Subject</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="assign_subject.php?faculty_id=<?= urlencode($faculty_id) ?>">
                            <input type="hidden" name="faculty_id" value="<?= htmlspecialchars($faculty_id) ?>">

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="dept_id" class="form-label">Department:</label>
                                    <select class="form-select" name="dept_id" id="dept_id" required onchange="this.form.submit()">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept['dept_id']) ?>" <?= ($selected_dept_id == $dept['dept_id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['dept_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="year" class="form-label">Year:</label>
                                    <select class="form-select" name="year" id="year" required onchange="this.form.submit()">
                                        <option value="">Select Year</option>
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($selected_year == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="semester" class="form-label">Semester:</label>
                                    <select class="form-select" name="semester" id="semester" required>
                                        <option value="">Select Semester</option>
                                        <?php for ($i = 1; $i <= 2; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($selected_semester == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="section_name" class="form-label">Section:</label>
                                    <select class="form-select" name="section_name" id="section_name" required>
                                        <option value="">Select Section</option>
                                        <?php foreach ($available_sections as $section): ?>
                                            <option value="<?= htmlspecialchars($section) ?>" <?= ($selected_section == $section) ? 'selected' : '' ?>><?= htmlspecialchars($section) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary" name="filter">Load Subjects</button>
                                </div>
                            </div>

                        </form>

                         <?php if ($is_filter_request): ?>
                        <form method="POST" action="assign_subject.php?faculty_id=<?= urlencode($faculty_id) ?>">
                            <input type="hidden" name="faculty_id" value="<?= htmlspecialchars($faculty_id) ?>">
                            <input type="hidden" name="dept_id" value="<?= htmlspecialchars($selected_dept_id) ?>">
                            <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                            <input type="hidden" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">
                            <input type="hidden" name="section_name" value="<?= htmlspecialchars($selected_section) ?>">


                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="subject_id" class="form-label">Subject:</label>
                                    <select class="form-select" name="subject_id" id="subject_id" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($available_subjects as $subject): ?>
                                            <option value="<?= htmlspecialchars($subject['subject_id']) ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success" name="assign_subject">Assign Subject</button>
                        </form>
                    <?php endif; ?>

                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Current Subject Assignments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($current_assignments)): ?>
                            <p>No subjects currently assigned to this faculty.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Year</th>
                                            <th>Semester</th>
                                            <th>Department</th>
                                            <th>Section</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($current_assignments as $assignment): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($assignment['subject_name']) ?></td>
                                                <td><?= htmlspecialchars($assignment['year']) ?></td>
                                                <td><?= htmlspecialchars($assignment['semester']) ?></td>
                                                <td><?= htmlspecialchars($assignment['dept_name']) ?></td>
                                                <td><?= htmlspecialchars($assignment['section_name']) ?></td>
                                                <td>
                                                    <a href="assign_subject.php?remove=1&faculty_id=<?= urlencode($faculty_id) ?>&subject_id=<?= urlencode($assignment['subject_id']) ?>&year=<?= urlencode($assignment['year']) ?>&semester=<?= urlencode($assignment['semester']) ?>&section=<?= urlencode($assignment['section_name']) ?>&dept=<?= urlencode($assignment['dept_id']) ?>" class="btn btn-danger btn-sm">Remove</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

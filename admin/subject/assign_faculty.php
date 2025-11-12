<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Get parameters from URL
$subject_id = $_GET['subject_id'] ?? '';
$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '';
$section = $_GET['section'] ?? '';
$dept_id = $_GET['dept_id'] ?? '';

// Check if all required parameters are present
if (empty($subject_id) || empty($year) || empty($semester) || empty($section) || empty($dept_id)) {
    $_SESSION['error'] = "Missing required parameters";
    header("Location: view_allocations.php");
    exit();
}

// Get subject details
$subject_query = "SELECT s.subject_name 
                  FROM Subjects s 
                  WHERE s.subject_id = ? AND s.year = ? AND s.semester = ?";
$stmt = $conn->prepare($subject_query);
$stmt->bind_param("sii", $subject_id, $year, $semester);
$stmt->execute();
$subject_result = $stmt->get_result();
$subject = $subject_result->fetch_assoc();

if (!$subject) {
    $_SESSION['error'] = "Subject not found";
    header("Location: view_allocations.php");
    exit();
}

// Get currently assigned faculty (if any)
$current_faculty_query = "SELECT f.faculty_id, f.name 
                          FROM Faculty_Subject_Assign fsa
                          JOIN Faculty f ON fsa.faculty_id = f.faculty_id
                          WHERE fsa.subject_id = ? 
                          AND fsa.year = ? 
                          AND fsa.semester = ? 
                          AND fsa.section_name = ? 
                          AND fsa.dept_id = ?";
$stmt = $conn->prepare($current_faculty_query);
$stmt->bind_param("siiss", $subject_id, $year, $semester, $section, $dept_id);
$stmt->execute();
$current_faculty_result = $stmt->get_result();
$current_faculty = $current_faculty_result->fetch_assoc();

// Get all faculty from the department for dropdown
$faculty_query = "SELECT faculty_id, name 
                  FROM Faculty 
                  WHERE dept_id = ? 
                  ORDER BY name";
$stmt = $conn->prepare($faculty_query);
$stmt->bind_param("s", $dept_id);
$stmt->execute();
$faculty_result = $stmt->get_result();
$faculties = [];
while ($row = $faculty_result->fetch_assoc()) {
    $faculties[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_id = $_POST['faculty_id'] ?? '';
    
    if (empty($faculty_id)) {
        $_SESSION['error'] = "Please select a faculty";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Check if there's already an assignment
            if ($current_faculty) {
                // Update existing assignment
                $update_query = "UPDATE Faculty_Subject_Assign 
                                SET faculty_id = ? 
                                WHERE subject_id = ? 
                                AND year = ? 
                                AND semester = ? 
                                AND section_name = ? 
                                AND dept_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssiiss", $faculty_id, $subject_id, $year, $semester, $section, $dept_id);
                $stmt->execute();
                $success_message = "Faculty assignment updated successfully!";
            } else {
                // Insert new assignment
                $insert_query = "INSERT INTO Faculty_Subject_Assign 
                                (faculty_id, subject_id, year, semester, section_name, dept_id) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ssiiss", $faculty_id, $subject_id, $year, $semester, $section, $dept_id);
                $stmt->execute();
                $success_message = "Faculty assigned successfully!";
            }
            
            $conn->commit();
            
            // Store success message in session
            $_SESSION['success'] = $success_message;
            
            // Optional: Delay redirect to show success message on this page
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'view_allocations.php';
                }, 2000); // Redirect after 2 seconds
            </script>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-10 offset-md-1">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Assign Faculty to Subject</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_allocations.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Allocations
                    </a>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus"></i> Faculty Assignment
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Subject Details:</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Subject Code</th>
                                    <td><?php echo $subject_id; ?></td>
                                </tr>
                                <tr>
                                    <th>Subject Name</th>
                                    <td><?php echo $subject['subject_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td><?php echo $dept_id; ?></td>
                                </tr>
                                <tr>
                                    <th>Year & Semester</th>
                                    <td>Year <?php echo $year; ?>, Semester <?php echo $semester; ?></td>
                                </tr>
                                <tr>
                                    <th>Section</th>
                                    <td><?php echo $section; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Current Assignment:</h5>
                            <?php if ($current_faculty): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Currently assigned to: 
                                    <strong><?php echo $current_faculty['name'] . ' (' . $current_faculty['faculty_id'] . ')'; ?></strong>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> No faculty currently assigned
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?subject_id=$subject_id&year=$year&semester=$semester&section=$section&dept_id=$dept_id"; ?>">
                        <div class="mb-3">
                            <label for="faculty_id" class="form-label">Select Faculty:</label>
                            <select class="form-select" id="faculty_id" name="faculty_id" required>
                                <option value="">-- Select Faculty --</option>
                                <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?php echo $faculty['faculty_id']; ?>"
                                        <?php echo ($current_faculty && $current_faculty['faculty_id'] == $faculty['faculty_id']) ? 'selected' : ''; ?>>
                                        <?php echo $faculty['name'] . ' (' . $faculty['faculty_id'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="view_allocations.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $current_faculty ? '<i class="fas fa-user-edit"></i> Update Assignment' : '<i class="fas fa-user-plus"></i> Assign Faculty'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Faculty Teaching Load -->
            <?php
            $load_query = "SELECT f.faculty_id, f.name, COUNT(fsa.subject_id) as teaching_load
                          FROM Faculty f
                          LEFT JOIN Faculty_Subject_Assign fsa ON f.faculty_id = fsa.faculty_id
                          WHERE f.dept_id = ?
                          GROUP BY f.faculty_id
                          ORDER BY teaching_load DESC";
            $stmt = $conn->prepare($load_query);
            $stmt->bind_param("s", $dept_id);
            $stmt->execute();
            $load_result = $stmt->get_result();
            ?>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar"></i> Faculty Teaching Load
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Faculty ID</th>
                                    <th>Name</th>
                                    <th>Teaching Load</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $load_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['faculty_id']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 20px;">
                                                <?php 
                                                $load_percent = min(100, $row['teaching_load'] * 20); // Assuming 5 subjects is 100%
                                                $load_class = 'bg-success';
                                                if ($load_percent > 80) {
                                                    $load_class = 'bg-danger';
                                                } else if ($load_percent > 60) {
                                                    $load_class = 'bg-warning';
                                                }
                                                ?>
                                                <div class="progress-bar <?php echo $load_class; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $load_percent; ?>%;" 
                                                     aria-valuenow="<?php echo $row['teaching_load']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="5">
                                                    <?php echo $row['teaching_load']; ?>
                                                </div>
                                            </div>
                                            <span class="ms-2"><?php echo $row['teaching_load']; ?> subject(s)</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Custom JavaScript -->
<script>
    $(document).ready(function() {
        $('#faculty_id').select2({
            theme: 'bootstrap4',
            placeholder: 'Select a faculty member'
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
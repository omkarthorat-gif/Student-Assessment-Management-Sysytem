<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Check if ID is provided
if (!isset($_GET['id']) || !isset($_GET['year']) || !isset($_GET['semester'])) {
    $_SESSION['error'] = "Missing required parameters.";
    header("Location: view_subjects.php");
    exit();
}

$subject_id = $_GET['id'];
$year = $_GET['year'];
$semester = $_GET['semester'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $subject_name = trim($_POST['subject_name']);
    $dept_id = trim($_POST['dept_id']);
    $new_year = intval($_POST['year']);
    $new_semester = intval($_POST['semester']);
    
    // Basic validation
    if (empty($subject_name) || empty($dept_id) || $new_year < 1 || $new_year > 4 || ($new_semester != 1 && $new_semester != 2)) {
        $_SESSION['error'] = "Please fill all required fields with valid values.";
    } else {
        // Update subject
        $update_query = "UPDATE Subjects SET subject_name = ?, dept_id = ?, year = ?, semester = ? 
                        WHERE subject_id = ? AND year = ? AND semester = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssiisii", $subject_name, $dept_id, $new_year, $new_semester, $subject_id, $year, $semester);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Subject updated successfully.";
            header("Location: view_subjects.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating subject: " . $stmt->error;
        }
    }
}

// Fetch subject details
$query = "SELECT s.*, d.dept_name 
          FROM Subjects s
          JOIN Departments d ON s.dept_id = d.dept_id
          WHERE s.subject_id = ? AND s.year = ? AND s.semester = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $subject_id, $year, $semester);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Subject not found.";
    header("Location: view_subjects.php");
    exit();
}

$subject = $result->fetch_assoc();

// Fetch departments for dropdown
$dept_query = "SELECT dept_id, dept_name FROM Departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
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
                <h1 class="h2">Edit Subject</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_subjects.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . urlencode($subject_id) . "&year=" . urlencode($year) . "&semester=" . urlencode($semester)); ?>">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject ID</label>
                            <input type="text" class="form-control" id="subject_id" value="<?php echo $subject['subject_id']; ?>" readonly>
                            <div class="form-text">Subject ID cannot be changed.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" value="<?php echo $subject['subject_name']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="dept_id" class="form-label">Department</label>
                            <select class="form-select" id="dept_id" name="dept_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>" <?php if ($subject['dept_id'] == $dept['dept_id']) echo 'selected'; ?>>
                                        <?php echo $dept['dept_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year" required>
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php if ($subject['year'] == $i) echo 'selected'; ?>>
                                            Year <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="1" <?php if ($subject['semester'] == 1) echo 'selected'; ?>>Semester 1</option>
                                    <option value="2" <?php if ($subject['semester'] == 2) echo 'selected'; ?>>Semester 2</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="view_subjects.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
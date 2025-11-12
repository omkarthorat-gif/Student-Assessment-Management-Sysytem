<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Initialize variables for form data and error messages
$subject_id = $subject_name = $dept_id = $year = $semester = "";
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $subject_id = trim($_POST['subject_id']);
    $subject_name = trim($_POST['subject_name']);
    $dept_id = trim($_POST['dept_id']);
    $year = intval($_POST['year']);
    $semester = intval($_POST['semester']);

    // Validation
    if (empty($subject_id) || empty($subject_name) || empty($dept_id) || empty($year) || empty($semester)) {
        $error = "All fields are required";
    } elseif (strlen($subject_id) > 7 ||strlen($subject_id) < 7 ) {
        $error = "Subject ID must be 7 characters";
    } elseif ($year < 1 || $year > 4) {
        $error = "Year must be between 1 and 4";
    } elseif (!in_array($semester, [1, 2])) {
        $error = "Semester must be either 1 or 2";
    } else {
        // Check if subject ID already exists for the same year and semester
        $stmt = $conn->prepare("SELECT subject_id FROM Subjects WHERE subject_id = ? AND year = ? AND semester = ?");
        $stmt->bind_param("sii", $subject_id, $year, $semester);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Subject ID already exists for this year and semester";
        } else {
            // Check if department exists
            $stmt = $conn->prepare("SELECT dept_id FROM Departments WHERE dept_id = ?");
            $stmt->bind_param("s", $dept_id);
            $stmt->execute();
            $dept_result = $stmt->get_result();
            
            if ($dept_result->num_rows == 0) {
                $error = "Selected department does not exist";
            } else {
                // Check if year-semester combination exists
                $stmt = $conn->prepare("SELECT * FROM Year_Semester WHERE year = ? AND semester = ?");
                $stmt->bind_param("ii", $year, $semester);
                $stmt->execute();
                $year_sem_result = $stmt->get_result();
                
                if ($year_sem_result->num_rows == 0) {
                    $error = "Selected year-semester combination does not exist";
                } else {
                    // Insert subject data
                    $stmt = $conn->prepare("INSERT INTO Subjects (subject_id, subject_name, dept_id, year, semester) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssii", $subject_id, $subject_name, $dept_id, $year, $semester);
                    
                    if ($stmt->execute()) {
                        $success = "Subject added successfully.";
                        // Clear form data after successful submission
                        $subject_id = $subject_name = $dept_id = $year = $semester = "";
                    } else {
                        $error = "Error adding subject: " . $stmt->error;
                    }
                }
            }
        }
    }
}

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
            <h1 class="h2 mb-4">Add New Subject</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="subject_id" class="form-label">Subject ID (7 characters)</label>
                                <input type="text" class="form-control" id="subject_id" name="subject_id" value="<?php echo $subject_id; ?>" maxlength="7" required>
                            </div>
                            <div class="col-md-6">
                                <label for="subject_name" class="form-label">Subject Name</label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" value="<?php echo $subject_name; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dept_id" class="form-label">Department</label>
                                <select class="form-select" id="dept_id" name="dept_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['dept_id']; ?>" <?php if ($dept_id == $dept['dept_id']) echo 'selected'; ?>>
                                            <?php echo $dept['dept_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="">Select Year</option>
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php if ($year == $i) echo 'selected'; ?>>
                                            Year <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1" <?php if ($semester == 1) echo 'selected'; ?>>Semester 1</option>
                                    <option value="2" <?php if ($semester == 2) echo 'selected'; ?>>Semester 2</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Add Subject</button>
                            <a href="view_subjects.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Ajax script to validate year-semester combination -->
            <script>
                $(document).ready(function() {
                    $('#year, #semester').change(function() {
                        var year = $('#year').val();
                        var semester = $('#semester').val();
                        
                        if (year && semester) {
                            $.ajax({
                                url: '../year_semester/check_year_semester.php',
                                type: 'POST',
                                data: {year: year, semester: semester},
                                dataType: 'json',
                                success: function(data) {
                                    if (!data.exists) {
                                        alert('This year-semester combination does not exist. Please add it first in the Year & Semester section.');
                                        $('#year').val('');
                                        $('#semester').val('');
                                    }
                                }
                            });
                        }
                    });
                });
            </script>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
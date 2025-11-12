<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Get student ID from the URL
if (!isset($_GET['reg_no']) || empty($_GET['reg_no'])) {
    $_SESSION['error_message'] = "Invalid request! Student ID is required.";
    header("Location: edit_student.php");
    exit();
}

$reg_no = $_GET['reg_no'];
$success_message = "";
$error_message = "";

// Fetch student details with department name
$query = "SELECT s.reg_no, s.name, s.dept_id, s.year, s.section_name, s.semester, d.dept_name 
          FROM Students s
          JOIN Departments d ON s.dept_id = d.dept_id
          WHERE s.reg_no = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $reg_no);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $error_message = "Student not found!";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $section_name = $_POST['section_name'];

    // Update student year, semester and section
    $update_query = "UPDATE Students SET year = ?, semester = ?, section_name = ? WHERE reg_no = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("iiss", $year, $semester, $section_name, $reg_no);

    if ($update_stmt->execute()) {
        $success_message = "Student details updated successfully!";
        
        // Refresh student data after update
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
    } else {
        $error_message = "Error updating student details: " . $conn->error;
    }
}

// Get available sections for the student's department and year
if ($student) {
    $sections_query = "SELECT DISTINCT section_name FROM Students WHERE dept_id = ? AND year = ? ORDER BY section_name";
    $sections_stmt = $conn->prepare($sections_query);
    $sections_stmt->bind_param("si", $student['dept_id'], $student['year']);
    $sections_stmt->execute();
    $sections_result = $sections_stmt->get_result();
    $sections = [];
    while ($row = $sections_result->fetch_assoc()) {
        $sections[] = $row['section_name'];
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
                <h1 class="h2"><i class="fas fa-user-edit text-primary me-2"></i>Edit Student</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_students.php">Students</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Student</li>
                    </ol>
                </nav>
            </div>

            <!-- Display Success or Error Message -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($student): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Student Information</h5>
                </div>
                <div class="card-body p-0">
                    <form method="POST" id="editStudentForm">
                        <!-- Scrollable form content area -->
                        <div class="form-content-scroll" style="max-height: 400px; overflow-y: auto; padding: 1.25rem;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Registration Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['reg_no']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Department</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['dept_name']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="year" class="form-label fw-bold">Year <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            <select name="year" id="year" class="form-select" required>
                                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($student['year'] == $i) ? 'selected' : ''; ?>>
                                                        Year <?php echo $i; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="section_name" class="form-label fw-bold">Section <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-users"></i></span>
                                            <select name="section_name" id="section_name" class="form-select" required>
                                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $section): ?>
                                                    <option value="<?php echo $section; ?>" <?php echo ($student['section_name'] == $section) ? 'selected' : ''; ?>>
                                                        Section <?php echo $section; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="semester" class="form-label fw-bold">Semester <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-book"></i></span>
                                            <select name="semester" id="semester" class="form-select" required>
                                                <option value="1" <?php echo ($student['semester'] == 1) ? 'selected' : ''; ?>>Semester 1</option>
                                                <option value="2" <?php echo ($student['semester'] == 2) ? 'selected' : ''; ?>>Semester 2</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fixed footer with action buttons -->
                        <div class="card-footer bg-light d-flex justify-content-between py-3">
                            <a href="#" class="btn btn-secondary" onclick="window.history.back();">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <div>
                                <button type="reset" class="btn btn-warning me-2">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-success" id="updateBtn">
                                    <i class="fas fa-save me-2"></i>Update Student
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Student record not found. <a href="#" onclick="window.history.back();">Go back</a>.
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Year change handler - Load appropriate sections
    $('#year').change(function() {
        var year = $(this).val();
        var deptId = '<?php echo $student ? $student['dept_id'] : ""; ?>';
        
        if (!deptId) return;
        
        // Show loading
        $('#loading-overlay').show();
        
        $.ajax({
            url: 'get_sections.php',
            type: 'POST',
            data: {
                year: year,
                dept_id: deptId
            },
            dataType: 'json',
            success: function(data) {
                var options = '';
                if (data.length > 0) {
                    $.each(data, function(key, value) {
                        options += '<option value="' + value.section_name + '">Section ' + value.section_name + '</option>';
                    });
                } else {
                    // Default sections if none found
                    <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $section): ?>
                        options += '<option value="<?php echo $section; ?>">Section <?php echo $section; ?></option>';
                    <?php endforeach; ?>
                }
                $('#section_name').html(options);
                
                // Hide loading
                $('#loading-overlay').hide();
            },
            error: function() {
                // On error, load default sections
                var options = '';
                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $section): ?>
                    options += '<option value="<?php echo $section; ?>">Section <?php echo $section; ?></option>';
                <?php endforeach; ?>
                $('#section_name').html(options);
                
                // Hide loading
                $('#loading-overlay').hide();
            }
        });
    });

    // Form submission with validation
    $('#editStudentForm').submit(function(e) {
        // Basic form validation
        var year = $('#year').val();
        var section = $('#section_name').val();
        var semester = $('#semester').val();
        
        if (!year || !section || !semester) {
            e.preventDefault();
            alert('Please fill all required fields!');
            return false;
        }
        
        // Show loading overlay during form submission
        $('#loading-overlay').show();
        $('#updateBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        
        // Form will submit normally
        return true;
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>

<style>
/* Custom scrollbar for better appearance */
.form-content-scroll::-webkit-scrollbar {
    width: 8px;
}

.form-content-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.form-content-scroll::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.form-content-scroll::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<?php include '../includes/footer.php'; ?>
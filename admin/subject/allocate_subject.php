<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Process AJAX requests first before any HTML output
if (isset($_GET['ajax'])) {
    // AJAX handler for getting sections
    if ($_GET['ajax'] == 'get_sections') {
        $dept_id = $_GET['dept_id'] ?? '';
        $year = $_GET['year'] ?? '';
        
        if (empty($dept_id) || empty($year)) {
            echo '<div class="alert alert-warning">Invalid parameters</div>';
            exit;
        }
        
        // Fetch sections for the specified department and year
        $sections_query = "SELECT section_name FROM Sections 
                          WHERE dept_id = ? AND year = ?
                          ORDER BY section_name";
        $stmt = $conn->prepare($sections_query);
        $stmt->bind_param("si", $dept_id, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo '<div class="row">';
            while ($row = $result->fetch_assoc()) {
                echo '<div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="section_' . $row['section_name'] . '" 
                                name="sections[]" value="' . $row['section_name'] . '">
                            <label class="form-check-label" for="section_' . $row['section_name'] . '">
                                Section ' . $row['section_name'] . '
                            </label>
                        </div>
                      </div>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">No sections found for this department and year. Please create sections first.</div>';
        }
        exit;
    }
    
    // AJAX handler for getting subjects
    if ($_GET['ajax'] == 'get_subjects') {
        $dept_id = $_GET['dept_id'] ?? '';
        $year = $_GET['year'] ?? '';
        $semester = $_GET['semester'] ?? '';
        
        if (empty($dept_id) || empty($year) || empty($semester)) {
            echo '<option value="">Invalid parameters</option>';
            exit;
        }
        
        // Fetch subjects for the specified department, year, and semester
        $subjects_query = "SELECT subject_id, subject_name FROM Subjects 
                          WHERE dept_id = ? AND year = ? AND semester = ?
                          ORDER BY subject_name";
        $stmt = $conn->prepare($subjects_query);
        $stmt->bind_param("sii", $dept_id, $year, $semester);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo '<option value="">Select a subject</option>';
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . $row['subject_id'] . '">' . $row['subject_name'] . ' (' . $row['subject_id'] . ')</option>';
            }
        } else {
            echo '<option value="">No subjects found for this selection</option>';
        }
        exit;
    }
}

// Check if admin is logged in
include '../includes/session_check.php';

// Process allocation deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_allocation'])) {
    $subject_id = $_POST['delete_subject_id'];
    $year = $_POST['delete_year'];
    $semester = $_POST['delete_semester'];
    $section_name = $_POST['delete_section'];
    $dept_id = $_POST['delete_dept_id'];
    
    // Check if there are faculty assignments for this allocation
    $check_query = "SELECT * FROM Faculty_Subject_Assign 
                    WHERE subject_id = ? AND year = ? AND semester = ? AND section_name = ? AND dept_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("siiss", $subject_id, $year, $semester, $section_name, $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Cannot remove allocation. It is assigned to faculty. Please remove faculty assignments first.";
    } else {
        // Delete the allocation
        $delete_query = "DELETE FROM Subject_Allocation 
                         WHERE subject_id = ? AND year = ? AND semester = ? AND section_name = ? AND dept_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("siiss", $subject_id, $year, $semester, $section_name, $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Subject allocation removed successfully.";
        } else {
            $_SESSION['error'] = "Error removing allocation: " . $stmt->error;
        }
    }
    
    // Redirect to avoid resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Process form submission for subject allocation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['allocate_subject'])) {
    // Get form data
    $dept_id = $_POST['dept_id'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $subject_id = $_POST['subject_id'];
    $sections = isset($_POST['sections']) ? $_POST['sections'] : [];
    
    // Validate input
    $errors = [];
    if (empty($dept_id)) $errors[] = "Department is required";
    if (empty($year)) $errors[] = "Year is required";
    if (empty($semester)) $errors[] = "Semester is required";
    if (empty($subject_id)) $errors[] = "Subject is required";
    if (empty($sections)) $errors[] = "At least one section must be selected";
    
    if (empty($errors)) {
        // Insert records for each selected section
        $success = true;
        $inserted_count = 0;
        
        foreach ($sections as $section_name) {
            // Check if record already exists
            $check_sql = "SELECT * FROM Subject_Allocation 
                          WHERE subject_id = ? AND year = ? AND semester = ? 
                          AND section_name = ? AND dept_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("siiss", $subject_id, $year, $semester, $section_name, $dept_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Subject already allocated to Year $year Semester $semester Section $section_name";
                continue;
            }
            
            // Insert new allocation
            $sql = "INSERT INTO Subject_Allocation (subject_id, year, semester, section_name, dept_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siiss", $subject_id, $year, $semester, $section_name, $dept_id);
            
            if ($stmt->execute()) {
                $inserted_count++;
            } else {
                $errors[] = "Error allocating subject to Section $section_name: " . $stmt->error;
                $success = false;
            }
        }
        
        if ($inserted_count > 0) {
            $_SESSION['success'] = "Subject successfully allocated to $inserted_count section(s).";
        } else {
            $_SESSION['error'] = "Failed to allocate subject. " . implode(" ", $errors);
        }
        
        // Redirect to avoid resubmission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['error'] = "Form validation failed: " . implode(", ", $errors);
    }
}

// Fetch departments for dropdowns
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
                <h1 class="h2">Subject Allocation</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_subjects.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Subject Allocation Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Allocate Subjects to Sections</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="allocationForm">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="dept_id" class="form-label">Department</label>
                                <select class="form-select" id="dept_id" name="dept_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['dept_id']; ?>">
                                            <?php echo $dept['dept_name'] . ' (' . $dept['dept_id'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="subject_id" class="form-label">Subject</label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">First select department, year, and semester</option>
                                </select>
                                <div class="form-text">Only subjects for the selected department, year, and semester are shown</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Sections</label>
                                <div id="sections-container" class="border rounded p-3">
                                    <div class="alert alert-info">Please select department and year to view available sections</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" name="allocate_subject" class="btn btn-primary">Allocate Subject</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- View Current Allocations -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Current Subject Allocations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Section</th>
                                    <th>Subject</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch existing allocations
                                $alloc_query = "SELECT sa.dept_id, d.dept_name, sa.year, sa.semester, 
                                               sa.section_name, sa.subject_id, s.subject_name
                                               FROM Subject_Allocation sa
                                               JOIN Departments d ON sa.dept_id = d.dept_id
                                               JOIN Subjects s ON sa.subject_id = s.subject_id 
                                                    AND sa.year = s.year AND sa.semester = s.semester
                                               ORDER BY sa.dept_id, sa.year, sa.semester, sa.section_name";
                                $alloc_result = $conn->query($alloc_query);
                                
                                if ($alloc_result->num_rows > 0) {
                                    while($row = $alloc_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $row['dept_name'] . " (" . $row['dept_id'] . ")</td>";
                                        echo "<td>Year " . $row['year'] . "</td>";
                                        echo "<td>Semester " . $row['semester'] . "</td>";
                                        echo "<td>" . $row['section_name'] . "</td>";
                                        echo "<td>" . $row['subject_name'] . " (" . $row['subject_id'] . ")</td>";
                                        echo "<td>";
                                        echo "<a href=\"javascript:void(0);\" class=\"btn btn-sm btn-danger\" 
                                               onclick=\"confirmDeleteAllocation('".$row['subject_id']."', ".$row['year'].", ".$row['semester'].", '".$row['section_name']."', '".$row['dept_id']."')\">
                                               <i class=\"fas fa-trash\"></i> Remove</a>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>No allocations found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Allocation Confirmation Modal -->
<div class="modal fade" id="deleteAllocationModal" tabindex="-1" aria-labelledby="deleteAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAllocationModalLabel">Confirm Remove Allocation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to remove this subject allocation?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteAllocationForm" method="post" action="">
                    <input type="hidden" id="delete_subject_id" name="delete_subject_id">
                    <input type="hidden" id="delete_year" name="delete_year">
                    <input type="hidden" id="delete_semester" name="delete_semester">
                    <input type="hidden" id="delete_section" name="delete_section">
                    <input type="hidden" id="delete_dept_id" name="delete_dept_id">
                    <button type="submit" class="btn btn-danger" name="delete_allocation">Remove</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // When department and year are selected, load sections
    document.addEventListener('DOMContentLoaded', function() {
        // Handle department, year, semester change
        document.getElementById('dept_id').addEventListener('change', updateSections);
        document.getElementById('year').addEventListener('change', updateSections);
        document.getElementById('semester').addEventListener('change', loadSubjects);
        
        // Initial load if values already selected
        if (document.getElementById('dept_id').value && document.getElementById('year').value) {
            updateSections();
        }
    });
    
    // Function to update sections based on department and year
    function updateSections() {
        const deptId = document.getElementById('dept_id').value;
        const year = document.getElementById('year').value;
        const sectionsContainer = document.getElementById('sections-container');
        
        if (!deptId || !year) {
            sectionsContainer.innerHTML = '<div class="alert alert-info">Please select department and year to view available sections</div>';
            return;
        }
        
        // Show loading indicator
        sectionsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Use AJAX to fetch sections
        fetch('<?php echo $_SERVER["PHP_SELF"]; ?>?ajax=get_sections&dept_id=' + deptId + '&year=' + year)
            .then(response => response.text())
            .then(data => {
                sectionsContainer.innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                sectionsContainer.innerHTML = '<div class="alert alert-danger">Error loading sections. Please try again.</div>';
            });
            
        // Reset and update subjects dropdown
        document.getElementById('subject_id').innerHTML = '<option value="">First select department, year, and semester</option>';
        if (document.getElementById('semester').value) {
            loadSubjects();
        }
    }
    
    // Function to load subjects based on department, year, and semester
    function loadSubjects() {
        const deptId = document.getElementById('dept_id').value;
        const year = document.getElementById('year').value;
        const semester = document.getElementById('semester').value;
        const subjectDropdown = document.getElementById('subject_id');
        
        if (!deptId || !year || !semester) {
            subjectDropdown.innerHTML = '<option value="">First select department, year, and semester</option>';
            return;
        }
        
        // Show loading indicator
        subjectDropdown.innerHTML = '<option value="">Loading subjects...</option>';
        
        // Use AJAX to fetch subjects
        fetch('<?php echo $_SERVER["PHP_SELF"]; ?>?ajax=get_subjects&dept_id=' + deptId + '&year=' + year + '&semester=' + semester)
            .then(response => response.text())
            .then(data => {
                subjectDropdown.innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                subjectDropdown.innerHTML = '<option value="">Error loading subjects. Please try again.</option>';
            });
    }
    
    // Function to confirm deletion of allocation
    function confirmDeleteAllocation(subjectId, year, semester, section, deptId) {
        document.getElementById('delete_subject_id').value = subjectId;
        document.getElementById('delete_year').value = year;
        document.getElementById('delete_semester').value = semester;
        document.getElementById('delete_section').value = section;
        document.getElementById('delete_dept_id').value = deptId;
        
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteAllocationModal'));
        deleteModal.show();
    }
</script>

<?php include '../includes/footer.php'; ?>
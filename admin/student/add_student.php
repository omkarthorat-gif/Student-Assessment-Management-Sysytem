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
$name = $reg_no = $year = $section_name = $dept_id = $semester = "";
$error = "";
$success = "";

// Process form submission (single student)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_FILES['csv_file'])) {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $reg_no = trim($_POST['reg_no']);
    $year = intval($_POST['year']);
    $section_name = trim($_POST['section_name']);
    $dept_id = trim($_POST['dept_id']);
    $semester = intval($_POST['semester']);

    // Validation
    if (empty($name) || empty($reg_no) || empty($year) || empty($section_name) || empty($dept_id) || empty($semester)) {
        $error = "All fields are required";
    } elseif (strlen($reg_no) != 10) {
        $error = "Registration number must be 10 characters";
    } elseif ($year < 1 || $year > 4) {
        $error = "Year must be between 1 and 4";
    } elseif (!in_array($semester, [1, 2])) {
        $error = "Semester must be either 1 or 2";
    } else {
        // Check if registration number already exists
        $stmt = $conn->prepare("SELECT reg_no FROM Students WHERE reg_no = ?");
        $stmt->bind_param("s", $reg_no);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Registration number already exists";
        } else {
            // Check if section exists for the selected year and department
            $stmt = $conn->prepare("SELECT * FROM Sections WHERE section_name = ? AND year = ? AND dept_id = ?");
            $stmt->bind_param("sis", $section_name, $year, $dept_id);
            $stmt->execute();
            $section_result = $stmt->get_result();
            
            if ($section_result->num_rows == 0) {
                $error = "Selected section does not exist for this year and department";
            } else {
                // Check if year-semester combination exists
                $stmt = $conn->prepare("SELECT * FROM Year_Semester WHERE year = ? AND semester = ?");
                $stmt->bind_param("ii", $year, $semester);
                $stmt->execute();
                $year_sem_result = $stmt->get_result();
                
                if ($year_sem_result->num_rows == 0) {
                    $error = "Selected year-semester combination does not exist";
                } else {
                    // Insert student data
                    $stmt = $conn->prepare("INSERT INTO Students (reg_no, name, year, section_name, dept_id, semester) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssissi", $reg_no, $name, $year, $section_name, $dept_id, $semester);
                    
                    if ($stmt->execute()) {
                        // Also create user account for student
                        $default_password = password_hash($reg_no, PASSWORD_DEFAULT);
                        $role = "Student";
                        
                        $stmt = $conn->prepare("INSERT INTO Users (username, password, role) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $reg_no, $default_password, $role);
                        $stmt->execute();
                        
                        $success = "Student added successfully. Default password is their registration number.";
                        $name = $reg_no = $year = $section_name = $dept_id = $semester = "";
                    } else {
                        $error = "Error adding student: " . $stmt->error;
                    }
                }
            }
        }
    }
}

// Process CSV import
// Process CSV import
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    $row = 0;
    $successful_imports = 0;
    $failed_imports = 0;
    $import_errors = []; // To track specific errors
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // Skip header row
        
        $reg_no = trim($data[0]);
        $name = trim($data[1]);
        $year = intval($data[2]);
        $section_name = trim($data[3]);
        $dept_id = trim($data[4]);
        $semester = intval($data[5]);
        
        $import_status = true; // Track if this specific row import was successful
        $error_message = "";
        
        // Validation checks
        if (empty($reg_no) || empty($name) || empty($year) || empty($section_name) || empty($dept_id) || empty($semester)) {
            $import_status = false;
            $error_message = "Row $row: Missing required fields";
        } elseif (strlen($reg_no) != 10) {
            $import_status = false;
            $error_message = "Row $row: Registration number must be 10 characters";
        } elseif ($year < 1 || $year > 4) {
            $import_status = false;
            $error_message = "Row $row: Year must be between 1 and 4";
        } elseif (!in_array($semester, [1, 2])) {
            $import_status = false;
            $error_message = "Row $row: Semester must be either 1 or 2";
        } else {
            // Check if registration number exists
            $stmt = $conn->prepare("SELECT reg_no FROM Students WHERE reg_no = ?");
            $stmt->bind_param("s", $reg_no);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $import_status = false;
                $error_message = "Row $row: Registration number already exists";
            } else {
                // Check section and year-semester combination
                $stmt = $conn->prepare("SELECT * FROM Sections WHERE section_name = ? AND year = ? AND dept_id = ?");
                $stmt->bind_param("sis", $section_name, $year, $dept_id);
                $stmt->execute();
                $section_result = $stmt->get_result();
                
                if ($section_result->num_rows == 0) {
                    $import_status = false;
                    $error_message = "Row $row: Selected section does not exist for this year and department";
                } else {
                    $stmt = $conn->prepare("SELECT * FROM Year_Semester WHERE year = ? AND semester = ?");
                    $stmt->bind_param("ii", $year, $semester);
                    $stmt->execute();
                    $year_sem_result = $stmt->get_result();
                    
                    if ($year_sem_result->num_rows == 0) {
                        $import_status = false;
                        $error_message = "Row $row: Selected year-semester combination does not exist";
                    }
                }
            }
        }
        
        // Only proceed with insertion if validation passed
        if ($import_status) {
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Insert student data
                $stmt = $conn->prepare("INSERT INTO Students (reg_no, name, year, section_name, dept_id, semester) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissi", $reg_no, $name, $year, $section_name, $dept_id, $semester);
                
                if ($stmt->execute()) {
                    // Also create user account for student
                    $default_password = password_hash($reg_no, PASSWORD_DEFAULT);
                    $role = "Student";
                    
                    $stmt = $conn->prepare("INSERT INTO Users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $reg_no, $default_password, $role);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $successful_imports++;
                    } else {
                        // If user creation fails, roll back
                        $conn->rollback();
                        $failed_imports++;
                        $import_errors[] = "Row $row: Failed to create user account - " . $stmt->error;
                    }
                } else {
                    // If student insertion fails, roll back
                    $conn->rollback();
                    $failed_imports++;
                    $import_errors[] = "Row $row: Failed to add student - " . $stmt->error;
                }
            } catch (Exception $e) {
                // If any exception occurs, roll back
                $conn->rollback();
                $failed_imports++;
                $import_errors[] = "Row $row: " . $e->getMessage();
            }
        } else {
            $failed_imports++;
            $import_errors[] = $error_message;
        }
    }
    
    fclose($handle);
    
    // Set success message with details
    $success = "CSV Import completed: $successful_imports students added successfully, $failed_imports failed.";
    
    // If there were failures, show detailed errors
    if ($failed_imports > 0) {
        $success .= " <button class='btn btn-sm btn-outline-danger' type='button' data-bs-toggle='collapse' data-bs-target='#importErrors'>Show Errors</button>";
        $success .= "<div class='collapse mt-2' id='importErrors'><div class='card card-body'><ul>";
        foreach ($import_errors as $err) {
            $success .= "<li>" . htmlspecialchars($err) . "</li>";
        }
        $success .= "</ul></div></div>";
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
            <h1 class="h2 mb-4">Add New Student</h1>
            
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
            
            <div class="card mb-4">
                <div class="card-header">Add Single Student</div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="reg_no" class="form-label">Registration Number (10 characters)</label>
                                <input type="text" class="form-control" id="reg_no" name="reg_no" value="<?php echo $reg_no; ?>" maxlength="10" required>
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
                                <label for="section_name" class="form-label">Section</label>
                                <select class="form-select" id="section_name" name="section_name" required>
                                    <option value="">Select Section</option>
                                    <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $section): ?>
                                        <option value="<?php echo $section; ?>" <?php if ($section_name == $section) echo 'selected'; ?>>
                                            Section <?php echo $section; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                            <button type="submit" class="btn btn-primary">Add Student</button>
                            <a href="view_students.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Import Students from CSV</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <small class="form-text text-muted">
                                CSV format should be: reg_no,name,year,section_name,dept_id,semester<br>
                                Example: 221FA07057,Nagasatwika Potla,3,A,IT,2
                            </small>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Import CSV</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Ajax script to validate section based on year and department -->
            <script>
                $(document).ready(function() {
                    $('#year, #dept_id').change(function() {
                        var year = $('#year').val();
                        var dept_id = $('#dept_id').val();
                        
                        if (year && dept_id) {
                            $.ajax({
                                url: 'get_sections.php',
                                type: 'POST',
                                data: {year: year, dept_id: dept_id},
                                dataType: 'json',
                                success: function(data) {
                                    var options = '<option value="">Select Section</option>';
                                    $.each(data, function(key, value) {
                                        options += '<option value="' + value.section_name + '">Section ' + value.section_name + '</option>';
                                    });
                                    $('#section_name').html(options);
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
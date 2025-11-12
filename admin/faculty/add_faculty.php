<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Initialize variables for form data and messages
$name = $faculty_id = $dept_id = "";
$error = "";
$success = "";

// Process manual form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_FILES['csv_file'])) {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $faculty_id = trim($_POST['faculty_id']);
    $dept_id = trim($_POST['dept_id']);

    // Validation
    if (empty($name) || empty($faculty_id) || empty($dept_id)) {
        $error = "All fields are required";
    } elseif (strlen($faculty_id) != 4) {
        $error = "Faculty ID must be 4 characters";
    } else {
        // Check if faculty ID already exists
        $stmt = $conn->prepare("SELECT faculty_id FROM Faculty WHERE faculty_id = ?");
        $stmt->bind_param("s", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Faculty ID already exists";
        } else {
            // Check if department exists
            $stmt = $conn->prepare("SELECT dept_id FROM Departments WHERE dept_id = ?");
            $stmt->bind_param("s", $dept_id);
            $stmt->execute();
            $dept_result = $stmt->get_result();
            
            if ($dept_result->num_rows == 0) {
                $error = "Selected department does not exist";
            } else {
                // Insert faculty data
                $stmt = $conn->prepare("INSERT INTO Faculty (faculty_id, name, dept_id) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $faculty_id, $name, $dept_id);
                
                if ($stmt->execute()) {
                    // Create user account for faculty
                    $default_password = password_hash($faculty_id, PASSWORD_DEFAULT); // Using faculty_id as default password
                    $role = "Faculty";
                    
                    $stmt = $conn->prepare("INSERT INTO Users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $faculty_id, $default_password, $role);
                    $stmt->execute();
                    
                    $success = "Faculty added successfully. Default password is their faculty ID.";
                    // Clear form data after successful submission
                    $name = $faculty_id = $dept_id = "";
                } else {
                    $error = "Error adding faculty: " . $stmt->error;
                }
            }
        }
    }
}

// Process CSV file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    $row = 0;
    $successful_imports = 0;
    $failed_imports = 0;
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // Skip header row
        
        $faculty_id = trim($data[0]);
        $name = trim($data[1]);
        $dept_id = trim($data[2]);
        
        // Validation
        if (empty($faculty_id) || empty($name) || empty($dept_id)) {
            $failed_imports++;
            continue;
        }
        if (strlen($faculty_id) != 4) {
            $failed_imports++;
            continue;
        }
        
        // Check if faculty ID already exists
        $stmt = $conn->prepare("SELECT faculty_id FROM Faculty WHERE faculty_id = ?");
        $stmt->bind_param("s", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $failed_imports++;
            continue;
        }
        
        // Check if department exists
        $stmt = $conn->prepare("SELECT dept_id FROM Departments WHERE dept_id = ?");
        $stmt->bind_param("s", $dept_id);
        $stmt->execute();
        $dept_result = $stmt->get_result();
        
        if ($dept_result->num_rows == 0) {
            $failed_imports++;
            continue;
        }
        
        // Insert faculty data
        $stmt = $conn->prepare("INSERT INTO Faculty (faculty_id, name, dept_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $faculty_id, $name, $dept_id);
        
        if ($stmt->execute()) {
            // Create user account for faculty
            $default_password = password_hash($faculty_id, PASSWORD_DEFAULT);
            $role = "Faculty";
            
            $stmt = $conn->prepare("INSERT INTO Users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $faculty_id, $default_password, $role);
            $stmt->execute();
            $successful_imports++;
        } else {
            $failed_imports++;
        }
    }
    
    fclose($handle);
    $success = "CSV Import completed: $successful_imports faculty members added successfully, $failed_imports failed.";
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
            <h1 class="h2 mb-4">Add New Faculty</h1>
            
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
                <div class="card-header">Add Single Faculty</div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Faculty Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="faculty_id" class="form-label">Faculty ID (4 characters)</label>
                                <input type="text" class="form-control" id="faculty_id" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>" maxlength="4" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dept_id" class="form-label">Department</label>
                                <select class="form-select" id="dept_id" name="dept_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['dept_id']); ?>" <?php if ($dept_id == $dept['dept_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Add Faculty</button>
                            <a href="view_faculty.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Import Faculty from CSV</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <small class="form-text text-muted">
                                CSV format should be: faculty_id,name,dept_id<br>
                                Example: 1000,Dr.K.Sujatha,IT
                            </small>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Import CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
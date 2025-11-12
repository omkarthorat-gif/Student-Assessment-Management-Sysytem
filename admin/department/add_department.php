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
$dept_id = $dept_name = "";
$error = "";
$success = "";

// Process form submission (single department)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_FILES['csv_file'])) {
    // Validate and sanitize input
    $dept_id = strtoupper(trim($_POST['dept_id']));
    $dept_name = trim($_POST['dept_name']);

    // Validation
    if (empty($dept_id) || empty($dept_name)) {
        $error = "All fields are required";
    } elseif (strlen($dept_id) > 10) {
        $error = "Department ID must not exceed 10 characters";
    } elseif (strlen($dept_name) > 100) {
        $error = "Department name must not exceed 100 characters";
    } else {
        // Check if department ID or name already exists
        $stmt = $conn->prepare("SELECT dept_id, dept_name FROM Departments WHERE dept_id = ? OR dept_name = ?");
        $stmt->bind_param("ss", $dept_id, $dept_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['dept_id'] === $dept_id) {
                $error = "Department ID already exists";
            } else {
                $error = "Department name already exists";
            }
        } else {
            // Insert department data
            $stmt = $conn->prepare("INSERT INTO Departments (dept_id, dept_name) VALUES (?, ?)");
            $stmt->bind_param("ss", $dept_id, $dept_name);
            
            if ($stmt->execute()) {
                $success = "Department added successfully";
                $dept_id = $dept_name = "";
            } else {
                $error = "Error adding department: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Process CSV import
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    $row = 0;
    $successful_imports = 0;
    $failed_imports = 0;
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // Skip header row
        
        $dept_id = strtoupper(trim($data[0]));
        $dept_name = trim($data[1]);
        
        // Validation
        if (empty($dept_id) || empty($dept_name) || strlen($dept_id) > 10 || strlen($dept_name) > 100) {
            $failed_imports++;
            continue;
        }
        
        // Check if department exists
        $stmt = $conn->prepare("SELECT dept_id FROM Departments WHERE dept_id = ? OR dept_name = ?");
        $stmt->bind_param("ss", $dept_id, $dept_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $failed_imports++;
            continue;
        }
        
        // Insert department data
        $stmt = $conn->prepare("INSERT INTO Departments (dept_id, dept_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $dept_id, $dept_name);
        
        if ($stmt->execute()) {
            $successful_imports++;
        } else {
            $failed_imports++;
        }
    }
    
    fclose($handle);
    $success = "CSV Import completed: $successful_imports departments added successfully, $failed_imports failed.";
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
            <h1 class="h2 mb-4">Add New Department</h1>
            
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
                <div class="card-header">Add Single Department</div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dept_id" class="form-label">Department ID (max 10 chars)</label>
                                <input type="text" class="form-control" id="dept_id" name="dept_id" value="<?php echo $dept_id; ?>" maxlength="10" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dept_name" class="form-label">Department Name (max 100 chars)</label>
                                <input type="text" class="form-control" id="dept_name" name="dept_name" value="<?php echo $dept_name; ?>" maxlength="100" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Add Department</button>
                            <a href="view_departments.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Import Departments from CSV</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <small class="form-text text-muted">
                                CSV format should be: dept_id,dept_name<br>
                                Example: IT,Information Technology
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
<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Initialize variables
$dept_id = $dept_name = "";
$error = "";
$success = "";

// Check if dept_id is provided
if (!isset($_GET['dept_id']) || empty($_GET['dept_id'])) {
    $_SESSION['error_message'] = "No department selected for editing.";
    header("Location: view_departments.php");
    exit;
}

$original_dept_id = trim($_GET['dept_id']);

// Fetch existing department data
$stmt = $conn->prepare("SELECT dept_id, dept_name FROM Departments WHERE dept_id = ?");
$stmt->bind_param("s", $original_dept_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Department not found.";
    header("Location: view_departments.php");
    exit;
}

$row = $result->fetch_assoc();
$dept_id = $row['dept_id'];
$dept_name = $row['dept_name'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_dept_id = strtoupper(trim($_POST['dept_id']));
    $dept_name = trim($_POST['dept_name']);

    // Validation
    if (empty($new_dept_id) || empty($dept_name)) {
        $error = "All fields are required";
    } elseif (strlen($new_dept_id) > 10) {
        $error = "Department ID must not exceed 10 characters";
    } elseif (strlen($dept_name) > 100) {
        $error = "Department name must not exceed 100 characters";
    } else {
        // Check if new dept_id or dept_name already exists (excluding the current department)
        $stmt = $conn->prepare("SELECT dept_id, dept_name FROM Departments WHERE (dept_id = ? OR dept_name = ?) AND dept_id != ?");
        $stmt->bind_param("sss", $new_dept_id, $dept_name, $original_dept_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['dept_id'] === $new_dept_id) {
                $error = "Department ID already exists";
            } else {
                $error = "Department name already exists";
            }
        } else {
            // Update department data
            $stmt = $conn->prepare("UPDATE Departments SET dept_id = ?, dept_name = ? WHERE dept_id = ?");
            $stmt->bind_param("sss", $new_dept_id, $dept_name, $original_dept_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Department updated successfully";
                header("Location: view_departments.php");
                exit;
            } else {
                $error = "Error updating department: " . $stmt->error;
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-10 offset-md-1">
            <h1 class="h2 mb-4">Edit Department</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">Edit Department Details</div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?dept_id=' . urlencode($original_dept_id); ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dept_id" class="form-label">Department ID (max 10 chars)</label>
                                <input type="text" class="form-control" id="dept_id" name="dept_id" value="<?php echo htmlspecialchars($dept_id); ?>" maxlength="10" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dept_name" class="form-label">Department Name (max 100 chars)</label>
                                <input type="text" class="form-control" id="dept_name" name="dept_name" value="<?php echo htmlspecialchars($dept_name); ?>" maxlength="100" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Update Department</button>
                            <a href="view_departments.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
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
$faculty_id = '';
$faculty_name = '';
$faculty_dept = '';
$departments = [];
$error_messages = [];

// Fetch all departments for dropdown
$dept_query = "SELECT dept_id, dept_name FROM Departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Check if ID is provided in URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No faculty ID provided.";
    header("Location: view_faculty.php");
    exit();
}

$faculty_id = trim($_GET['id']);

// Fetch faculty details
$query = "SELECT * FROM Faculty WHERE faculty_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Faculty member not found.";
    header("Location: view_faculty.php");
    exit();
}

$faculty = $result->fetch_assoc();
$faculty_name = $faculty['name'];
$faculty_dept = $faculty['dept_id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $faculty_name = trim($_POST['name']);
    $faculty_dept = trim($_POST['dept_id']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Basic validation
    if (empty($faculty_name)) {
        $error_messages[] = "Faculty name is required.";
    }
    
    if (empty($faculty_dept)) {
        $error_messages[] = "Department is required.";
    }
    
    // Password validation (only if password field is not empty)
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $error_messages[] = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error_messages[] = "Passwords do not match.";
        }
    }
    
    // If no errors, proceed with update
    if (empty($error_messages)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Update Faculty table
            $update_faculty = "UPDATE Faculty SET name = ?, dept_id = ? WHERE faculty_id = ?";
            $stmt = $conn->prepare($update_faculty);
            $stmt->bind_param("sss", $faculty_name, $faculty_dept, $faculty_id);
            $stmt->execute();
            
            // Update Users table (password only if provided)
            if (!empty($password)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $update_user = "UPDATE Users SET password = ? WHERE username = ?";
                $stmt = $conn->prepare($update_user);
                $stmt->bind_param("ss", $hashed_password, $faculty_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success'] = "Faculty information updated successfully.";
            header("Location: view_faculty.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_messages[] = "Error updating faculty: " . $e->getMessage();
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Edit Faculty Member</h1>
                <a href="view_faculty.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Faculty List
                </a>
            </div>
            
            <?php if (!empty($error_messages)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                    <ul class="mb-0">
                        <?php foreach ($error_messages as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Faculty Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . urlencode($faculty_id)); ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="faculty_id" class="form-label">Faculty ID</label>
                                <input type="text" class="form-control" id="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>" readonly>
                                <div class="form-text text-muted">Faculty ID cannot be changed</div>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($faculty_name); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="dept_id" class="form-label">Department</label>
                                <select class="form-select" id="dept_id" name="dept_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['dept_id']); ?>" 
                                                <?php if ($dept['dept_id'] == $faculty_dept) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        

                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="view_faculty.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
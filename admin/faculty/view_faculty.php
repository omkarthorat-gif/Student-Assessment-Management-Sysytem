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
$search = "";
$departments = [];
$selected_dept = "";
$faculty_list = [];
$success_message = "";
$error_message = "";

// Display session messages if any
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Process delete request if any
if (isset($_POST['delete']) && isset($_POST['faculty_id'])) {
    $faculty_id = $_POST['faculty_id'];
    
    // Check if faculty has subject assignments
    $check_assignments = $conn->prepare("SELECT COUNT(*) as count FROM Faculty_Subject_Assign WHERE faculty_id = ?");
    $check_assignments->bind_param("s", $faculty_id);
    $check_assignments->execute();
    $assignment_result = $check_assignments->get_result();
    $assignment_count = $assignment_result->fetch_assoc()['count'];
    
    if ($assignment_count > 0) {
        $error_message = "Cannot delete faculty member. Please remove all subject assignments first.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First delete from Users table
            $stmt = $conn->prepare("DELETE FROM Users WHERE username = ?");
            $stmt->bind_param("s", $faculty_id);
            $stmt->execute();
            
            // Then delete from Faculty table
            $stmt = $conn->prepare("DELETE FROM Faculty WHERE faculty_id = ?");
            $stmt->bind_param("s", $faculty_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            $success_message = "Faculty member deleted successfully.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error deleting faculty member: " . $e->getMessage();
        }
    }
}

// Get all departments for filter
$dept_query = "SELECT dept_id, dept_name FROM Departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Handle search and filters
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    }
    
    if (isset($_GET['department'])) {
        $selected_dept = $_GET['department'];
    }
}

// Build the query based on filters
$query = "SELECT f.faculty_id, f.name, d.dept_name, d.dept_id,
          (SELECT COUNT(*) FROM Faculty_Subject_Assign WHERE faculty_id = f.faculty_id) as subject_count
          FROM Faculty f
          JOIN Departments d ON f.dept_id = d.dept_id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (f.name LIKE ? OR f.faculty_id LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($selected_dept)) {
    $query .= " AND f.dept_id = ?";
    $params[] = $selected_dept;
    $types .= "s";
}

$query .= " ORDER BY f.name";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch the results
while ($row = $result->fetch_assoc()) {
    $faculty_list[] = $row;
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
                <h1 class="h2">Faculty Management</h1>
                <a href="add_faculty.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Add New Faculty
                </a>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Search and filter -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Search & Filter</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Search by name or ID" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary" type="submit">
                                    Search
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <select class="form-select" name="department" onchange="this.form.submit()">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['dept_id']); ?>" <?php if ($selected_dept == $dept['dept_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <a href="view_faculty.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Faculty list -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Faculty Directory</h5>
                        <span class="badge bg-primary"><?php echo count($faculty_list); ?> faculty members</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($faculty_list) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="facultyTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Faculty ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Subjects</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faculty_list as $faculty): ?>
                                        <tr>
                                            <td class="ps-3 align-middle"><?php echo htmlspecialchars($faculty['faculty_id']); ?></td>
                                            <td class="align-middle fw-medium"><?php echo htmlspecialchars($faculty['name']); ?></td>
                                            <td class="align-middle"><?php echo htmlspecialchars($faculty['dept_name']); ?></td>
                                            <td class="align-middle">
                                                <span class="badge rounded-pill <?php echo $faculty['subject_count'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $faculty['subject_count']; ?> subjects
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="assign_subject.php?faculty_id=<?php echo urlencode($faculty['faculty_id']); ?>" 
                                                       class="btn btn-sm btn-primary" title="Assign Subjects">
                                                        <i class="fas fa-book me-1"></i> Assign Subjects
                                                    </a>
                                                    <a href="edit_faculty.php?id=<?php echo urlencode($faculty['faculty_id']); ?>" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal<?php echo $faculty['faculty_id']; ?>"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $faculty['faculty_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="deleteModalLabel">
                                                                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete faculty member <strong><?php echo htmlspecialchars($faculty['name']); ?></strong>?</p>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-info-circle me-2"></i>This action cannot be undone and will also delete the associated user account.
                                                                </div>
                                                                <?php if ($faculty['subject_count'] > 0): ?>
                                                                    <div class="alert alert-danger">
                                                                        <i class="fas fa-ban me-2"></i>This faculty has <?php echo $faculty['subject_count']; ?> subject assignments. Please remove them first.
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    <i class="fas fa-times me-1"></i>Cancel
                                                                </button>
                                                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                                    <input type="hidden" name="faculty_id" value="<?php echo $faculty['faculty_id']; ?>">
                                                                    <button type="submit" name="delete" class="btn btn-danger" 
                                                                           <?php if ($faculty['subject_count'] > 0): ?>disabled<?php endif; ?>>
                                                                        <i class="fas fa-trash me-1"></i>Delete Faculty
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info m-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>No faculty members found. 
                            <?php if (!empty($search) || !empty($selected_dept)): ?>Try changing your search criteria.<?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Initialize DataTable -->
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#facultyTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "responsive": true,
            "order": [[1, "asc"]],
            "columnDefs": [
                { "orderable": false, "targets": 4 }
            ],
            "language": {
                "search": "Quick search:",
                "lengthMenu": "Show _MENU_ faculty members",
                "info": "Showing _START_ to _END_ of _TOTAL_ faculty members"
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
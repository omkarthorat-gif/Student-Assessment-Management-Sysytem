<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Handle subject deletion
if (isset($_GET['delete']) && isset($_GET['year']) && isset($_GET['semester'])) {
    $subject_id = $_GET['delete'];
    $year = $_GET['year'];
    $semester = $_GET['semester'];
    
    // First check if subject is allocated or assigned to faculty
    $check_query = "SELECT * FROM Subject_Allocation WHERE subject_id = ? AND year = ? AND semester = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("sii", $subject_id, $year, $semester);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Cannot delete subject. It is allocated to one or more sections.";
    } else {
        // Delete subject
        $delete_query = "DELETE FROM Subjects WHERE subject_id = ? AND year = ? AND semester = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("sii", $subject_id, $year, $semester);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Subject deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting subject: " . $stmt->error;
        }
    }
    
    // Redirect to avoid resubmission on refresh
    header("Location: view_subjects.php");
    exit();
}

// Pagination variables
$limit = 10; // Records per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search and filter variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_dept = isset($_GET['dept_id']) ? $_GET['dept_id'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$filter_semester = isset($_GET['semester']) ? $_GET['semester'] : '';

// Build the WHERE clause for filtering
$where_clause = "1=1"; // Always true condition to start
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause .= " AND (s.subject_id LIKE ? OR s.subject_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($filter_dept)) {
    $where_clause .= " AND s.dept_id = ?";
    $params[] = $filter_dept;
    $types .= "s";
}

if (!empty($filter_year)) {
    $where_clause .= " AND s.year = ?";
    $params[] = $filter_year;
    $types .= "i";
}

if (!empty($filter_semester)) {
    $where_clause .= " AND s.semester = ?";
    $params[] = $filter_semester;
    $types .= "i";
}

// Fetch subjects with department name
$query = "SELECT s.*, d.dept_name 
          FROM Subjects s
          JOIN Departments d ON s.dept_id = d.dept_id
          WHERE $where_clause
          ORDER BY s.subject_name
          LIMIT ?, ?";

$stmt = $conn->prepare($query);
// Add pagination parameters
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

// Bind parameters dynamically
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM Subjects s WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);

// Bind parameters for count query (excluding pagination params)
if (!empty($params)) {
    // Remove the last two parameters (offset and limit)
    array_pop($params);
    array_pop($params);
    $count_types = substr($types, 0, -2);
    if (!empty($count_types)) {
        $count_stmt->bind_param($count_types, ...$params);
    }
}

$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_records = $count_result['total'];
$total_pages = ceil($total_records / $limit);

// Fetch departments for filter dropdown
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
                <h1 class="h2">Manage Subjects</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_subject.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add New Subject
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
            
            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo $search; ?>" placeholder="Subject ID or Name">
                        </div>
                        <div class="col-md-3">
                            <label for="dept_id" class="form-label">Department</label>
                            <select class="form-select" id="dept_id" name="dept_id">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>" <?php if ($filter_dept == $dept['dept_id']) echo 'selected'; ?>>
                                        <?php echo $dept['dept_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <option value="">All Years</option>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if ($filter_year == $i) echo 'selected'; ?>>
                                        Year <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester">
                                <option value="">All Semesters</option>
                                <option value="1" <?php if ($filter_semester == 1) echo 'selected'; ?>>Semester 1</option>
                                <option value="2" <?php if ($filter_semester == 2) echo 'selected'; ?>>Semester 2</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mb-3 me-2">Filter</button>
                            <a href="view_subjects.php" class="btn btn-secondary mb-3">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Subjects Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Subject ID</th>
                                    <th>Subject Name</th>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($subjects) > 0): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo $subject['subject_id']; ?></td>
                                            <td><?php echo $subject['subject_name']; ?></td>
                                            <td><?php echo $subject['dept_name']; ?></td>
                                            <td>Year <?php echo $subject['year']; ?></td>
                                            <td>Semester <?php echo $subject['semester']; ?></td>
                                            <td>
                                                <a href="edit_subject.php?id=<?php echo $subject['subject_id']; ?>&year=<?php echo $subject['year']; ?>&semester=<?php echo $subject['semester']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No subjects found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo $search; ?>&dept_id=<?php echo $filter_dept; ?>&year=<?php echo $filter_year; ?>&semester=<?php echo $filter_semester; ?>">Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&dept_id=<?php echo $filter_dept; ?>&year=<?php echo $filter_year; ?>&semester=<?php echo $filter_semester; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo $search; ?>&dept_id=<?php echo $filter_dept; ?>&year=<?php echo $filter_year; ?>&semester=<?php echo $filter_semester; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
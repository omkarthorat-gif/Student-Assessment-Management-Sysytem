<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Initialize filters
$dept_filter = $_GET['dept_id'] ?? '';
$year_filter = $_GET['year'] ?? '';
$semester_filter = $_GET['semester'] ?? '';

// Fetch departments for dropdowns
$dept_query = "SELECT dept_id, dept_name FROM Departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Build query based on filters with explicit aliases
$query = "SELECT sa.dept_id, d.dept_name, sa.year, sa.semester, 
          sa.section_name, sa.subject_id, s.subject_name,
          f.faculty_id, f.name AS faculty_name
          FROM Subject_Allocation sa
          JOIN Departments d ON sa.dept_id = d.dept_id
          JOIN Subjects s ON sa.subject_id = s.subject_id 
               AND sa.year = s.year AND sa.semester = s.semester
          LEFT JOIN Faculty_Subject_Assign fsa ON sa.subject_id = fsa.subject_id
               AND sa.year = fsa.year AND sa.semester = fsa.semester
               AND sa.section_name = fsa.section_name AND sa.dept_id = fsa.dept_id
          LEFT JOIN Faculty f ON fsa.faculty_id = f.faculty_id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($dept_filter)) {
    $query .= " AND sa.dept_id = ?";
    $params[] = $dept_filter;
    $types .= "s";
}

if (!empty($year_filter)) {
    $query .= " AND sa.year = ?";
    $params[] = $year_filter;
    $types .= "i";
}

if (!empty($semester_filter)) {
    $query .= " AND sa.semester = ?";
    $params[] = $semester_filter;
    $types .= "i";
}

$query .= " ORDER BY sa.dept_id, sa.year, sa.semester, sa.section_name, sa.subject_id";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Debugging aid
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error); // Debugging aid
}
$alloc_result = $stmt->get_result();

// Get total count of allocations
$count_query = "SELECT COUNT(*) as total FROM Subject_Allocation";
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'];

// Get filtered count
$filtered_count = $alloc_result->num_rows;

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
                <h1 class="h2">View Subject Allocations</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="allocate_subject.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Allocate Subjects
                    </a>
                </div>
            </div>
            
            <!-- Filter Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filter Allocations
                    </h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="dept_id" class="form-label">Department</label>
                            <select class="form-select" id="dept_id" name="dept_id">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>" 
                                        <?php echo ($dept_filter == $dept['dept_id']) ? 'selected' : ''; ?>>
                                        <?php echo $dept['dept_name'] . ' (' . $dept['dept_id'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <option value="">All Years</option>
                                <option value="1" <?php echo ($year_filter == '1') ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2" <?php echo ($year_filter == '2') ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3" <?php echo ($year_filter == '3') ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4" <?php echo ($year_filter == '4') ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester">
                                <option value="">All Semesters</option>
                                <option value="1" <?php echo ($semester_filter == '1') ? 'selected' : ''; ?>>Semester 1</option>
                                <option value="2" <?php echo ($semester_filter == '2') ? 'selected' : ''; ?>>Semester 2</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted">
                    Showing <?php echo $filtered_count; ?> of <?php echo $total_count; ?> allocations
                    <?php if (!empty($dept_filter) || !empty($year_filter) || !empty($semester_filter)): ?>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Export buttons -->
            <div class="mb-3">
                <button type="button" class="btn btn-success me-2" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </button>
            </div>

            <!-- Allocations Table -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> Subject Allocations
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($filtered_count > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="allocationsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Semester</th>
                                        <th>Section</th>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Assigned Faculty</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $alloc_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['dept_name'] . ' (' . $row['dept_id'] . ')'; ?></td>
                                        <td>Year <?php echo $row['year']; ?></td>
                                        <td>Semester <?php echo $row['semester']; ?></td>
                                        <td><?php echo $row['section_name']; ?></td>
                                        <td><?php echo $row['subject_id']; ?></td>
                                        <td><?php echo $row['subject_name']; ?></td>
                                        <td>
                                            <?php if (!empty($row['faculty_id'])): ?>
                                                <?php echo $row['faculty_name'] . ' (' . $row['faculty_id'] . ')'; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if (empty($row['faculty_id'])): ?>
                                                    <a href="assign_faculty.php?subject_id=<?php echo $row['subject_id']; ?>&year=<?php echo $row['year']; ?>&semester=<?php echo $row['semester']; ?>&section=<?php echo $row['section_name']; ?>&dept_id=<?php echo $row['dept_id']; ?>" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-user-plus"></i> Assign Faculty
                                                    </a>
                                                <?php else: ?>
                                                    <a href="assign_faculty.php?subject_id=<?php echo $row['subject_id']; ?>&year=<?php echo $row['year']; ?>&semester=<?php echo $row['semester']; ?>&section=<?php echo $row['section_name']; ?>&dept_id=<?php echo $row['dept_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-user-edit"></i> Change Faculty
                                                    </a>
                                                <?php endif; ?>
                                                <a href="allocate_subject.php?remove=true&subject_id=<?php echo $row['subject_id']; ?>&year=<?php echo $row['year']; ?>&semester=<?php echo $row['semester']; ?>&section=<?php echo $row['section_name']; ?>&dept_id=<?php echo $row['dept_id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to remove this allocation?')">
                                                    <i class="fas fa-trash"></i> Remove
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No subject allocations found with the selected filters.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mt-4">
                <?php 
                // Get summary counts with explicit aliases
                $summary_query = "SELECT 
                    COUNT(DISTINCT CONCAT(sa.dept_id, sa.year, sa.semester, sa.section_name)) AS total_classes,
                    COUNT(DISTINCT sa.subject_id) AS total_subjects,
                    SUM(CASE WHEN fsa.faculty_id IS NULL THEN 1 ELSE 0 END) AS unassigned_count
                FROM Subject_Allocation sa
                LEFT JOIN Faculty_Subject_Assign fsa ON sa.subject_id = fsa.subject_id
                    AND sa.year = fsa.year AND sa.semester = fsa.semester
                    AND sa.section_name = fsa.section_name AND sa.dept_id = fsa.dept_id";
                
                $summary_result = $conn->query($summary_query);
                $summary = $summary_result->fetch_assoc();
                ?>
                
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-clipboard-list"></i> Total Allocations</h5>
                            <p class="card-text display-4"><?php echo $total_count; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chalkboard"></i> Total Classes</h5>
                            <p class="card-text display-4"><?php echo $summary['total_classes']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-exclamation-triangle"></i> Unassigned Allocations</h5>
                            <p class="card-text display-4"><?php echo $summary['unassigned_count']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#allocationsTable').DataTable({
            pageLength: 25,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    });
    
    // Export functions
    function exportToExcel() {
        let table = document.getElementById('allocationsTable');
        let html = table.outerHTML;
        let blob = new Blob([html], {type: 'application/vnd.ms-excel'});
        let a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'subject_allocations.xls';
        a.click();
    }
    
    function exportToPDF() {
        window.print();
    }
</script>

<style>
    @media print {
        .container-fluid { width: 100%; }
        #sidebar, .btn-toolbar, .card-header, .card-footer, .btn-group, .btn, form { display: none !important; }
        main { margin-left: 0 !important; width: 100% !important; }
        .card { border: none !important; }
        .badge.bg-warning { color: black !important; background-color: #ffcd39 !important; padding: 5px; border-radius: 4px; }
        th { background-color: #333 !important; color: white !important; }
        table { width: 100% !important; }
    }
</style>

<?php include '../includes/footer.php'; ?>
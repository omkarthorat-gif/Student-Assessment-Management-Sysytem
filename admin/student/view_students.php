<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../config.php';

// Check if admin is logged in
include '../includes/session_check.php';

// Export handlers
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    $query = "SELECT s.*, d.dept_name 
              FROM Students s
              JOIN Departments d ON s.dept_id = d.dept_id
              WHERE 1=1";
    $params = [];
    $types = "";
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if (!empty($search)) {
        $search_term = "%$search%";
        $query .= " AND (s.reg_no LIKE ? OR s.name LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }
    
    $dept_filter = isset($_GET['dept_id']) ? trim($_GET['dept_id']) : '';
    if (!empty($dept_filter)) {
        $query .= " AND s.dept_id = ?";
        $params[] = $dept_filter;
        $types .= "s";
    }
    
    $year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;
    if ($year_filter > 0) {
        $query .= " AND s.year = ?";
        $params[] = $year_filter;
        $types .= "i";
    }
    
    $section_filter = isset($_GET['section_name']) ? trim($_GET['section_name']) : '';
    if (!empty($section_filter)) {
        $query .= " AND s.section_name = ?";
        $params[] = $section_filter;
        $types .= "s";
    }
    
    $semester_filter = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
    if ($semester_filter > 0) {
        $query .= " AND s.semester = ?";
        $params[] = $semester_filter;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $ref_params = [];
        $ref_params[] = &$types;
        foreach($params as $key => $value) {
            $ref_params[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $ref_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($export_type === 'excel') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="students_list.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Reg No', 'Name', 'Department', 'Year', 'Section', 'Semester']);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['reg_no'],
                $row['name'],
                $row['dept_name'],
                'Year ' . $row['year'],
                'Section ' . $row['section_name'],
                'Semester ' . $row['semester']
            ]);
        }
        fclose($output);
        exit;
    }
    
    if ($export_type === 'pdf') {
        require_once '../../vendor/autoload.php'; // Assuming you're using TCPDF via Composer
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('School System');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Students List');
        $pdf->SetSubject('Students Report');
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        
        $html = '<h1>Students List</h1>
                <table border="1" cellpadding="4">
                    <thead>
                        <tr style="background-color: #ddd;">
                            <th>Reg No</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Section</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['reg_no']) . '</td>
                <td>' . htmlspecialchars($row['name']) . '</td>
                <td>' . htmlspecialchars($row['dept_name']) . '</td>
                <td>Year ' . htmlspecialchars($row['year']) . '</td>
                <td>Section ' . htmlspecialchars($row['section_name']) . '</td>
                <td>Semester ' . htmlspecialchars($row['semester']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('students_list.pdf', 'D');
        exit;
    }
}

// Session messages and rest of the code
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> ' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['error_message']);
}

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept_filter = isset($_GET['dept_id']) ? trim($_GET['dept_id']) : '';
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;
$section_filter = isset($_GET['section_name']) ? trim($_GET['section_name']) : '';
$semester_filter = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$query = "SELECT s.*, d.dept_name 
          FROM Students s
          JOIN Departments d ON s.dept_id = d.dept_id
          WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM Students s WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (s.reg_no LIKE ? OR s.name LIKE ?)";
    $count_query .= " AND (s.reg_no LIKE ? OR s.name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($dept_filter)) {
    $query .= " AND s.dept_id = ?";
    $count_query .= " AND s.dept_id = ?";
    $params[] = $dept_filter;
    $types .= "s";
}

if ($year_filter > 0) {
    $query .= " AND s.year = ?";
    $count_query .= " AND s.year = ?";
    $params[] = $year_filter;
    $types .= "i";
}

if (!empty($section_filter)) {
    $query .= " AND s.section_name = ?";
    $count_query .= " AND s.section_name = ?";
    $params[] = $section_filter;
    $types .= "s";
}

if ($semester_filter > 0) {
    $query .= " AND s.semester = ?";
    $count_query .= " AND s.semester = ?";
    $params[] = $semester_filter;
    $types .= "i";
}

$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_params = $params;
    $ref_count_params = [];
    $ref_count_params[] = &$types;
    foreach($count_params as $key => $value) {
        $ref_count_params[] = &$count_params[$key];
    }
    call_user_func_array([$count_stmt, 'bind_param'], $ref_count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

$query .= " ORDER BY s.reg_no ASC LIMIT ?, ?";
$main_params = $params;
$main_params[] = $offset;
$main_params[] = $records_per_page;
$main_types = $types . "ii";

$stmt = $conn->prepare($query);
if (!empty($main_params)) {
    $ref_main_params = [];
    $ref_main_params[] = &$main_types;
    foreach($main_params as $key => $value) {
        $ref_main_params[] = &$main_params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $ref_main_params);
}
$stmt->execute();
$result = $stmt->get_result();

$dept_query = "SELECT dept_id, dept_name FROM Departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-10 offset-md-1">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Students</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_student.php" class="btn btn-sm btn-primary me-2">
                        <i class="fas fa-plus"></i> Add New Student
                    </a>
                    <button id="printBtn" class="btn btn-sm btn-info me-2">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="?export=excel&search=<?php echo urlencode($search); ?>&dept_id=<?php echo urlencode($dept_filter); ?>&year=<?php echo $year_filter; ?>§ion_name=<?php echo urlencode($section_filter); ?>&semester=<?php echo $semester_filter; ?>" class="btn btn-sm btn-success me-2">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="?export=pdf&search=<?php echo urlencode($search); ?>&dept_id=<?php echo urlencode($dept_filter); ?>&year=<?php echo $year_filter; ?>§ion_name=<?php echo urlencode($section_filter); ?>&semester=<?php echo $semester_filter; ?>" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            
            <div class="card mb-4 no-print">
                <div class="card-body">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                        <!-- Filter form remains same -->
                        <div class="col-md-4 col-sm-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or Registration Number">
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label for="dept_id" class="form-label">Department</label>
                            <select class="form-select" id="dept_id" name="dept_id">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>" <?php if ($dept_filter == $dept['dept_id']) echo 'selected'; ?>>
                                        <?php echo $dept['dept_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <option value="">All Years</option>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if ($year_filter == $i) echo 'selected'; ?>>
                                        Year <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label for="section_name" class="form-label">Section</label>
                            <select class="form-select" id="section_name" name="section_name">
                                <option value="">All Sections</option>
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $section): ?>
                                    <option value="<?php echo $section; ?>" <?php if ($section_filter == $section) echo 'selected'; ?>>
                                        Section <?php echo $section; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester">
                                <option value="">All Semesters</option>
                                <option value="1" <?php if ($semester_filter == 1) echo 'selected'; ?>>Semester 1</option>
                                <option value="2" <?php if ($semester_filter == 2) echo 'selected'; ?>>Semester 2</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="printArea">
                <?php if ($result->num_rows > 0): ?>
                    <table class="table table-striped table-hover" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Reg No</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Section</th>
                                <th>Semester</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                    <td>Year <?php echo htmlspecialchars($row['year']); ?></td>
                                    <td>Section <?php echo htmlspecialchars($row['section_name']); ?></td>
                                    <td>Semester <?php echo htmlspecialchars($row['semester']); ?></td>
                                    <td class="no-print">
                                        <a href="edit_student.php?reg_no=<?php echo urlencode($row['reg_no']); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Edit</span>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-reg-no="<?php echo htmlspecialchars($row['reg_no']); ?>" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <nav class="no-print" aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="<?php if ($page <= 1) echo '#'; else echo '?page=' . ($page - 1) . '&search=' . urlencode($search) . '&dept_id=' . urlencode($dept_filter) . '&year=' . $year_filter . '§ion_name=' . urlencode($section_filter) . '&semester=' . $semester_filter; ?>">
                                    Previous
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&dept_id=<?php echo urlencode($dept_filter); ?>&year=<?php echo $year_filter; ?>§ion_name=<?php echo urlencode($section_filter); ?>&semester=<?php echo $semester_filter; ?>" 
       style="<?php if ($page == $i) echo 'color: white; background-color: blue; font-weight: bold;'; ?>">
        <?php echo $i; ?>
    </a>
</li>

                            <?php endfor; ?>
                            <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                <a class="page-link" href="<?php if ($page >= $total_pages) echo '#'; else echo '?page=' . ($page + 1) . '&search=' . urlencode($search) . '&dept_id=' . urlencode($dept_filter) . '&year=' . $year_filter . '§ion_name=' . urlencode($section_filter) . '&semester=' . $semester_filter; ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info no-print" role="alert">
                        <i class="fas fa-info-circle"></i> No students found. Please try different search criteria or <a href="add_student.php" class="alert-link">add a new student</a>.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<div class="modal fade no-print" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this student? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="delete_student.php" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        
        #printArea, #printArea * {
            visibility: visible;
        }
        
        #printArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 20mm;
        }
        
        .no-print {
            display: none !important;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            border: 1px solid #000;
            padding: 8px;
        }
        
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.delete-btn').click(function() {
        var regNo = $(this).data('reg-no');
        $('#confirmDelete').attr('href', 'delete_student.php?reg_no=' + encodeURIComponent(regNo));
    });
    
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
                    var options = '<option value="">All Sections</option>';
                    $.each(data, function(key, value) {
                        var selected = (value.section_name == '<?php echo $section_filter; ?>') ? 'selected' : '';
                        options += '<option value="' + value.section_name + '" ' + selected + '>Section ' + value.section_name + '</option>';
                    });
                    $('#section_name').html(options);
                }
            });
        }
    });
    
    $('#printBtn').click(function(e) {
        e.preventDefault();
        window.print();
    });
    
    function adjustTableForSmallScreens() {
        if (window.innerWidth < 768) {
            $('.d-md-inline').addClass('d-none');
        } else {
            $('.d-md-inline').removeClass('d-none');
        }
    }
    
    adjustTableForSmallScreens();
    $(window).resize(adjustTableForSmallScreens);
});
</script>

<?php include '../includes/footer.php'; ?>
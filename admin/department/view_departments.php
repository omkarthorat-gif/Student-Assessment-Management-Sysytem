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
    
    $query = "SELECT dept_id, dept_name FROM Departments WHERE 1=1";
    $params = [];
    $types = "";
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if (!empty($search)) {
        $search_term = "%$search%";
        $query .= " AND (dept_id LIKE ? OR dept_name LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
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
        header('Content-Disposition: attachment; filename="departments_list.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Department ID', 'Department Name']);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['dept_id'],
                $row['dept_name']
            ]);
        }
        fclose($output);
        exit;
    }
    
    if ($export_type === 'pdf') {
        require_once '../../vendor/autoload.php'; // Assuming TCPDF is installed via Composer
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('School System');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Departments List');
        $pdf->SetSubject('Departments Report');
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        
        $html = '<h1>Departments List</h1>
                <table border="1" cellpadding="4">
                    <thead>
                        <tr style="background-color: #ddd;">
                            <th>Department ID</th>
                            <th>Department Name</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['dept_id']) . '</td>
                <td>' . htmlspecialchars($row['dept_name']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('departments_list.pdf', 'D');
        exit;
    }
}

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$query = "SELECT dept_id, dept_name FROM Departments WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM Departments WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (dept_id LIKE ? OR dept_name LIKE ?)";
    $count_query .= " AND (dept_id LIKE ? OR dept_name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
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

$query .= " ORDER BY dept_name ASC LIMIT ?, ?";
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

include '../includes/header.php';
?>

<!-- Alert Container for top-right notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="toast show bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <i class="fas fa-check-circle me-2"></i>
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?php echo $_SESSION['success_message']; ?>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="toast show bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong class="me-auto">Error</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?php echo $_SESSION['error_message']; ?>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
</div>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-10 offset-md-1">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Departments</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_department.php" class="btn btn-sm btn-primary me-2">
                        <i class="fas fa-plus"></i> Add New Department
                    </a>
                    <button id="printBtn" class="btn btn-sm btn-info me-2">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="?export=excel&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-success me-2">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="?export=pdf&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            
            <div class="card mb-4 no-print">
                <div class="card-body">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                        <div class="col-md-6 col-sm-12">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Department ID or Name">
                        </div>
                        <div class="col-md-6 col-sm-12 d-flex align-items-end">
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
                    <table class="table table-striped table-hover" id="departmentsTable">
                        <thead>
                            <tr>
                                <th>Department ID</th>
                                <th>Department Name</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['dept_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                    <td class="no-print">
                                        <a href="edit_department.php?dept_id=<?php echo urlencode($row['dept_id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Edit</span>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-dept-id="<?php echo htmlspecialchars($row['dept_id']); ?>" data-bs-toggle="modal" data-bs-target="#deleteModal">
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
                                <a class="page-link" href="<?php if ($page <= 1) echo '#'; else echo '?page=' . ($page - 1) . '&search=' . urlencode($search); ?>">
                                    Previous
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                       style="<?php if ($page == $i) echo 'color: white; background-color: blue; font-weight: bold;'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                <a class="page-link" href="<?php if ($page >= $total_pages) echo '#'; else echo '?page=' . ($page + 1) . '&search=' . urlencode($search); ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info no-print" role="alert">
                        <i class="fas fa-info-circle"></i> No departments found. Please try different search criteria or <a href="add_department.php" class="alert-link">add a new department</a>.
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
                Are you sure you want to delete this department? This action cannot be undone and may affect related data (e.g., students, faculty).
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="delete_department.php" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Toast notification styling */
    .toast {
        width: 350px;
        max-width: 100%;
        font-size: 0.9rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        backdrop-filter: blur(10px);
    }
    
    .toast-header .fas {
        font-size: 1.1rem;
    }
    
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
            size: A4 portrait;
            margin: 15mm;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.delete-btn').click(function() {
        var deptId = $(this).data('dept-id');
        $('#confirmDelete').attr('href', 'delete_department.php?dept_id=' + encodeURIComponent(deptId));
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
    
    // Auto-dismiss toast notifications after 3 seconds
    setTimeout(function() {
        $('.toast').toast('hide');
    }, 3000);
});
</script>

<?php include '../includes/footer.php'; ?>
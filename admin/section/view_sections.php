<?php
ob_start();
require_once '../includes/session_check.php';
require_once '../../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    ob_end_flush();
    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';

// Get filter values from GET parameters
$dept_filter = isset($_GET['dept']) ? $_GET['dept'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';

// Build the query with filters
$query = "
    SELECT s.section_name, s.year, s.dept_id, d.dept_name 
    FROM sections s 
    JOIN departments d ON s.dept_id = d.dept_id
    WHERE 1=1
";
if ($dept_filter) {
    $query .= " AND s.dept_id = '" . mysqli_real_escape_string($conn, $dept_filter) . "'";
}
if ($year_filter) {
    $query .= " AND s.year = '" . mysqli_real_escape_string($conn, $year_filter) . "'";
}
$query .= " ORDER BY s.year, s.section_name, d.dept_name";

$result = mysqli_query($conn, $query);
if (!$result) {
    $error = "Failed to fetch sections: " . mysqli_error($conn);
}

// Get departments for dropdown
$dept_query = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name";
$dept_result = mysqli_query($conn, $dept_query);
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h2 class="mb-1 fw-bold">View Sections</h2>
                <p class="text-muted mb-0">Browse and filter sections by year and department</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="add_section.php" class="btn btn-primary me-2 shadow-sm">
                    <i class="fas fa-plus me-1"></i> Add New Section
                </a>
                <a href="../dashboard/index.php" class="btn btn-outline-primary shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0 fw-semibold">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="dept" class="form-label fw-medium">Department</label>
                        <select name="dept" id="dept" class="form-select">
                            <option value="">All Departments</option>
                            <?php while ($dept = mysqli_fetch_assoc($dept_result)): ?>
                                <option value="<?php echo htmlspecialchars($dept['dept_id']); ?>"
                                    <?php echo $dept_filter === $dept['dept_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="year" class="form-label fw-medium">Year</label>
                        <select name="year" id="year" class="form-select">
                            <option value="">All Years</option>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" 
                                    <?php echo $year_filter === (string)$i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">
                            <i class="fas fa-filter me-1"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sections Table Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0 fw-semibold">Sections List</h5>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="fw-medium">Section</th>
                                    <th scope="col" class="fw-medium">Year</th>
                                    <th scope="col" class="fw-medium">Department</th>
                                    <th scope="col" class="fw-medium">Department ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['dept_id']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info m-3 shadow-sm" role="alert">
                        No sections found matching the selected filters.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.main-content {
    padding: 20px;
    background-color: #f8f9fa;
    min-height: 100vh;
}

.card {
    border: none;
    border-radius: 8px;
    overflow: hidden;
}

.card-header {
    border-bottom: 1px solid #e9ecef;
    padding: 15px 20px;
}

.table th,
.table td {
    vertical-align: middle;
    padding: 12px 15px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}

.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.btn-outline-primary {
    border-color: #007bff;
    color: #007bff;
}

.btn-outline-primary:hover {
    background-color: #007bff;
    color: white;
}

.shadow-sm {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = 0;
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
        setTimeout(() => {
            card.style.opacity = 1;
            card.style.transform = 'translateY(0)';
        }, 100 + (index * 100));
    });
});
</script>

<?php
mysqli_free_result($result);
mysqli_free_result($dept_result);
mysqli_close($conn);
include '../includes/footer.php';
ob_end_flush();
?>
<?php
ob_start();
require_once '../includes/session_check.php';
require_once '../../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../dashboard/index.php");
    ob_end_flush();
    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';

// Handle form submission (manual entry)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['csv_file'])) {
    $section_name = strtoupper(trim($_POST['section_name'] ?? ''));
    $year = (int)($_POST['year'] ?? 0);
    $dept_id = trim($_POST['dept_id'] ?? '');

    $valid_sections = ['A', 'B', 'C', 'D', 'E', 'F'];
    if (!in_array($section_name, $valid_sections)) {
        $error = "Invalid section name. Must be A, B, C, D, E, or F.";
    } elseif ($year < 1 || $year > 4) {
        $error = "Year must be between 1 and 4.";
    } elseif (empty($dept_id)) {
        $error = "Department is required.";
    } else {
        $check_query = "SELECT * FROM sections WHERE section_name = ? AND year = ? AND dept_id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sis", $section_name, $year, $dept_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $error = "This section already exists for the selected year and department.";
            } else {
                $insert_query = "INSERT INTO sections (section_name, year, dept_id) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sis", $section_name, $year, $dept_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Section added successfully!";
                    } else {
                        $error = "Failed to add section: " . mysqli_error($conn);
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
        $csv = array_map('str_getcsv', file($file['tmp_name']));
        $header = array_shift($csv); // Remove and get header row
        
        $expected_headers = ['section_name', 'year', 'dept_id'];
        if ($header !== $expected_headers) {
            $error = "Invalid CSV format. Expected headers: section_name, year, dept_id";
        } else {
            $valid_sections = ['A', 'B', 'C', 'D', 'E', 'F'];
            $inserted = 0;
            $skipped = 0;
            
            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO sections (section_name, year, dept_id) VALUES (?, ?, ?)");
                foreach ($csv as $row) {
                    $section_name = strtoupper(trim($row[0]));
                    $year = (int)$row[1];
                    $dept_id = trim($row[2]);

                    if (!in_array($section_name, $valid_sections) || $year < 1 || $year > 4 || empty($dept_id)) {
                        $skipped++;
                        continue;
                    }

                    mysqli_stmt_bind_param($stmt, "sis", $section_name, $year, $dept_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                }
                mysqli_commit($conn);
                $success = "CSV imported successfully! $inserted sections added, $skipped skipped.";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Failed to import CSV: " . $e->getMessage();
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Please upload a valid CSV file.";
    }
}

// Fetch departments
$dept_query = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name";
$dept_result = mysqli_query($conn, $dept_query);
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-1">Add New Section</h2>
                <p class="text-muted">Create a new section manually or import via CSV</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="../dashboard/index.php" class="btn btn-outline-primary">Back to Dashboard</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Manual Entry Form -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add Single Section</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="section_name" class="form-label">Section Name</label>
                                <select class="form-select" id="section_name" name="section_name" required>
                                    <option value="">Select Section</option>
                                    <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $section): ?>
                                        <option value="<?php echo $section; ?>"><?php echo $section; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="">Select Year</option>
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="dept_id" class="form-label">Department</label>
                                <select class="form-select" id="dept_id" name="dept_id" required>
                                    <option value="">Select Department</option>
                                    <?php while ($dept = mysqli_fetch_assoc($dept_result)): ?>
                                        <option value="<?php echo htmlspecialchars($dept['dept_id']); ?>">
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Add Section</button>
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- CSV Import Form -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Import Sections from CSV</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">Upload CSV File</label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                <small class="form-text text-muted">
                                    CSV should have headers: section_name, year, dept_id
                                    <br>Example: A,3,IT
                                </small>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Import CSV</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.style.opacity = 0;
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
        setTimeout(() => {
            card.style.opacity = 1;
            card.style.transform = 'translateY(0)';
        }, 100);
    });

    // Manual entry form validation
    const manualForm = document.querySelector('form[action=""]:not([enctype])');
    manualForm.addEventListener('submit', function(e) {
        const section = document.getElementById('section_name').value;
        const year = document.getElementById('year').value;
        const dept = document.getElementById('dept_id').value;
        
        if (!section || !year || !dept) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });

    // CSV form validation
    const csvForm = document.querySelector('form[enctype]');
    csvForm.addEventListener('submit', function(e) {
        const fileInput = document.getElementById('csv_file');
        if (!fileInput.value) {
            e.preventDefault();
            alert('Please select a CSV file to upload.');
        } else if (!fileInput.value.endsWith('.csv')) {
            e.preventDefault();
            alert('Please upload a valid CSV file.');
        }
    });
});
</script>

<?php
mysqli_close($conn);
include '../includes/footer.php';
ob_end_flush();
?>
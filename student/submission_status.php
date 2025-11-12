<?php
// t2_t3_assessment/student/submission_status.php
require_once 'includes/session_check.php';  // Ensure student is logged in
require_once '../config.php';              // Database connection
include 'includes/header.php';             // Header inclusion
include 'includes/sidebar.php';            // Sidebar inclusion

$reg_no = $_SESSION['reg_no'];

// Get student details
$student_query = "SELECT s.*, d.dept_name 
                 FROM students s 
                 JOIN departments d ON s.dept_id = d.dept_id 
                 WHERE s.reg_no = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("s", $reg_no);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// Get current semester subjects
$subjects_query = "SELECT s.subject_id, s.subject_name 
                  FROM subjects s 
                  WHERE s.year = ? AND s.semester = ? AND s.dept_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("iis", $student['year'], $student['semester'], $student['dept_id']);
$stmt->execute();
$subjects_result = $stmt->get_result();

// Get submission and marks status
$status_query = "SELECT sub.*, m.t2_mark1, m.t2_mark2, m.t3_mark1, m.t3_mark2, 
                 m.total_t2_marks, m.total_t3_marks, m.assessed_status 
                 FROM t2_t3_submissions sub 
                 LEFT JOIN marks m ON sub.submission_id = m.submission_id 
                 WHERE sub.reg_no = ? AND sub.semester = ?";
$stmt = $conn->prepare($status_query);
$stmt->bind_param("si", $reg_no, $student['semester']);
$stmt->execute();
$status_result = $stmt->get_result();

$submissions = [];
while ($row = $status_result->fetch_assoc()) {
    $submissions[$row['subject_id']][$row['document_type']] = $row;
}

// Store all subjects for use in JavaScript (optional, kept for potential future use)
$all_subjects = [];
$subjects_result_copy = $subjects_result;
while ($subject = $subjects_result_copy->fetch_assoc()) {
    $all_subjects[] = $subject;
}
mysqli_data_seek($subjects_result, 0); // Reset the pointer
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <!-- Header Section -->
        <div class="row mb-4 mt-4">
            <div class="col-12">
                <div class="card hero-card border-0 shadow-sm position-relative">
                    <div class="card-body p-3">
                        <h2 class="fw-bold text-white mb-2">Submission Status</h2>
                        <p class="text-light mb-3">View your T2 and T3 assessment submission status</p>
                        <div class="student-info bg-light p-2 rounded">
                            <div class="d-flex flex-wrap gap-3">
                                <span><i class="fas fa-id-card me-2 text-primary"></i><?php echo htmlspecialchars($reg_no); ?></span>
                                <span><i class="fas fa-graduation-cap me-2 text-primary"></i><?php echo htmlspecialchars($student['dept_name']); ?></span>
                                <span><i class="fas fa-calendar-alt me-2 text-primary"></i>Year <?php echo htmlspecialchars($student['year']); ?>, Sem <?php echo htmlspecialchars($student['semester']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="bubble-container"></div>
                </div>
            </div>
        </div>

        <!-- Submission Status Table -->
        <div class="row">
            <div class="col-12 mb-3">
                <h4 class="section-title"><i class="fas fa-list-check me-2"></i>Submission Overview</h4>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>T2 Status</th>
                                        <th>T2 Marks</th>
                                        <th>T3 Status</th>
                                        <th>T3 Marks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($subject = $subjects_result->fetch_assoc()): 
                                        $subject_id = $subject['subject_id'];
                                        $t2_submission = $submissions[$subject_id]['T2'] ?? null;
                                        $t3_submission = $submissions[$subject_id]['T3'] ?? null;
                                    ?>
                                    <tr class="fade-in" style="animation-delay: <?php echo $subjects_result->num_rows * 0.05; ?>s;">
                                        <td><?php echo htmlspecialchars($subject_id); ?></td>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>

                                        <!-- T2 Status -->
                                        <td>
                                            <?php if ($t2_submission): ?>
                                                <span class="badge bg-success">Submitted</span>
                                                <div class="small text-muted"><?php echo date('M d, Y', strtotime($t2_submission['upload_date'])); ?></div>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Not Submitted</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- T2 Marks -->
                                        <td>
                                            <?php if ($t2_submission && $t2_submission['assessed_status'] === 'Yes'): ?>
                                                <span class="fw-bold"><?php echo $t2_submission['total_t2_marks']; ?>/10</span>
                                            <?php else: ?>
                                                <span class="text-muted">Not Assessed</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- T3 Status -->
                                        <td>
                                            <?php if ($t3_submission): ?>
                                                <span class="badge bg-success">Submitted</span>
                                                <div class="small text-muted"><?php echo date('M d, Y', strtotime($t3_submission['upload_date'])); ?></div>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Not Submitted</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- T3 Marks -->
                                        <td>
                                            <?php if ($t3_submission && $t3_submission['assessed_status'] === 'Yes'): ?>
                                                <span class="fw-bold"><?php echo $t3_submission['total_t3_marks']; ?>/10</span>
                                            <?php else: ?>
                                                <span class="text-muted">Not Assessed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($subjects_result->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No subjects available for this semester.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 mt-4 text-center">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Base styling */
body {
    background-color: #f8f9fa;
}

.main-content {
    padding: 2rem 0;
    min-height: calc(100vh - 60px);
}

.section-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}

/* Hero card styling */
.hero-card {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border-radius: 10px;
    margin-top: 20px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.hero-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2) !important;
}

.bubble-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 0;
}

.bubble {
    position: absolute;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    animation: bubbleRise 6s infinite ease-in;
}

@keyframes bubbleRise {
    0% {
        opacity: 0;
        transform: translateY(100%) scale(0.5);
    }
    50% {
        opacity: 0.8;
    }
    100% {
        opacity: 0;
        transform: translateY(-100%) scale(1);
    }
}

.student-info {
    display: inline-block;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.student-info span {
    font-size: 0.9rem;
}

/* Table card styling */
.card:not(.hero-card) {
    border-radius: 10px;
    transition: transform 0.3s;
}

.card:not(.hero-card):hover {
    transform: translateY(-5px);
}

.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
    padding: 0.75rem;
}

.table td {
    vertical-align: middle;
    padding: 0.75rem;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Animation classes */
.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .student-info {
        display: block;
    }
    
    .student-info span {
        display: block;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .hero-card h2 {
        font-size: 1.5rem;
    }
    
    .hero-card .card-body {
        padding: 1.5rem;
    }
    
    .student-info span {
        font-size: 0.85rem;
    }
}

@media (max-width: 576px) {
    .main-content {
        padding: 1rem 0;
    }
    
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .hero-card h2 {
        font-size: 1.3rem;
    }
    
    .hero-card .card-body {
        padding: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach((row, index) => {
        setTimeout(() => {
            row.classList.add('fade-in');
        }, 100 * index);
    });

    // Add hover effect to student info
    const studentInfo = document.querySelector('.student-info');
    studentInfo.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
    });
    
    studentInfo.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = 'none';
    });

    // Create bubbles
    const bubbleContainer = document.querySelector('.bubble-container');
    function createBubble() {
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        const size = Math.random() * 40 + 20;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${Math.random() * 4 + 4}s`;
        bubbleContainer.appendChild(bubble);
        
        // Remove bubble after animation
        setTimeout(() => {
            bubble.remove();
        }, 6000);
    }

    // Generate bubbles at intervals
    setInterval(createBubble, 500);
    // Initial burst of bubbles
    for (let i = 0; i < 5; i++) {
        setTimeout(createBubble, i * 100);
    }
});
</script>

<?php
$stmt->close();
mysqli_close($conn);
include 'includes/footer.php';
?>
<?php
// t2_t3_assessment/student/marks.php
require_once 'includes/session_check.php';  
include 'includes/header.php';           
include 'includes/sidebar.php';            
require_once '../config.php';           

$reg_no = $_SESSION['reg_no'];

// Get student details
$student_query = "SELECT s.*, d.dept_name 
                 FROM students s 
                 JOIN departments d ON s.dept_id = d.dept_id 
                 WHERE s.reg_no = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("s", $reg_no);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Get current semester subjects and marks
$marks_query = "SELECT sub.subject_id, sub.document_type, 
                       s.subject_name,
                       m.t2_mark1, m.t2_mark2, m.t3_mark1, m.t3_mark2, 
                       m.total_t2_marks, m.total_t3_marks, m.assessed_status 
                FROM t2_t3_submissions sub 
                LEFT JOIN marks m ON sub.submission_id = m.submission_id 
                JOIN subjects s ON sub.subject_id = s.subject_id
                WHERE sub.reg_no = ? AND sub.semester = ?
                ORDER BY sub.subject_id, sub.document_type";
$stmt = $conn->prepare($marks_query);
$stmt->bind_param("si", $reg_no, $student['semester']);
$stmt->execute();
$marks_result = $stmt->get_result();

$marks_data = [];
while ($row = $marks_result->fetch_assoc()) {
    $marks_data[$row['subject_id']][$row['document_type']] = $row;
}
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <!-- Header Section -->
        <div class="row mb-4 mt-4">
            <div class="col-12">
                <div class="card hero-card border-0 shadow-sm position-relative">
                    <div class="card-body p-3">
                        <h2 class="fw-bold text-white mb-2">Here are your Marks, <?php echo htmlspecialchars($student['name']); ?>!</h2>
                        <p class="text-light mb-3">View your T2 and T3 assessment marks</p>
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

        <!-- Marks Table Section -->
        <div class="row">
            <div class="col-12 mb-3">
                <h4 class="section-title"><i class="fas fa-award me-2"></i>Marks Overview</h4>
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
                                        <th>Assessment</th>
                                        <th>R1( /5)</th>
                                        <th>R2( /5)</th>
                                        <th>Total( /10)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($marks_data as $subject_id => $assessments): ?>
                                        <?php 
                                        $subject_name = $assessments['T2']['subject_name'] ?? $assessments['T3']['subject_name'] ?? 'Unknown';
                                        ?>
                                        <!-- T2 Row -->
                                        <tr class="<?php echo isset($assessments['T2']) && $assessments['T2']['assessed_status'] === 'Yes' ? 'table-success' : (isset($assessments['T2']) ? 'table-warning' : 'table-light'); ?>">
                                            <td><?php echo htmlspecialchars($subject_id); ?></td>
                                            <td><?php echo htmlspecialchars($subject_name); ?></td>
                                            <td>T2 Document</td>
                                            <td><?php echo isset($assessments['T2']) && $assessments['T2']['assessed_status'] === 'Yes' ? $assessments['T2']['t2_mark1'] : 'N/A'; ?></td>
                                            <td><?php echo isset($assessments['T2']) && $assessments['T2']['assessed_status'] === 'Yes' ? $assessments['T2']['t2_mark2'] : 'N/A'; ?></td>
                                            <td><?php echo isset($assessments['T2']) && $assessments['T2']['assessed_status'] === 'Yes' ? $assessments['T2']['total_t2_marks'] : 'N/A'; ?></td>
                                            <td><?php echo isset($assessments['T2']) ? ($assessments['T2']['assessed_status'] === 'Yes' ? 'Assessed' : 'Pending') : 'Not Submitted'; ?></td>
                                        </tr>
                                        <!-- T3 Row -->
                                        <tr class="<?php echo isset($assessments['T3']) && $assessments['T3']['assessed_status'] === 'Yes' ? 'table-success' : (isset($assessments['T3']) ? 'table-warning' : 'table-light'); ?>">
                                            <td><?php echo htmlspecialchars($subject_id); ?></td>
                                            <td><?php echo htmlspecialchars($subject_name); ?></td>
                                            <td>T3 Presentation</td>
                                            <td><?php echo isset($assessments['T3']) && $assessments['T3']['assessed_status'] === 'Yes' ? $assessments['T3']['t3_mark1'] : 'N/A'; ?></td>
                                            <td><?php echo isset($assessments['T3']) && $assessments['T3']['assessed_status'] === 'Yes' ? $assessments['T3']['t3_mark2'] : 'N/A'; ?></td>
                                            <td><?php echo isset($assessments['T3']) && $assessments['T3']['assessed_status'] === 'Yes' ? $assessments['T3']['total_t3_marks'] : 'N/A'; ?></td>
                                            <td><?php echo isset($assessments['T3']) ? ($assessments['T3']['assessed_status'] === 'Yes' ? 'Assessed' : 'Pending') : 'Not Submitted'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($marks_data)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No marks available yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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

.table-success {
    background-color: #e8f5e9 !important;
}

.table-warning {
    background-color: #fff8e1 !important;
}

.table-light {
    background-color: #f8f9fa !important;
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
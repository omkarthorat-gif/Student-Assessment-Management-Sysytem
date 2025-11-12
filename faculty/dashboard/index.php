<?php
// t2_t3_assessment/faculty/dashboard/index.php
require_once '../includes/session_check.php';
include '../includes/header.php';
include '../includes/sidebar.php';
require_once '../../config.php';

$faculty_id = $_SESSION['faculty_id'];

// Fetch faculty details
$faculty_query = "SELECT f.*, d.dept_name 
                 FROM faculty f 
                 JOIN departments d ON f.dept_id = d.dept_id 
                 WHERE f.faculty_id = ?";
$stmt = $conn->prepare($faculty_query);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();

// Fetch assigned subjects for this faculty with year and semester
$subjects_query = "SELECT DISTINCT s.subject_id, s.subject_name, s.year, s.semester
                  FROM faculty_subject_assign fsa
                  JOIN subjects s ON fsa.subject_id = s.subject_id
                  WHERE fsa.faculty_id = ?
                  ORDER BY s.year, s.semester, s.subject_name";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$subjects_result = $stmt->get_result();

// Handle subject and section selection
$selected_subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$selected_section = isset($_GET['section_name']) ? $_GET['section_name'] : '';
$avg_marks = ['t2_avg' => 0, 't3_avg' => 0];
$total_students = 0;
$selected_year = '';
$selected_semester = '';

if ($selected_subject_id) {
    // Get year and semester for selected subject
    $subject_info_query = "SELECT year, semester FROM subjects WHERE subject_id = ?";
    $stmt = $conn->prepare($subject_info_query);
    $stmt->bind_param("s", $selected_subject_id);
    $stmt->execute();
    $subject_info = $stmt->get_result()->fetch_assoc();
    $selected_year = $subject_info['year'];
    $selected_semester = $subject_info['semester'];

    if ($selected_section) {
        // Get total students in the selected section
        $total_students_query = "SELECT COUNT(*) as total 
                                FROM students 
                                WHERE section_name = ? 
                                AND dept_id = (SELECT dept_id FROM faculty WHERE faculty_id = ?)";
        $stmt = $conn->prepare($total_students_query);
        $stmt->bind_param("ss", $selected_section, $faculty_id);
        $stmt->execute();
        $total_students = $stmt->get_result()->fetch_assoc()['total'];

        // Fetch average marks for all students (including non-submitted as 0)
        $stats_query = "SELECT 
            AVG(COALESCE(m.total_t2_marks, 0)) as t2_avg,
            AVG(COALESCE(m.total_t3_marks, 0)) as t3_avg
            FROM students s
            LEFT JOIN (
                SELECT reg_no, 
                       MAX(CASE WHEN document_type = 'T2' THEN submission_id END) as t2_sub_id,
                       MAX(CASE WHEN document_type = 'T3' THEN submission_id END) as t3_sub_id
                FROM t2_t3_submissions
                WHERE subject_id = ?
                GROUP BY reg_no
            ) sub ON s.reg_no = sub.reg_no
            LEFT JOIN marks m ON m.submission_id IN (sub.t2_sub_id, sub.t3_sub_id)
            WHERE s.section_name = ? 
            AND s.dept_id = (SELECT dept_id FROM faculty WHERE faculty_id = ?)";
        
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("sss", $selected_subject_id, $selected_section, $faculty_id);
        $stmt->execute();
        $avg_marks = $stmt->get_result()->fetch_assoc() ?: ['t2_avg' => 0, 't3_avg' => 0];
    }
}
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <!-- Header with Hero Section -->
        <div class="row mb-4" style="margin-top: 30px;">
            <div class="col-12">
                <div class="hero-card card border-0 shadow-sm position-relative overflow-hidden bg-gradient-primary">
                    <div class="bubble-container position-absolute w-100 h-100" style="top: 0; left: 0;"></div>
                    <div class="card-body p-4 position-relative">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-5 fw-bold mb-2 text-white">Welcome, <?php echo htmlspecialchars($faculty['name']); ?>!</h1>
                                <p class="lead text-light mb-3">Select a subject and section to view class performance</p>
                            </div>
                            <div class="col-md-4">
                                <div class="faculty-info-card bg-white p-3 rounded shadow-sm">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-id-card me-2 text-primary"></i>
                                        <span><?php echo htmlspecialchars($faculty_id); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-graduation-cap me-2 text-primary"></i>
                                        <span><?php echo htmlspecialchars($faculty['dept_name']); ?></span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="../profile.php" class="btn btn-primary btn-sm flex-grow-1">
                                            <i class="fas fa-user me-1"></i> Profile
                                        </a>
                                        <a href="../authentication/logout.php" class="btn btn-outline-primary btn-sm flex-grow-1">
                                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject and Section Selection Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="section-title"><i class="fas fa-book me-2"></i>Select Subject and Section</h4>
                        <form method="GET" action="">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <select name="subject_id" id="subject_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- Select a Subject --</option>
                                        <?php 
                                        $subjects_result->data_seek(0); // Reset result pointer
                                        while ($subject = $subjects_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>"
                                                data-year="<?php echo $subject['year']; ?>"
                                                data-semester="<?php echo $subject['semester']; ?>"
                                                <?php echo $selected_subject_id === $subject['subject_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars("{$subject['subject_name']} ({$subject['subject_id']})"); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <?php if ($selected_subject_id): ?>
                                    <div class="col-md-4 mb-3">
                                        <select name="section_name" class="form-select" onchange="this.form.submit()">
                                            <option value="">-- Select Section --</option>
                                            <?php
                                            $sections_query = "SELECT DISTINCT section_name 
                                                             FROM faculty_subject_assign 
                                                             WHERE faculty_id = ? AND subject_id = ?";
                                            $stmt = $conn->prepare($sections_query);
                                            $stmt->bind_param("ss", $faculty_id, $selected_subject_id);
                                            $stmt->execute();
                                            $sections = $stmt->get_result();
                                            while ($section = $sections->fetch_assoc()): ?>
                                                <option value="<?php echo $section['section_name']; ?>"
                                                    <?php echo $selected_section === $section['section_name'] ? 'selected' : ''; ?>>
                                                    Section <?php echo $section['section_name']; ?> (Year <?php echo $selected_year; ?> Sem <?php echo $selected_semester; ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Overview Section -->
        <?php if ($selected_subject_id && $selected_section): ?>
        <div class="row mb-4">
            <div class="col-12 mb-3">
                <h4 class="section-title"><i class="fas fa-chart-bar me-2"></i>Class Performance Overview</h4>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card progress-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">Average T2 Marks</h6>
                            <div class="badge bg-primary"><?php echo number_format($avg_marks['t2_avg'], 2); ?>/10</div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: <?php echo ($avg_marks['t2_avg'] * 10); ?>%" 
                                 aria-valuenow="<?php echo $avg_marks['t2_avg']; ?>" 
                                 aria-valuemin="0" aria-valuemax="10"></div>
                        </div>
                        <p class="text-muted mt-2 mb-0 small">Out of <?php echo $total_students; ?> students</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card progress-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">Average T3 Marks</h6>
                            <div class="badge bg-info"><?php echo number_format($avg_marks['t3_avg'], 2); ?>/10</div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?php echo ($avg_marks['t3_avg'] * 10); ?>%" 
                                 aria-valuenow="<?php echo $avg_marks['t3_avg']; ?>" 
                                 aria-valuemin="0" aria-valuemax="10"></div>
                        </div>
                        <p class="text-muted mt-2 mb-0 small">Out of <?php echo $total_students; ?> students</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Marks Section -->
        <div class="row">
            <div class="col-12 mb-3">
                <h4 class="section-title"><i class="fas fa-file-alt me-2"></i>Student Marks</h4>
            </div>
            <div class="col-12">
                <div class="card subject-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                            $marks_query = "SELECT 
                                s.reg_no, 
                                s.name,
                                MAX(CASE WHEN sub.document_type = 'T2' THEN m.t2_mark1 END) as t2_mark1,
                                MAX(CASE WHEN sub.document_type = 'T2' THEN m.t2_mark2 END) as t2_mark2,
                                MAX(CASE WHEN sub.document_type = 'T3' THEN m.t3_mark1 END) as t3_mark1,
                                MAX(CASE WHEN sub.document_type = 'T3' THEN m.t3_mark2 END) as t3_mark2,
                                MAX(m.assessed_status) as assessed_status
                            FROM students s
                            LEFT JOIN t2_t3_submissions sub ON s.reg_no = sub.reg_no AND sub.subject_id = ?
                            LEFT JOIN marks m ON sub.submission_id = m.submission_id
                            WHERE s.section_name = ? 
                            AND s.dept_id = (SELECT dept_id FROM faculty WHERE faculty_id = ?)
                            GROUP BY s.reg_no, s.name
                            ORDER BY s.reg_no";
                            $stmt = $conn->prepare($marks_query);
                            $stmt->bind_param("sss", $selected_subject_id, $selected_section, $faculty_id);
                            $stmt->execute();
                            $marks_result = $stmt->get_result();
                            ?>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Reg No</th>
                                        <th>Name</th>
                                        <th>T2 Mark1</th>
                                        <th>T2 Mark2</th>
                                        <th>T3 Mark1</th>
                                        <th>T3 Mark2</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = $marks_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['reg_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo $student['t2_mark1'] ?? '-'; ?></td>
                                        <td><?php echo $student['t2_mark2'] ?? '-'; ?></td>
                                        <td><?php echo $student['t3_mark1'] ?? '-'; ?></td>
                                        <td><?php echo $student['t3_mark2'] ?? '-'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $student['assessed_status'] === 'Yes' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $student['assessed_status'] === 'Yes' ? 'Assessed' : 'Not Assessed'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
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

.bg-gradient-primary {
    background: linear-gradient(135deg, #6B48FF, #00DDEB);
}

.hero-card {
    border-radius: 15px;
    transition: all 0.3s ease;
}

.hero-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
}

.bubble-container {
    pointer-events: none;
    z-index: 0;
}

.bubble {
    position: absolute;
    bottom: -50px;
    width: 30px;
    height: 30px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    animation: bubble-rise 6s infinite ease-in;
    opacity: 0;
}

@keyframes bubble-rise {
    0% { opacity: 0; transform: translateY(0) scale(0.5); }
    20% { opacity: 0.7; }
    100% { opacity: 0; transform: translateY(-600px) scale(1.5); }
}

.faculty-info-card {
    transition: all 0.3s ease;
}

.faculty-info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.progress-card {
    border-radius: 10px;
    transition: transform 0.3s;
}

.progress-card:hover {
    transform: translateY(-5px);
}

.progress {
    border-radius: 20px;
    background-color: #e9ecef;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.subject-card {
    border-radius: 10px;
    transition: all 0.3s;
    overflow: hidden;
}

.subject-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.table {
    margin-bottom: 0;
}

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

@media (max-width: 992px) {
    .progress-card {
        margin-bottom: 1rem;
    }
    
    .hero-card .row {
        flex-direction: column;
        text-align: center;
    }
    
    .faculty-info-card {
        margin-top: 20px;
        max-width: 300px;
        margin-left: auto;
        margin-right: auto;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bubble animation
    const bubbleContainer = document.querySelector('.bubble-container');
    function createBubble() {
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${4 + Math.random() * 4}s`;
        bubble.style.width = `${20 + Math.random() * 30}px`;
        bubble.style.height = bubble.style.width;
        bubbleContainer.appendChild(bubble);

        setTimeout(() => {
            bubble.remove();
        }, 6000);
    }
    setInterval(createBubble, 500);
});
</script>

<?php
$stmt->close();
mysqli_close($conn);
include '../includes/footer.php';
?>
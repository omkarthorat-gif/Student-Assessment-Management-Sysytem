<?php
// t2_t3_assessment/student/dashboard/index.php
require_once '../includes/session_check.php';  
include '../includes/header.php';           
include '../includes/sidebar.php';            
require_once '../../config.php';           

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

// Get current semester subjects
$subjects_query = "SELECT s.subject_id, s.subject_name 
                  FROM subjects s 
                  WHERE s.year = ? AND s.semester = ? AND s.dept_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("iis", $student['year'], $student['semester'], $student['dept_id']);
$stmt->execute();
$subjects_result = $stmt->get_result();

// Get submission status and marks
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

// Calculate overall progress for progress bars
$total_subjects = $subjects_result->num_rows;
$subjects_result->data_seek(0);

$t2_submitted = 0;
$t3_submitted = 0;
$t2_assessed = 0;
$t3_assessed = 0;

foreach ($submissions as $subject_id => $docs) {
    if (isset($docs['T2'])) {
        $t2_submitted++;
        if ($docs['T2']['assessed_status'] === 'Yes') {
            $t2_assessed++;
        }
    }
    
    if (isset($docs['T3'])) {
        $t3_submitted++;
        if ($docs['T3']['assessed_status'] === 'Yes') {
            $t3_assessed++;
        }
    }
}

$t2_submission_percentage = $total_subjects > 0 ? round(($t2_submitted / $total_subjects) * 100) : 0;
$t3_submission_percentage = $total_subjects > 0 ? round(($t3_submitted / $total_subjects) * 100) : 0;
$t2_assessment_percentage = $t2_submitted > 0 ? round(($t2_assessed / $t2_submitted) * 100) : 0;
$t3_assessment_percentage = $t3_submitted > 0 ? round(($t3_assessed / $t3_submitted) * 100) : 0;
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <!-- Header with Hero Section -->
        <div class="row mb-4" style="margin-top: 30px;"> <!-- Adjusted margin-top to push it down -->
            <div class="col-12">
                <div class="hero-card card border-0 shadow-sm position-relative overflow-hidden bg-gradient-primary">
                    <div class="bubble-container position-absolute w-100 h-100" style="top: 0; left: 0;"></div>
                    <div class="card-body p-4 position-relative">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-5 fw-bold mb-2 text-white">Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h1>
                                <p class="lead text-light mb-3">Track your academic progress and submissions</p>
                            </div>
                            <div class="col-md-4">
                                <div class="student-info-card bg-white p-3 rounded shadow-sm">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-id-card me-2 text-primary"></i>
                                        <span><?php echo htmlspecialchars($reg_no); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-graduation-cap me-2 text-primary"></i>
                                        <span><?php echo htmlspecialchars($student['dept_name']); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                        <span>Year <?php echo htmlspecialchars($student['year']); ?>, Semester <?php echo htmlspecialchars($student['semester']); ?></span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="../profile.php" class="btn btn-primary btn-sm flex-grow-1">
                                            <i class="fas fa-user me-1"></i> Profile
                                        </a>
                                        <a href="/t2_t3_assessment/student/authentication/logout.php" class="btn btn-outline-primary btn-sm flex-grow-1">
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

        <!-- Progress Overview Section -->
        <div class="row mb-4">
            <div class="col-12 mb-3">
                <h4 class="section-title"><i class="fas fa-chart-line me-2"></i>Progress Overview</h4>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card progress-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">T2 Submissions</h6>
                            <div class="badge bg-primary"><?php echo $t2_submitted; ?> / <?php echo $total_subjects; ?></div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $t2_submission_percentage; ?>%" 
                                aria-valuenow="<?php echo $t2_submission_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2 mb-0 small"><?php echo $t2_submission_percentage; ?>% submitted</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card progress-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">T3 Submissions</h6>
                            <div class="badge bg-info"><?php echo $t3_submitted; ?> / <?php echo $total_subjects; ?></div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $t3_submission_percentage; ?>%" 
                                aria-valuenow="<?php echo $t3_submission_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2 mb-0 small"><?php echo $t3_submission_percentage; ?>% submitted</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card progress-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">T2 Assessment</h6>
                            <div class="badge bg-success"><?php echo $t2_assessed; ?> / <?php echo $t2_submitted; ?></div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $t2_assessment_percentage; ?>%" 
                                aria-valuenow="<?php echo $t2_assessment_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2 mb-0 small"><?php echo $t2_assessment_percentage; ?>% assessed</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card progress-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0">T3 Assessment</h6>
                            <div class="badge bg-warning"><?php echo $t3_assessed; ?> / <?php echo $t3_submitted; ?></div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $t3_assessment_percentage; ?>%" 
                                aria-valuenow="<?php echo $t3_assessment_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2 mb-0 small"><?php echo $t3_assessment_percentage; ?>% assessed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subjects and Submissions Section -->
        <div class="row">
            <div class="col-12 mb-3">
                <h4 class="section-title"><i class="fas fa-book me-2"></i>Current Semester Subjects & Submissions</h4>
            </div>
            
            <?php 
            $subjects_result->data_seek(0);
            while ($subject = $subjects_result->fetch_assoc()): 
                $t2_exists = isset($submissions[$subject['subject_id']]['T2']);
                $t3_exists = isset($submissions[$subject['subject_id']]['T3']);
                
                $t2_status = $t2_exists ? 'submitted' : 'pending';
                $t3_status = $t3_exists ? 'submitted' : 'pending';
                
                $t2_assessment = ($t2_exists && $submissions[$subject['subject_id']]['T2']['assessed_status'] === 'Yes') ? 'assessed' : 'not-assessed';
                $t3_assessment = ($t3_exists && $submissions[$subject['subject_id']]['T3']['assessed_status'] === 'Yes') ? 'assessed' : 'not-assessed';
                
                $t2_marks = $t2_exists && $submissions[$subject['subject_id']]['T2']['assessed_status'] === 'Yes' ? 
                            $submissions[$subject['subject_id']]['T2']['total_t2_marks'] : 'N/A';
                
                $t3_marks = $t3_exists && $submissions[$subject['subject_id']]['T3']['assessed_status'] === 'Yes' ? 
                            $submissions[$subject['subject_id']]['T3']['total_t3_marks'] : 'N/A';
            ?>
            <div class="col-12 col-lg-6 mb-4">
                <div class="card subject-card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                        <span class="subject-code"><?php echo htmlspecialchars($subject['subject_id']); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="submission-status <?php echo $t2_status; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-container me-3">
                                            <?php if ($t2_exists): ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-clock"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">T2 Document</h6>
                                            <?php if ($t2_exists): ?>
                                                <p class="text-muted small mb-0">Submitted on <?php echo date('M d, Y', strtotime($submissions[$subject['subject_id']]['T2']['upload_date'])); ?></p>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">Pending submission</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="submission-status <?php echo $t3_status; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-container me-3">
                                            <?php if ($t3_exists): ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-clock"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">T3 Presentation</h6>
                                            <?php if ($t3_exists): ?>
                                                <p class="text-muted small mb-0">Submitted on <?php echo date('M d, Y', strtotime($submissions[$subject['subject_id']]['T3']['upload_date'])); ?></p>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">Pending submission</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="marks-card <?php echo $t2_assessment; ?>">
                                    <h6><i class="fas fa-award me-2"></i>T2 Marks</h6>
                                    <?php if ($t2_exists && $submissions[$subject['subject_id']]['T2']['assessed_status'] === 'Yes'): ?>
                                        <div class="marks">
                                            <span class="mark"><?php echo $t2_marks; ?></span>
                                            <span class="total">/10</span>
                                        </div>
                                        <div class="marks-breakdown">
                                            <div class="d-flex justify-content-between">
                                                <span>Criterion 1:</span>
                                                <span><?php echo $submissions[$subject['subject_id']]['T2']['t2_mark1']; ?>/5</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Criterion 2:</span>
                                                <span><?php echo $submissions[$subject['subject_id']]['T2']['t2_mark2']; ?>/5</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="not-assessed">
                                            <i class="fas fa-hourglass-half me-2"></i>Not Assessed
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="marks-card <?php echo $t3_assessment; ?>">
                                    <h6><i class="fas fa-award me-2"></i>T3 Marks</h6>
                                    <?php if ($t3_exists && $submissions[$subject['subject_id']]['T3']['assessed_status'] === 'Yes'): ?>
                                        <div class="marks">
                                            <span class="mark"><?php echo $t3_marks; ?></span>
                                            <span class="total">/10</span>
                                        </div>
                                        <div class="marks-breakdown">
                                            <div class="d-flex justify-content-between">
                                                <span>Criterion 1:</span>
                                                <span><?php echo $submissions[$subject['subject_id']]['T3']['t3_mark1']; ?>/5</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Criterion 2:</span>
                                                <span><?php echo $submissions[$subject['subject_id']]['T3']['t3_mark2']; ?>/5</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="not-assessed">
                                            <i class="fas fa-hourglass-half me-2"></i>Not Assessed
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <?php if (!$t2_exists): ?>
                                    <form action="../upload_submission.php" method="post" enctype="multipart/form-data" class="upload-form">
                                        <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                        <input type="hidden" name="document_type" value="T2">
                                        <div class="custom-file-input">
                                            <label for="t2_file_<?php echo $subject['subject_id']; ?>" class="file-label">
                                                <i class="fas fa-file-word me-2"></i>Select T2 Document
                                            </label>
                                            <input type="file" name="t2_file" id="t2_file_<?php echo $subject['subject_id']; ?>" accept=".docx" required class="file-input">
                                            <span class="selected-file" id="t2_filename_<?php echo $subject['subject_id']; ?>">No file chosen</span>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm mt-2 upload-btn">
                                            <i class="fas fa-upload me-1"></i>Upload T2
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="../view_submission.php?submission_id=<?php echo $submissions[$subject['subject_id']]['T2']['submission_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm view-submission">
                                        <i class="fas fa-eye me-1"></i>View T2 Submission
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-6">
                                <?php if (!$t3_exists): ?>
                                    <form action="../upload_submission.php" method="post" enctype="multipart/form-data" class="upload-form">
                                        <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                        <input type="hidden" name="document_type" value="T3">
                                        <div class="custom-file-input">
                                            <label for="t3_file_<?php echo $subject['subject_id']; ?>" class="file-label">
                                                <i class="fas fa-file-powerpoint me-2"></i>Select T3 Presentation
                                            </label>
                                            <input type="file" name="t3_file" id="t3_file_<?php echo $subject['subject_id']; ?>" accept=".ppt,.pptx" required class="file-input">
                                            <span class="selected-file" id="t3_filename_<?php echo $subject['subject_id']; ?>">No file chosen</span>
                                        </div>
                                        <button type="submit" class="btn btn-info btn-sm mt-2 upload-btn">
                                            <i class="fas fa-upload me-1"></i>Upload T3
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="../view_submission.php?submission_id=<?php echo $submissions[$subject['subject_id']]['T3']['submission_id']; ?>" 
                                       class="btn btn-outline-info btn-sm view-submission">
                                        <i class="fas fa-eye me-1"></i>View T3 Submission
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
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
.bg-gradient-primary {
    background: linear-gradient(135deg, #6B48FF, #00DDEB);
}

.hero-card {
    border-radius: 15px;
    margin-top: 20px;
    transition: all 0.3s ease;
    min-height: 150px; /* Added to maintain consistent height */
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
    background: rgba(255, 255, 255, 0.2); /* Adjusted for white bubbles on blue background */
    border-radius: 50%;
    animation: bubble-rise 6s infinite ease-in;
    opacity: 0;
}

@keyframes bubble-rise {
    0% {
        opacity: 0;
        transform: translateY(0) scale(0.5);
    }
    20% {
        opacity: 0.7;
    }
    100% {
        opacity: 0;
        transform: translateY(-600px) scale(1.5);
    }
}

.student-info-card {
    transition: all 0.3s ease;
}

.student-info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Progress cards */
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

/* Subject cards */
.subject-card {
    border-radius: 10px;
    transition: all 0.3s;
    overflow: hidden;
}

.subject-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.subject-card .card-header {
    border-bottom: 1px solid #f0f0f0;
    padding: 1rem;
}

.subject-code {
    font-size: 0.8rem;
    background-color: #f0f0f0;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: #666;
}

.submission-status {
    padding: 0.8rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.submission-status.submitted {
    background-color: #e8f5e9;
}

.submission-status.pending {
    background-color: #fff8e1;
}

.submission-status .icon-container {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.submission-status.submitted .icon-container {
    background-color: #c8e6c9;
    color: #4caf50;
}

.submission-status.pending .icon-container {
    background-color: #ffecb3;
    color: #ffc107;
}

/* Marks styling */
.marks-card {
    padding: 1rem;
    border-radius: 8px;
    height: 100%;
}

.marks-card.assessed {
    background-color: #e3f2fd;
}

.marks-card.not-assessed {
    background-color: #f5f5f5;
}

.marks-card h6 {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.marks {
    display: flex;
    align-items: baseline;
    margin-bottom: 0.5rem;
}

.marks .mark {
    font-size: 1.8rem;
    font-weight: bold;
    color: #1976d2;
}

.marks .total {
    font-size: 1rem;
    color: #666;
    margin-left: 0.25rem;
}

.marks-breakdown {
    font-size: 0.8rem;
    color: #666;
    border-top: 1px dashed #ccc;
    padding-top: 0.5rem;
}

.not-assessed {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #9e9e9e;
    font-weight: 500;
}

/* File upload styling */
.custom-file-input {
    position: relative;
    display: block;
    width: 100%;
}

.file-label {
    display: block;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    color: #555;
    font-size: 0.85rem;
}

.file-label:hover {
    background-color: #e9ecef;
}

.file-input {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 0.1px;
    height: 0.1px;
}

.selected-file {
    display: block;
    font-size: 0.8rem;
    color: #666;
    margin-top: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.upload-btn {
    width: 100%;
}

.view-submission {
    width: 100%;
    margin-top: 2.3rem;
}

/* Animation classes */
.fade-in {
    animation: fadeIn 0.5s;
}

.slide-up {
    animation: slideUp 0.5s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .progress-card {
        margin-bottom: 1rem;
    }
    
    .hero-card .row {
        flex-direction: column;
        text-align: center;
    }
    
    .student-info-card {
        margin-top: 20px;
        max-width: 300px;
        margin-left: auto;
        margin-right: auto;
    }
}

@media (max-width: 768px) {
    .submission-status .icon-container {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
    
    .marks .mark {
        font-size: 1.5rem;
    }
    
    .file-label {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .hero-card .card-body {
        padding: 1.5rem;
    }
    
    .section-title {
        font-size: 1.2rem;
    }
    
    .hero-card h1 {
        font-size: 1.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to cards
    const progressCards = document.querySelectorAll('.progress-card');
    const subjectCards = document.querySelectorAll('.subject-card');
    
    progressCards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, 100 * index);
    });
    
    subjectCards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('slide-up');
        }, 300 + (100 * index));
    });

    // Bubble animation for hero card
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
    
    // Handle file input changes
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileId = this.id;
            const filenameElement = document.getElementById(fileId.replace('file_', 'filename_'));
            filenameElement.textContent = this.files.length > 0 ? this.files[0].name : 'No file chosen';
        });
    });
    
    // Add hover effects to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
            this.style.transition = 'all 0.3s';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Notification system
    function showNotification(message, type = 'info') {
        const notificationDiv = document.createElement('div');
        notificationDiv.className = `notification ${type}`;
        notificationDiv.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="close-btn"><i class="fas fa-times"></i></button>
        `;
        
        document.body.appendChild(notificationDiv);
        
        setTimeout(() => {
            notificationDiv.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            closeNotification(notificationDiv);
        }, 5000);
        
        notificationDiv.querySelector('.close-btn').addEventListener('click', function() {
            closeNotification(notificationDiv);
        });
    }
    
    function closeNotification(notificationDiv) {
        notificationDiv.style.transform = 'translateX(110%)';
        setTimeout(() => {
            if (notificationDiv.parentNode) {
                notificationDiv.parentNode.removeChild(notificationDiv);
            }
        }, 300);
    }
    
    setTimeout(() => {
        showNotification('Welcome to your student dashboard! Track your submissions and assessments here.', 'success');
    }, 1000);
});

// Add notification styles
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        max-width: 350px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        z-index: 1000;
        transform: translateX(110%);
        transition: transform 0.3s ease-out;
    }
    
    .notification .notification-content {
        display: flex;
        align-items: center;
        padding: 15px;
    }
    
    .notification i {
        margin-right: 10px;
        font-size: 20px;
    }
    
    .notification.success i {
        color: #4CAF50;
    }
    
    .notification .close-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 5px;
        font-size: 16px;
    }
    
    .notification .close-btn:hover {
        color: #333;
    }
`;
document.head.appendChild(notificationStyles);
</script>

<?php
$stmt->close();
mysqli_close($conn);
include '../includes/footer.php';
?>
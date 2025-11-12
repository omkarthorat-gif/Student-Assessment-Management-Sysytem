<?php
// t2_t3_assessment/faculty/dashboard/assigned_subjects.php
require_once '../includes/session_check.php'; // Faculty session check
include '../includes/header.php';
include '../includes/sidebar.php';
require_once '../../config.php';

$faculty_id = $_SESSION['faculty_id'];

// Fetch faculty details to get dept_id if not in session
$faculty_query = "SELECT dept_id FROM faculty WHERE faculty_id = ?";
$stmt = $conn->prepare($faculty_query);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$faculty_result = $stmt->get_result();
$faculty = $faculty_result->fetch_assoc();
$dept_id = $faculty['dept_id'];

// Fetch assigned subjects
$subjects_query = "SELECT DISTINCT s.subject_id, s.subject_name, s.year, s.semester, fsa.section_name
                  FROM faculty_subject_assign fsa
                  JOIN subjects s ON fsa.subject_id = s.subject_id
                  WHERE fsa.faculty_id = ? AND fsa.dept_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("ss", $faculty_id, $dept_id);
$stmt->execute();
$subjects_result = $stmt->get_result();
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <!-- Header Section -->
        <div class="row mb-4" style="margin-top: 30px;">
            <div class="col-12">
                <div class="hero-card card border-0 shadow-sm position-relative overflow-hidden bg-gradient-primary">
                    <div class="bubble-container position-absolute w-100 h-100" style="top: 0; left: 0;"></div>
                    <div class="card-body p-4 position-relative">
                        <h1 class="display-5 fw-bold mb-2 text-white">Assigned Subjects</h1>
                        <p class="lead text-light mb-0">View your assigned subjects for the current semester</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Subjects Section -->
        <div class="row">
            <div class="col-12">
                <div class="card subject-card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="section-title"><i class="fas fa-book me-2"></i>Your Assigned Subjects</h4>
                        
                        <?php if ($subjects_result->num_rows === 0): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No subjects are currently assigned to you.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card subject-card border-0 shadow-sm">
                                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                            <span class="subject-code"><?php echo htmlspecialchars($subject['subject_id']); ?></span>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-graduation-cap me-2 text-primary"></i>
                                                <p class="mb-0"><strong>Year:</strong> <?php echo $subject['year']; ?></p>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                                <p class="mb-0"><strong>Semester:</strong> <?php echo $subject['semester']; ?></p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-users me-2 text-primary"></i>
                                                <p class="mb-0"><strong>Section:</strong> <?php echo $subject['section_name']; ?></p>
                                            </div>
                                            <div class="mt-3">
                                                <a href="student_submissions.php?subject_id=<?php echo urlencode($subject['subject_id']); ?>&year=<?php echo urlencode($subject['year']); ?>&semester=<?php echo urlencode($subject['semester']); ?>&section=<?php echo urlencode($subject['section_name']); ?>" 
                                                   class="btn btn-sm btn-primary w-100">
                                                    <i class="fas fa-eye me-1"></i> View Submissions
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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

.card-body p {
    margin-bottom: 0.5rem;
    color: #555;
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
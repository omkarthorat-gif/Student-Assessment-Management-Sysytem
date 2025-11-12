<?php
// t2_t3_assessment/student/subjects.php
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

// Get current semester subjects
$subjects_query = "SELECT s.subject_id, s.subject_name 
                  FROM subjects s 
                  WHERE s.year = ? AND s.semester = ? AND s.dept_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("iis", $student['year'], $student['semester'], $student['dept_id']);
$stmt->execute();
$subjects_result = $stmt->get_result();
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <!-- Compact & Stunning Hero Section with CSS Animations -->
        <div class="row mb-4 mt-4">
            <div class="col-12">
                <div class="card hero-card border-0 shadow-sm position-relative">
                    <div class="card-body p-3">
                        <h1 class="hero-title h3 fw-bold text-white mb-2 animate__animated animate__slideInLeft">
                            My Subjects Dashboard
                        </h1>
                        <p class="hero-subtitle small text-light mb-0 animate__animated animate__slideInRight animate__delay-0.5s">
                            <div class="student-info bg-light p-2 rounded d-inline-block">
                                <div class="d-flex flex-wrap gap-3">
                                    <span><i class="fas fa-id-card me-2 text-primary"></i><?php echo htmlspecialchars($reg_no); ?></span>
                                    <span><i class="fas fa-graduation-cap me-2 text-primary"></i><?php echo htmlspecialchars($student['dept_name']); ?></span>
                                    <span><i class="fas fa-calendar-alt me-2 text-primary"></i>Year <?php echo htmlspecialchars($student['year']); ?>, Sem <?php echo htmlspecialchars($student['semester']); ?></span>
                                </div>
                            </div>
                        </p>
                    </div>
                    <div class="bubble-container"></div>
                </div>
            </div>
        </div>

        <!-- Subjects List -->
        <div class="row">
            <div class="col-12 mb-4">
                <h4 class="section-title text-uppercase animate__animated animate__fadeInLeft">
                    <i class="fas fa-book me-2"></i>Current Semester Subjects
                </h4>
            </div>

            <?php 
            while ($subject = $subjects_result->fetch_assoc()): 
            ?>
            <div class="col-12 col-lg-6 col-xl-4 mb-4">
                <div class="card subject-card border-0 shadow-sm h-100 animate__animated animate__zoomIn">
                    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                        <span class="subject-code badge bg-dark"><?php echo htmlspecialchars($subject['subject_id']); ?></span>
                    </div>
                    <div class="card-body subject-body">
                        <div class="subject-details">
                            <p class="mb-3">
                                <i class="fas fa-id-card me-2 text-primary"></i>
                                <strong>Subject Code:</strong> <?php echo htmlspecialchars($subject['subject_id']); ?>
                            </p>
                            <a href="dashboard/index.php#subject-<?php echo $subject['subject_id']; ?>" 
                               class="btn btn-outline-light btn-block subject-btn">
                                <i class="fas fa-eye me-2"></i>View Submissions & Marks
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if ($subjects_result->num_rows === 0): ?>
            <div class="col-12">
                <div class="alert alert-info text-center animate__animated animate__fadeIn">
                    <i class="fas fa-info-circle me-2"></i>No subjects found for the current semester.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Base Reset */
body {
    background-color: #f4f7fa;
    font-family: 'Poppins', sans-serif;
}

.main-content {
    padding: 1rem 0 1rem 2rem; /* Added left padding to create space from sidebar */
    min-height: 100vh;
}

/* Hero Section */
.hero-card {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border-radius: 10px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-top: 1rem; /* Added for better spacing with sidebar */
}

/* Improved row spacing for better sidebar integration */
.row.mb-4.mt-4 {
    margin-top: 2rem !important; /* Increased for better positioning */
    margin-bottom: 2rem !important;
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

.hero-title {
    font-size: 1.5rem;
    position: relative;
    z-index: 1;
}

.hero-subtitle {
    font-size: 0.9rem;
    position: relative;
    z-index: 1;
}

/* Section Title */
.section-title {
    font-weight: 700;
    color: #2c3e50;
    border-bottom: 3px solid #3498db;
    padding-bottom: 0.5rem;
    letter-spacing: 1px;
}

/* Subject Cards */
.subject-card {
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: #fff;
}

.subject-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #3498db, #2980b9);
}

.subject-code {
    padding: 0.3rem 0.8rem;
    font-size: 0.9rem;
}

.subject-body {
    padding: 1.5rem;
    background: linear-gradient(145deg, #f9fbfd, #eef2f5);
}

.subject-details p {
    color: #34495e;
    font-size: 1rem;
    margin-bottom: 1.5rem;
}

.subject-btn {
    border-radius: 25px;
    padding: 0.6rem 1.2rem;
    font-weight: 500;
    border: 2px solid #3498db;
    color: #3498db;
    background: transparent;
    transition: all 0.3s ease;
}

.subject-btn:hover {
    background: #3498db;
    color: #fff;
    border-color: #3498db;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
}

/* Notification Styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 300px;
    background: #fff;
    border-radius: 10px;
    padding: 15px 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    z-index: 1000;
    opacity: 0;
    transition: all 0.3s ease;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification.success {
    border-left: 5px solid #2ecc71;
}

.notification-content {
    display: flex;
    align-items: center;
    color: #2c3e50;
}

.notification.success .notification-content i {
    color: #2ecc71;
}

.notification .close-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    border: none;
    background: none;
    cursor: pointer;
    color: #7f8c8d;
    padding: 5px;
}

.notification .close-btn:hover {
    color: #2c3e50;
}

/* Animations */
@keyframes float {
    0% { transform: translateY(0) translateX(0) rotate(0deg); }
    25% { transform: translateY(-10px) translateX(5px) rotate(5deg); }
    50% { transform: translateY(0) translateX(-5px) rotate(-5deg); }
    75% { transform: translateY(10px) translateX(3px) rotate(3deg); }
    100% { transform: translateY(0) translateX(0) rotate(0deg); }
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .hero-card {
        margin-top: 0.5rem; /* Less margin on smaller screens */
    }
    
    .row.mb-4.mt-4 {
        margin-top: 1rem !important;
    }
    
    .main-content {
        padding: 1rem 0 1rem 1rem; /* Reduced left padding for medium screens */
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 1.2rem;
    }
    
    .hero-subtitle {
        font-size: 0.8rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .subject-card {
        margin-bottom: 1.5rem;
    }
    
    .notification {
        min-width: 250px;
        right: 10px;
        top: 10px;
    }
    
    .main-content {
        padding: 1rem 0; /* No left padding for small screens */
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
    
    .hero-card .card-body {
        padding: 1rem;
    }
}

/* Additional spacing for container */
.container-fluid.px-4 {
    padding-top: 0.5rem;
}
</style>

<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Subject Card Animations
    const subjectCards = document.querySelectorAll('.subject-card');
    subjectCards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('animate__animated', 'animate__zoomIn');
            card.style.animationDelay = `${index * 0.1}s`;
        }, 100);
    });

    // Bubble Animation
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

    // Enhanced Notification System
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification animate__animated animate__slideInRight ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                <span>${message}</span>
            </div>
            <button class="close-btn"><i class="fas fa-times"></i></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            closeNotification(notification);
        }, 5000);
        
        notification.querySelector('.close-btn').addEventListener('click', () => closeNotification(notification));
    }
    
    function closeNotification(notification) {
        notification.classList.remove('show');
        notification.classList.add('animate__slideOutRight');
        setTimeout(() => {
            if (notification.parentNode) notification.parentNode.removeChild(notification);
        }, 500);
    }
    
    setTimeout(() => {
        showNotification('Welcome to your Subjects Dashboard!', 'success');
    }, 1000);
});
</script>

<?php
$stmt->close();
mysqli_close($conn);
include 'includes/footer.php';
?>
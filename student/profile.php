<?php
// t2_t3_assessment/student/profile.php
require_once 'includes/session_check.php';
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../config.php';

$reg_no = $_SESSION['reg_no'];

// Fetch student details with error handling
$student_query = "SELECT s.*, d.dept_name 
                 FROM students s 
                 JOIN departments d ON s.dept_id = d.dept_id 
                 WHERE s.reg_no = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("s", $reg_no);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    die("Student not found.");
}
$student = $student_result->fetch_assoc();
$stmt->close();
mysqli_close($conn); // Close connection early as it's no longer needed
?>

<div class="main-content">
    <div class="container-fluid px-4 py-4" style="padding-top: 0;">
        <!-- Profile Header -->
        <div class="row mb-5" style="margin-top: 20px;">
            <div class="col-12">
                <div class="card hero-card bg-gradient-primary border-0 shadow-sm">
                    <div class="bubble-container"></div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-5 fw-bold text-white mb-2">Your Profile</h1>
                                <p class="lead text-light mb-0">View and manage your personal information</p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="student-info text-light">
                                    <p class="mb-1"><i class="fas fa-id-card me-2"></i><?php echo htmlspecialchars($reg_no); ?></p>
                                    <p class="mb-0"><i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($student['dept_name']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="card profile-card border-0 shadow-sm">
                    <div class="bubble-container"></div>
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0 py-3 px-4"><i class="fas fa-user-circle me-2"></i>Student Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="profile-grid">
                            <?php
                            $fields = [
                                ['label' => 'Full Name', 'value' => $student['name'], 'icon' => 'fa-user'],
                                ['label' => 'Registration No.', 'value' => $student['reg_no'], 'icon' => 'fa-id-card'],
                                ['label' => 'Department', 'value' => $student['dept_name'], 'icon' => 'fa-building'],
                                ['label' => 'Year', 'value' => $student['year'], 'icon' => 'fa-calendar'],
                                ['label' => 'Semester', 'value' => $student['semester'], 'icon' => 'fa-book'],
                                ['label' => 'Section', 'value' => $student['section_name'], 'icon' => 'fa-users']
                            ];
                            foreach ($fields as $field) {
                                echo "
                                <div class='profile-item'>
                                    <div class='profile-icon'><i class='fas {$field['icon']}'></i></div>
                                    <div class='profile-content'>
                                        <span class='profile-label'>{$field['label']}</span>
                                        <span class='profile-value'>" . htmlspecialchars($field['value']) . "</span>
                                    </div>
                                </div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #6B48FF, #00DDEB);
}

.hero-card {
    border-radius: 15px;
    overflow: hidden;
    transition: transform 0.3s ease;
    position: relative;
    min-height: 150px;
}

.hero-card:hover {
    transform: translateY(-5px);
}

.profile-card {
    position: relative;
    border-radius: 15px;
    background: #ffffff;
    transition: all 0.3s ease;
    overflow: hidden;
}

.profile-card:hover {
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    transform: translateY(-5px);
}

.profile-card .card-header {
    border-bottom: 1px solid #e9ecef;
    position: relative;
    z-index: 1;
    background: linear-gradient(120deg, #f8f9fa, #ffffff);
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}

.profile-item {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.2rem;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.profile-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    background: #ffffff;
}

.profile-icon {
    width: 40px;
    height: 40px;
    background: #6B48FF;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.profile-content {
    display: flex;
    flex-direction: column;
}

.profile-label {
    font-size: 0.9rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-value {
    font-size: 1.1rem;
    color: #2c3e50;
    font-weight: 500;
}

/* Bubble Animation */
.bubble-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .hero-card .card-body { padding: 1.5rem; }
    .profile-grid { grid-template-columns: 1fr; }
    .display-5 { font-size: 2rem; }
    .profile-item { padding: 1rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Card animation
    const cards = document.querySelectorAll('.hero-card, .profile-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 200 + index * 200);
    });

    // Bubble animation
    const bubbleContainers = document.querySelectorAll('.bubble-container');
    const createBubble = (container) => {
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${4 + Math.random() * 4}s`;
        bubble.style.width = bubble.style.height = `${20 + Math.random() * 30}px`;
        container.appendChild(bubble);
        setTimeout(() => bubble.remove(), 6000);
    };
    bubbleContainers.forEach(container => setInterval(() => createBubble(container), 500));

    // Notification system
    const showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px; padding: 15px 25px;
            background: ${type === 'success' ? '#28a745' : '#17a2b8'};
            color: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000; transform: translateX(100%); transition: transform 0.3s ease;
        `;
        notification.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} me-2"></i>${message}`;
        document.body.appendChild(notification);
        setTimeout(() => notification.style.transform = 'translateX(0)', 100);
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    };

    setTimeout(() => showNotification('Profile loaded successfully!', 'success'), 1000);
});
</script>

<?php include 'includes/footer.php'; ?>
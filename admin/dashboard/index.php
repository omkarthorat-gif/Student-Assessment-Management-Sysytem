<?php
// index.php
ob_start(); // Start output buffering
require_once '../includes/session_check.php';
include '../includes/header.php';
include '../includes/sidebar.php';
require_once '../../config.php';

// Fetch statistics
$student_query = "SELECT COUNT(*) as total_students FROM Students";
$student_result = mysqli_query($conn, $student_query) or die("Error fetching students: " . mysqli_error($conn));
$student_data = mysqli_fetch_assoc($student_result);
$total_students = $student_data['total_students'];

$faculty_query = "SELECT COUNT(*) as total_faculty FROM Faculty";
$faculty_result = mysqli_query($conn, $faculty_query) or die("Error fetching faculty: " . mysqli_error($conn));
$faculty_data = mysqli_fetch_assoc($faculty_result);
$total_faculty = $faculty_data['total_faculty'];

$submission_query = "SELECT COUNT(*) as total_submissions FROM T2_T3_Submissions";
$submission_result = mysqli_query($conn, $submission_query) or die("Error fetching submissions: " . mysqli_error($conn));
$submission_data = mysqli_fetch_assoc($submission_result);
$total_submissions = $submission_data['total_submissions'];

$dept_query = "SELECT COUNT(*) as total_departments FROM Departments";
$dept_result = mysqli_query($conn, $dept_query) or die("Error fetching departments: " . mysqli_error($conn));
$dept_data = mysqli_fetch_assoc($dept_result);
$total_departments = $dept_data['total_departments'];

// Month-wise submissions
$submission_query = "SELECT MONTH(upload_date) as month, COUNT(*) as count 
                     FROM T2_T3_Submissions 
                     WHERE upload_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY MONTH(upload_date)
                     ORDER BY month ASC";
$submission_result = mysqli_query($conn, $submission_query) or die("Error fetching submission trends: " . mysqli_error($conn));
$submission_data = array();
$submission_labels = array();
while ($row = mysqli_fetch_assoc($submission_result)) {
    $month_name = date("M", mktime(0, 0, 0, $row['month'], 10));
    $submission_labels[] = $month_name;
    $submission_data[] = $row['count'];
}
$submission_labels_json = json_encode($submission_labels);
$submission_data_json = json_encode($submission_data);

// Recent activities
$activity_query = "SELECT 'Submission' as type, s.name, d.dept_name, sub.upload_date as date
                   FROM T2_T3_Submissions sub
                   JOIN Students s ON sub.reg_no = s.reg_no
                   JOIN Departments d ON s.dept_id = d.dept_id
                   UNION
                   SELECT 'Assessment' as type, s.name, d.dept_name, m.assessed_status as date
                   FROM Marks m
                   JOIN T2_T3_Submissions sub ON m.submission_id = sub.submission_id
                   JOIN Students s ON sub.reg_no = s.reg_no
                   JOIN Departments d ON s.dept_id = d.dept_id
                   WHERE m.assessed_status = 'Yes'
                   ORDER BY date DESC
                   LIMIT 4";
$activity_result = mysqli_query($conn, $activity_query) or die("Error fetching activities: " . mysqli_error($conn));

// Process note deletion
if (isset($_GET['delete_note']) && is_numeric($_GET['delete_note'])) {
    $note_id = (int)$_GET['delete_note'];
    $delete_query = "DELETE FROM admin_notes WHERE id = $note_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success_message'] = [
            'type' => 'delete',
            'title' => 'Note Deleted',
            'message' => 'Note removed successfully.'
        ];
        header("Location: index.php");
        ob_end_flush();
        exit;
    } else {
        $error_message = "Error deleting note: " . mysqli_error($conn);
    }
}

// Process note submission
if (isset($_POST['submit_note'])) {
    $title = mysqli_real_escape_string($conn, $_POST['note_title']);
    $content = mysqli_real_escape_string($conn, $_POST['note_content']);
    $due_date = !empty($_POST['due_date']) ? mysqli_real_escape_string($conn, $_POST['due_date']) : NULL;
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    
    $insert_query = "INSERT INTO admin_notes (title, content, due_date, priority, created_at) 
                     VALUES ('$title', '$content', " . ($due_date ? "'$due_date'" : "NULL") . ", '$priority', NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['success_message'] = [
            'type' => 'add',
            'title' => 'Note Added',
            'message' => "Note added successfully."
        ];
        header("Location: index.php");
        ob_end_flush();
        exit;
    } else {
        $error_message = "Error adding note: " . mysqli_error($conn);
    }
}

// Fetch admin notes
$notes_query = "SELECT * FROM admin_notes ORDER BY created_at DESC LIMIT 5";
$notes_result = mysqli_query($conn, $notes_query) or die("Error fetching notes: " . mysqli_error($conn));
$has_notes = mysqli_num_rows($notes_result) > 0;

// Dynamic greeting based on time (IST)
date_default_timezone_set('Asia/Kolkata');
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning, Admin!";
    $subcaption = "Start your day with purpose and insight.";
} elseif ($hour < 17) {
    $greeting = "Good Afternoon, Admin!";
    $subcaption = "Keep the momentum going strong.";
} else {
    $greeting = "Good Evening, Admin!";
    $subcaption = "Reflect and plan for a productive tomorrow.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        /* Improved Pastel Colors for Stats Cards */
        .stats-card.blue-pastel {
            background:rgb(255, 204, 204); /* Light blue pastel */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card.green-pastel {
            background: rgb(252, 187, 241); /* Light mint green pastel */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card.purple-pastel {
            background:rgb(200, 255, 245); /* Light pink/purple pastel */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card.orange-pastel {
            background:rgb(255, 248, 218); /* Light yellow pastel */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .datetime-container {
            position: relative;
            background: linear-gradient(135deg,rgb(127, 189, 246),rgb(94, 170, 237)); /* Lighter blue gradient */
            border-radius: 12px;
            padding: 1rem;
            color: #37474F;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            display: inline-block;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .datetime-container:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .datetime-content {
            position: relative;
            z-index: 2;
        }
        .datetime-container .date {
            font-size: 1rem;
            font-weight: 500;
        }
        .datetime-container .time {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s infinite ease-in-out;
        }
        .bubble:nth-child(1) { width: 20px; height: 20px; left: 10%; top: 20%; animation-delay: 0s; }
        .bubble:nth-child(2) { width: 15px; height: 15px; left: 70%; top: 40%; animation-delay: 2s; }
        .bubble:nth-child(3) { width: 25px; height: 25px; left: 40%; top: 60%; animation-delay: 4s; }

        @keyframes float {
            0% { transform: translateY(0); opacity: 0.8; }
            50% { transform: translateY(-20px); opacity: 0.4; }
            100% { transform: translateY(0); opacity: 0.8; }
        }

        /* Enhanced Toast Styling */
        .toast-container {
            z-index: 1100;
        }
        
        .toast-success {
            background: #E1F5FE;
            border-left: 4px solid #29B6F6;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            min-width: 280px;
        }
        
        .toast-success .toast-header {
            background: transparent;
            border-bottom: none;
            color: #0277BD;
            padding: 0.75rem 1rem;
        }
        
        .toast-success .toast-body {
            color: #0277BD;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
        
        .toast-success .btn-close {
            filter: opacity(0.6);
        }
        
        .toast-success .btn-close:hover {
            filter: opacity(1);
        }
        
        .toast-success.show {
            animation: slideInRight 0.3s ease-in-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-progress {
            height: 3px;
            background: #4FC3F7;
            animation: toastProgress 4s linear forwards;
        }
        
        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Other Styles */
        .hover-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .hover-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.07) !important; }
        .activity-icon { width: 40px; height: 40px; line-height: 40px; text-align: center; }
        .tiny { font-size: 0.85rem; }
        .hover-note { transition: all 0.3s ease; }
        .hover-note:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.06); }
        .notes-list { max-height: 400px; overflow-y: auto; }
        .notes-list::-webkit-scrollbar { width: 6px; }
        .notes-list::-webkit-scrollbar-track { background: #f7f9fc; border-radius: 3px; }
        .notes-list::-webkit-scrollbar-thumb { background: #c2cfe0; border-radius: 3px; }
        .notes-list::-webkit-scrollbar-thumb:hover { background: #a9b9d0; }
        body { background: #f8fafd; font-family: 'Segoe UI', sans-serif; }
        .main-content { padding: 2rem 0; }
        
        /* Activity Icons */
        .bg-success { background: #DCEDC8 !important; color: #558B2F !important; }
        .bg-warning { background: #FFF9C4 !important; color: #F57F17 !important; }
        
        /* Notes */
        .note-item { background: #F9FAFB !important; border-left: 3px solid #E1F5FE; }
        .note-item:hover { border-left-color: #81D4FA; }
        
        /* Priority badges */
        .badge.bg-danger { background-color: #FFEBEE !important; color: #C62828 !important; border: 1px solid #FFCDD2; }
        .badge.bg-warning { background-color: #FFF8E1 !important; color: #FF8F00 !important; border: 1px solid #FFE0B2; }
        .badge.bg-info { background-color: #E1F5FE !important; color: #0277BD !important; border: 1px solid #B3E5FC; }
        .badge.bg-success { background-color: #E8F5E9 !important; color: #2E7D32 !important; border: 1px solid #C8E6C9; }
        
        /* Modal styling */
        .modal-content { border: none; box-shadow: 0 5px 30px rgba(0,0,0,0.1); }
        .modal-header { background: #F5F7FA; }
        .form-control, .form-select { border-color: #E0E7FF; }
        .form-control:focus, .form-select:focus { border-color: #B3E5FC; box-shadow: 0 0 0 0.25rem rgba(3, 169, 244, 0.1); }
    </style>
</head>
<body>
<div class="main-content">
    <div class="container-fluid px-4">
        <div class="row align-items-center mb-5">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold text-dark mb-2 animate__animated animate__fadeIn">
                    <?php echo $greeting; ?>
                </h1>
                <p class="text-muted lead animate__animated animate__fadeIn animate__delay-1s">
                    <?php echo $subcaption; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="datetime-container animate__animated animate__fadeIn">
                    <div class="datetime-content">
                        <div class="date" id="currentDate"><?php echo date('D, M d, Y'); ?></div>
                        <div class="time" id="currentTime"><?php echo date('H:i:s'); ?></div>
                    </div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                </div>
            </div>
        </div>

        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="successToast" class="toast toast-success" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
                <div class="toast-header">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto" id="toastTitle">Success</strong>
                    <small id="toastTime"><?php echo date('H:i'); ?></small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="toastMessage">
                    Operation completed successfully!
                </div>
                <div class="toast-progress"></div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <?php 
            $stats = [
                ['count' => $total_students, 'title' => 'Students', 'icon' => 'fa-user-graduate', 'color' => 'blue'],
                ['count' => $total_faculty, 'title' => 'Faculty', 'icon' => 'fa-chalkboard-teacher', 'color' => 'purple'],
                ['count' => $total_submissions, 'title' => 'Submissions', 'icon' => 'fa-file-upload', 'color' => 'green'],
                ['count' => $total_departments, 'title' => 'Departments', 'icon' => 'fa-building', 'color' => 'orange']
            ];
            foreach ($stats as $index => $stat): ?>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm hover-card h-100 animate__animated animate__fadeInUp" style="animation-delay: <?php echo $index * 0.2; ?>s">
                    <div class="card-body p-4 stats-card <?php echo $stat['color']; ?>-pastel">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="display-4 fw-bold text-dark mb-0"><?php echo $stat['count']; ?></h2>
                                <p class="text-dark mt-1 opacity-75"><?php echo $stat['title']; ?></p>
                            </div>
                            <i class="fas <?php echo $stat['icon']; ?> fa-2x text-dark opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInLeft">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0">Recent Activities</h5>
                            <a href="#" class="btn btn-link text-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="activity-list">
                            <?php 
                            if (mysqli_num_rows($activity_result) > 0) {
                                while ($activity = mysqli_fetch_assoc($activity_result)) { 
                                    $icon_class = ($activity['type'] == 'Submission') ? 'fas fa-file-upload' : 'fas fa-chart-line';
                                    $bg_class = ($activity['type'] == 'Submission') ? 'bg-success' : 'bg-warning';
                                    $activity_title = ($activity['type'] == 'Submission') ? 'New Submission' : 'Assessment Completed';
                            ?>
                            <div class="activity-item d-flex align-items-start mb-3 animate__animated animate__fadeIn">
                                <div class="activity-icon <?php echo $bg_class; ?> rounded-circle text-white me-3">
                                    <i class="<?php echo $icon_class; ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-semibold mb-1"><?php echo $activity_title; ?></h6>
                                    <p class="text-muted small mb-1"><?php echo $activity['name']; ?> • <?php echo $activity['dept_name']; ?></p>
                                    <span class="text-muted tiny"><?php echo date('M d, Y H:i', strtotime($activity['date'])); ?></span>
                                </div>
                            </div>
                            <?php }} else { ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No recent activities</p>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInRight">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0">Important Notes</h5>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                <i class="fas fa-plus me-1"></i> Add Note
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($has_notes): ?>
                            <div class="notes-list">
                                <?php while ($note = mysqli_fetch_assoc($notes_result)): 
                                    $priority_class = [
                                        'High' => 'danger',
                                        'Medium' => 'warning',
                                        'Low' => 'info'
                                    ][$note['priority']] ?? 'info';
                                    
                                    $days_remaining = "";
                                    if ($note['due_date']) {
                                        $due_date = new DateTime($note['due_date']);
                                        $today = new DateTime();
                                        $interval = $today->diff($due_date);
                                        $days_remaining = $interval->format('%r%a');
                                    }
                                ?>
                                <div class="note-item rounded-3 p-3 mb-3 shadow-sm hover-note">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1 me-3">
                                            <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($note['title']); ?></h6>
                                            <p class="text-muted small mb-2"><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
                                            <div class="text-muted tiny">
                                                <span>Added: <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                                                <?php if ($note['due_date']): ?>
                                                    <span class="ms-2">Due: <?php echo date('M d, Y', strtotime($note['due_date'])); ?></span>
                                                    <span class="ms-2 badge bg-<?php echo $days_remaining < 0 ? 'danger' : ($days_remaining == 0 ? 'warning' : 'success'); ?>">
                                                        <?php 
                                                        if ($days_remaining < 0) echo "Overdue " . abs($days_remaining) . "d";
                                                        elseif ($days_remaining == 0) echo "Due Today";
                                                        else echo $days_remaining . "d left";
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-<?php echo $priority_class; ?> me-2"><?php echo $note['priority']; ?></span>
                                            <a href="index.php?delete_note=<?php echo $note['id']; ?>" 
                                               class="btn btn-outline-danger btn-sm rounded-circle" 
                                               onclick="return confirm('Are you sure you want to delete this note?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-sticky-note fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-3">No notes yet. Start by adding one!</p>
                                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                    <i class="fas fa-plus me-1"></i> Add Note
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="addNoteModalLabel">Add New Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="index.php" id="noteForm">
                <div class="modal-body px-4">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" class="form-control shadow-sm" id="note_title" name="note_title" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Content</label>
                        <textarea class="form-control shadow-sm" id="note_content" name="note_content" rows="4" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Due Date <span class="text-muted small">(Optional)</span></label>
                        <input type="date" class="form-control shadow-sm" id="due_date" name="due_date">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Priority</label>
                        <select class="form-select shadow-sm" id="priority" name="priority" required>
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_note" class="btn btn-primary rounded-pill px-4">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update time every second with IST
    function updateTime() {
        const now = new Date();
        const istTime = new Date(now.getTime() + (330 * 60000));
        const timeString = istTime.toLocaleTimeString('en-US', { hour12: false });
        document.getElementById('currentTime').textContent = timeString;
    }
    
    updateTime();
    setInterval(updateTime, 1000);

    // Handle success messages
    <?php if (isset($_SESSION['success_message'])): ?>
        const successMessage = <?php echo json_encode($_SESSION['success_message']); ?>;
        const toast = new bootstrap.Toast(document.getElementById('successToast'));
        
        document.getElementById('toastTitle').textContent = successMessage.title;
        document.getElementById('toastMessage').textContent = successMessage.message;
        document.getElementById('toastTime').textContent = '<?php echo date('H:i'); ?>';
        
        // Add specific icon based on action type
        const iconElement = document.querySelector('#successToast .toast-header i');
        iconElement.className = successMessage.type === 'add' ? 
            'fas fa-plus-circle me-2' : 
            'fas fa-trash-alt me-2';
        
        toast.show();
        
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
mysqli_close($conn);
include '../includes/footer.php';
ob_end_flush();
?>
</body>
</html>
<?php
// faculty_sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = dirname($_SERVER['PHP_SELF']);

// Get faculty info if logged in
$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty';
$faculty_id = $_SESSION['faculty_id'] ?? '';
$first_letter = substr($faculty_name, 0, 1);
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <img src="/t2_t3_assessment/assets/vignan.png" alt="Vignan University Logo" class="img-fluid" style="max-width: 200px; margin: 5px auto; display: block;">
        </div>
    </div>
    <div class="mt-4">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="/t2_t3_assessment/faculty/dashboard/index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/t2_t3_assessment/faculty/dashboard/assigned_subjects.php" class="nav-link <?php echo ($current_page == 'assigned_subjects.php') ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Assigned Subjects
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/t2_t3_assessment/faculty/dashboard/student_submissions.php" class="nav-link <?php echo ($current_page == 'student_submissions.php') ? 'active' : ''; ?>">
                    <i class="fas fa-folder-open"></i> Student Submissions
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/t2_t3_assessment/faculty/dashboard/enter_marks.php" class="nav-link <?php echo ($current_page == 'enter_marks.php') ? 'active' : ''; ?>">
                    <i class="fas fa-pen"></i> Enter Marks
                </a>
            </li>

            <li class="nav-item mt-4">
                <a href="/t2_t3_assessment/faculty/profile.php" class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/t2_t3_assessment/faculty/authentication/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="content-wrapper" id="content">
    <div class="top-navbar">
        <div class="user-dropdown dropdown">
            <a class="dropdown-toggle d-flex align-items-center text-decoration-none" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar me-2">
                    <span><?php echo $first_letter; ?></span>
                </div>
                <div>
                    <div class="fw-bold"><?php echo $faculty_name; ?></div>
                    <div class="small text-muted"><?php echo $faculty_id; ?></div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="/t2_t3_assessment/faculty/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/t2_t3_assessment/faculty/authentication/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

<style>
/* Same styling as student sidebar */
body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: linear-gradient(135deg, #1a4b8c 0%, #2673dd 100%);
    color: #fff;
    transition: all 0.3s ease-in-out;
    z-index: 1000;
}

.content-wrapper {
    margin-left: 250px;
    transition: all 0.3s ease-in-out;
}

.sidebar .nav-link {
    color: rgba(255, 255, 255, 0.85);
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
    padding: 12px 20px;
    margin: 4px 0;
    border-radius: 0 20px 20px 0;
}

.sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border-left: 3px solid #4dabf7;
    transform: translateX(5px);
}

.sidebar .nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    border-left: 3px solid #ffffff;
    font-weight: bold;
}

.sidebar .nav-link i {
    margin-right: 10px;
    transition: all 0.3s;
}

.sidebar .nav-link:hover i {
    transform: scale(1.2);
}

.user-avatar {
    background: #4dabf7;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
    transition: all 0.3s ease;
}

.user-avatar:hover {
    transform: scale(1.1);
    background: #0d6efd;
}

.top-navbar {
    position: fixed;
    top: 0;
    left: 250px;
    right: 0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    background: #ffffff;
    padding: 10px 20px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    transition: all 0.3s ease-in-out;
    z-index: 999;
}

.logo-container {
    max-width: 200px;
    margin: 5px auto;
    overflow: hidden;
    display: block;
}

.logo-zoomed {
    max-width: 100%;
    transform: scale(1.2);
    transform-origin: center;
    transition: transform 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.nav-item {
    opacity: 0;
    animation: fadeIn 0.5s ease forwards;
}

.nav-item:nth-child(1) { animation-delay: 0.1s; }
.nav-item:nth-child(2) { animation-delay: 0.2s; }
.nav-item:nth-child(3) { animation-delay: 0.3s; }
.nav-item:nth-child(4) { animation-delay: 0.4s; }
.nav-item:nth-child(5) { animation-delay: 0.5s; }
.nav-item:nth-child(6) { animation-delay: 0.6s; }

@media (max-width: 992px) {
    .sidebar {
        left: 0;
    }
    
    .content-wrapper {
        margin-left: 250px;
    }
    
    .top-navbar {
        left: 250px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.querySelector('i').classList.add('fa-beat');
        });
        
        item.addEventListener('mouseleave', function() {
            this.querySelector('i').classList.remove('fa-beat');
        });
    });
});
</script>
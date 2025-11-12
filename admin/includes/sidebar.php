<!-- sidebar.php -->
<?php
// sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = dirname($_SERVER['PHP_SELF']);
?>

<style>
    .vignan-logo {
        width: 100%;
        height: auto;
        max-width: 230px;
        display: block;
        margin: 5px 5px;
    }
    .sidebar {
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        position: fixed;
        width: 250px;
        background-color: #0d47a1; /* Darker blue background to match screenshot */
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        z-index: 1030;
    }
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: #0a3880;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 5px;
    }
    .submenu {
        padding-left: 25px;
        list-style: none;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .submenu.show {
        max-height: 500px;
    }
    .nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 20px;
        border-radius: 5px;
        margin: 2px 5px;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
        color: #ffffff; /* White text for all menu items */
    }
    .nav-link:hover {
        background-color: #1565c0;
    }
    /* Fix for text and icons in menu items */
    .nav-link div, 
    .nav-link i {
        color: #ffffff !important; /* Force white text and icons */
    }
    .nav-link i.menu-icon {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    .nav-link i.dropdown-icon {
        font-size: 12px;
        transition: transform 0.3s;
    }
    .nav-link.collapsed i.dropdown-icon {
        transform: rotate(-90deg);
    }
    .nav-item {
        margin-bottom: 2px;
    }
    .submenu .nav-link {
        padding-left: 15px;
        font-size: 0.9rem;
    }
    .active {
        background-color: #1976d2; /* Lighter blue for active items */
        font-weight: 600;
    }
    /* For submenu parent items that are active */
    .nav-link.active, 
    .nav-link.active.collapsed {
        background-color: #1976d2;
    }
    /* Make sure text in active nav-link div remains visible */
    .nav-link.active div,
    .nav-link.active.collapsed div {
        color: #ffffff !important;
        visibility: visible !important;
        opacity: 1 !important;
        display: flex !important;
    }
    /* Submenu items */
    .submenu .nav-link {
        background-color: #0d47a1; /* Match sidebar for submenu items */
    }
    .submenu .nav-link.active {
        background-color: #1976d2; /* Lighter blue for active submenu items */
    }
    .content-wrapper {
        margin-left: 250px;
        padding-top: 60px; /* Added to prevent content from hiding under navbar */
        transition: margin-left 0.3s ease;
        width: calc(100% - 250px); /* Set width to ensure content doesn't overflow */
    }
    .top-navbar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 10px 20px;
        background-color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        position: fixed; /* Changed from sticky to fixed */
        top: 0;
        z-index: 1020;
        width: calc(100% - 250px); /* Make it match the content area */
        right: 0;
        transition: all 0.3s ease;
    }
    .user-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background-color: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    .user-dropdown {
        position: relative;
    }
    .dropdown-menu {
        min-width: 200px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border: none;
        border-radius: 0.5rem;
    }
    .dropdown-menu .dropdown-item {
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
    }
    .dropdown-menu .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    /* Bubble animation */
    .bubble {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: bubble 8s infinite;
        pointer-events: none;
    }
    @keyframes bubble {
        0% { transform: scale(0); opacity: 0.8; }
        50% { opacity: 0.3; }
        100% { transform: scale(2); opacity: 0; }
    }
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .top-navbar {
            padding: 8px 15px;
        }
    }
    @media (max-width: 768px) {
        .sidebar {
            width: 200px;
        }
        .content-wrapper {
            margin-left: 200px;
            width: calc(100% - 200px);
        }
        .top-navbar {
            width: calc(100% - 200px); /* Match with content area for tablet */
        }
    }
    @media (max-width: 576px) {
        .sidebar {
            width: 0;
            transform: translateX(-200px);
        }
        .content-wrapper {
            margin-left: 0;
            width: 100%;
        }
        .top-navbar {
            width: 100%; /* Full width on mobile view */
            padding: 8px 10px;
        }
        /* Adjust dropdown position for mobile */
        .dropdown-menu {
            position: absolute;
            right: 0;
            left: auto;
        }
    }
</style>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <img src="../../assets/vignan.png" alt="Vignan University Logo" class="vignan-logo">
        </div>
    </div>
    <div class="mt-4">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="/t2_t3_assessment/admin/dashboard/index.php" class="nav-link <?php echo ($current_page == 'index.php' && strpos($current_dir, '/admin/dashboard') !== false) ? 'active' : ''; ?>">
                    <div><i class="fas fa-tachometer-alt menu-icon"></i> Dashboard</div>
                </a>
            </li>

            <!-- Students Section -->
            <li class="nav-item">
                <a href="#studentSubmenu" class="nav-link <?php echo (strpos($current_dir, '/admin/student') !== false) ? 'active' : 'collapsed'; ?>" data-toggle="collapse">
                    <div><i class="fas fa-user-graduate menu-icon"></i> Students</div>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="collapse submenu <?php echo (strpos($current_dir, '/admin/student') !== false) ? 'show' : ''; ?>" id="studentSubmenu">
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/student/add_student.php" class="nav-link <?php echo ($current_page == 'add_student.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Add Student
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/student/view_students.php" class="nav-link <?php echo ($current_page == 'view_students.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> View Students
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Faculty Section -->
            <li class="nav-item">
                <a href="#facultySubmenu" class="nav-link <?php echo (strpos($current_dir, '/admin/faculty') !== false) ? 'active' : 'collapsed'; ?>" data-toggle="collapse">
                    <div><i class="fas fa-chalkboard-teacher menu-icon"></i> Faculty</div>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="collapse submenu <?php echo (strpos($current_dir, '/admin/faculty') !== false) ? 'show' : ''; ?>" id="facultySubmenu">
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/faculty/add_faculty.php" class="nav-link <?php echo ($current_page == 'add_faculty.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Add Faculty
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/faculty/view_faculty.php" class="nav-link <?php echo ($current_page == 'view_faculty.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> View Faculty
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Departments Section -->
            <li class="nav-item">
                <a href="#departmentSubmenu" class="nav-link <?php echo (strpos($current_dir, '/admin/department') !== false) ? 'active' : 'collapsed'; ?>" data-toggle="collapse">
                    <div><i class="fas fa-building menu-icon"></i> Departments</div>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="collapse submenu <?php echo (strpos($current_dir, '/admin/department') !== false) ? 'show' : ''; ?>" id="departmentSubmenu">
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/department/add_department.php" class="nav-link <?php echo ($current_page == 'add_department.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Add Department
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/department/view_departments.php" class="nav-link <?php echo ($current_page == 'view_departments.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> View Departments
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Sections Section -->
            <li class="nav-item">
                <a href="#sectionSubmenu" class="nav-link <?php echo (strpos($current_dir, '/admin/section') !== false) ? 'active' : 'collapsed'; ?>" data-toggle="collapse">
                    <div><i class="fas fa-layer-group menu-icon"></i> Sections</div>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="collapse submenu <?php echo (strpos($current_dir, '/admin/section') !== false) ? 'show' : ''; ?>" id="sectionSubmenu">
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/section/add_section.php" class="nav-link <?php echo ($current_page == 'add_section.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Add Section
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/section/view_sections.php" class="nav-link <?php echo ($current_page == 'view_sections.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> View Sections
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Subjects Section -->
            <li class="nav-item">
                <a href="#subjectSubmenu" class="nav-link <?php echo (strpos($current_dir, '/admin/subject') !== false) ? 'active' : 'collapsed'; ?>" data-toggle="collapse">
                    <div><i class="fas fa-book menu-icon"></i> Subjects</div>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="collapse submenu <?php echo (strpos($current_dir, '/admin/subject') !== false) ? 'show' : ''; ?>" id="subjectSubmenu">
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/subject/add_subject.php" class="nav-link <?php echo ($current_page == 'add_subject.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Add Subject
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/subject/view_subjects.php" class="nav-link <?php echo ($current_page == 'view_subjects.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> View Subjects
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Modified Allocations Section -->
            <li class="nav-item">
                <a href="#allocationSubmenu" class="nav-link <?php echo (strpos($current_dir, '/admin/allocation') !== false || (strpos($current_dir, '/admin/subject') !== false && ($current_page == 'allocate_subject.php' || $current_page == 'view_allocations.php'))) ? 'active' : 'collapsed'; ?>" data-toggle="collapse">
                    <div><i class="fas fa-tasks menu-icon"></i> Allocations</div>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="collapse submenu <?php echo (strpos($current_dir, '/admin/allocation') !== false || (strpos($current_dir, '/admin/subject') !== false && ($current_page == 'allocate_subject.php' || $current_page == 'view_allocations.php'))) ? 'show' : ''; ?>" id="allocationSubmenu">
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/subject/allocate_subject.php" class="nav-link <?php echo ($current_page == 'allocate_subject.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Assign Subjects to Semester
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/t2_t3_assessment/admin/subject/view_allocations.php" class="nav-link <?php echo ($current_page == 'view_allocations.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> View Allocated Subjects
                        </a>
                    </li>
                </ul>
            </li>

            

            <!-- Settings and Logout -->
            
            <li class="nav-item">
                <a href="/t2_t3_assessment/admin/authentication/logout.php" class="nav-link">
                    <div><i class="fas fa-sign-out-alt menu-icon"></i> Logout</div>
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
                    <span>A</span>
                </div>
                <div>
                    <div class="small text-muted">Administrator</div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="/t2_t3_assessment/admin/authentication/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown toggles - Improved event handling
    const dropdownToggles = document.querySelectorAll('.nav-link[href^="#"]');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const submenu = document.querySelector(targetId);
            
            if (submenu) {
                submenu.classList.toggle('show');
                this.classList.toggle('collapsed');
                
                // Make sure text is still visible
                const menuText = this.querySelector('div');
                if (menuText) {
                    menuText.style.color = '#ffffff';
                    menuText.style.visibility = 'visible';
                    menuText.style.opacity = '1';
                }
            }
        });
    });

    // Bubble animation
    const sidebar = document.querySelector('.sidebar');
    function createBubble() {
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        
        const size = Math.random() * 30 + 20;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        bubble.style.left = `${Math.random() * 230}px`;
        bubble.style.top = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${Math.random() * 4 + 4}s`;
        
        sidebar.appendChild(bubble);
        
        setTimeout(() => {
            bubble.remove();
        }, 8000);
    }

    // Create bubbles at intervals
    setInterval(createBubble, 2000);
    
    // Handle responsiveness for sidebar and top navbar
    function handleResponsiveness() {
        const windowWidth = window.innerWidth;
        const sidebar = document.querySelector('.sidebar');
        const content = document.querySelector('.content-wrapper');
        const topNavbar = document.querySelector('.top-navbar');
        
        if (windowWidth <= 576) {
            // Mobile view adjustments
            sidebar.style.transform = 'translateX(-200px)';
            content.style.marginLeft = '0';
            content.style.width = '100%';
            topNavbar.style.width = '100%';
        } else if (windowWidth <= 768) {
            // Tablet view adjustments
            sidebar.style.transform = 'translateX(0)';
            sidebar.style.width = '200px';
            content.style.marginLeft = '200px';
            content.style.width = 'calc(100% - 200px)';
            topNavbar.style.width = 'calc(100% - 200px)';
        } else {
            // Desktop view adjustments
            sidebar.style.transform = 'translateX(0)';
            sidebar.style.width = '250px';
            content.style.marginLeft = '250px';
            content.style.width = 'calc(100% - 250px)';
            topNavbar.style.width = 'calc(100% - 250px)';
        }
    }
    
    // Initial check and listen for window resize
    handleResponsiveness();
    window.addEventListener('resize', handleResponsiveness);
});
</script>
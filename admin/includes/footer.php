<?php
// footer.php
?>
    </div> <!-- End content-wrapper -->
</div> <!-- End admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    // Sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const contentWrapper = document.querySelector('.content-wrapper');
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            contentWrapper.classList.toggle('active');
        });
        
        // Responsive handling
        function checkWidth() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('active');
                contentWrapper.classList.remove('active');
            } else {
                sidebar.classList.add('active');
                contentWrapper.classList.add('active');
            }
        }
        
        // Initial check
        checkWidth();
        
        // Listen for window resize
        window.addEventListener('resize', checkWidth);
        
        // Dropdown for mobile
        const dropdownItems = document.querySelectorAll('.has-dropdown');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                this.classList.toggle('show');
                const dropdown = this.querySelector('.sidebar-dropdown');
                if (dropdown) {
                    dropdown.classList.toggle('show');
                }
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                const dropdowns = document.querySelectorAll('.dropdown-menu.show');
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
        
        // Active link
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            if (currentPath.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    });
</script>
<script src="/admin/assets/js/script.js"></script>

</body>
</html>
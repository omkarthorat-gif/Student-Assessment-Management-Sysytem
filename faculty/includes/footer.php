<?php
// footer.php
?>
        </div> <!-- End main-content -->
    </div> <!-- End content-wrapper -->
</div> <!-- End faculty-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const contentWrapper = document.querySelector('.content-wrapper');

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            contentWrapper.classList.toggle('active');
        });

        function checkWidth() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('active');
                contentWrapper.classList.remove('active');
            } else {
                sidebar.classList.add('active');
                contentWrapper.classList.add('active');
            }
        }

        checkWidth();
        window.addEventListener('resize', checkWidth);
    });
</script>
<script src="/t2_t3_assessment/faculty/assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
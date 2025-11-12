<?php
// student/footer.php
?>
               
        </footer>
    </div> <!-- End of content-wrapper -->
</div> <!-- End of student-wrapper -->

<!-- Bootstrap & jQuery JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script>
    // Any additional custom scripts can go here
    
    // Example: Activate tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // Example: Confirm before dangerous actions
    document.querySelectorAll('.confirm-action').forEach(function(element) {
        element.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to perform this action?')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
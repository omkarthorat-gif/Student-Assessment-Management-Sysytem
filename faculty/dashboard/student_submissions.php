<?php
// t2_t3_assessment/faculty/student_submissions.php
require_once '../includes/session_check.php'; // Faculty session check
include '../includes/header.php';
include '../includes/sidebar.php';
require_once '../../config.php';

$faculty_id = $_SESSION['faculty_id'];

// Fetch faculty's assigned subjects for filtering
$subjects_query = "SELECT DISTINCT s.subject_id, s.subject_name
                  FROM faculty_subject_assign fsa
                  JOIN subjects s ON fsa.subject_id = s.subject_id
                  WHERE fsa.faculty_id = ?
                  ORDER BY s.subject_name";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$subjects_result = $stmt->get_result();

// Handle filter parameters
$filter_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$filter_semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$filter_section = isset($_GET['section']) ? $_GET['section'] : '';
$filter_doc_type = isset($_GET['doc_type']) ? $_GET['doc_type'] : '';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <!-- Header Section -->
        <div class="row mb-4" style="margin-top: 30px;">
            <div class="col-12">
                <div class="hero-card card border-0 shadow-sm position-relative overflow-hidden bg-gradient-primary">
                    <div class="bubble-container position-absolute w-100 h-100" style="top: 0; left: 0;"></div>
                    <div class="card-body p-4 position-relative">
                        <h1 class="display-5 fw-bold mb-2 text-white">Student Submissions</h1>
                        <p class="lead text-light mb-0">Filter and view student submissions for your assigned subjects</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="section-title"><i class="fas fa-filter me-2"></i>Filter Submissions</h4>
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="subject_id" class="form-label">Subject</label>
                                    <select name="subject_id" id="subject_id" class="form-select">
                                        <option value="">All Subjects</option>
                                        <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>"
                                                <?php echo $filter_subject === $subject['subject_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars("{$subject['subject_name']} ({$subject['subject_id']})"); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="year" class="form-label">Year</label>
                                    <select name="year" id="year" class="form-select">
                                        <option value="">All Years</option>
                                        <option value="1" <?php echo $filter_year === '1' ? 'selected' : ''; ?>>1</option>
                                        <option value="2" <?php echo $filter_year === '2' ? 'selected' : ''; ?>>2</option>
                                        <option value="3" <?php echo $filter_year === '3' ? 'selected' : ''; ?>>3</option>
                                        <option value="4" <?php echo $filter_year === '4' ? 'selected' : ''; ?>>4</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select name="semester" id="semester" class="form-select">
                                        <option value="">All Semesters</option>
                                        <option value="1" <?php echo $filter_semester === '1' ? 'selected' : ''; ?>>1</option>
                                        <option value="2" <?php echo $filter_semester === '2' ? 'selected' : ''; ?>>2</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="section" class="form-label">Section</label>
                                    <select name="section" id="section" class="form-select">
                                        <option value="">All Sections</option>
                                        <option value="A" <?php echo $filter_section === 'A' ? 'selected' : ''; ?>>A</option>
                                        <option value="B" <?php echo $filter_section === 'B' ? 'selected' : ''; ?>>B</option>
                                        <option value="C" <?php echo $filter_section === 'C' ? 'selected' : ''; ?>>C</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="doc_type" class="form-label">Document Type</label>
                                    <select name="doc_type" id="doc_type" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="T2" <?php echo $filter_doc_type === 'T2' ? 'selected' : ''; ?>>T2</option>
                                        <option value="T3" <?php echo $filter_doc_type === 'T3' ? 'selected' : ''; ?>>T3</option>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="row">
            <div class="col-12">
                <div class="card subject-card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="section-title"><i class="fas fa-file-alt me-2"></i>Submissions List</h4>
                        <div class="table-responsive">
                            <?php
                            // Build the submissions query based on filter_doc_type
                            if ($filter_doc_type === '') {
                                // When no doc_type is selected, group by reg_no and aggregate T2/T3 marks
                                $submissions_query = "SELECT 
                                    st.reg_no,
                                    st.name AS student_name,
                                    s.subject_name,
                                    s.subject_id,
                                    fsa.section_name,
                                    MAX(CASE WHEN sub.document_type = 'T2' THEN sub.upload_date END) AS t2_upload_date,
                                    MAX(CASE WHEN sub.document_type = 'T3' THEN sub.upload_date END) AS t3_upload_date,
                                    MAX(CASE WHEN sub.document_type = 'T2' THEN m.t2_mark1 END) AS t2_mark1,
                                    MAX(CASE WHEN sub.document_type = 'T2' THEN m.t2_mark2 END) AS t2_mark2,
                                    MAX(CASE WHEN sub.document_type = 'T3' THEN m.t3_mark1 END) AS t3_mark1,
                                    MAX(CASE WHEN sub.document_type = 'T3' THEN m.t3_mark2 END) AS t3_mark2,
                                    MAX(CASE WHEN sub.document_type = 'T2' THEN m.total_t2_marks END) AS total_t2_marks,
                                    MAX(CASE WHEN sub.document_type = 'T3' THEN m.total_t3_marks END) AS total_t3_marks,
                                    MAX(CASE WHEN sub.document_type = 'T2' THEN m.assessed_status END) AS t2_assessed,
                                    MAX(CASE WHEN sub.document_type = 'T3' THEN m.assessed_status END) AS t3_assessed,
                                    MAX(CASE WHEN sub.document_type = 'T2' THEN sub.submission_id END) AS t2_submission_id,
                                    MAX(CASE WHEN sub.document_type = 'T3' THEN sub.submission_id END) AS t3_submission_id
                                    FROM students st
                                    JOIN t2_t3_submissions sub ON sub.reg_no = st.reg_no
                                    JOIN subjects s ON sub.subject_id = s.subject_id
                                    JOIN faculty_subject_assign fsa ON sub.subject_id = fsa.subject_id 
                                        AND sub.year = fsa.year 
                                        AND sub.semester = fsa.semester
                                    LEFT JOIN marks m ON sub.submission_id = m.submission_id
                                    WHERE fsa.faculty_id = ?";
                            } else {
                                // When specific doc_type is selected
                                $submissions_query = "SELECT 
                                    sub.submission_id,
                                    sub.reg_no,
                                    st.name AS student_name,
                                    s.subject_name,
                                    s.subject_id,
                                    sub.document_type,
                                    sub.upload_date,
                                    m.t2_mark1, m.t2_mark2, m.t3_mark1, m.t3_mark2,
                                    m.total_t2_marks, m.total_t3_marks, m.assessed_status,
                                    fsa.section_name
                                    FROM t2_t3_submissions sub
                                    JOIN students st ON sub.reg_no = st.reg_no
                                    JOIN subjects s ON sub.subject_id = s.subject_id
                                    JOIN faculty_subject_assign fsa ON sub.subject_id = fsa.subject_id 
                                        AND sub.year = fsa.year 
                                        AND sub.semester = fsa.semester
                                    LEFT JOIN marks m ON sub.submission_id = m.submission_id
                                    WHERE fsa.faculty_id = ?";
                            }

                            $params = [$faculty_id];
                            $types = "s";

                            if ($filter_subject) {
                                $submissions_query .= " AND sub.subject_id = ?";
                                $params[] = $filter_subject;
                                $types .= "s";
                            }
                            if ($filter_year) {
                                $submissions_query .= " AND sub.year = ?";
                                $params[] = $filter_year;
                                $types .= "i";
                            }
                            if ($filter_semester) {
                                $submissions_query .= " AND sub.semester = ?";
                                $params[] = $filter_semester;
                                $types .= "i";
                            }
                            if ($filter_section) {
                                $submissions_query .= " AND fsa.section_name = ?";
                                $params[] = $filter_section;
                                $types .= "s";
                            }
                            if ($filter_doc_type) {
                                $submissions_query .= " AND sub.document_type = ?";
                                $params[] = $filter_doc_type;
                                $types .= "s";
                            }

                            if ($filter_doc_type === '') {
                                $submissions_query .= " GROUP BY st.reg_no, s.subject_id, fsa.section_name";
                            }
                            $submissions_query .= " ORDER BY " . ($filter_doc_type ? "sub.upload_date" : "st.reg_no") . " DESC";

                            $stmt = $conn->prepare($submissions_query);
                            $stmt->bind_param($types, ...$params);
                            $stmt->execute();
                            $submissions_result = $stmt->get_result();
                            ?>

                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Reg No</th>
                                        <th>Student Name</th>
                                        <th>Subject</th>
                                        <th>Section</th>
                                        <?php if ($filter_doc_type === 'T2'): ?>
                                            <th>Upload Date</th>
                                            <th>T2 Marks</th>
                                            <th>Status</th>
                                        <?php elseif ($filter_doc_type === 'T3'): ?>
                                            <th>Upload Date</th>
                                            <th>T3 Marks</th>
                                            <th>Status</th>
                                        <?php else: ?>
                                            <th>T2 Upload Date</th>
                                            <th>T2 Marks</th>
                                            <th>T2 Status</th>
                                            <th>T3 Upload Date</th>
                                            <th>T3 Marks</th>
                                            <th>T3 Status</th>
                                        <?php endif; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($submissions_result->num_rows > 0): ?>
                                        <?php while ($submission = $submissions_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($submission['reg_no']); ?></td>
                                                <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($submission['subject_name'] . " ({$submission['subject_id']})"); ?></td>
                                                <td><?php echo htmlspecialchars($submission['section_name']); ?></td>
                                                <?php if ($filter_doc_type === 'T2'): ?>
                                                    <td><?php echo date('M d, Y', strtotime($submission['upload_date'])); ?></td>
                                                    <td><?php echo $submission['assessed_status'] === 'Yes' ? htmlspecialchars($submission['total_t2_marks']) . '/10' : 'N/A'; ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $submission['assessed_status'] === 'Yes' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $submission['assessed_status'] === 'Yes' ? 'Assessed' : 'Pending'; ?>
                                                        </span>
                                                    </td>
                                                <?php elseif ($filter_doc_type === 'T3'): ?>
                                                    <td><?php echo date('M d, Y', strtotime($submission['upload_date'])); ?></td>
                                                    <td><?php echo $submission['assessed_status'] === 'Yes' ? htmlspecialchars($submission['total_t3_marks']) . '/10' : 'N/A'; ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $submission['assessed_status'] === 'Yes' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $submission['assessed_status'] === 'Yes' ? 'Assessed' : 'Pending'; ?>
                                                        </span>
                                                    </td>
                                                <?php else: ?>
                                                    <td><?php echo $submission['t2_upload_date'] ? date('M d, Y', strtotime($submission['t2_upload_date'])) : 'N/A'; ?></td>
                                                    <td><?php echo $submission['t2_assessed'] === 'Yes' ? htmlspecialchars($submission['total_t2_marks']) . '/10' : 'N/A'; ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $submission['t2_assessed'] === 'Yes' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $submission['t2_assessed'] === 'Yes' ? 'Assessed' : 'Pending'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $submission['t3_upload_date'] ? date('M d, Y', strtotime($submission['t3_upload_date'])) : 'N/A'; ?></td>
                                                    <td><?php echo $submission['t3_assessed'] === 'Yes' ? htmlspecialchars($submission['total_t3_marks']) . '/10' : 'N/A'; ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $submission['t3_assessed'] === 'Yes' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $submission['t3_assessed'] === 'Yes' ? 'Assessed' : 'Pending'; ?>
                                                        </span>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <?php if ($filter_doc_type): ?>
                                                        <a href="../view_submission.php?submission_id=<?php echo $submission['submission_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if ($submission['assessed_status'] !== 'Yes'): ?>
                                                            <button class="btn btn-sm btn-outline-success assess-btn" 
                                                                    data-submission-id="<?php echo $submission['submission_id']; ?>" 
                                                                    data-type="<?php echo $filter_doc_type; ?>">
                                                                <i class="fas fa-check"></i> Assess
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if ($submission['t2_submission_id']): ?>
                                                            <a href="../view_submission.php?submission_id=<?php echo $submission['t2_submission_id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary me-2">
                                                                <i class="fas fa-eye"></i> T2
                                                            </a>
                                                            <?php if ($submission['t2_assessed'] !== 'Yes'): ?>
                                                                <button class="btn btn-sm btn-outline-success assess-btn" 
                                                                        data-submission-id="<?php echo $submission['t2_submission_id']; ?>" 
                                                                        data-type="T2">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <?php if ($submission['t3_submission_id']): ?>
                                                            <a href="../view_submission.php?submission_id=<?php echo $submission['t3_submission_id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary me-2">
                                                                <i class="fas fa-eye"></i> T3
                                                            </a>
                                                            <?php if ($submission['t3_assessed'] !== 'Yes'): ?>
                                                                <button class="btn btn-sm btn-outline-success assess-btn" 
                                                                        data-submission-id="<?php echo $submission['t3_submission_id']; ?>" 
                                                                        data-type="T3">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo $filter_doc_type ? '7' : '10'; ?>" class="text-center">No submissions found matching the filters.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assessment Modal -->
<div class="modal fade" id="assessModal" tabindex="-1" aria-labelledby="assessModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assessModalLabel">Assess Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assessForm" method="post" action="../assess_submission.php">
                <div class="modal-body">
                    <input type="hidden" name="submission_id" id="submission_id">
                    <input type="hidden" name="document_type" id="document_type">
                    <div class="mb-3">
                        <label for="mark1" class="form-label">Criterion 1 (Max 5)</label>
                        <input type="number" class="form-control" id="mark1" name="mark1" min="0" max="5" required>
                    </div>
                    <div class="mb-3">
                        <label for="mark2" class="form-label">Criterion 2 (Max 5)</label>
                        <input type="number" class="form-control" id="mark2" name="mark2" min="0" max="5" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Assessment</button>
                </div>
            </form>
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

.table {
    margin-bottom: 0;
}

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.assess-btn {
    transition: all 0.3s;
}

.assess-btn:hover {
    transform: translateY(-2px);
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

    // Handle assess button clicks
    const assessButtons = document.querySelectorAll('.assess-btn');
    assessButtons.forEach(button => {
        button.addEventListener('click', function() {
            const submissionId = this.getAttribute('data-submission-id');
            const documentType = this.getAttribute('data-type');
            
            document.getElementById('submission_id').value = submissionId;
            document.getElementById('document_type').value = documentType;
            document.getElementById('assessModalLabel').textContent = `Assess ${documentType} Submission`;

            const modal = new bootstrap.Modal(document.getElementById('assessModal'));
            modal.show();
        });
    });

    // Form validation for assessment
    const assessForm = document.getElementById('assessForm');
    assessForm.addEventListener('submit', function(e) {
        const mark1 = document.getElementById('mark1').value;
        const mark2 = document.getElementById('mark2').value;
        
        if (mark1 < 0 || mark1 > 5 || mark2 < 0 || mark2 > 5) {
            e.preventDefault();
            alert('Marks must be between 0 and 5');
        }
    });
});
</script>

<?php
$stmt->close();
mysqli_close($conn);
include '../includes/footer.php';
?>
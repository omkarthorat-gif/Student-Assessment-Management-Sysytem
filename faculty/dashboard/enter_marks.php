<?php
ob_start();
require_once '../includes/session_check.php';
require_once '../../config.php';

$faculty_id = $_SESSION['faculty_id'];

$subjects_query = "SELECT DISTINCT s.subject_id, s.subject_name, s.year, s.semester
                  FROM faculty_subject_assign fsa
                  JOIN subjects s ON fsa.subject_id = s.subject_id
                  WHERE fsa.faculty_id = ?
                  ORDER BY s.year, s.semester, s.subject_name";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("s", $faculty_id);
$stmt->execute();
$subjects_result = $stmt->get_result();

$selected_subject_id = $_POST['subject_id'] ?? $_GET['subject_id'] ?? '';
$selected_section = $_POST['section_name'] ?? $_GET['section_name'] ?? '';
$selected_doc_type = $_POST['document_type'] ?? $_GET['document_type'] ?? 'All';
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$students_data = [];
$subject_details = [];
$is_finalized = false;

if ($selected_subject_id && $selected_section) {
    $subject_query = "SELECT year, semester FROM subjects WHERE subject_id = ?";
    $stmt = $conn->prepare($subject_query);
    $stmt->bind_param("s", $selected_subject_id);
    $stmt->execute();
    $subject_details = $stmt->get_result()->fetch_assoc();

    $finalize_check_query = "SELECT COUNT(*) as total, SUM(CASE WHEN m.assessed_status = 'Yes' THEN 1 ELSE 0 END) as finalized
                            FROM t2_t3_submissions sub
                            LEFT JOIN marks m ON sub.submission_id = m.submission_id
                            WHERE sub.subject_id = ? AND sub.year = ? AND sub.semester = ?
                            AND EXISTS (SELECT 1 FROM students s WHERE s.reg_no = sub.reg_no AND s.section_name = ?)
                            " . ($selected_doc_type !== 'All' ? "AND sub.document_type = ?" : "");
    $stmt = $conn->prepare($finalize_check_query);
    if ($selected_doc_type !== 'All') {
        $stmt->bind_param("siiss", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_section, $selected_doc_type);
    } else {
        $stmt->bind_param("siis", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_section);
    }
    $stmt->execute();
    $finalize_result = $stmt->get_result()->fetch_assoc();
    $is_finalized = ($finalize_result['total'] > 0 && $finalize_result['total'] === $finalize_result['finalized']);

    $students_query = "SELECT s.reg_no, s.name, sub.submission_id, sub.document_type, 
                             sub.file_path, m.t2_mark1, m.t2_mark2, m.t3_mark1, m.t3_mark2,
                             m.assessed_status
                      FROM students s
                      LEFT JOIN t2_t3_submissions sub ON s.reg_no = sub.reg_no 
                         AND sub.subject_id = ? 
                         AND sub.year = ? 
                         AND sub.semester = ?
                         " . ($selected_doc_type !== 'All' ? "AND sub.document_type = ?" : "") . "
                      LEFT JOIN marks m ON sub.submission_id = m.submission_id
                      WHERE s.section_name = ? 
                      AND s.dept_id = (SELECT dept_id FROM faculty WHERE faculty_id = ?)
                      ORDER BY s.reg_no";
    $stmt = $conn->prepare($students_query);
    if ($selected_doc_type !== 'All') {
        $stmt->bind_param("siisss", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_doc_type, $selected_section, $faculty_id);
    } else {
        $stmt->bind_param("siiss", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_section, $faculty_id);
    }
    $stmt->execute();
    $students_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_marks']) && !$is_finalized) {
    $conn->begin_transaction();
    try {
        $insert_missing_query = "INSERT INTO marks (submission_id, t2_mark1, t2_mark2, t3_mark1, t3_mark2)
                                SELECT sub.submission_id, NULL, NULL, NULL, NULL
                                FROM t2_t3_submissions sub
                                LEFT JOIN marks m ON sub.submission_id = m.submission_id
                                WHERE sub.subject_id = ? AND sub.year = ? AND sub.semester = ?
                                AND m.submission_id IS NULL
                                AND EXISTS (SELECT 1 FROM students s WHERE s.reg_no = sub.reg_no AND s.section_name = ?)
                                " . ($selected_doc_type !== 'All' ? "AND sub.document_type = ?" : "");
        $stmt = $conn->prepare($insert_missing_query);
        if ($selected_doc_type !== 'All') {
            $stmt->bind_param("siiss", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_section, $selected_doc_type);
        } else {
            $stmt->bind_param("siis", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_section);
        }
        $stmt->execute();

        $finalize_query = "UPDATE marks m
                          JOIN t2_t3_submissions sub ON m.submission_id = sub.submission_id
                          SET m.assessed_status = 'Yes'
                          WHERE sub.subject_id = ? AND sub.year = ? AND sub.semester = ?
                          AND EXISTS (
                              SELECT 1 FROM students s 
                              WHERE s.reg_no = sub.reg_no 
                              AND s.section_name = ?
                          )" . ($selected_doc_type !== 'All' ? " AND sub.document_type = ?" : "");
        $stmt = $conn->prepare($finalize_query);
        if ($selected_doc_type !== 'All') {
            $stmt->bind_param("siiss", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_section, $selected_doc_type);
        } else {
            $stmt->bind_param("siis", $selected_subject_id, $subject_details['year'], $subject_details['semester'], $selected_section);
        }
        $stmt->execute();

        $conn->commit();
        $message = "Marks finalized successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <div class="row mb-4" style="margin-top: 30px;">
            <div class="col-12">
                <div class="hero-card card border-0 shadow-sm position-relative overflow-hidden bg-gradient-primary">
                    <div class="bubble-container position-absolute w-100 h-100"></div>
                    <div class="card-body p-4 position-relative">
                        <h1 class="display-5 fw-bold mb-2 text-white">Enter Marks</h1>
                        <p class="lead text-light mb-3">Manage student marks for assessments</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-3">
                <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="section-title"><i class="fas fa-book me-2"></i>Select Subject and Section</h4>
                        <form method="POST" action="">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- Select Subject --</option>
                                        <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                                            <option value="<?php echo $subject['subject_id']; ?>"
                                                <?php echo $selected_subject_id === $subject['subject_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars("{$subject['subject_name']} ({$subject['subject_id']})"); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <?php if ($selected_subject_id): ?>
                                    <div class="col-md-4 mb-3">
                                        <select name="section_name" class="form-select" onchange="this.form.submit()">
                                            <option value="">-- Select Section --</option>
                                            <?php
                                            $sections_query = "SELECT DISTINCT section_name 
                                                             FROM faculty_subject_assign 
                                                             WHERE faculty_id = ? AND subject_id = ?";
                                            $stmt = $conn->prepare($sections_query);
                                            $stmt->bind_param("ss", $faculty_id, $selected_subject_id);
                                            $stmt->execute();
                                            $sections = $stmt->get_result();
                                            while ($section = $sections->fetch_assoc()): ?>
                                                <option value="<?php echo $section['section_name']; ?>"
                                                    <?php echo $selected_section === $section['section_name'] ? 'selected' : ''; ?>>
                                                    Section <?php echo $section['section_name']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <select name="document_type" class="form-select" onchange="this.form.submit()">
                                            <option value="All" <?php echo $selected_doc_type === 'All' ? 'selected' : ''; ?>>All Documents</option>
                                            <option value="T2" <?php echo $selected_doc_type === 'T2' ? 'selected' : ''; ?>>T2 Documents</option>
                                            <option value="T3" <?php echo $selected_doc_type === 'T3' ? 'selected' : ''; ?>>T3 Documents</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selected_subject_id && $selected_section && $students_data): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card subject-card border-0 shadow-sm">
                        <div class="card-body">
                            <h4 class="section-title"><i class="fas fa-file-alt me-2"></i>Enter Marks (Year <?php echo $subject_details['year']; ?>, Semester <?php echo $subject_details['semester']; ?>, <?php echo $selected_doc_type; ?>)</h4>
                            <form id="marks-form" method="POST" action="save_marks.php">
                                <input type="hidden" name="subject_id" value="<?php echo $selected_subject_id; ?>">
                                <input type="hidden" name="section_name" value="<?php echo $selected_section; ?>">
                                <input type="hidden" name="document_type" value="<?php echo $selected_doc_type; ?>">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Reg No</th>
                                                <th>Name</th>
                                                <th>Doc Type</th>
                                                <th>Submission</th>
                                                <th>T2 Mark1</th>
                                                <th>T2 Mark2</th>
                                                <th>T3 Mark1</th>
                                                <th>T3 Mark2</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students_data as $student): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['reg_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                    <td><?php echo $student['document_type'] ?? 'N/A'; ?></td>
                                                    <td>
                                                        <?php if ($student['submission_id']): ?>
                                                            <a href="../view_submission.php?submission_id=<?php echo $student['submission_id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">View <?php echo $student['document_type']; ?></a>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Not Submitted</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!$is_finalized && $student['submission_id'] && $student['document_type'] === 'T2'): ?>
                                                            <input type="number" name="marks[<?php echo $student['submission_id']; ?>][t2_mark1]" 
                                                                   value="<?php echo $student['t2_mark1'] !== null ? $student['t2_mark1'] : ''; ?>" 
                                                                   min="0" max="5" class="form-control">
                                                        <?php else: ?>
                                                            <?php echo $student['t2_mark1'] !== null ? $student['t2_mark1'] : '-'; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!$is_finalized && $student['submission_id'] && $student['document_type'] === 'T2'): ?>
                                                            <input type="number" name="marks[<?php echo $student['submission_id']; ?>][t2_mark2]" 
                                                                   value="<?php echo $student['t2_mark2'] !== null ? $student['t2_mark2'] : ''; ?>" 
                                                                   min="0" max="5" class="form-control">
                                                        <?php else: ?>
                                                            <?php echo $student['t2_mark2'] !== null ? $student['t2_mark2'] : '-'; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!$is_finalized && $student['submission_id'] && $student['document_type'] === 'T3'): ?>
                                                            <input type="number" name="marks[<?php echo $student['submission_id']; ?>][t3_mark1]" 
                                                                   value="<?php echo $student['t3_mark1'] !== null ? $student['t3_mark1'] : ''; ?>" 
                                                                   min="0" max="5" class="form-control">
                                                        <?php else: ?>
                                                            <?php echo $student['t3_mark1'] !== null ? $student['t3_mark1'] : '-'; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!$is_finalized && $student['submission_id'] && $student['document_type'] === 'T3'): ?>
                                                            <input type="number" name="marks[<?php echo $student['submission_id']; ?>][t3_mark2]" 
                                                                   value="<?php echo $student['t3_mark2'] !== null ? $student['t3_mark2'] : ''; ?>" 
                                                                   min="0" max="5" class="form-control">
                                                        <?php else: ?>
                                                            <?php echo $student['t3_mark2'] !== null ? $student['t3_mark2'] : '-'; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $student['assessed_status'] === 'Yes' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $student['assessed_status'] === 'Yes' ? 'Finalized' : 'Pending'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!$is_finalized): ?>
                                    <div class="mt-3">
                                        <button type="submit" id="save-marks-btn" class="btn btn-primary me-2">
                                            <i class="fas fa-save me-1"></i> Save Marks
                                        </button>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="subject_id" value="<?php echo $selected_subject_id; ?>">
                                            <input type="hidden" name="section_name" value="<?php echo $selected_section; ?>">
                                            <input type="hidden" name="document_type" value="<?php echo $selected_doc_type; ?>">
                                            <button type="submit" name="finalize_marks" class="btn btn-success"
                                                    onclick="return confirm('Are you sure you want to finalize all marks? This action cannot be undone and will finalize current marks only, including unsubmitted marks as zero.');">
                                                <i class="fas fa-check me-1"></i> Finalize All Marks
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-3">
                                        <span class="badge bg-success">All Marks Finalized</span>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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

.form-control[type="number"] {
    width: 80px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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

    const inputs = document.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value !== '' && (this.value < 0 || this.value > 5)) {
                alert('Marks must be between 0 and 5');
                this.value = '';
            }
        });
    });
});
</script>

<?php
$stmt->close();
mysqli_close($conn);
include '../includes/footer.php';
ob_end_flush();
?>
<?php
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marks'])) {
    $marks = $_POST['marks'];
    $subject_id = $_POST['subject_id'];
    $section_name = $_POST['section_name'];
    $document_type = $_POST['document_type'];

    $conn->begin_transaction();

    try {
        foreach ($marks as $submission_id => $mark_data) {
            $t2_mark1 = isset($mark_data['t2_mark1']) && $mark_data['t2_mark1'] !== '' ? (int)$mark_data['t2_mark1'] : null;
            $t2_mark2 = isset($mark_data['t2_mark2']) && $mark_data['t2_mark2'] !== '' ? (int)$mark_data['t2_mark2'] : null;
            $t3_mark1 = isset($mark_data['t3_mark1']) && $mark_data['t3_mark1'] !== '' ? (int)$mark_data['t3_mark1'] : null;
            $t3_mark2 = isset($mark_data['t3_mark2']) && $mark_data['t3_mark2'] !== '' ? (int)$mark_data['t3_mark2'] : null;

            if (($t2_mark1 !== null && ($t2_mark1 < 0 || $t2_mark1 > 5)) ||
                ($t2_mark2 !== null && ($t2_mark2 < 0 || $t2_mark2 > 5)) ||
                ($t3_mark1 !== null && ($t3_mark1 < 0 || $t3_mark1 > 5)) ||
                ($t3_mark2 !== null && ($t3_mark2 < 0 || $t3_mark2 > 5))) {
                throw new Exception('Marks must be between 0 and 5.');
            }

            $check_query = "SELECT assessed_status FROM marks WHERE submission_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $submission_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['assessed_status'] === 'Yes') {
                    continue; // Skip if already finalized
                }
                $update_query = "UPDATE marks SET 
                                t2_mark1 = ?, t2_mark2 = ?, t3_mark1 = ?, t3_mark2 = ?
                                WHERE submission_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("iiiis", $t2_mark1, $t2_mark2, $t3_mark1, $t3_mark2, $submission_id);
            } else {
                $insert_query = "INSERT INTO marks (submission_id, t2_mark1, t2_mark2, t3_mark1, t3_mark2) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iiiii", $submission_id, $t2_mark1, $t2_mark2, $t3_mark1, $t3_mark2);
            }

            if (!$stmt->execute()) {
                throw new Exception('Database update failed.');
            }
        }

        $conn->commit();
        $message = "Marks saved successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $message = $e->getMessage();
    }
} else {
    $message = "No marks entered";
}

header("Location: enter_marks.php?subject_id=$subject_id§ion_name=$section_name&document_type=$document_type&message=" . urlencode($message));
$conn->close();
exit;
?>
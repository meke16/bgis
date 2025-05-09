<?php
session_start();
require_once '../connect.php';

// Check authentication and role
if (!isset($_SESSION['user']['authenticated']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}


$teacher_id = $_SESSION['user']['id'];
$current_year = date('Y') . '-' . (date('Y') + 1);

$assigned_classes = [];
$stmt = $conn->prepare("SELECT 
                        ta.id AS assignment_id,
                        s.id AS subject_id,
                        s.name AS subject_name,
                        ta.grade,
                        ta.section,
                        ta.academic_year,
                        COUNT(st.id) AS student_count
                    FROM teacher_assignments ta
                    JOIN subjects s ON ta.subject_id = s.id
                    LEFT JOIN students st ON ta.grade = st.grade AND ta.section = st.section
                    WHERE ta.teacher_id = ?
                    GROUP BY ta.id, s.id, s.name, ta.grade, ta.section, ta.academic_year
                    ORDER BY ta.academic_year DESC, ta.grade, ta.section");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $assigned_classes[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = $_POST['assignment_id'];
    $mark_type = $_POST['mark_type'];
    $semester_id = $_POST['semester'];
    $student_marks = $_POST['marks'];

    // Verify the teacher is assigned to this class
    $valid_assignment = false;
    foreach ($assigned_classes as $class) {
        if ($class['assignment_id'] == $assignment_id) {
            $valid_assignment = true;
            $subject_id = $class['subject_id'];
            break;
        }
    }

    if ($valid_assignment) {
        $conn->begin_transaction();

try {
    // Prepare a single statement for insert/update
    $stmt = $conn->prepare("INSERT INTO student_marks 
                          (student_id, subject_id, semester_id, mark, mark_type, recorded_by)
                          VALUES (?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          mark = VALUES(mark),
                          recorded_by = VALUES(recorded_by),
                          recorded_at = NOW()");

    foreach ($student_marks as $student_id => $mark) {
        if (!empty($mark) || $mark === '0') {
            $mark = floatval($mark);
            
            // Debug output
            error_log("Processing: Student=$student_id, Subject=$subject_id, Semester=$semester_id, Type=$mark_type, Mark=$mark");
            
            $stmt->bind_param(
                "iiidsi",
                $student_id,
                $subject_id,
                $semester_id,
                $mark,
                $mark_type,
                $teacher_id
            );
            $stmt->execute();
            
            // Debug output
            error_log("Insert ID: ".$conn->insert_id." | Affected rows: ".$conn->affected_rows);
        }
    }

    $conn->commit();
    $_SESSION['success'] = "Marks updated successfully!";
    header("Location: marks.php?assignment=".$assignment_id."&semester=".$semester_id."&type=".urlencode($mark_type));
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error recording marks: ".$e->getMessage();
    error_log("Database error: ".$e->getMessage());
    error_log("Failed values - Student: $student_id, Subject: $subject_id, Semester: $semester_id, Type: $mark_type");
}
    }
}
// Get students for selected class
$students = [];
$selected_class = null;
$semester_id = null;
$mark_type = null;

if (isset($_GET['assignment'])) {
    $assignment_id = $_GET['assignment'];
    $semester_id = $_GET['semester'] ?? null;
    $mark_type = $_GET['type'] ?? null;
    
    foreach ($assigned_classes as $class) {
        if ($class['assignment_id'] == $assignment_id) {
            $selected_class = $class;
            
            // First get all students in this class
            $stmt = $conn->prepare("SELECT id, name, sex FROM students 
                                  WHERE grade = ? AND section = ?
                                  ORDER BY name");
            $stmt->bind_param("is", $class['grade'], $class['section']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($student = $result->fetch_assoc()) {
                $students[$student['id']] = $student;
                $students[$student['id']]['mark'] = null; // Initialize mark as null
            }
            
            // Then get existing marks if semester and type are selected
            if ($semester_id && $mark_type) {
                $stmt = $conn->prepare("SELECT student_id, mark FROM student_marks
                                      WHERE subject_id = ? AND semester_id = ? AND mark_type = ?");
                $stmt->bind_param("iis", $class['subject_id'], $semester_id, $mark_type);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($mark_row = $result->fetch_assoc()) {
                    if (isset($students[$mark_row['student_id']])) {
                        $students[$mark_row['student_id']]['mark'] = $mark_row['mark'];
                    }
                }
            }
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Marks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .table-responsive { overflow-x: auto; }
        .mark-input { max-width: 100px; }
        .assignment-card { cursor: pointer; transition: all 0.3s; }
        .assignment-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .assignment-card.active { border-left: 4px solid #3498db; background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="mb-4">Record Student Marks</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-book me-2"></i> My Classes
                            </div>
                            <div class="card-body">
                                <?php if (count($assigned_classes) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($assigned_classes as $class): ?>
                                            <a href="marks.php?assignment=<?= $class['assignment_id'] ?>" 
                                               class="list-group-item list-group-item-action assignment-card <?= ($selected_class && $selected_class['assignment_id'] == $class['assignment_id']) ? 'active' : '' ?>">
                                                <h6><?= htmlspecialchars($class['subject_name']) ?></h6>
                                                <small class="text-muted">Grade <?= $class['grade'] ?> - Section <?= $class['section'] ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">You have no assigned classes for this academic year.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <?php if ($selected_class): ?>
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <i class="bi bi-people me-2"></i> 
                                    <?= htmlspecialchars($selected_class['subject_name']) ?> - 
                                    Grade <?= $selected_class['grade'] ?> - 
                                    Section <?= $selected_class['section'] ?>
                                    <?php if ($mark_type && $semester_id): ?>
                                        <span class="float-end">
                                            <?= htmlspecialchars($mark_type) ?> - 
                                            Semester <?= $semester_id ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                <form id="myForm" method="GET" class="mb-4">
    <input type="hidden" name="assignment" value="<?= $selected_class['assignment_id'] ?>">

    <div class="row">
        <div class="col-md-5">
            <label class="form-label">Mark Type</label>
            <select id="markTypeSelect" name="type" class="form-select" required>
                <option value="">Select Mark Type</option>
                <option value="Midterm Exam" <?= $mark_type == 'Midterm Exam' ? 'selected' : '' ?>>Midterm Exam</option>
                <option value="Final Exam" <?= $mark_type == 'Final Exam' ? 'selected' : '' ?>>Final Exam</option>
                <option value="Quiz" <?= $mark_type == 'Quiz' ? 'selected' : '' ?>>Quiz</option>
                <option value="Assignment" <?= $mark_type == 'Assignment' ? 'selected' : '' ?>>Assignment</option>
                <option value="Project" <?= $mark_type == 'Project' ? 'selected' : '' ?>>Project</option>
                <option value="Other" <?= $mark_type == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>

        <div class="col-md-5">
            <label class="form-label">Semester</label>
            <select id="semesterSelect" name="semester" class="form-select" required>
                <option value="">Select Semester</option>
                <?php
                $semesters = $conn->query("SELECT * FROM semesters");
                while ($sem = $semesters->fetch_assoc()) {
                    $selected = ($sem['id'] == $semester_id) ? 'selected' : '';
                    echo "<option value='{$sem['id']}' $selected>{$sem['name']} </option>";
                }
                ?>
            </select>
        </div>
    </div>
</form>

                                    
                                    <?php if ($mark_type && $semester_id): ?>
                                        <form method="POST">
                                            <input type="hidden" name="assignment_id" value="<?= $selected_class['assignment_id'] ?>">
                                            <input type="hidden" name="mark_type" value="<?= $mark_type ?>">
                                            <input type="hidden" name="semester" value="<?= $semester_id ?>">
                                            <?php endif; ?>

                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Name</th>
                                                            <th>Mark</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php $num = 0; foreach ($students as $student): $num++; ?>
                                                    <tr>
                                                        <td><?= $num ?></td>
                                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                                        <td>
                                                            <input type="number" 
                                                                name="marks[<?= $student['id'] ?>]" 
                                                                class="form-control mark-input" 
                                                                min="0" 
                                                                max="100"
                                                                step="0.01"
                                                                value="<?= $student['mark'] !== null ? $student['mark'] : '' ?>">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i> Save Marks
                                            </button>
                                        </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> Please select a class to record marks.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus first mark input when marks are loaded
        document.addEventListener('DOMContentLoaded', function() {
            const markInputs = document.querySelectorAll('.mark-input');
            if (markInputs.length > 0) {
                markInputs[0].focus();
            }
        });
    </script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // Trigger form submission when the 'type' or 'semester' dropdown changes
    $('#markTypeSelect, #semesterSelect').on('change', function () {
        // Serialize the form data
        console.log('Dropdown changed');
        var formData = $('#myForm').serialize();
        console.log(formData);


        $.ajax({
    url: '', // The form will submit to itself (current page)
    type: 'POST', // Change to POST
    data: formData, // Send the serialized form data
    success: function (response) {
        console.log('Form submitted successfully');
        console.log(response); // If you want to inspect the response, you can log it here
    },
    error: function (xhr, status, error) {
        console.error('Error in submitting the form: ' + error);
    }
});

    });
});
</script>
</body>
</html>
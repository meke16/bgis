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

// Get teacher's assigned classes with student counts
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

// Get all available subjects for assignment form
$subjects = [];
$result = $conn->query("SELECT id, name FROM subjects ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .class-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .class-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .badge-year {
            background-color: #6c757d;
        }
        .student-count {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>My Classes</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                        <i class="bi bi-plus-circle me-2"></i>New Assignment
                    </button>
                </div>
                
                <?php if (count($assigned_classes) > 0): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($assigned_classes as $class): ?>
                            <div class="col">
                                <div class="card class-card h-100">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($class['subject_name']) ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-secondary">Grade <?= $class['grade'] ?></span>
                                            <span class="badge bg-info text-dark">Section <?= $class['section'] ?></span>
                                        </div>
                                        <div class="student-count mb-3">
                                            <i class="bi bi-people-fill me-2"></i>
                                            <?= $class['student_count'] ?> students
                                        </div>
                                        <span class="badge badge-year">
                                            <?= $class['academic_year'] ?>
                                        </span>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between">
                                            <a href="marks.php?assignment=<?= $class['assignment_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-journal-check me-1"></i>Record Marks
                                            </a>
                                            <a href="class_details.php?assignment=<?= $class['assignment_id'] ?>" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye me-1"></i>View Class
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> You don't have any class assignments yet.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- New Assignment Modal -->
    <div class="modal fade" id="newAssignmentModal" tabindex="-1" aria-labelledby="newAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newAssignmentModalLabel">New Class Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="add_assignment.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="grade" class="form-label">Grade</label>
                                <select class="form-select" id="grade" name="grade" required>
                                    <option value="">Select Grade</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>">Grade <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="section" class="form-label">Section</label>
                                <input type="text" class="form-control" id="section" name="section" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <select class="form-select" id="academic_year" name="academic_year" required>
                                <option value="">Select Year</option>
                                <?php 
                                $current_year = date('Y');
                                for ($i = -1; $i <= 1; $i++): 
                                    $year = $current_year + $i;
                                ?>
                                    <option value="<?= "$year-" . ($year + 1) ?>" <?= ($i == 0) ? 'selected' : '' ?>>
                                        <?= "$year-" . ($year + 1) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
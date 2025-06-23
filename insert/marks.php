<?php
session_start();
include 'session.php';
require_once '../connect.php';

// Check authentication and role
if (!isset($_SESSION['user']['authenticated']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: index.php");
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
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Marks | Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --text-light: #334155;
            --bg-dark: #0f172a;
            --border-dark: #334155;


        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-dark);
            color: var(--dark-color);
            transition: all 0.3s ease;
        }
        
        /* .dark-mode {
            background-color:var(--bg-dark);
            color: var(--text-dark);
            border-color: var(--border-dark);
        } */
        
        .dark-mode .card,
        .dark-mode .table,
        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: #16213e;
            color: #f8f9fa;
            border-color: #2d3748;
        }
        
        .dark-mode .card-header,
        .dark-mode .table th {
            background-color: #0f3460 !important;
            color: white !important;
        }
        
        .dark-mode .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: rgba(15, 52, 96, 0.5);
            color: #f8f9fa;
        }
        
        .dark-mode .table-hover>tbody>tr:hover>* {
            background-color: rgba(15, 52, 96, 0.7) !important;
            color: white !important;
        }
        
        .dark-mode .alert-info {
            background-color: #1a3e72;
            border-color: #2d4b7a;
            color: #e2e8f0;
        }
        
        .assignment-card {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 3px solid var(--primary-color);
        }
        
        .assignment-card.active {
            background-color: rgba(78, 115, 223, 0.1);
            border-left: 3px solid var(--primary-color);
        }
        
        .mark-input {
            width: 100px;
            transition: all 0.2s;
        }
        
        .mark-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            border-color: var(--primary-color);
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
        
        .btn-toggle {
            padding: 0.25rem 0.5rem;
            font-weight: 600;
            color: var(--bs-emphasis-color);
            background-color: transparent;
            border: 0;
        }
        
        .btn-toggle:hover,
        .btn-toggle:focus {
            color: var(--primary-color);
        }
        
        .btn-toggle::before {
            width: 1.25em;
            line-height: 0;
            content: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba%280,0,0,.5%29' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 14l6-6-6-6'/%3e%3c/svg%3e");
            transition: transform 0.35s ease;
            transform-origin: 0.5em 50%;
        }
        
        .btn-toggle[aria-expanded="true"]::before {
            transform: rotate(90deg);
        }
        
        .dark-mode .btn-toggle::before {
            content: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba%28255,255,255,.5%29' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 14l6-6-6-6'/%3e%3c/svg%3e");
        }
        
        @media (max-width: 768px) {
            .mark-input {
                width: 100px;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .form-select, .form-control {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .col-md-4, .col-md-8 {
                padding-left: 5px;
                padding-right: 5px;
            }
            
            .mark-input {
                width: 100px;
                padding: 0.25rem 0.5rem;
                font-size: 0.85rem;
            }
            
            .table td, .table th {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <a href="td.php" class="btn btn-outline-primary btn-sm me-2">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        Record Student Marks
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button id="themeToggle" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-moon-stars"></i> Toggle Theme
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-md-4 col-lg-3">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-book me-2"></i> My Classes</span>
                                <span class="badge bg-light text-dark"><?= count($assigned_classes) ?></span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (count($assigned_classes) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($assigned_classes as $class): ?>
                                            <a href="marks.php?assignment=<?= $class['assignment_id'] ?>" 
                                               class="list-group-item list-group-item-action assignment-card <?= ($selected_class && $selected_class['assignment_id'] == $class['assignment_id']) ? 'active' : '' ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($class['subject_name']) ?></h6>
                                                    <small class="text-muted"><?= $class['student_count'] ?> students</small>
                                                </div>
                                                <small class="text-muted">Grade <?= $class['grade'] ?> - Section <?= $class['section'] ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info m-3">
                                        <i class="bi bi-info-circle me-2"></i> You have no assigned classes for this academic year.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8 col-lg-9">
                        <?php if ($selected_class): ?>
                            <div class="card shadow-sm">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-people-fill me-2"></i>
                                        <strong><?= htmlspecialchars($selected_class['subject_name']) ?></strong> - 
                                        Grade <?= $selected_class['grade'] ?> - 
                                        Section <?= $selected_class['section'] ?>
                                    </div>
                                    <?php if ($mark_type && $semester_id): ?>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($mark_type) ?> - 
                                            <?php $semesters = $conn->query("SELECT * FROM semesters"); 
                                            while($sem = $semesters->fetch_assoc()) {
                                                if($sem['id'] == $semester_id) {
                                                    echo $sem['name'];
                                                }
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $teacher_id = $_SESSION['user']['id'];
                                    $mark_type = $_GET['type'] ?? '';
                                    $semester_id = $_GET['semester'] ?? '';

                                    // Fetch teacher's mark types
                                    $mark_types_result = mysqli_query($conn, "SELECT * FROM mark_types WHERE teacher_id = $teacher_id");
                                    ?>

                                    <form method="GET" class="mb-4">
                                        <input type="hidden" name="assignment" value="<?= $selected_class['assignment_id'] ?>">
                                        <div class="row g-3">

                                            <!-- Mark Type -->
                                            <div class="col-md-5">
                                                <label class="form-label">Mark Type</label>
                                                <select name="type" class="form-select" required onchange="this.form.submit()">
                                                    <option value="">Select Mark Type</option>
                                                    <?php while ($row = mysqli_fetch_assoc($mark_types_result)): ?>
                                                        <option value="<?= htmlspecialchars($row['type_name']) ?>" <?= $mark_type == $row['type_name'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($row['type_name']) ?> (Max: <?= $row['max_mark'] ?>)
                                                        </option>
                                                    <?php endwhile; ?>
                                                    <option disabled>────────────</option>
                                                    <option  value="add_new" <?= $mark_type === 'add_new' ? 'selected' : '' ?>>+ Add New Mark Type</option>
                                                </select>

                                                <!-- Link if "add_new" is selected -->
                                                <?php if ($mark_type === 'add_new'): ?>
                                                    <div class="mt-2">
                                                        <a href="mark_type.php" class="btn btn-sm btn-outline-primary">Go Add New Mark Type</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Semester -->
                                            <div class="col-md-5">
                                                <label class="form-label">Semester</label>
                                                <select name="semester" class="form-select" required onchange="this.form.submit()">
                                                    <option value="">Select Semester</option>
                                                    <?php
                                                    $semesters = $conn->query("SELECT * FROM semesters");
                                                    while ($sem = $semesters->fetch_assoc()) {
                                                        $selected = ($sem['id'] == $semester_id) ? 'selected' : '';
                                                        echo "<option value='{$sem['id']}' $selected>{$sem['name']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- Refresh Button -->
                                            <div class="col-md-2 d-flex align-items-end">
                                                <?php if ($mark_type && $semester_id): ?>
                                                    <button type="button" class="btn btn-outline-info w-100" title="Refresh view" onclick="window.location.reload()">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </form>
                                    <?php if ($mark_type && $semester_id): ?>
                                        <form method="POST" id="marksForm">
                                            <input type="hidden" name="assignment_id" value="<?= $selected_class['assignment_id'] ?>">
                                            <input type="hidden" name="mark_type" value="<?= $mark_type ?>">
                                            <input type="hidden" name="semester" value="<?= $semester_id ?>">
                                            
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th width="2%">#</th>
                                                            <th width="35%">Student Name</th>
                                                            <th width="50%">Mark (0-100)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php $num = 0; foreach ($students as $id => $student): $num++; ?>
                                                        <tr>
                                                            <td><?= $num ?></td>
                                                            <td>
                                                                <?= htmlspecialchars($student['name']) ?>
                                                                <span class="badge bg-secondary ms-2"><?= $student['sex'] ?></span>
                                                            </td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="number" 
                                                                        name="marks[<?= $id ?>]" 
                                                                        class="form-control mark-input" 
                                                                        min="0" 
                                                                        max="<?= $row['max_mark'] ?>"
                                                                        step="0.01"
                                                                        value="<?= $student['mark'] !== null ? $student['mark'] : '' ?>"
                                                                        placeholder="Enter mark">
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mt-3">
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearAllMarks()">
                                                    <i class="bi bi-x-circle me-2"></i> Clear All
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save me-2"></i> Save Marks
                                                </button>
                                                                                                </button>
                                                <button style="position: absolute; top: 120px; left: -150px;" type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save me-2"></i> Save Marks
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info text-center py-4">
                                            <i class="bi bi-info-circle-fill me-2"></i> Please select both mark type and semester to view and record marks.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card shadow-sm">
                                <div class="card-body text-center py-5">
                                    <i class="bi bi-journal-text display-4 text-muted mb-3"></i>
                                    <h4 class="text-muted">Select a class to record marks</h4>
                                    <p class="text-muted">Choose from your assigned classes on the left to begin</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        const themeIcon = themeToggle.querySelector('i');
        
        // Check for saved theme preference or use preferred color scheme
        const savedTheme = localStorage.getItem('theme') || 
                         (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        // Apply the saved theme
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-sun';
                themeToggle.innerHTML = '<i class="bi bi-sun"></i> Light Mode';
            } else {
                themeIcon.className = 'bi bi-moon-stars';
                themeToggle.innerHTML = '<i class="bi bi-moon-stars"></i> Dark Mode';
            }
        }
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-focus first mark input when marks are loaded
        document.addEventListener('DOMContentLoaded', function() {
            const markInputs = document.querySelectorAll('.mark-input');
            if (markInputs.length > 0) {
                markInputs[0].focus();
            }
        });
        
        // Function to clear all mark inputs
        function clearAllMarks() {
            if (confirm('Are you sure you want to clear all marks in this form?')) {
                document.querySelectorAll('.mark-input').forEach(input => {
                    input.value = '';
                });
                document.querySelector('.mark-input').focus();
            }
        }
        
        // Add keyboard navigation for mark inputs
        document.addEventListener('keydown', function(e) {
            if (e.target.classList.contains('mark-input')) {
                const inputs = Array.from(document.querySelectorAll('.mark-input'));
                const currentIndex = inputs.indexOf(e.target);
                
                if (e.key === 'Enter' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (currentIndex > 0) {
                        inputs[currentIndex - 1].focus();
                    }
                }
            }
        });
        
        /* Form submission confirmation
        document.getElementById('marksForm')?.addEventListener('submit', function(e) {
            const filledInputs = Array.from(document.querySelectorAll('.mark-input')).filter(input => input.value.trim() !== '').length;
            
            if (filledInputs === 0) {
                e.preventDefault();
                alert('Please enter at least one mark before submitting.');
                return;
            }
            
            if (!confirm(`You are about to save marks for ${filledInputs} student(s). Continue?`)) {
                e.preventDefault();
            }
        }); */
    </script>
</body>
</html>
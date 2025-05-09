<?php
include ("db_connect.php");
include ("session.php");

// Check if PDO is available
if (!extension_loaded('pdo')) {
    die('PDO extension is not enabled. Please enable it in your php.ini file.');
}

class MarksReport {
    private $pdo;
    private $markRanges = [
        '65-70' => ['min' => 65, 'max' => 70],
        '70-75' => ['min' => 70, 'max' => 75],
        '75-80' => ['min' => 75, 'max' => 80],
        '80-85' => ['min' => 80, 'max' => 85],
        '85-90' => ['min' => 85, 'max' => 90],
        '90-100' => ['min' => 90, 'max' => 100]
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getMarkRanges() {
        return $this->markRanges;
    }

    public function getSemesters() {
        $stmt = $this->pdo->query("SELECT id, name FROM semesters ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubjects() {
        $stmt = $this->pdo->query("SELECT id, name FROM subjects ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableGrades() {
        $stmt = $this->pdo->query("SELECT DISTINCT grade FROM students ORDER BY grade");
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getAvailableSections($grade = null) {
        $query = "SELECT DISTINCT section FROM students";
        $params = [];
        
        if ($grade !== null) {
            $query .= " WHERE grade = ?";
            $params[] = $grade;
        }
        
        $query .= " ORDER BY section";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getStudentsByMarkRange($subjectId, $semesterId, $min, $max, $grade, $section = null) {
        $this->validateInputs($subjectId, $semesterId, $min, $max, $grade);
        
        $query = "
            SELECT 
                s.id, 
                s.name, 
                s.sex, 
                s.grade,
                s.section,
                SUM(sm.mark) as total_mark,
                COUNT(sm.id) as exam_count
            FROM students s
            JOIN student_marks sm ON s.id = sm.student_id
            WHERE sm.subject_id = :subject_id 
              AND sm.semester_id = :semester_id
              AND s.grade = :grade
        ";
        
        $params = [
            ':subject_id' => $subjectId,
            ':semester_id' => $semesterId,
            ':grade' => $grade,
            ':min' => $min,
            ':max' => $max
        ];
        
        if ($section !== null && $section !== 'all') {
            $query .= " AND s.section = :section";
            $params[':section'] = $section;
        }
        
        $query .= "
            GROUP BY s.id, s.name, s.sex, s.grade, s.section
            HAVING total_mark BETWEEN :min AND :max
            ORDER BY total_mark DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubjectStatisticsByGender($semesterId, $grade, $section = null) {
        $this->validateSemester($semesterId);
        
        $subjects = $this->getSubjects();
        $stats = [];
        
        foreach ($subjects as $subject) {
            foreach ($this->markRanges as $rangeName => $range) {
                $query = "
                    SELECT 
                        s.sex,
                        COUNT(DISTINCT s.id) as count
                    FROM students s
                    JOIN (
                        SELECT 
                            student_id, 
                            SUM(mark) as total_mark
                        FROM student_marks
                        WHERE subject_id = :subject_id 
                          AND semester_id = :semester_id
                        GROUP BY student_id
                        HAVING total_mark BETWEEN :min AND :max
                    ) sm ON s.id = sm.student_id
                    WHERE s.grade = :grade
                ";
                
                $params = [
                    ':subject_id' => $subject['id'],
                    ':semester_id' => $semesterId,
                    ':min' => $range['min'],
                    ':max' => $range['max'],
                    ':grade' => $grade
                ];
                
                if ($section !== null && $section !== 'all') {
                    $query .= " AND s.section = :section";
                    $params[':section'] = $section;
                }
                
                $query .= " GROUP BY s.sex";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $maleCount = 0;
                $femaleCount = 0;
                
                foreach ($results as $row) {
                    if (strtolower($row['sex']) === 'male') {
                        $maleCount = (int)$row['count'];
                    } elseif (strtolower($row['sex']) === 'female') {
                        $femaleCount = (int)$row['count'];
                    }
                }
                
                $stats[$subject['id']][$rangeName] = [
                    'male' => $maleCount,
                    'female' => $femaleCount,
                    'total' => $maleCount + $femaleCount,
                    'subject_name' => $subject['name']
                ];
            }
        }
        
        return $stats;
    }

    private function validateInputs($subjectId, $semesterId, $min, $max, $grade) {
        // Validate subject exists
        $stmt = $this->pdo->prepare("SELECT id FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException("Invalid subject selected.");
        }
        
        // Validate semester exists
        $this->validateSemester($semesterId);
        
        // Validate grade exists
        $stmt = $this->pdo->prepare("SELECT 1 FROM students WHERE grade = ? LIMIT 1");
        $stmt->execute([$grade]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException("Invalid grade selected.");
        }
        
        if (!is_numeric($min) || !is_numeric($max) || $min < 0 || $max > 100 || $min > $max) {
            throw new InvalidArgumentException("Invalid mark range (must be 0-100).");
        }
    }
    
    private function validateSemester($semesterId) {
        $stmt = $this->pdo->prepare("SELECT id FROM semesters WHERE id = ?");
        $stmt->execute([$semesterId]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException("Invalid semester selected.");
        }
    }
}

// Create report instance
$report = new MarksReport($pdo);
$markRanges = $report->getMarkRanges();
$semesters = $report->getSemesters();
$subjects = $report->getSubjects();
$grades = $report->getAvailableGrades();
$sections = $report->getAvailableSections();

// Handle form submission
$results = [];
$genderStats = ['male' => 0, 'female' => 0];
$subjectStats = [];
$selectedSemester = $_POST['semester'] ?? ($semesters[0]['id'] ?? null);
$selectedGrade = $_POST['grade'] ?? null;
$selectedSection = $_POST['section'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['get_stats'])) {
            // Get statistics for all subjects
            $subjectStats = $report->getSubjectStatisticsByGender($selectedSemester, $selectedGrade, $selectedSection);
        } else if (isset($_POST['get_students'])) {
            // Get students in specific range
            $results = $report->getStudentsByMarkRange(
                $_POST['subject'],
                $_POST['semester'],
                (int)$_POST['min_mark'],
                (int)$_POST['max_mark'],
                $selectedGrade,
                $selectedSection
            );
            
            // Calculate gender statistics
            foreach ($results as $student) {
                if (strtolower($student['sex']) === 'male') {
                    $genderStats['male']++;
                } elseif (strtolower($student['sex']) === 'female') {
                    $genderStats['female']++;
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Update sections based on selected grade
if ($selectedGrade !== null) {
    $sections = $report->getAvailableSections($selectedGrade);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-header {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            text-align: center;
            padding: 15px;
        }
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stat-card .label {
            font-size: 1rem;
            color: #6c757d;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .nav-tabs {
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .range-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 10px;
            background-color: #e9ecef;
            color: #495057;
        }
        .bg-pink {
            background-color: #ff66b2;
            color: white;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .dashboard-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .dashboard-card:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .dashboard-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="dashboard-header text-center">
            <h1><i class="fas fa-chart-line"></i> Student Marks Analytics Dashboard</h1>
            <p class="lead">Comprehensive analysis of student performance across grades and sections</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!isset($_POST['get_stats']) && !isset($_POST['get_students'])): ?>
            <!-- Dashboard View -->
            <div class="row">
                <div class="col-md-12">
                    <div class="filter-section">
                        <h4><i class="fas fa-filter"></i> Filter Options</h4>
                        <form method="POST" id="dashboardForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="grade" class="form-label">Grade</label>
                                        <select class="form-select" name="grade" id="grade" required>
                                            <option value="">-- Select Grade --</option>
                                            <?php foreach ($grades as $grade): ?>
                                                <option value="<?= $grade ?>" <?= $selectedGrade == $grade ? 'selected' : '' ?>>
                                                    Grade <?= $grade ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="section" class="form-label">Section</label>
                                        <select class="form-select" name="section" id="section" <?= empty($sections) ? 'disabled' : '' ?>>
                                            <option value="all">All Sections</option>
                                            <?php foreach ($sections as $section): ?>
                                                <option value="<?= $section ?>" <?= $selectedSection == $section ? 'selected' : '' ?>>
                                                    Section <?= $section ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="semester" class="form-label">Semester</label>
                                        <select class="form-select" name="semester" id="semester" required>
                                            <?php foreach ($semesters as $semester): ?>
                                                <option value="<?= $semester['id'] ?>" <?= $selectedSemester == $semester['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($semester['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card dashboard-card text-center text-white bg-primary" 
                         onclick="document.getElementById('dashboardForm').action=''; document.getElementById('dashboardForm').submit();">
                        <div class="card-body">
                            <i class="fas fa-search"></i>
                            <h5 class="card-title">Marks Range Finder</h5>
                            <p class="card-text">Find students within specific mark ranges</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card text-center text-white bg-info" 
                         onclick="document.getElementById('dashboardForm').action=''; document.getElementById('dashboardForm').innerHTML += '<input type=\'hidden\' name=\'get_stats\' value=\'1\'>'; document.getElementById('dashboardForm').submit();">
                        <div class="card-body">
                            <i class="fas fa-chart-pie"></i>
                            <h5 class="card-title">Subject Statistics</h5>
                            <p class="card-text">View performance statistics by subject</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card text-center text-white bg-success">
                        <div class="card-body">
                            <i class="fas fa-users"></i>
                            <h5 class="card-title">Class Overview</h5>
                            <p class="card-text">View overall class performance</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Detailed View -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title"><i class="fas fa-search"></i> Marks Range Finder</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="get_students" value="1">
                                <div class="mb-3">
                                    <label for="grade" class="form-label">Grade</label>
                                    <select class="form-select" name="grade" id="grade" required>
                                        <?php foreach ($grades as $grade): ?>
                                            <option value="<?= $grade ?>" <?= $selectedGrade == $grade ? 'selected' : '' ?>>
                                                Grade <?= $grade ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="section" class="form-label">Section</label>
                                    <select class="form-select" name="section" id="section" <?= empty($sections) ? 'disabled' : '' ?>>
                                        <option value="all">All Sections</option>
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= $section ?>" <?= $selectedSection == $section ? 'selected' : '' ?>>
                                                Section <?= $section ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <select class="form-select" name="subject" id="subject" required>
                                        <option value="">-- Select Subject --</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?= $subject['id'] ?>" <?= isset($_POST['subject']) && $_POST['subject'] == $subject['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($subject['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select class="form-select" name="semester" id="semester" required>
                                        <?php foreach ($semesters as $semester): ?>
                                            <option value="<?= $semester['id'] ?>" <?= $selectedSemester == $semester['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($semester['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="min_mark" class="form-label">Minimum Mark</label>
                                    <input type="number" class="form-control" name="min_mark" id="min_mark" min="0" max="100" 
                                           value="<?= htmlspecialchars($_POST['min_mark'] ?? '65') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="max_mark" class="form-label">Maximum Mark</label>
                                    <input type="number" class="form-control" name="max_mark" id="max_mark" min="0" max="100" 
                                           value="<?= htmlspecialchars($_POST['max_mark'] ?? '70') ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Find Students
                                </button>
                                <a href="?" class="btn btn-secondary w-100 mt-2">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($results)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title"><i class="fas fa-users"></i> Quick Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="value text-primary"><?= count($results) ?></div>
                                        <div class="label">Total Students</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="value text-info"><?= round(count($results) > 0 ? (array_sum(array_column($results, 'total_mark')) / count($results)) : 0, 2) ?></div>
                                        <div class="label">Average Mark</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="value text-danger"><?= $genderStats['male'] ?></div>
                                        <div class="label">Male Students</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="value text-warning"><?= $genderStats['female'] ?></div>
                                        <div class="label">Female Students</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <?php if (!empty($results)): ?>
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title">
                                    <i class="fas fa-list"></i> Student Results 
                                    <span class="float-end">
                                        <?= count($results) ?> students found 
                                        (<?= $genderStats['male'] ?> male<?= $genderStats['male'] != 1 ? 's' : '' ?>, 
                                        <?= $genderStats['female'] ?> female<?= $genderStats['female'] != 1 ? 's' : '' ?>)
                                    </span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Student Name</th>
                                                <th>Grade</th>
                                                <th>Section</th>
                                                <th>Gender</th>
                                                <th>Total Mark</th>
                                                <th>Exam Count</th>
                                                <th>Performance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results as $row): 
                                                $mark = $row['total_mark'];
                                                if ($mark >= 90) {
                                                    $performance = 'Excellent';
                                                    $badgeClass = 'bg-success';
                                                } elseif ($mark >= 80) {
                                                    $performance = 'Very Good';
                                                    $badgeClass = 'bg-primary';
                                                } elseif ($mark >= 70) {
                                                    $performance = 'Good';
                                                    $badgeClass = 'bg-info';
                                                } elseif ($mark >= 60) {
                                                    $performance = 'Average';
                                                    $badgeClass = 'bg-warning';
                                                } else {
                                                    $performance = 'Below Average';
                                                    $badgeClass = 'bg-danger';
                                                }
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                                    <td>Grade <?= htmlspecialchars($row['grade']) ?></td>
                                                    <td>Section <?= htmlspecialchars($row['section']) ?></td>
                                                    <td>
                                                        <?php if (strtolower($row['sex']) === 'male'): ?>
                                                            <span class="badge bg-primary">Male</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-pink">Female</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['total_mark']) ?></td>
                                                    <td><?= htmlspecialchars($row['exam_count']) ?></td>
                                                    <td><span class="badge <?= $badgeClass ?>"><?= $performance ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php elseif (isset($_POST['get_students'])): ?>
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title text-muted">No students found in the specified range</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                                <a href="?" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($subjectStats)): ?>
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-bar"></i> 
                                    Grade <?= $selectedGrade ?> 
                                    <?= $selectedSection !== 'all' ? 'Section ' . $selectedSection : 'All Sections' ?> 
                                    Subject Statistics by Gender 
                                    (<?= htmlspecialchars($semesters[array_search($selectedSemester, array_column($semesters, 'id'))]['name'] ?? 'Selected' ) ?> Semester)
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="rangeTabs" role="tablist">
                                    <?php $first = true; ?>
                                    <?php foreach ($markRanges as $rangeName => $range): ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?= $first ? 'active' : '' ?>" id="<?= $rangeName ?>-tab" data-bs-toggle="tab" 
                                                data-bs-target="#<?= $rangeName ?>" type="button" role="tab">
                                                <?= $rangeName ?>
                                            </button>
                                        </li>
                                        <?php $first = false; ?>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="tab-content" id="rangeTabsContent">
                                    <?php $first = true; ?>
                                    <?php foreach ($markRanges as $rangeName => $range): ?>
                                        <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="<?= $rangeName ?>" role="tabpanel">
                                            <div class="chart-container">
                                                <canvas id="chart-<?= $rangeName ?>"></canvas>
                                            </div>
                                        </div>
                                        <?php $first = false; ?>
                                    <?php endforeach; ?>
                                </div>
                                <a href="?" class="btn btn-secondary mt-3">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Update sections when grade changes
        document.getElementById('grade').addEventListener('change', function() {
            const grade = this.value;
            const sectionSelect = document.getElementById('section');
            
            if (!grade) {
                sectionSelect.innerHTML = '<option value="all">All Sections</option>';
                sectionSelect.disabled = true;
                return;
            }
            
            // Fetch sections for selected grade
            fetch(`get_sections.php?grade=${grade}`)
                .then(response => response.json())
                .then(sections => {
                    let options = '<option value="all">All Sections</option>';
                    sections.forEach(section => {
                        options += `<option value="${section}">Section ${section}</option>`;
                    });
                    sectionSelect.innerHTML = options;
                    sectionSelect.disabled = false;
                });
        });

        <?php if (!empty($subjectStats)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($markRanges as $rangeName => $range): ?>
                let ctx<?= str_replace('-', '', $rangeName) ?> = document.getElementById('chart-<?= $rangeName ?>');
                
                if (ctx<?= str_replace('-', '', $rangeName) ?>) {
                    // Prepare subject names and data
                    let subjectNames = [];
                    let maleData = [];
                    let femaleData = [];
                    
                    <?php foreach ($subjects as $subject): ?>
                        subjectNames.push("<?= addslashes($subject['name']) ?>");
                        maleData.push(<?= $subjectStats[$subject['id']][$rangeName]['male'] ?? 0 ?>);
                        femaleData.push(<?= $subjectStats[$subject['id']][$rangeName]['female'] ?? 0 ?>);
                    <?php endforeach; ?>
                    
                    new Chart(ctx<?= str_replace('-', '', $rangeName) ?>, {
                        type: 'bar',
                        data: {
                            labels: subjectNames,
                            datasets: [
                                {
                                    label: 'Male Students',
                                    data: maleData,
                                    backgroundColor: 'rgba(54, 162, 235, 0.7)'
                                },
                                {
                                    label: 'Female Students',
                                    data: femaleData,
                                    backgroundColor: 'rgba(255, 99, 132, 0.7)'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            <?php endforeach; ?>
        });
        <?php endif; ?>
    </script>
</body>
</html>
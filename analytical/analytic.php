<?php
include ("db_connect.php");

// Check if PDO is available
if (!extension_loaded('pdo')) {
    die('PDO extension is not enabled. Please enable it in your php.ini file.');
}

class MarksReport {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
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

    private function validateInputs($subjectId, $semesterId, $min, $max, $grade) {
        // Validate subject exists
        $stmt = $this->pdo->prepare("SELECT id FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException("Invalid subject selected.");
        }
        
        // Validate semester exists
        $stmt = $this->pdo->prepare("SELECT id FROM semesters WHERE id = ?");
        $stmt->execute([$semesterId]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException("Invalid semester selected.");
        }
        
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
}

// Create report instance
$report = new MarksReport($pdo);
$semesters = $report->getSemesters();
$subjects = $report->getSubjects();
$grades = $report->getAvailableGrades();

// Get selected filters
$selectedSemester = $_GET['semester'] ?? ($semesters[0]['id'] ?? null);
$selectedGrade = $_GET['grade'] ?? null;
$selectedSection = $_GET['section'] ?? 'all';
$selectedSubject = $_GET['subject'] ?? null;
$minMark = $_GET['min_mark'] ?? 0;
$maxMark = $_GET['max_mark'] ?? 100;

// Get sections based on selected grade
$sections = $selectedGrade ? $report->getAvailableSections($selectedGrade) : [];

// Get student results if all filters are set
$results = [];
$genderStats = ['male' => 0, 'female' => 0];
if ($selectedSemester && $selectedGrade && $selectedSubject && is_numeric($minMark) && is_numeric($maxMark)) {
    try {
        $results = $report->getStudentsByMarkRange(
            $selectedSubject,
            $selectedSemester,
            (int)$minMark,
            (int)$maxMark,
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
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .bg-pink {
            background-color: #ff66b2;
            color: white;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
            margin-bottom: 15px;
        }
        .stat-card .value {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .stat-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .badge-excellent {
            background-color: #28a745;
        }
        .badge-good {
            background-color: #17a2b8;
        }
        .badge-average {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-poor {
            background-color: #fd7e14;
        }
        .badge-fail {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="dashboard-header text-center">
            <h1><i class="fas fa-chart-line"></i> Student Marks Analytics Dashboard</h1>
            <p class="lead">Find students by custom mark ranges and view performance</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="filter-section">
            <form id="filterForm" method="GET">
                <div class="row">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" name="subject" id="subject" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" <?= $selectedSubject == $subject['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="min_mark" class="form-label">Minimum Mark</label>
                            <input type="number" class="form-control" name="min_mark" id="min_mark" 
                                   min="0" max="100" value="<?= htmlspecialchars($minMark) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="max_mark" class="form-label">Maximum Mark</label>
                            <input type="number" class="form-control" name="max_mark" id="max_mark" 
                                   min="0" max="100" value="<?= htmlspecialchars($maxMark) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Find Students
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!empty($results)): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title">
                                <i class="fas fa-users"></i> Student Results 
                                <span class="float-end">
                                    <?= count($results) ?> students found 
                                    (<?= $genderStats['male'] ?> male<?= $genderStats['male'] != 1 ? 's' : '' ?>, 
                                    <?= $genderStats['female'] ?> female<?= $genderStats['female'] != 1 ? 's' : '' ?>)
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="value text-primary"><?= count($results) ?></div>
                                        <div class="label">Total Students</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="value text-info"><?= round(count($results) > 0 ? (array_sum(array_column($results, 'total_mark')) / count($results) ): 0, 2) ?></div>
                                        <div class="label">Average Mark</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="value text-primary"><?= $genderStats['male'] ?></div>
                                        <div class="label">Male Students</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="value text-pink"><?= $genderStats['female'] ?></div>
                                        <div class="label">Female Students</div>
                                    </div>
                                </div>
                            </div>
                            
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
                                                $badgeClass = 'badge-excellent';
                                            } elseif ($mark >= 80) {
                                                $performance = 'Very Good';
                                                $badgeClass = 'badge-good';
                                            } elseif ($mark >= 70) {
                                                $performance = 'Good';
                                                $badgeClass = 'badge-average';
                                            } elseif ($mark >= 60) {
                                                $performance = 'Average';
                                                $badgeClass = 'badge-poor';
                                            } else {
                                                $performance = 'Fail';
                                                $badgeClass = 'badge-fail';
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
                </div>
            </div>
        <?php elseif ($selectedGrade && $selectedSemester && $selectedSubject): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-times fa-4x text-muted mb-4"></i>
                    <h4>No students found in the specified range</h4>
                    <p class="text-muted">Try adjusting your mark range criteria</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-pie fa-4x text-muted mb-4"></i>
                    <h4>Select filters to find students</h4>
                    <p class="text-muted">Choose grade, section, semester, subject and mark range</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            
            // Enable the section select while loading
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="all">Loading...</option>';
            
            // Submit the form to get new sections
            document.getElementById('filterForm').submit();
        });

        // Validate mark range
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const minMark = parseInt(document.getElementById('min_mark').value);
            const maxMark = parseInt(document.getElementById('max_mark').value);
            
            if (minMark > maxMark) {
                alert('Minimum mark cannot be greater than maximum mark');
                e.preventDefault();
            }
            
            if (minMark < 0 || maxMark > 100) {
                alert('Marks must be between 0 and 100');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
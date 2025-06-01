<?php
include ("db_connect.php");

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

// Default values
$selectedSemester = $_POST['semester'] ?? ($semesters[0]['id'] ?? null);
$selectedGrade = $_POST['grade'] ?? ($grades[0] ?? null);
$selectedSection = $_POST['section'] ?? 'all';

// Get sections based on selected grade
$sections = $report->getAvailableSections($selectedGrade);

// Get statistics for all subjects
$subjectStats = $report->getSubjectStatisticsByGender($selectedSemester, $selectedGrade, $selectedSection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            background-color: #f8f9fa;
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
        .bg-pink {
            background-color: #ff66b2;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="mb-4">Student Marks Analytics</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title">Filter Options</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="filterForm">
                    <div class="row">
                        <div class="col-md-4">
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

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="value text-primary">
                        <?= count($subjects) ?>
                    </div>
                    <div class="label">Subjects</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="value text-success">
                        <?= $selectedGrade ?>
                    </div>
                    <div class="label">Grade</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="value text-info">
                        <?= $selectedSection === 'all' ? 'All' : $selectedSection ?>
                    </div>
                    <div class="label">Section</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="value text-warning">
                        <?= htmlspecialchars($semesters[array_search($selectedSemester, array_column($semesters, 'id'))]['name'] ?? 'N/A') ?>
                    </div>
                    <div class="label">Semester</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title">
                    Subject Statistics by Gender and Mark Range
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Update sections when grade changes
        document.getElementById('grade').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        document.getElementById('section').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        document.getElementById('semester').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($markRanges as $rangeName => $range): ?>
                let ctx<?= str_replace('-', '', $rangeName) ?> = document.getElementById('chart-<?= $rangeName ?>');
                
                if (ctx<?= str_replace('-', '', $rangeName) ?>) {
                    // Prepare subject names and data
                    let subjectNames = [];
                    let maleData = [];
                    let femaleData = [];
                    let totalData = [];
                    
                    <?php foreach ($subjects as $subject): ?>
                        subjectNames.push("<?= addslashes($subject['name']) ?>");
                        maleData.push(<?= $subjectStats[$subject['id']][$rangeName]['male'] ?? 0 ?>);
                        femaleData.push(<?= $subjectStats[$subject['id']][$rangeName]['female'] ?? 0 ?>);
                        totalData.push(<?= $subjectStats[$subject['id']][$rangeName]['total'] ?? 0 ?>);
                    <?php endforeach; ?>
                    
                    new Chart(ctx<?= str_replace('-', '', $rangeName) ?>, {
                        type: 'bar',
                        data: {
                            labels: subjectNames,
                            datasets: [
                                {
                                    label: 'Male Students',
                                    data: maleData,
                                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Female Students',
                                    data: femaleData,
                                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Total Students',
                                    data: totalData,
                                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1,
                                    type: 'line',
                                    fill: false
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Students'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Subjects'
                                    }
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Students with marks between <?= $rangeName ?>',
                                    font: {
                                        size: 16
                                    }
                                },
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        afterBody: function(context) {
                                            const datasetIndex = context[0].datasetIndex;
                                            const dataIndex = context[0].dataIndex;
                                            const total = datasetIndex === 0 ? 
                                                maleData[dataIndex] + femaleData[dataIndex] : 
                                                datasetIndex === 1 ? 
                                                maleData[dataIndex] + femaleData[dataIndex] : 
                                                totalData[dataIndex];
                                            
                                            return `Total: ${total}`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
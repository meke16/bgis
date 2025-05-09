<?php
include ("db_connect.php");
include ("session.php");
$grade=12;
// Check if PDO is available
if (!extension_loaded('pdo')) {
    die('PDO extension is not enabled. Please enable it in your php.ini file.');
}
class MarksReport {
    private $pdo;
    private $allowedSemesters = ['first', 'second'];
    private $allowedSubjects = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
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

    // Add public method to get mark ranges
    public function getMarkRanges() {
        return $this->markRanges;
    }

    public function getStudentsByMarkRange($subjectNumber, $semester, $min, $max) {
        $this->validateInputs($subjectNumber, $semester, $min, $max);
        
        $column = $semester === 'second' ? "sub{$subjectNumber}_second_semester" : "sub{$subjectNumber}";
        
        $stmt = $this->pdo->prepare("
            SELECT g.name, g.sex, m.{$column} as mark 
            FROM students g
            JOIN marks m ON g.id = m.student_id
            WHERE grade=12 AND m.{$column} BETWEEN :min AND :max
            ORDER BY m.{$column} DESC
        ");
        $stmt->execute([':min' => $min, ':max' => $max]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubjectStatisticsByGender($semester) {
        $this->validateSemester($semester);
        
        $stats = [];
        foreach ($this->allowedSubjects as $subject) {
            $column = $semester === 'second' ? "sub{$subject}_second_semester" : "sub{$subject}";
            
            foreach ($this->markRanges as $rangeName => $range) {
                $query = "
                    SELECT 
                        g.sex,
                        COUNT(*) as count
                    FROM students g
                    JOIN marks m ON g.id = m.student_id
                    WHERE grade=12 AND m.{$column} BETWEEN :min AND :max
                    GROUP BY g.sex
                ";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([':min' => $range['min'], ':max' => $range['max']]);
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
                
                $stats[$subject][$rangeName] = [
                    'male' => $maleCount,
                    'female' => $femaleCount,
                    'total' => $maleCount + $femaleCount
                ];
            }
        }
        
        return $stats;
    }

    private function validateInputs($subjectNumber, $semester, $min, $max) {
        if (!in_array($subjectNumber, $this->allowedSubjects)) {
            throw new InvalidArgumentException("Invalid subject selected.");
        }
        $this->validateSemester($semester);
        if (!is_numeric($min) || !is_numeric($max) || $min < 0 || $max > 100 || $min > $max) {
            throw new InvalidArgumentException("Invalid mark range (must be 0-100).");
        }
    }
    
    private function validateSemester($semester) {
        if (!in_array($semester, $this->allowedSemesters)) {
            throw new InvalidArgumentException("Invalid semester selected.");
        }
    }
}

// Handle form submission
$results = [];
$genderStats = ['male' => 0, 'female' => 0];
$subjectStats = [];
$selectedSemester = $_POST['semester'] ?? 'first';

// Create report instance
$report = new MarksReport($pdo);
$markRanges = $report->getMarkRanges(); // Get the mark ranges

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['get_stats'])) {
            // Get statistics for all subjects
            $subjectStats = $report->getSubjectStatisticsByGender($selectedSemester);
        } else {
            // Get students in specific range
            $results = $report->getStudentsByMarkRange(
                $_POST['subject'],
                $_POST['semester'],
                (int)$_POST['min_mark'],
                (int)$_POST['max_mark']
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></style>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="dashboard-header text-center">
            <h1><i class="fas fa-chart-line"></i> Student Marks Analytics Dashboard For <?php echo "Grade ".$grade; ?></h1>
            <p class="lead">Comprehensive analysis of student performance across subjects and semesters</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title"><i class="fas fa-search"></i> Marks Range Finder</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" name="subject" id="subject" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= isset($_POST['subject']) && $_POST['subject'] == $i ? 'selected' : '' ?>>
                                            Subject <?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" name="semester" id="semester" required>
                                    <option value="first" <?= isset($_POST['semester']) && $_POST['semester'] == 'first' ? 'selected' : '' ?>>First Semester</option>
                                    <option value="second" <?= isset($_POST['semester']) && $_POST['semester'] == 'second' ? 'selected' : '' ?>>Second Semester</option>
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
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title"><i class="fas fa-chart-pie"></i> Subject Statistics</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="get_stats" value="1">
                            <div class="mb-3">
                                <label for="stats_semester" class="form-label">Semester for Statistics</label>
                                <select class="form-select" name="semester" id="stats_semester" required>
                                    <option value="first" <?= $selectedSemester == 'first' ? 'selected' : '' ?>>First Semester</option>
                                    <option value="second" <?= $selectedSemester == 'second' ? 'selected' : '' ?>>Second Semester</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-sync-alt"></i> Generate Statistics
                            </button>
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
                                    <div class="value text-info"><?= round(count($results) > 0 ? (array_sum(array_column($results, 'mark')) / count($results)) : 0, 2) ?></div>
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
                                            <th>Gender</th>
                                            <th>Mark</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $row): 
                                            $mark = $row['mark'];
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
                                                <td>
                                                    <?php if (strtolower($row['sex']) === 'male'): ?>
                                                        <span class="badge bg-primary">Male</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-pink">Female</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['mark']) ?></td>
                                                <td><span class="badge <?= $badgeClass ?>"><?= $performance ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['get_stats'])): ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted">No students found in the specified range</h5>
                            <p class="text-muted">Try adjusting your search criteria</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($subjectStats)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title"><i class="fas fa-chart-bar"></i> Grade9 Subject Statistics by Gender (<?= ucfirst($selectedSemester) ?> Semester)</h5>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <!-- <script src="https://kit.fontawesome.com/7dfeb58e6.js" crossorigin="anonymous"></script> -->
    
    <?php if (!empty($subjectStats)): ?>
    <script>
       document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($subjectStats)): ?>
        <?php foreach ($markRanges as $rangeName => $range): ?>
            // Use let instead of const for variables that might be redeclared
            let ctx<?= str_replace('-', '', $rangeName) ?> = document.getElementById('chart-<?= $rangeName ?>');
            
            if (ctx<?= str_replace('-', '', $rangeName) ?>) {
                // Define chart data directly without separate variables
                new Chart(ctx<?= str_replace('-', '', $rangeName) ?>, {
                    type: 'bar',
                    data: {
                        labels: ['Subject 1', 'Subject 2', 'Subject 3', 'Subject 4', 
                                'Subject 5', 'Subject 6', 'Subject 7', 'Subject 8',
                                'Subject 9', 'Subject 10', 'Subject 11', 'Subject 12'],
                        datasets: [
                            {
                                label: 'Male Students',
                                data: [
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <?= $subjectStats[$i][$rangeName]['male'] ?? 0 ?>,
                                    <?php endfor; ?>
                                ],
                                backgroundColor: 'rgba(54, 162, 235, 0.7)'
                            },
                            {
                                label: 'Female Students',
                                data: [
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <?= $subjectStats[$i][$rangeName]['female'] ?? 0 ?>,
                                    <?php endfor; ?>
                                ],
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
    <?php endif; ?>
});     
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
    </script>
    <?php endif; ?>
</body>
</html>
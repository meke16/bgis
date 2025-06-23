<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

try {
    // Fetch student data
    $student = fetchStudentData($pdo, $_SESSION['student_id']);
    
    // Fetch academic data
    $semesters = fetchSemesters($pdo, $_SESSION['student_id']);
    $allMarks = fetchAllMarks($pdo, $_SESSION['student_id']);
    
    // Organize and process marks
    $organizedMarks = organizeMarks($allMarks);
    $allSubjects = extractAllSubjects($organizedMarks);
    
    // Calculate averages
    calculateAverages($organizedMarks);
    $overallAverage = calculateOverallAverage($organizedMarks);
    
    // Calculate rankings
    $semesterRanks = calculateSemesterRanks($pdo, $organizedMarks, $_SESSION['student_id']);
    $overallRank = calculateOverallRank($pdo, $_SESSION['student_id']);
    
    // Get student photo
    $photo = $student['photo'] ?? null;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while processing your request.");
}

/**
 * Fetches student data from database
 */
function fetchStudentData($pdo, $studentId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->execute([':id' => $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Student not found");
    }
    
    return $student;
}

/**
 * Fetches all semesters for a student
 */
function fetchSemesters($pdo, $studentId) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT sub.id, sub.name, sub.start_date 
        FROM student_marks sm
        JOIN semesters sub ON sm.semester_id = sub.id
        WHERE sm.student_id = :student_id
        ORDER BY sub.start_date
    ");
    $stmt->execute([':student_id' => $studentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches all marks for a student
 */
function fetchAllMarks($pdo, $studentId) {
    $stmt = $pdo->prepare("
        SELECT 
            sm.*, 
            s.name AS subject_name,
            sub.name AS semester_name,
            sub.start_date AS semester_start_date,
            sub.id AS semester_id
        FROM student_marks sm
        JOIN subjects s ON sm.subject_id = s.id
        JOIN semesters sub ON sm.semester_id = sub.id
        WHERE sm.student_id = :student_id
        ORDER BY sub.start_date, s.name, sm.mark_type
    ");
    $stmt->execute([':student_id' => $studentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Organizes marks by semester and subject
 */
function organizeMarks($allMarks) {
    $organizedMarks = [];
    
    foreach ($allMarks as $mark) {
        $semesterId = $mark['semester_id'];
        $subjectId = $mark['subject_id'];
        
        if (!isset($organizedMarks[$semesterId])) {
            $organizedMarks[$semesterId] = [
                'name' => $mark['semester_name'],
                'start_date' => $mark['semester_start_date'],
                'subjects' => [],
                'total' => 0,
                'subject_count' => 0
            ];
        }
        
        if (!isset($organizedMarks[$semesterId]['subjects'][$subjectId])) {
            $organizedMarks[$semesterId]['subjects'][$subjectId] = [
                'name' => $mark['subject_name'],
                'marks' => [],
                'total' => 0,
                'count' => 0
            ];
            $organizedMarks[$semesterId]['subject_count']++;
        }
        
        $organizedMarks[$semesterId]['subjects'][$subjectId]['marks'][] = [
            'type' => $mark['mark_type'],
            'mark' => $mark['mark']
        ];
        
        $organizedMarks[$semesterId]['subjects'][$subjectId]['total'] += $mark['mark'];
        $organizedMarks[$semesterId]['subjects'][$subjectId]['count']++;
        $organizedMarks[$semesterId]['total'] += $mark['mark'];
    }
    
    return $organizedMarks;
}

/**
 * Extracts all unique subjects
 */
function extractAllSubjects($organizedMarks) {
    $allSubjects = [];
    
    foreach ($organizedMarks as $semester) {
        foreach ($semester['subjects'] as $subjectId => $subject) {
            if (!isset($allSubjects[$subjectId])) {
                $allSubjects[$subjectId] = $subject['name'];
            }
        }
    }
    
    return $allSubjects;
}

/**
 * Calculates semester averages
 */
function calculateAverages(&$organizedMarks) {
    foreach ($organizedMarks as $semesterId => &$semesterData) {
        $semesterData['average'] = $semesterData['subject_count'] > 0 
            ? round($semesterData['total'] / $semesterData['subject_count'], 2) 
            : 0;
    }
}

/**
 * Calculates overall average
 */
function calculateOverallAverage($organizedMarks) {
    global $overallTotal;
    if (empty($organizedMarks)) return 0;

    $overallTotal = 0;
    $semesterCount = 0;

    foreach ($organizedMarks as $semester) {
        if (isset($semester['total'])) {
            $overallTotal += $semester['total'];
            $semesterCount++;
        }
    }

    return $semesterCount > 0 
        ? round($overallTotal / $semesterCount, 2) 
        : 0;
}


/**
 * Calculates semester rankings
 */
function calculateSemesterRanks($pdo, $organizedMarks, $studentId) {
    $semesterRanks = [];
    ksort($organizedMarks);
    
    foreach ($organizedMarks as $semesterId => $semesterData) {
        $stmt = $pdo->prepare("
            SELECT sm.student_id, SUM(sm.mark) as total
            FROM student_marks sm
            WHERE sm.semester_id = :semester_id
            GROUP BY sm.student_id
            ORDER BY total DESC
        ");
        $stmt->execute([':semester_id' => $semesterId]);
        $semesterTotals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $rank = 1;
        foreach ($semesterTotals as $row) {
            if ($row['student_id'] == $studentId) break;
            $rank++;
        }
        
        $semesterRanks[$semesterId] = [
            'rank' => $rank,
            'total_students' => count($semesterTotals)
        ];
    }
    
    return $semesterRanks;
}

/**
 * Calculates overall ranking
 */
function calculateOverallRank($pdo, $studentId) {
    $stmt = $pdo->prepare("
        SELECT sm.student_id, SUM(sm.mark) as total
        FROM student_marks sm
        GROUP BY sm.student_id
        ORDER BY total DESC
    ");
    $stmt->execute();
    $allStudentsTotals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $rank = 1;
    foreach ($allStudentsTotals as $row) {
        if ($row['student_id'] == $studentId) break;
        $rank++;
    }
    
    return $rank;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annual Report Card - <?php echo htmlspecialchars($student['name']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #ef233c;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .report-card {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .watermark {
            position: absolute;
            right: 30px;
            opacity: 0.1;
            font-size: 120px;
            font-weight: bold;
        }
        
        .profile-photo-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            margin-right: 30px;
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }
        
        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-photo {
            color: #6c757d;
            font-size: 3rem;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .student-id {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .meta-item i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .content {
            padding: 30px;
        }
        
        .report-title {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        
        .report-title h2 {
            font-size: 24px;
            color: var(--primary);
            display: inline-block;
            padding: 0 20px;
            background: white;
            position: relative;
            z-index: 1;
        }
        
        .report-title::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
            z-index: 0;
        }
        
        .annual-performance {
            margin: 30px 0;
        }
        
        .term-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .term-title {
            background-color: var(--primary);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
        }
        
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .marks-table th {
            background-color: var(--primary);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .marks-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .marks-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .subject-row {
            font-weight: 600;
            background-color: #f1f3ff !important;
        }
        
        .mark-type {
            padding-left: 30px;
            color: #666;
        }
        
        .term-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            border-top: 3px solid var(--primary);
        }
        
        .summary-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .grade {
            font-weight: 600;
        }
        
        .grade-A { color: #28a745; }
        .grade-B { color: #17a2b8; }
        .grade-C { color: #ffc107; }
        .grade-D { color: #fd7e14; }
        .grade-F { color: #dc3545; }
        
        .progress-container {
            margin-top: 10px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .annual-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .annual-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin: 40px auto 10px;
            width: 80%;
        }
        
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            z-index: 100;
        }
        .rank-badge {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            margin-left: 10px;
            margin-bottom: 9px;
        }

        .term-rank {
            font-size: 0.9rem;
            color: white;
            background-color: var(--secondary);
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 10px;
        }
        
        @media print {
            body {
                background: none;
                font-size: 12pt;
            }
            
            .report-card {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
            
            .print-btn {
                display: none;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .profile-photo-container {
                margin: 0 auto 20px;
            }
            
            .meta-grid {
                grid-template-columns: 1fr;
            }
            
            .marks-table {
                font-size: 14px;
            }
            
            .marks-table th, 
            .marks-table td {
                padding: 8px 10px;
            }
            
            .term-summary,
            .annual-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="report-card">
        <!-- Header with Student Profile -->
        <div class="header">
            <div class="watermark no-print">ANNUAL REPORT</div>
            <div class="profile-photo-container">
                <?php if (!empty($photo) && file_exists($photo)): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Student Photo">
                <?php else: ?>
                    <div class="no-photo"><i class="bi bi-person"></i></div>
                <?php endif; ?>
            </div>
            
            <div class="student-info">
                <h1 class="student-name"><?php echo htmlspecialchars($student['name']); ?>
                    <span class="rank-badge">Overall Rank: #<?php echo $overallRank; ?></span>
                </h1>
                <div class="meta-grid">
                    <div class="meta-item">
                        <i class="bi bi-mortarboard"></i>
                        <?php echo htmlspecialchars($student['grade'] . ' - ' . $student['section']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-gender-ambiguous"></i>
                        <?php echo htmlspecialchars($student['sex']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-calendar"></i>
                        <?php echo date('Y'); ?> Academic Year
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-award"></i>
                        Overall Average: <?php echo htmlspecialchars($overallAverage); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="report-title">
                <h2>ANNUAL ACADEMIC PERFORMANCE REPORT</h2>
            </div>
            
            <div class="annual-performance">
                <?php if (count($organizedMarks) > 0): ?>
                    <?php ksort($organizedMarks) ?>
                    <?php foreach ($organizedMarks as $semesterId => $semesterData): ?>
                        <div class="term-section">
                            <div class="term-title">
                                <h3><?php echo htmlspecialchars($semesterData['name']); ?>
                                    <span class="term-rank">
                                        Rank: #<?php echo $semesterRanks[$semesterId]['rank']; ?> 
                                        of <?php echo $semesterRanks[$semesterId]['total_students']; ?>
                                    </span>
                                </h3>
                                <span>Average: <?php echo $semesterData['average']; ?></span>
                            </div>
                            
                            <table class="marks-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Total Marks</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($semesterData['subjects'] as $subjectId => $subject): ?>
                                        <tr class="subject-row">
                                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                            <td><?php echo $subject['total']; ?></td>
                                            <td class="grade <?php echo getGradeClass($subject['total']); ?>">
                                                <?php echo getGradeLetter($subject['total']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <div class="term-summary">
                                <div class="summary-card">
                                    <div class="summary-label">Term Total</div>
                                    <div class="summary-value"><?php echo $semesterData['total']; ?></div>
                                </div>
                                
                                <div class="summary-card">
                                    <div class="summary-label">Term Average</div>
                                    <div class="summary-value"><?php echo $semesterData['average']; ?></div>
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>0</span>
                                            <span>100</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $semesterData['average']; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="summary-card">
                                    <div class="summary-label">Subjects Count</div>
                                    <div class="summary-value"><?php echo $semesterData['subject_count']; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Annual Summary -->
                    <div class="annual-summary">
                        <h3>Annual Academic Summary</h3>
                        
                        <div class="annual-stats">
                            <div class="summary-card">
                                <div class="summary-label">Total Marks All Terms</div>
                                <div class="summary-value"><?php echo $overallTotal; ?></div>
                            </div>
                            
                            <div class="summary-card">
                                <div class="summary-label">Overall Average</div>
                                <div class="summary-value"><?php echo  calculateOverallAverage($organizedMarks); ?></div>
                                <div class="progress-container">
                                    <div class="progress-label">
                                        <span>0</span>
                                        <span>100</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $overallAverage; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="summary-card">
                                <div class="summary-label">Total Subjects</div>
                                <div class="summary-value"><?php echo count($allSubjects); ?></div>
                            </div>
                            
                            <div class="summary-card">
                                <div class="summary-label">Total Terms</div>
                                <div class="summary-value"><?php echo count($organizedMarks); ?></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        No academic data available for this student.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Signature Area -->
            <div class="signature-area">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div>Class Teacher</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div>Principal</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div>Date: <?php echo date('d/m/Y'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <button class="print-btn no-print" onclick="window.print()" title="Print Report">
        <i class="bi bi-printer" style="font-size: 20px;">print</i>
    </button>

    <?php
    function getGradeLetter($mark) {
        if ($mark >= 90) return 'A+';
        if ($mark >= 85) return 'A';
        if ($mark >= 80) return 'A-';
        if ($mark >= 75) return 'B+';
        if ($mark >= 70) return 'B';
        if ($mark >= 65) return 'B-';
        if ($mark >= 60) return 'C+';
        if ($mark >= 50) return 'C';
        if ($mark >= 45) return 'C-';
        if ($mark >= 40) return 'D';
        return 'F';
    }
    
    function getGradeClass($mark) {
        if ($mark >= 80) return 'grade-A';
        if ($mark >= 70) return 'grade-B';
        if ($mark >= 60) return 'grade-C';
        if ($mark >= 50) return 'grade-D';
        return 'grade-F';
    }
    ?>
</body>
</html>
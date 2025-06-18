<?php
session_start();

include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

try {
    // Get student info
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("Student not found");
    }

    // Get all semesters/terms for this student
    $semestersStmt = $pdo->prepare("
        SELECT DISTINCT sub.id, sub.name, sub.start_date 
        FROM student_marks sm
        JOIN semesters sub ON sm.semester_id = sub.id
        WHERE sm.student_id = :student_id
        ORDER BY sub.start_date
    ");
    $semestersStmt->execute([':student_id' => $_SESSION['student_id']]);
    $semesters = $semestersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all marks grouped by semester and subject
    $marksStmt = $pdo->prepare("
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
    $marksStmt->execute([':student_id' => $_SESSION['student_id']]);
    $allMarks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize marks by semester and subject
    $organizedMarks = [];
    $subjectAverages = [];
    $semesterAverages = [];
    $overallTotal = 0;
    $markCount = 0;

    foreach ($allMarks as $mark) {
        $semesterId = $mark['semester_id'];
        $subjectId = $mark['subject_id'];
        
        if (!isset($organizedMarks[$semesterId])) {
            $organizedMarks[$semesterId] = [
                'name' => $mark['semester_name'],
                'start_date' => $mark['semester_start_date'],
                'subjects' => [],
                'total' => 0,
                'count' => 0
            ];
        }
        
        if (!isset($organizedMarks[$semesterId]['subjects'][$subjectId])) {
            $organizedMarks[$semesterId]['subjects'][$subjectId] = [
                'name' => $mark['subject_name'],
                'marks' => [],
                'total' => 0,
                'count' => 0
            ];
        }
        
        $organizedMarks[$semesterId]['subjects'][$subjectId]['marks'][] = [
            'type' => $mark['mark_type'],
            'mark' => $mark['mark']
        ];
        
        $organizedMarks[$semesterId]['subjects'][$subjectId]['total'] += $mark['mark'];
        $organizedMarks[$semesterId]['subjects'][$subjectId]['count']++;
        
        $organizedMarks[$semesterId]['total'] += $mark['mark'];
        $organizedMarks[$semesterId]['count']++;
        
        $overallTotal += $mark['mark'];
        $markCount++;
    }

    // Calculate averages
    foreach ($organizedMarks as $semesterId => $semesterData) {
        $semesterAverages[$semesterId] = [
            'name' => $semesterData['name'],
            'average' => $semesterData['count'] > 0 ? round($semesterData['total'] / $semesterData['count'], 2) : 0,
            'total' => $semesterData['total']
        ];
        
        foreach ($semesterData['subjects'] as $subjectId => $subjectData) {
            if (!isset($subjectAverages[$subjectId])) {
                $subjectAverages[$subjectId] = [
                    'name' => $subjectData['name'],
                    'total' => 0,
                    'count' => 0,
                    'semesters' => []
                ];
            }
            
            $subjectAverage = $subjectData['count'] > 0 ? round($subjectData['total'] / $subjectData['count'], 2) : 0;
            
            $subjectAverages[$subjectId]['total'] += $subjectData['total'];
            $subjectAverages[$subjectId]['count'] += $subjectData['count'];
            $subjectAverages[$subjectId]['semesters'][$semesterId] = $subjectAverage;
        }
    }

    // Calculate overall averages
    $overallAverage = $markCount > 0 ? round($overallTotal / $markCount, 2) : 0;

    // Calculate rank (same as before)
    $allStudentsTotalsStmt = $pdo->prepare("
        SELECT sm.student_id, SUM(sm.mark) AS total_mark
        FROM student_marks sm
        GROUP BY sm.student_id
    ");
    $allStudentsTotalsStmt->execute();
    $allStudentsTotals = $allStudentsTotalsStmt->fetchAll(PDO::FETCH_ASSOC);

    $rank = 1;
    foreach ($allStudentsTotals as $otherStudent) {
        if ($otherStudent['total_mark'] > $overallTotal) {
            $rank++;
        }
    }

    // Handle photo upload/deletion (same as before)
    if (isset($_POST['upload_photo']) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 10 * 1024 * 1024;
        $uploadDir = '../uploads/';

        if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxFileSize) {
            $fileExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('student_') . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                $updateStmt = $pdo->prepare("UPDATE students SET photo = :photo WHERE id = :id");
                $updateStmt->execute([':photo' => $destination, ':id' => $_SESSION['student_id']]);
                header("Location: dashboard.php");
                exit();
            } else {
                $uploadError = "Error uploading file.";
            }
        } else {
            $uploadError = "Invalid file type or size.";
        }
    }

    if (isset($_POST['delete_photo'])) {
        $selectPhotoStmt = $pdo->prepare("SELECT photo FROM students WHERE id = :id");
        $selectPhotoStmt->execute([':id' => $_SESSION['student_id']]);
        $currentPhoto = $selectPhotoStmt->fetchColumn();

        if ($currentPhoto && file_exists($currentPhoto)) {
            unlink($currentPhoto);
        }

        $updateStmt = $pdo->prepare("UPDATE students SET photo = NULL WHERE id = :id");
        $updateStmt->execute([':id' => $_SESSION['student_id']]);
        header("Location: dashboard.php");
        exit();
    }

    $photo = $student['photo'];
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Determine active semester
$activeSemester = isset($_GET['semester']) ? $_GET['semester'] : (count($semesters) > 0 ? $semesters[0]['id'] : null);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report Card</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .report-card {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            display: flex;
            align-items: center;
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
        }
        
        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-photo {
            color: #6c757d;
            font-size: 2.5rem;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-size: 1.8rem;
            margin: 0 0 5px;
            font-weight: 600;
        }
        
        .student-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
        }
        
        .meta-item i {
            margin-right: 8px;
            font-size: 1.1rem;
        }
        
        .content {
            padding: 30px;
        }
        
        .semester-selector {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .semester-tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .semester-tab:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .semester-tab.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .marks-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .marks-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .marks-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .subject-header {
            background-color: #e9ecef !important;
            font-weight: 600;
        }
        
        .mark-type {
            padding-left: 30px !important;
        }
        
        .summary-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .summary-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .rank-badge {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            margin-top: 10px;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
        
        .grade-A { color: var(--success-color); font-weight: bold; }
        .grade-B { color: #17a2b8; font-weight: bold; }
        .grade-C { color: var(--warning-color); font-weight: bold; }
        .grade-D { color: #fd7e14; font-weight: bold; }
        .grade-F { color: var(--danger-color); font-weight: bold; }
        
        .upload-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px dashed #ced4da;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .profile-photo-container {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .student-meta {
                justify-content: center;
            }
            
            .semester-selector {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="report-card">
        <!-- Header with Student Profile -->
        <div class="header">
            <div class="profile-photo-container">
                <?php if (!empty($photo) && file_exists($photo)): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Student Photo">
                <?php else: ?>
                    <div class="no-photo"><i class="bi bi-person"></i></div>
                <?php endif; ?>
            </div>
            
            <div class="student-info">
                <h1 class="student-name"><?php echo htmlspecialchars($student['name']); ?></h1>
                <p>Academic Report Card</p>
                
                <div class="student-meta">
                    <div class="meta-item">
                        <i class="bi bi-mortarboard"></i>
                        <?php echo htmlspecialchars($student['grade'] . ' - ' . $student['section']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-person-vcard"></i>
                        ID: <?php echo htmlspecialchars($student['id']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-gender-ambiguous"></i>
                        <?php echo htmlspecialchars($student['sex']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-phone"></i>
                        <?php echo htmlspecialchars($student['phone']); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Semester Selector -->
        <?php if (count($semesters) > 0): ?>
            <div class="semester-selector">
                <?php foreach ($semesters as $semester): ?>
                    <div class="semester-tab <?php echo $semester['id'] == $activeSemester ? 'active' : ''; ?>" 
                         onclick="window.location.href='?semester=<?php echo $semester['id']; ?>'">
                        <?php echo htmlspecialchars($semester['name']); ?> 
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="content">
            <!-- Photo Upload Form -->
            <div class="upload-container">
                <h5>Update Profile Photo</h5>
                <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <input type="file" class="form-control" name="photo" id="photo" accept="image/*">
                        <?php if (isset($uploadError)): ?>
                            <div class="text-danger mt-2"><?php echo htmlspecialchars($uploadError); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100" name="upload_photo">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                    </div>
                    <?php if (!empty($photo)): ?>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-danger w-100" name="delete_photo">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($activeSemester && isset($organizedMarks[$activeSemester])): ?>
                <?php $currentSemester = $organizedMarks[$activeSemester]; ?>
                
                <h3 class="mt-4"><?php echo htmlspecialchars($currentSemester['name']); ?> Performance</h3>
                
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Mark</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentSemester['subjects'] as $subjectId => $subject): ?>
                            <!-- Subject Name and Marks Row -->
                            <tr>
                                <td style="font-weight:600;">
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </td>
                                <?php 
                                $subjectTotal = 0;
                                $subjectCount = 0;
                                ?>
                                
                                <!-- Loop through marks to calculate total and count -->
                                <?php foreach ($subject['marks'] as $mark): ?>
                                    <?php 
                                    $subjectTotal += $mark['mark'];
                                    $subjectCount++;
                                    ?>
                                <?php endforeach; ?>
                                
                                <td><strong><?php echo $subjectCount > 0 ? $subjectTotal : 0; ?></strong></td>
                                <td><strong><?php echo getGrade($subjectCount > 0 ? $subjectTotal: 0); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Semester Summary -->
                <div class="summary-card">
                    <h4>Semester Summary</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-label">Total Marks</div>
                            <div class="summary-value"><?php echo htmlspecialchars($currentSemester['total']); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Average Mark</div>
                            <div class="summary-value"><?php echo htmlspecialchars($currentSemester['count'] > 0 ? ($currentSemester['total'] / count($currentSemester['subjects']) ): 0); ?></div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $currentSemester['count'] > 0 ? ($currentSemester['total'] / count($currentSemester['subjects']) ) : 0; ?>%"></div>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Overall Rank</div>
                            <div class="summary-value"><span class="rank-badge">#<?php echo htmlspecialchars($rank); ?></span></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No marks data available for the selected semester.</div>
             <?php endif; ?> 
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Helper function to convert marks to letter grades
function getGrade($mark) {
    if ($mark >= 90) return '<span class="grade-A">A</span>';
    if ($mark >= 80) return '<span class="grade-B">B</span>';
    if ($mark >= 70) return '<span class="grade-C">C</span>';
    if ($mark >= 60) return '<span class="grade-D">D</span>';
    return '<span class="grade-F">F</span>';
}
?>
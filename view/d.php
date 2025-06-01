<?php
session_start();
include 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

// Get student info
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("Student not found");
    }

    // Get all semesters for the student
    $semestersStmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.name, s.start_date 
        FROM semesters s
        JOIN student_marks sm ON sm.semester_id = s.id
        WHERE sm.student_id = :student_id
        ORDER BY s.start_date DESC
    ");
    $semestersStmt->execute([':student_id' => $_SESSION['student_id']]);
    $semesters = $semestersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all subjects for the student
    $subjectsStmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.name
        FROM subjects s
        JOIN student_marks sm ON sm.subject_id = s.id
        WHERE sm.student_id = :student_id
        ORDER BY s.name
    ");
    $subjectsStmt->execute([':student_id' => $_SESSION['student_id']]);
    $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get student marks - organized by semester and subject
    $marksStmt = $pdo->prepare("
        SELECT sm.*, s.name AS subject_name, sub.name AS semester_name, 
               sub.start_date AS semester_start_date, sub.end_date AS semester_end_date
        FROM student_marks sm
        JOIN subjects s ON sm.subject_id = s.id
        JOIN semesters sub ON sm.semester_id = sub.id
        WHERE sm.student_id = :student_id
        ORDER BY sub.start_date DESC, sub.name, s.name, sm.mark_type
    ");
    $marksStmt->execute([':student_id' => $_SESSION['student_id']]);
    $marks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize marks by semester and subject for easier display
    $organizedMarks = [];
    $subjectTotals = [];
    $semesterTotals = [];
    
    foreach ($marks as $mark) {
        $semesterId = $mark['semester_id'];
        $subjectId = $mark['subject_id'];
        
        // Initialize semester if not exists
        if (!isset($organizedMarks[$semesterId])) {
            $organizedMarks[$semesterId] = [
                'semester_name' => $mark['semester_name'],
                'start_date' => $mark['semester_start_date'],
                'end_date' => $mark['semester_end_date'],
                'subjects' => [],
                'total_marks' => 0,
                'mark_count' => 0
            ];
        }
        
        // Initialize subject if not exists
        if (!isset($organizedMarks[$semesterId]['subjects'][$subjectId])) {
            $organizedMarks[$semesterId]['subjects'][$subjectId] = [
                'subject_name' => $mark['subject_name'],
                'marks' => [],
                'total' => 0,
                'count' => 0
            ];
        }
        
        // Add the mark
        $organizedMarks[$semesterId]['subjects'][$subjectId]['marks'][] = [
            'type' => $mark['mark_type'],
            'mark' => $mark['mark']
        ];
        
        // Update subject totals
        $organizedMarks[$semesterId]['subjects'][$subjectId]['total'] += $mark['mark'];
        $organizedMarks[$semesterId]['subjects'][$subjectId]['count']++;
        
        // Update semester totals
        $organizedMarks[$semesterId]['total_marks'] += $mark['mark'];
        $organizedMarks[$semesterId]['mark_count']++;
        
        // For overall subject totals (across all semesters)
        if (!isset($subjectTotals[$subjectId])) {
            $subjectTotals[$subjectId] = [
                'subject_name' => $mark['subject_name'],
                'total' => 0,
                'count' => 0
            ];
        }
        $subjectTotals[$subjectId]['total'] += $mark['mark'];
        $subjectTotals[$subjectId]['count']++;
    }

    // Calculate subject averages
    $subjectAverages = [];
    foreach ($subjectTotals as $subjectId => $data) {
        $subjectAverages[$subjectId] = [
            'subject_name' => $data['subject_name'],
            'average' => $data['count'] > 0 ? round($data['total'] / $data['count'], 2) : 0,
            'total' => $data['total'],
            'count' => $data['count']
        ];
    }

    // Calculate semester averages
    $semesterAverages = [];
    foreach ($organizedMarks as $semesterId => $semester) {
        $semesterAverages[$semesterId] = [
            'semester_name' => $semester['semester_name'],
            'start_date' => $semester['start_date'],
            'end_date' => $semester['end_date'],
            'average' => $semester['mark_count'] > 0 ? round($semester['total_marks'] / $semester['mark_count'], 2) : 0,
            'total' => $semester['total_marks'],
            'mark_count' => $semester['mark_count']
        ];
    }

    // Calculate overall average
    $overallTotal = 0;
    $overallCount = 0;
    foreach ($semesterAverages as $semester) {
        $overallTotal += $semester['total'];
        $overallCount += $semester['mark_count'];
    }
    $overallAverage = $overallCount > 0 ? round($overallTotal / $overallCount, 2) : 0;

    // Get all students' total marks for ranking
    $allStudentsTotalsStmt = $pdo->prepare("
        SELECT sm.student_id, SUM(sm.mark) AS total_mark
        FROM student_marks sm
        GROUP BY sm.student_id
        ORDER BY total_mark DESC
    ");
    $allStudentsTotalsStmt->execute();
    $allStudentsTotals = $allStudentsTotalsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate rank for the current student
    $currentStudentTotal = $overallTotal;
    $rank = 1;
    foreach ($allStudentsTotals as $otherStudent) {
        if ($otherStudent['student_id'] == $_SESSION['student_id']) {
            break;
        }
        $rank++;
    }
    $totalStudents = count($allStudentsTotals);

    // Handle photo upload
    if (isset($_POST['upload_photo']) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        $uploadDir = '../uploads/'; // Ensure this directory exists and is writable

        if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxFileSize) {
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $newFileName = 'student_' . $_SESSION['student_id'] . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                // Update student record with the new photo path
                $updateStmt = $pdo->prepare("UPDATE students SET photo = :photo WHERE id = :id");
                $updateStmt->execute([':photo' => $destination, ':id' => $_SESSION['student_id']]);
                header("Location: dashboard.php"); // Redirect to refresh the page
                exit();
            } else {
                $uploadError = "Error uploading file.";
            }
        } else {
            $uploadError = "Invalid file type or size (max 10MB allowed).";
        }
    }

    // Handle photo deletion
    if (isset($_POST['delete_photo'])) {
        // Get current photo path
        $selectPhotoStmt = $pdo->prepare("SELECT photo FROM students WHERE id = :id");
        $selectPhotoStmt->execute([':id' => $_SESSION['student_id']]);
        $currentPhoto = $selectPhotoStmt->fetchColumn();

        if ($currentPhoto && file_exists($currentPhoto)) {
            unlink($currentPhoto); // Delete the file
        }

        // Update student record to remove the photo path
        $updateStmt = $pdo->prepare("UPDATE students SET photo = NULL WHERE id = :id");
        $updateStmt->execute([':id' => $_SESSION['student_id']]);
        header("Location: dashboard.php"); // Redirect to refresh the page
        exit();
    }

    $photo = $student['photo']; // Get the photo path
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Determine active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'summary';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <meta name="description" content="Student dashboard for viewing academic performance and records">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
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
        }

        [data-bs-theme="dark"] {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #2a3042;
            --dark-color: #d1d3e2;
            --body-bg: #1a1a2e;
            --card-bg: #16213e;
            --text-color: #f8f9fa;
        }

        body {
            background-color: var(--body-bg, #f8f9fc);
            color: var(--text-color, #5a5c69);
            transition: all 0.3s ease;
        }

        .sidebar {
            background-color: var(--card-bg, #fff);
            color: var(--text-color, #5a5c69);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            min-height: 100vh;
            transition: all 0.3s;
        }

        .sidebar .nav-link {
            color: var(--text-color, #5a5c69);
            padding: 1rem;
            font-weight: 600;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.1);
        }

        .sidebar .nav-link.active {
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
            background-color: rgba(78, 115, 223, 0.1);
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }

        .main-content {
            background-color: var(--body-bg, #f8f9fc);
            padding: 20px;
            width: 100%;
        }

        .card {
            background-color: var(--card-bg, #fff);
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .card-header {
            background-color: var(--card-bg, #f8f9fc);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }

        .profile-card {
            background-color: var(--card-bg, #fff);
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .profile-header {
            display: flex;
            align-items: center;
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
        }

        .profile-photo-container {
            position: relative;
            margin-right: 20px;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .no-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ddd;
            color: #777;
            font-size: 3rem;
        }

        .profile-info h3 {
            margin: 0;
            font-weight: 600;
        }

        .profile-details {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-item {
            background-color: rgba(0, 0, 0, 0.05);
            padding: 10px;
            border-radius: 5px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 600;
        }

        .upload-container {
            padding: 15px 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .subject-card {
            background-color: var(--card-bg, #fff);
            border-radius: 0.35rem;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 0.15rem 0.5rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.3s;
        }

        .subject-card:hover {
            transform: translateY(-5px);
        }

        .subject-card h5 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .subject-score {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 10px;
        }

        .subject-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        .rank-display {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
        }

        .theme-toggle {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            background-color: var(--card-bg);
            border: 1px solid var(--secondary-color);
            color: var(--text-color);
        }

        .mark-detail {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.1);
        }

        .mark-detail:last-child {
            border-bottom: none;
        }

        .mark-type {
            font-weight: 600;
        }

        .mark-value {
            color: var(--primary-color);
        }

        .semester-selector {
            margin-bottom: 20px;
        }

        .semester-btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                width: 100%;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-photo-container {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .profile-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container d-flex flex-column flex-lg-row">
        <!-- Sidebar Navigation -->
        <div class="sidebar flex-shrink-0">
            <div class="sidebar-header p-3 d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Student Portal</h3>
                <button class="theme-toggle btn btn-sm" id="themeToggle">
                    <i class="bi bi-moon-fill"></i>
                </button>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'summary' ? 'active' : ''; ?>" href="?tab=summary">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'subjects' ? 'active' : ''; ?>" href="?tab=subjects">
                        <i class="bi bi-book"></i> Subjects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'marks' ? 'active' : ''; ?>" href="?tab=marks">
                        <i class="bi bi-list-check"></i> Detailed Marks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cp.php">
                        <i class="bi bi-shield-lock"></i> Change Password
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content flex-grow-1">
            <!-- Header -->
            <div class="header d-flex justify-content-between align-items-center mb-4">
                <h2>Student Dashboard</h2>
                <div class="rank-display">
                    Rank: #<?php echo htmlspecialchars($rank); ?> of <?php echo htmlspecialchars($totalStudents); ?>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-photo-container">
                        <?php if (!empty($photo) && file_exists($photo)): ?>
                            <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Student Photo">
                        <?php else: ?>
                            <div class="profile-photo no-photo"><i class="bi bi-person"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                        <p><?php echo htmlspecialchars($student['grade'] . ' - ' . $student['section']); ?></p>
                        <p class="mb-0">Overall Average: <strong><?php echo htmlspecialchars($overallAverage); ?>/100</strong></p>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-item">
                        <div class="detail-label">Gender</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['sex']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['phone']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Username</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['username']); ?></div>
                    </div>
                </div>
                
                <div class="upload-container">
                    <h6>Update Profile Photo</h6>
                    <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control form-control-sm" name="photo" id="photo" accept="image/*" required>
                            <?php if (isset($uploadError)): ?>
                                <small class="text-danger"><?php echo htmlspecialchars($uploadError); ?></small>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" name="upload_photo">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                        <?php if (!empty($photo)): ?>
                            <button type="submit" class="btn btn-danger btn-sm" name="delete_photo">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Content based on active tab -->
            <?php if ($activeTab === 'summary'): ?>
                <!-- Summary View -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Academic Summary</h5>
                        <div>Overall Average: <strong><?php echo htmlspecialchars($overallAverage); ?>/100</strong></div>
                    </div>
                    <div class="card-body">
                        <?php if (count($semesterAverages) > 0): ?>
                            <div class="row">
                                <div class="col-lg-8">
                                    <canvas id="performanceChart" height="300"></canvas>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Semester Averages</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($semesterAverages as $semesterId => $semester): ?>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span><?php echo htmlspecialchars($semester['semester_name']); ?></span>
                                                        <span><?php echo htmlspecialchars($semester['average']); ?>/100</span>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo htmlspecialchars($semester['average']); ?>%" 
                                                             aria-valuenow="<?php echo htmlspecialchars($semester['average']); ?>" 
                                                             aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        Total: <?php echo htmlspecialchars($semester['total']); ?> marks
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <?php foreach ($semesterAverages as $semesterId => $semester): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($semester['semester_name']); ?></h6>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($semester['average']); ?>/100
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="semesterChart<?php echo $semesterId; ?>" height="200"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No academic data available.</div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($activeTab === 'subjects'): ?>
                <!-- Subjects View -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Subject Performance</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($subjectAverages) > 0): ?>
                            <div class="row">
                                <?php foreach ($subjectAverages as $subjectId => $subject): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="subject-card">
                                            <h5><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                            <div class="subject-score"><?php echo htmlspecialchars($subject['average']); ?></div>
                                            <div class="subject-meta">
                                                <span>Average</span>
                                                <span>Total: <?php echo htmlspecialchars($subject['total']); ?></span>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <h6 class="small">Performance by Semester:</h6>
                                                <?php 
                                                $subjectSemesters = [];
                                                foreach ($organizedMarks as $semesterId => $semester) {
                                                    if (isset($semester['subjects'][$subjectId])) {
                                                        $subjectSemesters[] = [
                                                            'semester_name' => $semester['semester_name'],
                                                            'average' => round($semester['subjects'][$subjectId]['total'] / $semester['subjects'][$subjectId]['count'], 2)
                                                        ];
                                                    }
                                                }
                                                ?>
                                                <?php foreach ($subjectSemesters as $item): ?>
                                                    <div class="mark-detail">
                                                        <span class="mark-type"><?php echo htmlspecialchars($item['semester_name']); ?></span>
                                                        <span class="mark-value"><?php echo htmlspecialchars($item['average']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No subject data available.</div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($activeTab === 'marks'): ?>
                <!-- Detailed Marks View -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Detailed Marks</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($organizedMarks) > 0): ?>
                            <div class="semester-selector mb-4">
                                <h6>Select Semester:</h6>
                                <div class="d-flex flex-wrap">
                                    <?php foreach ($organizedMarks as $semesterId => $semester): ?>
                                        <a href="#semester-<?php echo $semesterId; ?>" 
                                           class="btn btn-outline-primary btn-sm semester-btn">
                                            <?php echo htmlspecialchars($semester['semester_name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php foreach ($organizedMarks as $semesterId => $semester): ?>
                                <div class="mb-5" id="semester-<?php echo $semesterId; ?>">
                                    <h4 class="mb-3 border-bottom pb-2">
                                        <?php echo htmlspecialchars($semester['semester_name']); ?>
                                        <small class="text-muted">
                                            (Average: <?php echo round($semester['total_marks'] / $semester['mark_count'], 2); ?>)
                                        </small>
                                    </h4>
                                    
                                    <div class="row">
                                        <?php foreach ($semester['subjects'] as $subjectId => $subject): ?>
                                            <div class="col-md-6 mb-4">
                                                <div class="card h-100">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                                        <span class="badge bg-primary">
                                                            <?php echo round($subject['total'] / $subject['count'], 2); ?>
                                                        </span>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php foreach ($subject['marks'] as $mark): ?>
                                                            <div class="mark-detail">
                                                                <span class="mark-type"><?php echo htmlspecialchars($mark['type']); ?></span>
                                                                <span class="mark-value"><?php echo htmlspecialchars($mark['mark']); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <div class="mark-detail mt-2 pt-2 border-top">
                                                            <span class="mark-type fw-bold">Average</span>
                                                            <span class="mark-value fw-bold">
                                                                <?php echo round($subject['total'] / $subject['count'], 2); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No marks recorded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    </body>
    </html>
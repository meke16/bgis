<?php
session_start();
require_once 'config.php';

// Enhanced security: Check session validity and regenerate ID periodically
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

// Regenerate session ID every 30 minutes for security
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Get student info with error handling
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student not found");
    }

    // Get all semesters for the student with proper error handling
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

    // Get student marks with proper JOINs and ordering
    $marksStmt = $pdo->prepare("
        SELECT sm.*, s.name AS subject_name, 
               sem.name AS semester_name, sem.start_date AS semester_start_date, 
               sem.end_date AS semester_end_date
        FROM student_marks sm
        JOIN subjects s ON sm.subject_id = s.id
        JOIN semesters sem ON sm.semester_id = sem.id
        WHERE sm.student_id = :student_id
        ORDER BY sem.start_date DESC, sem.name, s.name, sm.mark_type
    ");
    $marksStmt->execute([':student_id' => $_SESSION['student_id']]);
    $marks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize marks by semester and subject
    $organizedMarks = [];
    $subjectTotals = [];
    $semesterTotals = [];
    
    foreach ($marks as $mark) {
        $semesterId = $mark['semester_id'];
        $subjectId = $mark['subject_id'];
        
        if (!isset($organizedMarks[$semesterId])) {
            $organizedMarks[$semesterId] = [
                'semester_name' => htmlspecialchars($mark['semester_name']),
                'start_date' => $mark['semester_start_date'],
                'end_date' => $mark['semester_end_date'],
                'subjects' => [],
                'total_marks' => 0,
                'mark_count' => 0
            ];
        }
        
        if (!isset($organizedMarks[$semesterId]['subjects'][$subjectId])) {
            $organizedMarks[$semesterId]['subjects'][$subjectId] = [
                'subject_name' => htmlspecialchars($mark['subject_name']),
                'marks' => [],
                'total' => 0,
                'count' => 0
            ];
        }
        
        // Sanitize mark data before storing
        $organizedMarks[$semesterId]['subjects'][$subjectId]['marks'][] = [
            'type' => htmlspecialchars($mark['mark_type']),
            'mark' => (float)$mark['mark']
        ];
        
        // Update totals
        $organizedMarks[$semesterId]['subjects'][$subjectId]['total'] += (float)$mark['mark'];
        $organizedMarks[$semesterId]['subjects'][$subjectId]['count']++;
        
        $organizedMarks[$semesterId]['total_marks'] += (float)$mark['mark'];
        $organizedMarks[$semesterId]['mark_count']++;
        
        // For overall subject totals
        if (!isset($subjectTotals[$subjectId])) {
            $subjectTotals[$subjectId] = [
                'subject_name' => htmlspecialchars($mark['subject_name']),
                'total' => 0,
                'count' => 0
            ];
        }
        $subjectTotals[$subjectId]['total'] += (float)$mark['mark'];
        $subjectTotals[$subjectId]['count']++;
    }

// Calculate semester averages
$semesterAverages = [];
foreach ($organizedMarks as $semesterId => $semester) {
    $totalMarks = 0;
    $subjectCount = count($semester['subjects']);
    
    foreach ($semester['subjects'] as $subject) {
        $totalMarks += $subject['total'];
    }
    
    $semesterAverages[$semesterId] = [
        'semester_name' => $semester['semester_name'],
        'start_date' => $semester['start_date'],
        'end_date' => $semester['end_date'],
        'average' => $subjectCount > 0 ? round($totalMarks / $subjectCount, 2) : 0,
        'total' => $totalMarks,
        'subject_count' => $subjectCount
    ];
}

// Calculate overall average
$overallTotal = 0;
$overallSubjectCount = 0;
foreach ($semesterAverages as $semester) {
    $overallTotal += $semester['total'];
    $overallSubjectCount += $semester['subject_count'];
}
$overallAverage = $overallSubjectCount > 0 ? round($overallTotal / $overallSubjectCount, 2) : 0;

    // Get ranking information with proper error handling
    $allStudentsTotalsStmt = $pdo->prepare("
        SELECT sm.student_id, SUM(sm.mark) AS total_mark
        FROM student_marks sm
        GROUP BY sm.student_id
        ORDER BY total_mark DESC
    ");
    $allStudentsTotalsStmt->execute();
    $allStudentsTotals = $allStudentsTotalsStmt->fetchAll(PDO::FETCH_ASSOC);

    $currentStudentTotal = $overallTotal;
    $rank = 1;
    foreach ($allStudentsTotals as $otherStudent) {
        if ($otherStudent['student_id'] == $_SESSION['student_id']) {
            break;
        }
        $rank++;
    }
    $totalStudents = count($allStudentsTotals);

    // Handle photo upload with enhanced security
    if (isset($_POST['upload_photo'])) {
        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $uploadError = "Invalid request";
        } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $maxFileSize = 2 * 1024 * 1024; // 2MB
            $uploadDir = '../uploads/';
            
            // Verify upload directory exists and is writable
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            
            if (!is_writable($uploadDir)) {
                $uploadError = "Upload directory is not writable";
            } else {
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $_FILES['photo']['tmp_name']);
                finfo_close($fileInfo);
                
                if (array_key_exists($mimeType, $allowedTypes) && 
                    $_FILES['photo']['size'] <= $maxFileSize) {
                    
                    $fileExt = $allowedTypes[$mimeType];
                    $newFileName = 'student_' . $_SESSION['student_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
                    $destination = $uploadDir . $newFileName;
                    
                    // Remove old photo if exists
                    if (!empty($student['photo']) && file_exists($student['photo'])) {
                        @unlink($student['photo']);
                    }
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                        // Update database with relative path
                        $updateStmt = $pdo->prepare("UPDATE students SET photo = :photo WHERE id = :id");
                        $updateStmt->execute([':photo' => $destination, ':id' => $_SESSION['student_id']]);
                        
                        // Update student data in memory
                        $student['photo'] = $destination;
                        
                        // Regenerate CSRF token after successful upload
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $uploadError = "Error uploading file";
                    }
                } else {
                    $uploadError = "Invalid file type (only JPG, PNG, GIF allowed) or size exceeds 2MB";
                }
            }
        } else {
            $uploadError = "No file uploaded or upload error occurred";
        }
    }

    // Handle photo deletion with CSRF protection
    if (isset($_POST['delete_photo'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $uploadError = "Invalid request";
        } else {
            if (!empty($student['photo']) && file_exists($student['photo'])) {
                @unlink($student['photo']);
            }
            
            $updateStmt = $pdo->prepare("UPDATE students SET photo = NULL WHERE id = :id");
            $updateStmt->execute([':id' => $_SESSION['student_id']]);
            
            // Regenerate CSRF token after successful deletion
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            header("Location: dashboard.php");
            exit();
        }
    }

    // Initialize CSRF token if not set
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $photo = $student['photo'] ?? null;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    die("An error occurred: " . htmlspecialchars($e->getMessage()));
}

// Determine active tab with sanitization
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['summary', 'subjects', 'marks']) 
    ? $_GET['tab'] 
    : 'summary';
if (isset($_GET['msg']) && $_GET['msg'] === 'pay_success') {
    echo "<h2 style='color: green;'>🎉 Payment was successful! Thank you for your support.</h2>";
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo htmlspecialchars($student['name']); ?></title>
    <meta name="description" content="Student dashboard for viewing academic performance and records">

    <!-- Preload resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" as="style">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css" as="style">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
        <link rel="stylesheet" href="../css/td.css">
    
    <!-- Inline CSS (minified) -->
    <style>
        :root{--primary-color:#4e73df;--secondary-color:#858796;--success-color:#1cc88a;--info-color:#36b9cc;--warning-color:#f6c23e;--danger-color:#e74a3b;--light-color:#f8f9fc;--dark-color:#5a5c69}[data-bs-theme=dark]{--primary-color:#4e73df;--secondary-color:#858796;--success-color:#1cc88a;--info-color:#36b9cc;--warning-color:#f6c23e;--danger-color:#e74a3b;--light-color:#2a3042;--dark-color:#d1d3e2;--body-bg:#1a1a2e;--card-bg:#16213e;--text-color:#f8f9fa}body{background-color:var(--body-bg,#f8f9fc);color:var(--text-color,#5a5c69);transition:all .3s ease}
       .main-content{background-color:var(--body-bg,#f8f9fc);padding:20px;width:100%}.card{background-color:var(--card-bg,#fff);border:none;box-shadow:0 .15rem 1.75rem 0 rgba(58,59,69,.1);margin-bottom:20px;transition:all .3s}.card-header{background-color:var(--card-bg,#f8f9fc);border-bottom:1px solid rgba(0,0,0,.1);font-weight:600}.profile-card{background-color:var(--card-bg,#fff);border-radius:.35rem;box-shadow:0 .15rem 1.75rem 0 rgba(58,59,69,.1);margin-bottom:20px;overflow:hidden}.profile-header{display:flex;align-items:center;padding:20px;background-color:var(--primary-color);color:#fff}.profile-photo-container{position:relative;margin-right:20px}.profile-photo{width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #fff}.no-photo{display:flex;align-items:center;justify-content:center;background-color:#ddd;color:#777;font-size:3rem}.profile-info h3{margin:0;font-weight:600}.profile-details{padding:20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px}.detail-item{background-color:rgba(0,0,0,.05);padding:10px;border-radius:5px}.detail-label{font-size:.8rem;color:var(--secondary-color);margin-bottom:5px}.detail-value{font-weight:600}.upload-container{padding:15px 20px;border-top:1px solid rgba(0,0,0,.1)}.subject-card{background-color:var(--card-bg,#fff);border-radius:.35rem;padding:15px;margin-bottom:15px;box-shadow:0 .15rem .5rem 0 rgba(58,59,69,.1);transition:transform .3s}.subject-card:hover{transform:translateY(-5px)}.subject-card h5{color:var(--primary-color);margin-bottom:10px}.subject-score{font-size:2rem;font-weight:700;color:var(--success-color);margin-bottom:10px}.subject-meta{display:flex;justify-content:space-between;font-size:.9rem;color:var(--secondary-color)}.progress{height:10px;border-radius:5px;background-color:rgba(0,0,0,.1)}.progress-bar{background-color:var(--primary-color)}.rank-display{background-color:var(--primary-color);color:#fff;padding:5px 15px;border-radius:20px;font-weight:600}.theme-toggle{cursor:pointer;padding:5px 10px;border-radius:5px;background-color:var(--card-bg);border:1px solid var(--secondary-color);color:var(--text-color)}.mark-detail{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px dashed rgba(0,0,0,.1)}.mark-detail:last-child{border-bottom:none}.mark-type{font-weight:600}.mark-value{color:var(--primary-color)}.semester-selector{margin-bottom:20px}.semester-btn{margin-right:10px;margin-bottom:10px}@media (max-width:768px){.sidebar{min-height:auto;width:100%}.profile-header{flex-direction:column;text-align:center}.profile-photo-container{margin-right:0;margin-bottom:15px}.profile-details{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="dashboard-container d-flex flex-column flex-lg-row">
        <!-- Sidebar Navigation -->
        <div class="sidebar flex-shrink-0">
            <div class="sidebar-header p-3 d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Student Portal</h3>
                <button class="theme-toggle btn btn-sm" id="themeToggle" aria-label="Toggle dark mode">
                    <i class="bi bi-moon-fill"></i>
                </button>
            </div>
            <ul  class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'summary' ? 'active' : ''; ?>" href="?tab=summary">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'marks' ? 'active' : ''; ?>" href="?tab=marks">
                        <i class="bi bi-list-check"></i> Detailed Marks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="rc.php">
                        <i class="bi bi-speedometer2"></i> Report Card
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cp.php">
                        <i class="bi bi-shield-lock"></i> Change Password
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white " href="chapa_payment.php">
                        <i class="bi bi-wallet2 me-2"></i> Donate
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
            <header class="header d-flex justify-content-between align-items-center mb-4">
                <h1>Student Dashboard</h1>
                <div class="rank-display">
                    Rank: #<?php echo htmlspecialchars($rank); ?> of <?php echo htmlspecialchars($totalStudents); ?>
                </div>
            </header>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-photo-container">
                        <?php if (!empty($photo) && file_exists($photo)): ?>
                            <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Student Photo">
                        <?php else: ?>
                            <div class="profile-photo no-photo" aria-hidden="true"><i class="bi bi-person"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['name']); ?></h2>
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
                    <!-- <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
                    </div> -->
                    <div class="detail-item">
                        <div class="detail-label">Username</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['username']); ?></div>
                    </div>
                </div>
                
                <div class="upload-container">
                    <h3>Update Profile Photo</h3>
                    <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control form-control-sm" name="photo" id="photo" accept="image/jpeg,image/png,image/gif" required>
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
                        <h2 class="mb-0">Academic Summary</h2>
                        <div>Overall Average: <strong><?php echo htmlspecialchars($overallAverage); ?>/100</strong></div>
                    </div>
                    <div class="card-body">
                        <?php if (count($semesterAverages) > 0): ?>
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="chart-container" style="position: relative; height:300px;">
                                        <canvas id="performanceChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h3 class="mb-0">Semester Averages</h3>
                                        </div>
                                        <div class="card-body">
                                            <?php ksort($semesterAverages) ?>
                                            <?php foreach ($semesterAverages as $semesterId => $semester): ?>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span><?php echo htmlspecialchars($semester['semester_name']); ?></span>
                                                        <span>Averege:<?php echo htmlspecialchars($semester['average']); ?></span>
                                                    </div>
                                                    <div class="progress" role="progressbar" aria-valuenow="<?php echo htmlspecialchars($semester['average']); ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="progress-bar" style="width: <?php echo htmlspecialchars($semester['average']); ?>%"></div>
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
                                                <h3 class="mb-0"><?php echo htmlspecialchars($semester['semester_name']); ?></h3>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($semester['average']); ?>/100
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container" style="position: relative; height:200px;">
                                                    <canvas id="semesterChart<?php echo $semesterId; ?>"></canvas>
                                                </div>
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

            <?php elseif ($activeTab === 'marks'): ?>
                <!-- Detailed Marks View -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">Detailed Marks</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($organizedMarks) > 0): ?>
                            <div class="semester-selector mb-4">
                                <h3>Select Semester:</h3>
                                <div class="d-flex flex-wrap">
                                    <?php ksort($organizedMarks); ?>
                                    <?php foreach ($organizedMarks as $semesterId => $semester): ?>
                                        <a href="#semester-<?php echo $semesterId; ?>" 
                                           class="btn btn-outline-primary btn-sm semester-btn">
                                            <?php echo htmlspecialchars($semester['semester_name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php foreach ($organizedMarks as $semesterId => $semester): ?>
                                <section class="mb-5" id="semester-<?php echo $semesterId; ?>">
                                    <h3 class="mb-3 border-bottom pb-2">
                                        <?php echo htmlspecialchars($semester['semester_name']); ?>
                                    </h3>
                                    
                                    <div class="row">
                                        <?php foreach ($semester['subjects'] as $subjectId => $subject): ?>
                                            <div class="col-md-6 mb-4">
                                                <div class="card h-100">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <h4 class="mb-0"><?php echo htmlspecialchars($subject['subject_name']); ?></h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php foreach ($subject['marks'] as $mark): ?>
                                                            <div class="mark-detail">
                                                                <span class="mark-type"><?php echo htmlspecialchars($mark['type']); ?></span>
                                                                <span class="mark-value"><?php echo htmlspecialchars($mark['mark']); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <div class="mark-detail mt-2 pt-2 border-top">
                                                        <span class="mark-type fw-bold">Total</span>
                                                        <span class="mark-value fw-bold">
                                                            <?php echo $subject['total']; ?>
                                                        </span>
                                                    </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No marks recorded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <script>
        // Theme toggle functionality
        document.getElementById('themeToggle').addEventListener('click', function() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const icon = this.querySelector('i');
            icon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        });

        // Initialize theme from localStorage
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            
            // Set correct icon
            const icon = document.getElementById('themeToggle').querySelector('i');
            icon.className = savedTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        })();

        // Initialize charts if they exist
        document.addEventListener('DOMContentLoaded', function() {
            // Performance chart
            const performanceCtx = document.getElementById('performanceChart');
            if (performanceCtx) {
                new Chart(performanceCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($semesterAverages as $semester): ?>
                '<?= addslashes($semester['semester_name']) ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Semester Average',
            data: [
                <?php foreach ($semesterAverages as $semester): ?>
                    <?= $semester['average'] ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.2)',
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#4e73df',
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Average: ' + context.raw.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                min: 0,
                max: 100,
                title: {
                    display: true,
                    text: 'Average (%)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Semester'
                }
            }
        }
    }
});

            }

            // Semester subject charts
            <?php foreach ($organizedMarks as $semesterId => $semester): ?>
                const ctx<?php echo $semesterId; ?> = document.getElementById('semesterChart<?php echo $semesterId; ?>');
                if (ctx<?php echo $semesterId; ?>) {
                    new Chart(ctx<?php echo $semesterId; ?>, {
                        type: 'bar',
                        data: {
                            labels: [
                                <?php foreach ($semester['subjects'] as $subject): ?>
                                    '<?php echo addslashes($subject['subject_name']); ?>',
                                <?php endforeach; ?>
                            ],
                            datasets: [{
                                label: 'Subject Total',
                                data: [
                                    <?php foreach ($semester['subjects'] as $subject): ?>
                                        <?php echo ($subject['total']); ?>,
                                    <?php endforeach; ?>
                                ],
                                backgroundColor: '#4e73df'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'Total: ' + context.raw.toFixed(2);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    min: 0,
                                    max: 100
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
<?php
session_start();
include 'config.php';
// $_SESSION['student_id']=$_POST['viewid'];
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

    // Get student marks
    $marksStmt = $pdo->prepare("
        SELECT sm.*, s.name AS subject_name, sub.name AS semester_name, sub.start_date AS semester_start_date
        FROM student_marks sm
        JOIN subjects s ON sm.subject_id = s.id
        JOIN semesters sub ON sm.semester_id = sub.id
        WHERE sm.student_id = :student_id
        ORDER BY sub.start_date, sub.name, s.name
    ");
    $marksStmt->execute([':student_id' => $_SESSION['student_id']]);
    $marks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate subject totals
    $subjectTotals = [];
    foreach ($marks as $mark) {
        $subjectKey = $mark['subject_id'];
        if (!isset($subjectTotals[$subjectKey])) {
            $subjectTotals[$subjectKey] = [
                'subject_name' => $mark['subject_name'],
                'total' => 0,
                'count' => 0
            ];
        }
        $subjectTotals[$subjectKey]['total'] += $mark['mark'];
        $subjectTotals[$subjectKey]['count']++;
    }

    // Calculate subject averages
    $subjectAverages = [];
    foreach ($subjectTotals as $subjectId => $data) {
        $subjectAverages[$subjectId] = [
            'subject_name' => $data['subject_name'],
            'average' => $data['count'] > 0 ? round($data['total'] / $data['count'], 2) : 0,
            'total' => $data['total']
        ];
    }

    // Calculate total marks and store them per semester
    $semesterTotals = [];
    foreach ($marks as $mark) {
        $semesterKey = $mark['semester_id'];
        if (!isset($semesterTotals[$semesterKey])) {
            $semesterTotals[$semesterKey] = [
                'total' => 0,
                'count' => 0,
                'semester_name' => $mark['semester_name'],
                'semester_start_date' => $mark['semester_start_date'],
            ];
        }
        $semesterTotals[$semesterKey]['total'] += $mark['mark'];
        $semesterTotals[$semesterKey]['count']++;
    }

    // Calculate average marks per semester
    $semesterAverages = [];
    foreach ($semesterTotals as $semesterId => $data) {
        $semesterAverages[$semesterId] = [
            'average' => $data['count'] > 0 ? round($data['total'] / $data['count'], 2) : 0,
            'semester_name' => $data['semester_name'],
            'semester_start_date' => $data['semester_start_date'],
            'total' => $data['total'],
        ];
    }

    // Get all students' total marks for ranking
    $allStudentsTotalsStmt = $pdo->prepare("
        SELECT sm.student_id, SUM(sm.mark) AS total_mark
        FROM student_marks sm
        GROUP BY sm.student_id
    ");
    $allStudentsTotalsStmt->execute();
    $allStudentsTotals = $allStudentsTotalsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate rank for the current student
    $currentStudentTotal = 0;
    foreach ($marks as $mark) {
        $currentStudentTotal += $mark['mark'];
    }

    $rank = 1;
    foreach ($allStudentsTotals as $otherStudent) {
        if ($otherStudent['total_mark'] > $currentStudentTotal) {
            $rank++;
        }
    }

    // Handle photo upload
    if (isset($_POST['upload_photo']) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        $uploadDir = '../uploads'; // Ensure this directory exists and is writable

        if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxFileSize) {
            $fileExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('student_') . '.' . $fileExt;
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
            $uploadError = "Invalid file type or size.";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <meta name="description" content="Administrative dashboard for managing student records and school events">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Student Portal</h3>
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
                    <a class="nav-link" href="detail.php">
                        <i class="bi bi-speedometer2"></i> tttt
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
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h2>Student Dashboard</h2>
                <div class="rank-display">
                    Rank: #<?php echo htmlspecialchars($rank); ?>
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
                        <div class="detail-label">Username</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['username']); ?></div>
                    </div>
                </div>
                
                <div class="upload-container">
                    <h6>Update Profile Photo</h6>
                    <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control form-control-sm" name="photo" id="photo" accept="image/*">
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
                    <div class="card-header">
                        <h5>Academic Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($semesterAverages) > 0): ?>
                            <div class="row">
                                <?php foreach ($semesterAverages as $semesterId => $semester): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($semester['semester_name']); ?> </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="text-muted">Average Score</span>
                                                    <span class="font-weight-bold"><?php echo htmlspecialchars($semester['average']); ?>/100</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo htmlspecialchars($semester['average']); ?>%" aria-valuenow="<?php echo htmlspecialchars($semester['average']); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="mt-3">
                                                    <span class="text-muted">Total Marks: </span>
                                                    <span class="font-weight-bold"><?php echo htmlspecialchars($semester['total']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No academic data available.</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($activeTab === 'subjects'): ?>
                <!-- Subjects View -->
                <div class="card">
                    <div class="card-header">
                        <h5>Subject Performance</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($subjectAverages) > 0): ?>
                            <div class="row">
                                <?php foreach ($subjectAverages as $subjectId => $subject): ?>
                                    <div class="col-md-4">
                                        <div class="subject-card">
                                            <h5><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                            <div class="subject-score"><?php echo htmlspecialchars($subject['average']); ?></div>
                                            <div class="subject-meta">
                                                <span>Average Score</span>
                                                <span>Total: <?php echo htmlspecialchars($subject['total']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No subject data available.</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($activeTab === 'marks'): ?>
                <!-- Detailed Marks View -->
                <div class="card">
                    <div class="card-header">
                        <h5>Detailed Marks</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($marks) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Semester</th>
                                            <th>Subject</th>
                                            <th>Assessment Type</th>
                                            <th>Mark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($marks as $mark): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('Y', strtotime($mark['semester_start_date']))); ?></td>
                                                <td><?php echo htmlspecialchars($mark['semester_name']); ?></td>
                                                <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($mark['mark_type']); ?></td>
                                                <td><?php echo htmlspecialchars($mark['mark']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No marks recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple script to highlight active nav item
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
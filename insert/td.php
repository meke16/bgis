<?php
session_start();
// Include database configuration
require_once '../connect.php';
// Check authentication
if (!isset($_SESSION['user']['authenticated']) || $_SESSION['user']['authenticated'] !== true) {
    header("Location: index.php");
    exit();
}

// Check role
if ($_SESSION['user']['role'] !== 'teacher') {
    header("Location: unauthorized.php");
    exit();
}


$teacher_id = $_SESSION['user']['id'];
$current_year = date('Y') . '-' . (date('Y') + 1);

// Get teacher's current assignments
$assignments = [];
$stmt = $conn->prepare("SELECT 
                        s.name AS subject_name, 
                        ta.grade, 
                        ta.section,
                        ta.academic_year
                    FROM teacher_assignments ta
                    JOIN subjects s ON ta.subject_id = s.id
                    WHERE ta.teacher_id = ? AND ta.academic_year = ?
                    ORDER BY ta.grade, ta.section");
$stmt->bind_param("is", $teacher_id, $current_year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}

// Fetch the teacher's photo path from the database
$query = "SELECT photo FROM teachers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch the associative array
$teacher = $result->fetch_assoc();

// Default to 'default-profile.jpg' if no photo is available
$photo = $teacher['photo'] ?? 'default-profile.jpg';

// Close the statement
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --secondary-color: #f7f7f7;
            --text-color: #333;
            --light-gray: #e9ecef;
            --border-radius: 8px;
            --box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
        }

        .sidebar {
            background-color: #343a40;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 10px;
            padding: 12px 18px;
            border-radius: 6px;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px 20px;
        }

        .profile-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin-bottom: 15px;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }

        .assignment-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .announcement-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .announcement-date {
            font-size: 0.8rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar col-md-3 col-lg-2 d-md-block">
        <div class="text-center mb-4">
            <img src="../teacher/<?= htmlspecialchars($photo) ?>" alt="Teacher Photo" class="profile-img">
            <h5><?= htmlspecialchars($_SESSION['user']['name']) ?></h5>
            <p class="text-muted small">Teacher</p>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="teacher_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="classes.php">
                    <i class="fas fa-users"></i> My Classes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="marks.php">
                    <i class="fas fa-clipboard-check"></i> Record Marks
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="attendance.php">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="messages.php">
                    <i class="fas fa-envelope"></i> Messages
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="index.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Teacher Dashboard</h2>
            <div class="text-muted">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= date('l, F j, Y') ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <h4 class="card-title">Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</h4>
                        <p class="card-text">You have <?= count($assignments) ?> active class assignments this academic year.</p>
                        <a href="classes.php" class="btn btn-primary">View My Classes</a>
                    </div>
                    <div class="col-md-2 text-center">
                        <img src="../teacher/<?= htmlspecialchars($photo) ?>" alt="Teaching" class="img-fluid" style="max-height: 120px;">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-book-open me-2"></i> My Current Assignments
                    </div>
                    <div class="card-body">
                        <?php if (count($assignments) > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($assignments as $assignment): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6><?= htmlspecialchars($assignment['subject_name']) ?></h6>
                                            <small class="text-muted">
                                                Grade <?= $assignment['grade'] ?> - Section <?= $assignment['section'] ?>
                                            </small>
                                        </div>
                                        <span class="assignment-badge"><?= $assignment['academic_year'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info">
                                You don't have any assignments for the current academic year.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <a href="marks.php?action=new" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                    Record Marks
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="attendance.php?action=take" class="btn btn-outline-success w-100 py-3">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i><br>
                                    Take Attendance
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="reports.php" class="btn btn-outline-info w-100 py-3">
                                    <i class="fas fa-chart-pie fa-2x mb-2"></i><br>
                                    View Reports
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="messages.php?action=new" class="btn btn-outline-warning w-100 py-3">
                                    <i class="fas fa-envelope fa-2x mb-2"></i><br>
                                    Send Message
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Activate tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>

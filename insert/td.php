<?php
session_start();
include 'session.php';
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
                        ta.section
                    FROM teacher_assignments ta
                    JOIN subjects s ON ta.subject_id = s.id
                    WHERE ta.teacher_id = ? 
                    ORDER BY ta.grade, ta.section");
$stmt->bind_param("i", $teacher_id);
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
// Handle photo upload
if (isset($_POST['upload_photo']) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 10 * 1024 * 1024;
    $uploadDir = '../uploads/';

    if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxFileSize) {
        $fileExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('teacher_') . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
            // Update photo in the database
            $updateStmt = $conn->prepare("UPDATE teachers SET photo = ? WHERE id = ?");
            $updateStmt->bind_param("si", $destination, $_SESSION['user']['id']); // "si" means string for photo and integer for id
            if ($updateStmt->execute()) {
                header("Location:td.php");
                exit();
            } else {
                $uploadError = "Error updating database.";
            }
        } else {
            $uploadError = "Error uploading file.";
        }
    } else {
        $uploadError = "Invalid file type or size.";
    }
}

if (isset($_POST['delete_photo'])) {
    // Update the database to set the photo to NULL
    $updateStmt = $conn->prepare("UPDATE teachers SET photo = NULL WHERE id = ?");
    $updateStmt->bind_param("i", $_SESSION['user']['id']);
    
    if ($updateStmt->execute()) {
        header("Location: td.php");
        exit();
    } else {
        $uploadError = "Error updating database.";
    }
}

// Default to 'default-profile.jpg' if no photo is available
$photo = $teacher['photo'] ?? 'default-profile.jpg';

// Close the statement
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/td.css">

</head>
<body>
    <!-- Mobile Navbar -->
    <nav class="navbar d-lg-none">
        <div class="container-fluid">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="#]">EduPortal</a>
            <div class="d-flex align-items-center">
                <div class="theme-toggle me-3" id="themeToggle">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                    <span class="toggle-thumb"></span>
                </div>
                <div class="dropdown">
                    <!-- <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false"> -->
                        <?php if (!empty($photo) && file_exists($photo)): ?>
                            <a href="#" class="d-flex align-items-center text-decoration-none " id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo htmlspecialchars($photo); ?>" width="32" height="32" class="rounded-circle me-2">
                            </a>
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        <?php endif; ?>
                    <!-- </a> -->
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Manage Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-lg-block" id="sidebar">
            <div class="text-center mb-4 px-3">
                <?php if (!empty($photo) && file_exists($photo)): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-img" alt="Teacher Photo">
                <?php else: ?>
                    <div class="no-photo"><i class="fas fa-user-tie"></i></div>
                <?php endif; ?>
                <h5 class="mb-1"><?= htmlspecialchars($_SESSION['user']['name']) ?></h5>
                <p class="text-white-50 small mb-3">Teacher</p>
                
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <div class="theme-toggle me-3 d-none d-lg-flex" id="desktopThemeToggle">
                        <i class="fas fa-sun"></i>
                        <i class="fas fa-moon"></i>
                        <span class="toggle-thumb"></span>
                    </div>
                    <span class="badge bg-white text-primary"><?= $current_year ?></span>
                </div>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="td.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mark_type.php">
                        <i class="fas fa-clipboard-check"></i> Setup Mark Type
                    </a>
                </li>             
                <li class="nav-item">
                    <a class="nav-link" href="marks.php">
                        <i class="fas fa-clipboard-check"></i> Record Marks
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="cp.php">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </li> -->
                <li class="nav-item mt-2">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user-circle"></i> profiles
                    </a>
                </li>
                <li class="nav-item mt-2">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-gradient">Teacher Dashboard</h2>
                <div class="text-muted d-none d-lg-block">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="card welcome-card mb-4 border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="card-title">Welcome back, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</h4>
                            <p class="card-text opacity-75">You have <?= count($assignments) ?> active class assignments this academic year.</p>
                            <a href="marks.php" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-right me-1"></i> Record Marks
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Assignments Card -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-book-open"></i> My Current Assignments
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($assignments) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($assignments as $assignment): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($assignment['subject_name']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i> Grade <?= $assignment['grade'] ?> - Section <?= $assignment['section'] ?>
                                                </small>
                                            </div>
                                            <span class="assignment-badge"><?= $current_year ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info m-3">
                                    <i class="fas fa-info-circle me-2"></i> You don't have any assignments for the current academic year.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 w-100">
                        <div class="card-header">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="marks.php?action=new" class="quick-action-btn">
                                        <i class="fas fa-edit text-success"></i>
                                        <span>Record Marks</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="profile.php" class="quick-action-btn">
                                        <i class="fas fa-id-card text-warning"></i>
                                        <span>Manage Your Profile</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="mark_type.php" class="quick-action-btn">
                                        <i class="fas fa-plus text-info"></i>
                                        <span>Setup Mark Type</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="logout.php" class="quick-action-btn">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Logout</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme toggle functionality
            const themeToggle = document.getElementById('themeToggle');
            const desktopThemeToggle = document.getElementById('desktopThemeToggle');
            const html = document.documentElement;
            
            function setTheme(theme) {
                html.setAttribute('data-bs-theme', theme);
                localStorage.setItem('theme', theme);
            }
            
            function toggleTheme() {
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                setTheme(newTheme);
            }
            
            // Initialize theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);
            
            // Add event listeners
            if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
            if (desktopThemeToggle) desktopThemeToggle.addEventListener('click', toggleTheme);
            
            // Mobile menu toggle
                // Toggle sidebar on mobile
            // document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            //     document.getElementById('sidebar').classList.toggle('active');
            // });

            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileMenuBtn && sidebar) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    new bootstrap.Alert(alert).close();
                });
            }, 5000);
            
            // Add animation to cards on page load
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
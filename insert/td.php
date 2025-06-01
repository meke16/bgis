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

    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary: #94a3b8;
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            
            --bg-light: #ffffff;
            --bg-dark: #0f172a;
            --card-light: #ffffff;
            --card-dark: #1e293b;
            --text-light: #334155;
            --text-dark: #f8fafc;
            --border-light: #e2e8f0;
            --border-dark: #334155;
        }

        [data-bs-theme="dark"] {
            --bs-body-bg: var(--bg-dark);
            --bs-body-color: var(--text-dark);
            --bs-border-color: var(--border-dark);
        }

        [data-bs-theme="light"] {
            --bs-body-bg: var(--bg-light);
            --bs-body-color: var(--text-light);
            --bs-border-color: var(--border-light);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            transition: all 0.3s ease;
        }

        .sidebar {
            background:var(--border-dark);            ;
            min-height: 100vh;
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 280px;
            z-index: 1000;
        }
        .sidebar.show {
        transform: translateX(0);
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 1.1rem;
            text-align: center;
        }

        .main-content {
            width: 100%;
            padding: 2rem;
            margin-left: 280px;
            transition: margin 0.3s ease;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: var(--bs-card-bg);
            margin-bottom: 1.5rem;
            border: 1px solid var(--bs-border-color);
        }

        [data-bs-theme="light"] .card {
            --bs-card-bg: var(--card-light);
        }

        [data-bs-theme="dark"] .card {
            --bs-card-bg: var(--card-dark);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--bs-border-color);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
        }

        .card-header i {
            margin-right: 0.75rem;
            color: var(--primary);
        }

        .assignment-badge {
            background-color: var(--primary);
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .quick-action-btn {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            padding: 1.5rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            border: 1px solid var(--bs-border-color);
            background-color: var(--bs-card-bg);
        }

        .quick-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            color: var(--primary);
            border-color: var(--primary);
        }

        .quick-action-btn i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        .theme-toggle {
            background: var(--bs-card-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 2rem;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            position: relative;
            width: 60px;
            height: 30px;
        }

        .theme-toggle i {
            font-size: 1rem;
            z-index: 2;
            padding: 0.25rem;
        }

        .theme-toggle .toggle-thumb {
            position: absolute;
            background-color: var(--primary);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            left: 3px;
            transition: transform 0.3s ease;
            z-index: 1;
        }

        [data-bs-theme="dark"] .theme-toggle .toggle-thumb {
            transform: translateX(30px);
        }

        .list-group-item {
            background-color: var(--bs-card-bg);
            border-color: var(--bs-border-color);
            padding: 1.25rem;
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            background-color: rgba(var(--primary-light), 0.1);
        }

        .no-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 1rem;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            overflow: hidden;
        }

        .welcome-card::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        .welcome-card .card-body {
            position: relative;
            z-index: 1;
        }

        .navbar {
            background-color: var(--bs-card-bg);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--bs-body-color);
        }

        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: block !important;
            }
            .sidebar {
                display: none;
            }
            .sidebar.show {
                display: block;
            }
        }
        
    </style>
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
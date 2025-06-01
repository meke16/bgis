<?php
// Start session and check authenticatio
session_start();
include 'connect.php';
// include("session.php");

// Get admin username securely
$stmt = $conn->prepare("SELECT username FROM admin WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

// Get male and female counts for each grade from the students table with prepared statements
$grades = [9, 10, 11, 12];
$counts = [];

foreach ($grades as $grade) {
    // Validate table name to prevent SQL injection
    if (!in_array($grade, $grades)) continue;

    $maleCount = $conn->query("SELECT COUNT(*) FROM students WHERE grade='$grade' AND sex = 'Male'")->fetch_row()[0];
    $femaleCount = $conn->query("SELECT COUNT(*) FROM students WHERE grade='$grade' AND sex = 'Female'")->fetch_row()[0];
    // Store the counts in the $counts array
    $counts[$grade] = [
        'male' => $maleCount,
        'female' => $femaleCount,
        'total' => $maleCount + $femaleCount
    ];
}

$current_year = date('Y') . '-' . (date('Y') + 1);

?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System | Dashboard</title>
    <meta name="description" content="Administrative dashboard for managing student records and school events">

    <!-- Favicon -->
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <style>

    </style>
</head>

<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h3><i class="bi bi-mortarboard-fill logo-icon"></i>BGIS SchoolSystem</h3>
        </div>

        <div class="sidebar-menu">
            <div class="menu-title">Main Navigation</div>
            <a href="home.php" class="menu-item active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <div class="menu-title">Subject Management</div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#sregisterMenu" aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Manage Subject. <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="sregisterMenu">
                <a href="subject/add.php" class="menu-item"><i></i>Add Here</a>
            </div>
            <div class="menu-title">Semester Management</div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#ssregisterMenu" aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Manage Semster. <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="ssregisterMenu">
                <a href="subject/sem.php" class="menu-item"><i></i>Add Semester</a>
            </div>

            <div class="menu-title">Teacher Management</div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#tregisterMenu" aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Register Teacher. <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="tregisterMenu">
                <a href="teacher/teachers_view.php?=success" class="menu-item"><i></i>Register Teacher</a>
            </div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#tMenu" aria-expanded="false">
                <i class="bi bi-people-fill"></i> Teacher Records <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="tMenu">
                <a href="teacher/grade.php?grade=9" class="menu-item"><i class="bi "></i> Grade 9</a>
                <a href="teacher/grade.php?grade=10" class="menu-item"><i class="bi bi-10-circle"></i> Grade 10</a>
                <a href="teacher/grade.php?grade=11" class="menu-item"><i class="bi bi-11-circle"></i> Grade 11</a>
                <a href="teacher/grade.php?grade=12" class="menu-item"><i class="bi bi-12-circle"></i> Grade 12</a>
            </div>

            <div class="menu-title">Student Management</div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#registerMenu" aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Register Student. <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="registerMenu">
                <a href="students/registerAll.php?=success" class="menu-item"><i></i>Register Student</a>
            </div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#recordsMenu" aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Student Records <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="recordsMenu">
                <a href="students/student.php?grade=9" class="menu-item"><i class=""></i> Grade 9</a>
                <a href="students/student.php?grade=10" class="menu-item"><i class=""></i> Grade 10</a>
                <a href="students/student.php?grade=11" class="menu-item"><i class=""></i> Grade 11</a>
                <a href="students/student.php?grade=12" class="menu-item"><i class=""></i> Grade 12</a>
            </div>

            <div class="menu-title">Academic Performance</div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#marksMenu" aria-expanded="false">
                <i class="bi bi-graph-up"></i> Student Marks <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="marksMenu">
                <a href="allMarks/display.php" class="menu-item"><i class="bi bi-percent"></i> SUMMARY-MARKS</a>
            </div>

            <div class="menu-title">Analytics Dashboard</div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#maMenu" aria-expanded="false">
                <i class="bi bi-bar-chart-line-fill"></i> Analytical Summary <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="maMenu">
                <a href="analytical/analytic.php" class="menu-item"><i class="bi bi-square"></i>List</a>
                <a href="analytical/analytics.php" class="menu-item"><i class="bi bi-square"></i>Graphical</a>
            </div>

            <div class="menu-title">Account</div>
            <a href="logout.php" class="menu-item"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-end align-items-end mt-3 mb-3 ">
            <div class="theme-toggle me-3" id="desktopThemeToggle">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <span class="toggle-thumb"></span>
            </div>
        </div>
           
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="toast-container">
                <div class="toast show align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <?php echo $_SESSION['flash_message']; ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <!-- Top Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
            <div class="container-fluid">
                <button class="menu-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div  class="d-flex align-items-center ms-auto ">
                    <div class="user-info me-3">
                        <?php $name = $conn->query('SELECT name from admin')->fetch_row()[0]; ?>
                        <?php $sex = $conn->query('SELECT sex from admin')->fetch_row()[0]; ?>
                        <!-- <img src="https://ui-avatars.com/api/?name=<?php echo "AD"; ?>&background=<?php echo (substr(md5($name), 0, 6)); ?>&color=fff" alt="<?php echo $name ?>" class="user-avatar"> -->
                        <span class="name"><?php echo ($sex=="Male" ? "Mr." : "Mrs.").htmlspecialchars($name); ?></span>
                        <span id="date-time" class="date"><?= date('Y/M/D') ?> </span><span  class="time" id="current-time"></span>
                    </div>
                    <!-- <a href="logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Logout</span>
                    </a> -->
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="container-fluid animate-fade-in">
            <h1 class="page-title">
                <i class="bi bi-speedometer2"></i> Dashboard Overview
            </h1>

            <!-- Gender Distribution Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title"><i class="bi bi-gender-male"></i> Gender Distribution Across Grades</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($grades as $grade):
                            $gradeName = "Grade-$grade";
                        ?>
                            <div class="col-md-3 col-sm-6 mb-4">
                                <h5 class="text-center mb-3"><?php echo $gradeName; ?></h5>
                                <hr style="border:solid grey">
                                <div class="chart-container">
                                    <canvas id="<?php echo $grade; ?>Chart"></canvas>
                                </div>
                                <hr>
                                <div class="text-center mt-3">
                                    <!-- <div class="d-flex justify-content-center mb-1"> -->
                                        <span class="badge bg-primary">Male: <?php echo $counts[$grade]['male']; ?></span>
                                        <span class="badge bg-danger">Female: <?php echo $counts[$grade]['female']; ?></span>
                                        <span class="badge bg-dark">Total: <?php echo $counts[$grade]['total']; ?></span>
                                    <!-- </div> -->
                                </div>
                                <hr>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Row -->
            <div class="row g-4 mb-6">
                <div class="col-md-4">
                    <div class="stat-card bg-primary">
                        <i class="bi bi-people-fill stat-icon"></i>
                        <p class="stat-title">Total Students</p>
                        <h3 class="stat-value">
                            <?php
                            $total = 0;
                            foreach ($grades as $grade) {
                                $total += $counts[$grade]['total'];
                            }
                            echo $total;
                            ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-success">
                        <i class="bi bi-gender-male stat-icon"></i>
                        <p class="stat-title">Male Students</p>
                        <h3 class="stat-value">
                            <?php
                            $maleTotal = 0;
                            foreach ($grades as $grade) {
                                $maleTotal += $counts[$grade]['male'];
                            }
                            echo $maleTotal;
                            ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-warning">
                        <i class="bi bi-gender-female stat-icon"></i>
                        <p class="stat-title">Female Students</p>
                        <h3 class="stat-value">
                            <?php
                            $femaleTotal = 0;
                            foreach ($grades as $grade) {
                                $femaleTotal += $counts[$grade]['female'];
                            }
                            echo $femaleTotal;
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>
<script>
        function updateTime() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-GB'); // 24-hour format
            const timeElement = document.getElementById('current-time');
                    timeElement.textContent = time;
        
        // Apply direct styles
        timeElement.style.color = '#2ecc71';
        timeElement.style.fontWeight = 'bold';
        timeElement.style.marginLeft = '9px';         
       }

            updateTime(); // initial call
            setInterval(updateTime, 1000); // update every second
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            const counts = <?php echo json_encode($counts); ?>;
            const gradeColors = {
                '9': ['#3498db', '#e74c3c'],
                '10': ['#2ecc71', '#9b59b6'],
                '11': ['#f39c12', '#1abc9c'],
                '12': ['#e67e22', '#34495e']
            };

            <?php foreach ($grades as $grade): ?>
                {
                    const ctx = document.getElementById('<?php echo "$grade"; ?>Chart').getContext('2d');
                    const maleCount = counts['<?php echo $grade; ?>']['male'];
                    const femaleCount = counts['<?php echo $grade; ?>']['female'];
                    const totalCount = maleCount + femaleCount;
                    const colors = gradeColors['<?php echo $grade; ?>'];

                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Male', 'Female'],
                            datasets: [{
                                data: [maleCount, femaleCount],
                                backgroundColor: colors,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const percentage = Math.round((value / totalCount) * 100);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            },
                            cutout: '70%'
                        }
                    });
                }
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
<?php
// Start session and check authentication
include 'connect.php';
include("session.php");
 
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
$mame = $conn->query('SELECT name from admin')->fetch_row()[0];

// Now you can use $counts to access the male, female, and total count for each grade
?>

<!DOCTYPE html>
<html lang="en">

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

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --sidebar-width: 280px;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Segoe UI', 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
            padding-left: var(--sidebar-width);
            transition: padding-left var(--transition-speed);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
            transition: transform var(--transition-speed);
        }

        .sidebar-brand {
            padding: 1.5rem 1rem;
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background-color: rgba(0, 0, 0, 0.1);
        }

        .sidebar-brand h3 {
            margin-bottom: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-brand .logo-icon {
            font-size: 1.75rem;
            margin-right: 0.5rem;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .menu-title {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 1.5rem;
            margin: 1rem 0 0.5rem;
            font-weight: 600;
        }

        .menu-item {
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all var(--transition-speed);
            border-left: 3px solid transparent;
            margin: 0.25rem 0;
        }

        .menu-item:hover,
        .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid var(--accent-color);
        }

        .menu-item i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .menu-item .menu-arrow {
            margin-left: auto;
            transition: transform var(--transition-speed);
        }

        .menu-item[aria-expanded="true"] .menu-arrow {
            transform: rotate(90deg);
        }

        .submenu {
            padding-left: 0;
            background: rgba(0, 0, 0, 0.1);
            max-height: 0;
            overflow: hidden;
            transition: max-height var(--transition-speed);
        }

        .submenu.show {
            max-height: 500px;
        }

        .submenu .menu-item {
            padding: 0.5rem 1rem 0.5rem 3.5rem;
            font-size: 0.9rem;
            border-left: none;
        }

        /* Main Content Styles */
        .main-content {
            padding: 2rem;
            transition: margin-left var(--transition-speed);
        }

        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            position: sticky;
            top: 0;
            z-index: 999;
            transition: all var(--transition-speed);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-right: 0.75rem;
            color: var(--accent-color);
        }

        .card {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-bottom: none;
        }

        .card-header .card-title {
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }

        .card-header .card-title i {
            margin-right: 0.75rem;
        }

        .chart-container {
            position: relative;
            height: 250px;
            padding: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid var(--light-color);
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
        }

        .logout-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .logout-btn i {
            margin-right: 0.5rem;
        }

        /* Stats Cards */
        .stat-card {
            border-radius: 10px;
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .stat-card .stat-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        /* Events table styles */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
            padding: 1rem;
        }

        .table tbody tr {
            transition: background-color var(--transition-speed);
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            font-size: 0.85em;
        }

        .badge-primary {
            background-color: var(--primary-color);
        }

        .badge-secondary {
            background-color: var(--secondary-color);
        }

        .badge-success {
            background-color: var(--success-color);
        }

        .badge-warning {
            background-color: var(--warning-color);
        }

        .badge-danger {
            background-color: var(--danger-color);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #1a252f;
            border-color: #1a252f;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Modal styles */
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close-white {
            filter: invert(1);
        }

        /* Toast notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        .toast {
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: none;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .navbar-custom {
                width: 100%;
                margin-left: 0;
            }

            .menu-toggle {
                display: block !important;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.75rem;
            }

            .stat-card .stat-value {
                font-size: 1.75rem;
            }

        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
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
                <a href="subject/add.php" class="menu-item"><i></i><button class="btn btn-info">Add Here</button></a>
            </div>

            <div class="menu-title">Teacher Management</div>
            <a href="#" class="menu-item" data-bs-toggle="collapse" data-bs-target="#tregisterMenu" aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Register Teacher. <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse hide submenu" id="tregisterMenu">
                <a href="teacher/teachers_view.php?=success" class="menu-item"><i></i><button class="btn btn-info">Register Teacher</button></a>
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
                <a href="students/registerAll.php" class="menu-item"><i></i><button class="btn btn-info">Register Student</button></a>
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
                <a href="analytical/analytic9.php" class="menu-item"><i class="bi bi-9-square"></i> Grade 9</a>
                <a href="analytical/analytic10.php" class="menu-item"><i class="bi bi-10-square"></i> Grade 10</a>
                <a href="analytical/analytic11.php" class="menu-item"><i class="bi bi-11-square-fill"></i> Grade 11</a>
                <a href="analytical/analytic12.php" class="menu-item"><i class="bi bi-12-square-fill"></i> Grade 12</a>
            </div>

            <div class="menu-title">Account</div>
            <a href="logout.php" class="menu-item"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
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

                <div class="d-flex align-items-center ms-auto">
                    <div class="user-info me-3">
                    <?php $name = $conn->query('SELECT name from admin')->fetch_row()[0]; ?>
                        <img src="https://ui-avatars.com/api/?name=<?php echo 'Admin'; ?>&background=<?php echo substr(md5($name), 0, 6); ?>&color=fff" alt="<?php echo $name ?>" class="user-avatar">
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($name); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Logout</span>
                    </a>
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
                                <div class="chart-container">
                                    <canvas id="<?php echo $grade; ?>Chart"></canvas>
                                </div>
                                <div class="text-center mt-3">
                                    <div class="d-flex justify-content-center mb-1">
                                        <span class="badge bg-primary me-2">Male: <?php echo $counts[$grade]['male']; ?></span>
                                        <span class="badge bg-danger me-2">Female: <?php echo $counts[$grade]['female']; ?></span>
                                        <span class="badge bg-dark">Total: <?php echo $counts[$grade]['total']; ?></span>
                                    </div>
                                </div>
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

            <!-- JavaScript Libraries -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <script>
                // Initialize charts for all grades
                document.addEventListener('DOMContentLoaded', function() {
                    const counts = <?php echo json_encode($counts); ?>;
                    const gradeColors = {
                        '9': ['#3498db', '#e74c3c'],
                        '10': ['#2ecc71', '#9b59b6'],
                        '11': ['#f39c12', '#1abc9c'],
                        '12': ['#e67e22', '#34495e']
                    };

                    <?php foreach ($grades as $grade): ?> {
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
                    // Toggle sidebar on mobile
                    document.getElementById('sidebarToggle').addEventListener('click', function() {
                        document.querySelector('.sidebar').classList.toggle('active');
                    });
                    // Auto-hide toast notifications after 5 seconds
                    const toasts = document.querySelectorAll('.toast');
                    toasts.forEach(toast => {
                        setTimeout(() => {
                            toast.classList.remove('show');
                        }, 5000);
                    });
                });
            </script>
</body>

</html>
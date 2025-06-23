<?php
include 'teachers.php';
$grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 9;

$sql = "SELECT t.*, 
               GROUP_CONCAT(CONCAT('Grade ', ta.grade, ' - ', ta.section, ' (', s.name, ')') SEPARATOR '<br>') AS assignments_info
        FROM teachers t
        JOIN teacher_assignments ta ON ta.teacher_id = t.id
        LEFT JOIN subjects s ON ta.subject_id = s.id
        WHERE t.role = 'teacher'
          AND t.name LIKE ?
          AND ta.grade = ?
        GROUP BY t.id
        ORDER BY t.name";

$stmt = $conn->prepare($sql);
$likeQuery = "%$searchQuery%";
$stmt->bind_param("si", $likeQuery, $grade);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/grade.css">
    <link rel="stylesheet" href="../css/teacher.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar no-print">
        <div class="sidebar-brand">
            <h3><i class="bi bi-mortarboard-fill logo-icon"></i>BGIS SchoolSystem</h3>
        </div>
        <hr>
        <div class="sidebar-menu">
            <div class="menu-title">Main Navigation</div>
            <a href="../home.php" class="menu-item">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <hr>

            <div class="menu-title">Teacher Management</div>
            <div class="menu-item">
                <i class="bi bi-people-fill"></i> Register Teacher
            </div>
            <div class="show submenu">
                <a href="../teacher/teachers_view.php?grade=0" class="menu-item <?= $grade == 0 ? 'active' : '' ?>"> Register Teacher</a>
            </div>
            <hr>
            <br>
            <div class="menu-item">
                <i class="bi bi-people-fill"></i> Teacher Records
            </div>
            <div class="show submenu">
                <a href="grade.php?grade=9" class="menu-item <?= $grade == 9 ? 'active' : '' ?>"> Grade 9</a>
                <a href="grade.php?grade=10" class="menu-item <?= $grade == 10 ? 'active' : '' ?>"> Grade 10</a>
                <a href="grade.php?grade=11" class="menu-item <?= $grade == 11 ? 'active' : '' ?>"> Grade 11</a>
                <a href="grade.php?grade=12" class="menu-item <?= $grade == 12 ? 'active' : '' ?>"> Grade 12</a>
            </div>
            <hr>
        </div>

        <!-- Print Button in Sidebar -->
        <button class="print-btn no-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Print Records
        </button>
        <hr>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header style="height: 150px;" class="header text-center">
            <div class="container-fluid d-flex align-items-center">
                <button class="menu-toggle no-print" id="menuToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="text-center flex-grow-1">
                    <h1>Teacher Records Grade <?php echo $grade ?></h1>
                    <p class="lead mb-0">bgis Secondary School</p>
                </div>
            </div>
        </header>

        <div class="container-fluid">
            <div class="search-box no-print mb-4">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="Search students..."
                            name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="btn btn-info" name="search">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <a href="./teachers_view.php" class="btn btn-info w-100">
                        <i class="bi bi-table"></i> To Register New student
                    </a>
                </div>
            </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Teacher Name</th>
                            <th>Gender</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th class="no-print">Phone</th>
                            <th style="width: 50px;">Photo</th>
                            <th class="no-print text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                $num++;
                                $id = $row['id'];
                                $name = $row['name'];
                                $sex = $row['sex'];
                                $contact = $row['contact'];
                                $photo = !empty($row['photo']) ? $row['photo'] : 'assets/default-profile.jpg';
                                // Extract assignments info into an array for easier handling
                                $assignments = explode('<br>', $row['assignments_info']);
                                ?>
                                <tr>
                                    <td data-label="#"><?= $num; ?></td>
                                    <td data-label="Teacher Name"><?= htmlspecialchars($name); ?></td>
                                    <td data-label="Gender"><?= htmlspecialchars($sex); ?></td>
                                    <!-- Grade Column with Colorful Badges -->
                                    <td data-label="Grade">
                                        <?php
                                        foreach ($assignments as $assignment):
                                            preg_match('/Grade (\d+) - ([A-Z]) \((.*?)\)/', $assignment, $matches);
                                            if ($matches):
                                                $grade = $matches[1];
                                        ?>
                                                <span class="badge bg-primary"><?= $grade; ?></span><br>
                                        <?php endif;
                                        endforeach; ?>
                                    </td>
                                    <!-- Section Column with Colorful Badges -->
                                    <td data-label="Section">
                                        <?php
                                        foreach ($assignments as $assignment):
                                            preg_match('/Grade (\d+) - ([A-Z]) \((.*?)\)/', $assignment, $matches);
                                            if ($matches):
                                                $section = $matches[2];
                                        ?>
                                                <span class="badge bg-success"><?= $section; ?></span><br>
                                        <?php endif;
                                        endforeach; ?>
                                    </td>
                                    <!-- Subject Column with Colorful Badges -->
                                    <td data-label="Subject">
                                        <?php
                                        foreach ($assignments as $assignment):
                                            preg_match('/Grade (\d+) - ([A-Z]) \((.*?)\)/', $assignment, $matches);
                                            if ($matches):
                                                $subject = $matches[3];
                                        ?>
                                                <span class="badge bg-warning text-dark"><?= $subject; ?></span><br>
                                        <?php endif;
                                        endforeach; ?>
                                    </td>
                                    <td class="no-print" data-label="Phone"><?= htmlspecialchars($contact); ?></td>
                                    <td data-label="Photo">
                                        <div class="profile-photo-container">
                                            <?php if (!empty($photo) && file_exists($photo)): ?>
                                                <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Teacher Photo">
                                            <?php else: ?>
                                                <div class="profile-photo no-photo"><i class="bi bi-person"></i></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="no-print text-center actions-column">
                                        <button class="btn btn-sm btn-dark" onclick="viewProfile(<?= $id; ?>)">
                                            <i class="bi bi-info-circle"></i> Display
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No teachers registered</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Profile View Overlay -->
        <div id="profileOverlay" class="overlay">
            <div class="overlay-content">
                <div id="profileContent"></div>
                <button class="btn btn-primary mt-3" onclick="closeProfile()">Close</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/viewProfile.js"></script>
    <script>
        // Initialize on load and resize
        $(document).ready(function() {
            setupResponsiveTable();
            $(window).resize(setupResponsiveTable);
        });
    </script>
</body>

</html>
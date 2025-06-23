<?php
include '../connect.php';
include 'register.php';

// Search functionality
$searchQuery = '';
if (isset($_POST['search']) && !empty(trim($_POST['search_query']))) {
    $searchQuery = trim($_POST['search_query']);
    $searchQuery = ucwords(strtolower($searchQuery));
}
$sql = "SELECT * FROM students WHERE name LIKE '%$searchQuery%'  ORDER BY grade ASC, name";
$result = mysqli_query($conn, $sql);
$num = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css?v=10">
    <link rel="stylesheet" href="sidebar.css?v=10">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom CSS -->
    <style>
       
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h3><i class="bi bi-mortarboard-fill logo-icon"></i>BGIS SchoolSystem</h3>
        </div>

        <div class="sidebar-menu">
            <div class="menu-title">Main Navigation</div>
            <a href="../home.php" class="menu-item">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="menu-title">Student Management</div>
            <a href="#" class="menu-item " aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Register Students 
            </a>
            <div class="collapse show submenu">
                <a href="registerAll.php?=0" class="menu-item <?= $grade == 0 ? 'active' : '' ?>">Register student</a>
            </div>
            <a href="#" class="menu-item " aria-expanded="true">
                <i class="bi bi-people-fill"></i> Student Records 
            </a>
            <div class="collapse show submenu" >
                <a href="student.php?grade=9" class="menu-item <?= $grade == 9 ? 'active' : '' ?>"> Grade 9</a>
                <a href="student.php?grade=10" class="menu-item <?= $grade == 10 ? 'active' : '' ?>"> Grade 10</a>
                <a href="student.php?grade=11" class="menu-item <?= $grade == 11 ? 'active' : '' ?>"> Grade 11</a>
                <a href="student.php?grade=12" class="menu-item <?= $grade == 12 ? 'active' : '' ?>"> Grade 12</a>
            </div>
        </div>
        <hr>
        <!-- Print Button in Sidebar -->
        <button class="print-btn no-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Print Records
        </button>
    </div>
    <header class="header text-center">
        <h1> Student Management System</h1>
        <p class="lead mt-2">School Name Secondary School</p>
        <button class="menu-toggle no-print" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>
    </header>
    <div class="container">
        <div id="off" class="form-container animated">
            <button id="toggleFormBtn" class="btn btn-primary mb-4">
                <i class="bi bi-person-plus"></i> Add New Student
            </button>

            <div id="studentForm" class="collapse">
                <form id="form1" action="register.php" method="POST" class="row g-3 needs-validation" novalidate enctype="multipart/form-data">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Please provide a student name.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="sex" class="form-label">Gender</label>
                        <select class="form-select" id="sex" name="sex" required>
                            <option value="" selected disabled>Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <div class="invalid-feedback">Please select a gender.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="sex" class="form-label">Grade</label>
                        <select class="form-select" id="grade" name="grade" required>
                            <option value="">Select Grade</option>
                            <?php for ($i = 9; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>">Grade <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="invalid-feedback">Please select a gender.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" name="section" id="section" required>
                            <option value="">Select Section</option>
                            <?php foreach (range('A', 'Z') as $letter): ?>
                                <option value="<?= $letter ?>"><?= $letter ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please provide a section.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username (min 6 chars)</label>
                        <input autocomplete="off" type="text" class="form-control" id="username" name="username" required minlength="6">
                        <span id="username-result"></span>
                        <div class="invalid-feedback">Username must be at least 6 characters</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password (min 6 chars)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Password must be at least 6 characters</div>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Contact</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                        <div class="invalid-feedback">Please provide a contact number.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="photo" id="photo" value="uuu" accept="image/*">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success" name="submit">
                            <i class="bi bi-save"></i> Save Student
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="search-box no-print mb-4">
            <form id="form2" method="POST" class="row g-3">
                <div class="col-md-12">
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="Search students..."
                            name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button class="btn btn-primary" name="search">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div id="tt1" class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Gender</th>
                        <th>Grade</th>
                        <th>Section</th>
                        <th>Contact</th>
                        <th style="width: 50px;">photo</th>
                        <th id="off" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result):
                        while ($row = mysqli_fetch_assoc($result)) :
                            $id = $row['id'];
                            $name = $row['name'];
                            $sex = $row['sex'];
                            $grade = $row['grade'];
                            $section = $row['section'];
                            $username = $row['username'];
                            $password = $row['password'];
                            $phone = $row['phone'];
                            $photo = $row['photo'];
                            $num++;
                    ?>
                            <tr>
                                <td class="num"><?= htmlspecialchars($num) ?></td>
                                <td class="uu"><?= htmlspecialchars($name) ?></td>
                                <td class="uu"><?= htmlspecialchars($sex) ?></td>
                                <td class="uu"><?= htmlspecialchars($grade) ?></td>
                                <td class="uu"><?= htmlspecialchars($section) ?></td>
                                <td class="uu"><?= htmlspecialchars($phone) ?></td>
                                <td>
                                    <div class="profile-photo-container">
                                        <?php if (!empty($photo) && file_exists($photo)): ?>
                                            <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Student Photo" loading="lazy">
                                        <?php else: ?>
                                            <div class="profile-photo no-photo"><i class="bi bi-person"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td id="off" class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="update.php?updateid=<?= $id ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="showOverlay(<?= $id ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <!-- <button class="btn btn-sm btn-info view-student-btn" data-student-id="<?= $id ?>">
                                            <i class="bi bi-info-circle"></i> Info
                                        </button> -->
                                        <!-- <a href="../view/dashboard.php" class="btn btn-sm btn-info">
                                            <i class="bi bi-info-circle"></i>
                                        </a> -->
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No students registered yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Overlay (hidden by default) -->
    <div id="overlay" class="overlay">
        <div class="overlay-content">
            <h4>Are you sure you want to delete this student?</h4>
            <p class="text-muted">This action cannot be undone.</p>
            <div class="d-flex justify-content-center gap-3 mt-3">
                <button class="btn btn-danger px-4" onclick="deleteItem()">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button class="btn btn-secondary px-4" onclick="closeOverlay()">
                    <i class="bi bi-x"></i> Cancel
                </button>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script src="../js/sidebar.js"></script>
    <script>
        $(document).ready(function() {
            // Bind the input event to the username field
            $('#username').on('input', function() {
                var username = $(this).val(); // Get the value of the username

                // Check if username is not empty before sending the request
                if (username.length > 0) {
                    // Perform AJAX request
                    $.ajax({
                        url: 'registerAll.php',
                        type: 'GET',
                        data: {
                            check_username: true,
                            username: username
                        },
                        success: function(response) {
                            // Update the HTML with the response from PHP
                            $('#username-result').html(response);
                        },
                        error: function() {
                            $('#username-result').html('<span class="text-danger">Error checking username</span>');
                        }
                    });
                } else {
                    // Clear the result when the input is empty
                    $('#username-result').html('');
                }
            });
        });
        // Overlay functions
        let currentItemId = null;

        function showOverlay(id) {
            currentItemId = id;
            document.getElementById('overlay').classList.add('active');
        }

        function closeOverlay() {
            document.getElementById('overlay').classList.remove('active');
        }

        // Function to handle the delete action
        function deleteItem() {
            if (currentItemId !== null) {
                // Send the delete request using AJAX
                const xhr = new XMLHttpRequest();
                xhr.open("GET", "delete.php?deleteid=" + currentItemId, true);
                xhr.onload = function() {
                    if (xhr.status == 200) {
                        // Successfully deleted, hide the overlay and reload the page
                        closeOverlay();
                        //alert("Item deleted successfully!");
                        location.reload(); // Optionally reload the page to reflect changes
                    } else {
                        alert("Error deleting item.");
                    }
                };
                xhr.send();
            } else {
                alert("No item selected for deletion.");
            }
        }
    </script>
</body>

</html>
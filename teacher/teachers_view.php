<?php 
include 'connect.php';
include 'teachers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/tv.css">
    <style>
.container {
    transition: margin-left var(--transition-speed);
    margin-left: 4px;
    width: 100%;
    min-width: 83vw;
}

@media (min-width: 992px) {
    .container {
        margin-left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width));
    }
}
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
            
            <div class="menu-title">Teacher Management</div>
            <a href="#" class="menu-item " data-bs-toggle="collapse" data-bs-target="#tregisterMenu" aria-expanded="false">
                <i class="bi bi-person-lines-fill"></i> Register Teacher <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse show submenu" id="tregisterMenu">
                <a href="../teacher/teachers_view.php?=success" class="menu-item"><i></i>Register Teacher</a>
            </div>
            <a href="#" class="menu-item " data-bs-toggle="collapse" data-bs-target="#tMenu" aria-expanded="true">
                <i class="bi bi-people-fill"></i> Teacher Records <i class="bi bi-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse show submenu" id="tMenu">
                <a href="grade.php?grade=9" class="menu-item <?= $grade == 9 ? 'active' : '' ?>"><i class=""></i> Grade 9</a>
                <a href="grade.php?grade=10" class="menu-item <?= $grade == 10 ? 'active' : '' ?>"><i class=""></i> Grade 10</a>
                <a href="grade.php?grade=11" class="menu-item <?= $grade == 11 ? 'active' : '' ?>"><i class=""></i> Grade 11</a>
                <a href="grade.php?grade=12" class="menu-item <?= $grade == 12 ? 'active' : '' ?>"><i class=""></i> Grade 12</a>
            </div>
        </div>

        <!-- Print Button in Sidebar -->
        <button class="print-btn no-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Print Records
        </button>
    </div>

    <header class="header text-center">
        <h1>Teacher Management System</h1>
        <p class="lead mt-2">School Name Secondary School</p>
        <button class="menu-toggle no-print" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>
    </header>
<div class="container">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <script> 
           // Auto-dismiss alerts after 5 seconds
           setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>

    <div class="form-container">
        <button id="toggleFormBtn" class="btn btn-primary mb-4" data-bs-toggle="collapse" data-bs-target="#teacherForm">
            <i class="bi bi-person-plus"></i> Add New Teacher
        </button>

        <div id="teacherForm" class="collapse">
            <form method="POST" class="row g-3 needs-validation" novalidate enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="edit_id" value="0">

                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" id="name" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Gender</label>
                    <select class="form-select" name="sex" id="sex" required>
                        <option value="" selected disabled>Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="col-md-6">
                        <label for="username" class="form-label">Username (min 6 chars)</label>
                        <input autocomplete="off" type="text" class="form-control" id="username" name="username" required minlength="6">
                        <span id="username-result"></span>
                        <div class="invalid-feedback">Username must be at least 6 characters</div>
                    </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" minlength="6">
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>
                    <small class="text-muted">Leave blank when editing to keep current password</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="contact" id="contact" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Photo</label>
                    <input type="file" class="form-control" name="photo" id="photo" value="uuu" accept="image/*">
                </div>

                <!-- Assignments Section -->
                <div class="col-12">
                    <h5>Assignments</h5>
                    <div id="assignments-container">
                        <!-- Assignment fields will be added here -->
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addAssignment()">
                        <i class="bi bi-plus-circle"></i> Add Another Assignment
                    </button>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success" name="submit">
                        <i class="bi bi-save"></i> Save Teacher
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeForm()">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="search-box no-print mb-4">
        <form method="POST" class="input-group">
            <input type="search" class="form-control" placeholder="Search teachers..." 
                name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn btn-primary" name="search">
                <i class="bi bi-search"></i> Search
            </button>
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
            <th style="width: 50px;">photo</th>
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
                    <td><?= $num; ?></td>
                    <td><?= htmlspecialchars($name); ?></td>
                    <td><?= htmlspecialchars($sex); ?></td>
                    <!-- Grade Column with Colorful Badges -->
                    <td>
                        <?php 
                        foreach ($assignments as $assignment): 
                            preg_match('/Grade (\d+) - ([A-Z]) \((.*?)\)/', $assignment, $matches);
                            if ($matches):
                                $grade = $matches[1];
                        ?>
                            <span class="badge bg-primary"><?= $grade; ?></span><br>
                        <?php endif; endforeach; ?>
                    </td>
                    <!-- Section Column with Colorful Badges -->
                    <td>
                        <?php 
                        foreach ($assignments as $assignment): 
                            preg_match('/Grade (\d+) - ([A-Z]) \((.*?)\)/', $assignment, $matches);
                            if ($matches):
                                $section = $matches[2];
                        ?>
                            <span class="badge bg-success"><?= $section; ?></span><br>
                        <?php endif; endforeach; ?>
                    </td>
                    <!-- Subject Column with Colorful Badges -->
                    <td>
                        <?php 
                        foreach ($assignments as $assignment): 
                            preg_match('/Grade (\d+) - ([A-Z]) \((.*?)\)/', $assignment, $matches);
                            if ($matches):
                                $subject = $matches[3];
                        ?>
                            <span class="badge bg-warning text-dark"><?= $subject; ?></span><br>
                        <?php endif; endforeach; ?>
                    </td>
                    <td class="no-print"><?= htmlspecialchars($contact); ?></td>
                    <td>
                        <div class="profile-photo-container">
                            <?php if (!empty($photo) && file_exists($photo)): ?>
                                <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Student Photo">
                            <?php else: ?>
                                <div class="profile-photo no-photo"><i class="bi bi-person"></i></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="no-print text-center">
                        <button class="btn btn-sm btn-primary" onclick="editTeacher(<?= $id; ?>)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $id; ?>)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                        <button class="btn btn-sm btn-info" onclick="viewProfile(<?= $id; ?>)">
                            <i class="bi bi-info-circle"></i> Info
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">No teachers registered</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
        
</div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this teacher? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Profile View Overlay -->
<div id="profileOverlay" class="overlay">
    <div class="overlay-content">
        <div id="profileContent"></div>
        <button class="btn btn-primary mt-3" onclick="closeProfile()">Close</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../js/sidebar.js"></script>
<script src="../js/viewProfile.js"></script>

<script>
    $(document).ready(function() {
// Bind the input event to the username field
$('#username').on('input', function() {
    var username = $(this).val(); // Get the value of the username

    // Check if username is not empty before sending the request
    if (username.length > 0) {
        // Perform AJAX request
        $.ajax({
            url: 'teachers.php',  
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
// Global variables
let assignmentCounter = 0;

// Initialize form with one assignment
$(document).ready(function() {
    addAssignment();
    
    // Username availability check
    $('#username').on('blur', function() {
        const username = $(this).val();
        const editId = $('#edit_id').val();
        
        if (username.length >= 6) {
            $.get('teachers.php', {
                check_username: 1,
                username: username,
                edit_id: editId
            }, function(data) {
                $('#usernameStatus').html(data);
            });
        }
    });
});

// Add a new assignment field group
function addAssignment(assignment = null) {
    assignmentCounter++;
    const container = $('#assignments-container');
    
    const html = `
    <div class="assignment-group border p-3 mb-3" id="assignment-${assignmentCounter}">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Grade</label>
                <select class="form-select" name="assignments[${assignmentCounter}][grade]" required>
                    <option value="">Select Grade</option>
                    <?php for($i=9; $i<=12; $i++): ?>
                        <option value="<?= $i ?>">Grade <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Section</label>
                <select class="form-select" name="assignments[${assignmentCounter}][section]"  required>
                        <option value="">Select Section</option>
                       <?php foreach(range('A', 'Z') as $letter): ?>
                        <option value="<?= $letter ?>"><?= $letter ?></option>
                     <?php endforeach; ?>
            </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Subject</label>
                <select class="form-select" name="assignments[${assignmentCounter}][subject_id]" required>
                    <option value="">Select Subject</option>
                    <?php 
                    $subjects = $conn->query("SELECT id, name FROM subjects ORDER BY name");
                    while($subject = $subjects->fetch_assoc()): ?>
                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeAssignment(${assignmentCounter})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
    `;
    
    container.append(html);
    
    // Set values if editing an existing assignment
    if (assignment) {
        $(`#assignment-${assignmentCounter} select[name$="[grade]"]`).val(assignment.grade);
        $(`#assignment-${assignmentCounter} select[name$="[section]"]`).val(assignment.section);
        $(`#assignment-${assignmentCounter} select[name$="[subject_id]"]`).val(assignment.subject_id);
    }
}

// Remove an assignment field group
function removeAssignment(id) {
    $(`#assignment-${id}`).remove();
}

// Toggle password visibility
function togglePassword() {
    const password = $('#password');
    const icon = $('#toggleIcon');
    
    if (password.attr('type') === 'password') {
        password.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        password.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

// Edit teacher
function editTeacher(id) {
    $.get('teachers.php', {
        get_teacher: 1,
        id: id
    }, function(data) {
        try {
            const teacher = JSON.parse(data);
            if (teacher.error) {
                alert(teacher.error);
                return;
            }
            
            // Basic info
            $('#edit_id').val(teacher.id);
            $('#name').val(teacher.name);
            $('#sex').val(teacher.sex);
            $('#contact').val(teacher.contact);
            $('#username').val(teacher.username);

            // Clear password field and make optional
            $('#password').val('').removeAttr('required');
            
            // Clear existing assignments and add new ones
            $('#assignments-container').empty();
            if (teacher.assignments && teacher.assignments.length > 0) {
                teacher.assignments.forEach(assignment => {
                    addAssignment(assignment);
                });
            } else {
                addAssignment();
            }
            
            // Update form title
            $('#toggleFormBtn').html('<i class="bi bi-pencil"></i> Edit Teacher');
            
            // Show the form
            new bootstrap.Collapse(document.getElementById('teacherForm')).show();
        } catch (e) {
            console.error('Error parsing teacher data:', e);
            alert('Error loading teacher data');
        }
    });
}

// Close form and reset
function closeForm() {
    $('#edit_id').val('0');
    $('#teacherForm form')[0].reset();
    $('#teacherForm form').removeClass('was-validated');
    $('#usernameStatus').html('');
    $('#assignments-container').empty();
    addAssignment();
    $('#toggleFormBtn').html('<i class="bi bi-person-plus"></i> Add New Teacher');
    
    new bootstrap.Collapse(document.getElementById('teacherForm')).hide();
}
// Confirm delete
function confirmDelete(id) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    
    deleteBtn.href = 'teachers_view.php?deleteid=' + id;
    deleteModal.show();
}

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
</body>

</html>
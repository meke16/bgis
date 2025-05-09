<?php 
include 'connect.php';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
    <style>
        td span {
                margin-bottom: 5px; /* Adds some space between badges */
            }
        .assignment-group {
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .password-toggle {
            cursor: pointer;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow-y: auto;
        }
        .overlay-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 5px;
        }
        .profile-img {
            max-width: 200px;
            border-radius: 50%;
        }
        .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
        }
        .profile-photo-container {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
            background-color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-photo {
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
<header class="header text-center">
    <h1>Teacher Records Grade-<?php echo $grade ?></h1>
    <p class="lead mt-2">School Name Secondary School</p>
</header>

<div class="container">
<div class="d-flex justify-content-between mb-4 no-print">
        <a href="../home.php" class="btn btn-back">
            <i class="bi bi-arrow-left"></i> Go Back Home
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
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
<script>
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
            $.get('<?php echo $self; ?>', {
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
            <div class="col-md-3">
                <label class="form-label">Grade</label>
                <select class="form-select" name="assignments[${assignmentCounter}][grade]" required>
                    <option value="">Select Grade</option>
                    <?php for($i=9; $i<=12; $i++): ?>
                        <option value="<?= $i ?>">Grade <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Section</label>
                <input type="text" class="form-control" name="assignments[${assignmentCounter}][section]" 
                       value="${assignment ? assignment.section : ''}" required>
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
            <div class="col-md-2">
                <label class="form-label">Academic Year</label>
                <select class="form-select" name="assignments[${assignmentCounter}][academic_year]" required>
                    <option value="">Select Year</option>
                    <?php 
                    $current_year = date('Y');
                    for($i=-2; $i<=2; $i++): 
                        $year = $current_year + $i;
                    ?>
                        <option value="<?= "$year-" . ($year+1) ?>"><?= "$year-" . ($year+1) ?></option>
                    <?php endfor; ?>
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
        $(`#assignment-${assignmentCounter} select[name$="[subject_id]"]`).val(assignment.subject_id);
        $(`#assignment-${assignmentCounter} select[name$="[academic_year]"]`).val(assignment.academic_year);
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
    $.get('<?php echo $self; ?>', {
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

// View teacher profile
function viewProfile(id) {
    $.get('<?php echo $self; ?>', {
        get_teacher: 1,
        id: id
    }, function(data) {
        try {
            const teacher = JSON.parse(data);
            if (teacher.error) {
                alert(teacher.error);
                return;
            }
            
            const photo = teacher.photo ? '../teachers/upload/' + teacher.photo : 'assets/default-profile.jpg';
            
            let assignmentsHtml = '';
            if (teacher.assignments && teacher.assignments.length > 0) {
                assignmentsHtml = teacher.assignments.map(assignment => `
                    <div class="assignment-detail mb-3">
                        <h6>Assignment ${teacher.assignments.indexOf(assignment) + 1}</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Grade:</strong> ${assignment.grade || 'N/A'}
                            </div>
                            <div class="col-md-3">
                                <strong>Section:</strong> ${assignment.section || 'N/A'}
                            </div>
                 
                            <div class="col-md-3">
                                <strong>Academic Year:</strong> ${assignment.academic_year || 'N/A'}
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                assignmentsHtml = '<p>No assignments found</p>';
            }
            
            const profileHtml = `
                <div class="profile-header text-center">
                    <img src="${photo}" class="profile-img mb-3" alt="Teacher Photo">
                    <h3>${teacher.name}</h3>
                    <p class="mb-0">${teacher.role === 'admin' ? 'Administrator' : 'Teacher'}</p>
                </div>
                <div class="profile-details mt-4">
                    <div class="row detail-row">
                        <div class="col-md-3 detail-label">Gender:</div>
                        <div class="col-md-9">${teacher.sex || 'N/A'}</div>
                    </div>
                    <div class="row detail-row">
                        <div class="col-md-3 detail-label">Username:</div>
                        <div class="col-md-9">${teacher.username || 'N/A'}</div>
                    </div>
                    <div class="row detail-row">
                        <div class="col-md-3 detail-label">Contact:</div>
                        <div class="col-md-9">${teacher.contact || 'N/A'}</div>
                    </div>
                    <div class="row detail-row">
                        <div class="col-md-12 detail-label">Assignments:</div>
                        <div class="col-md-12">
                            ${assignmentsHtml}
                        </div>
                    </div>
                </div>
            `;
            
            $('#profileContent').html(profileHtml);
            $('#profileOverlay').show();
        } catch (e) {
            console.error('Error parsing teacher data:', e);
            alert('Error loading teacher profile');
        }
    }).fail(function() {
        alert('Failed to load teacher profile');
    });
}

// Close profile view
function closeProfile() {
    $('#profileOverlay').hide();
}

// Confirm delete
function confirmDelete(id) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    
    deleteBtn.href = '<?php echo $self; ?>?deleteid=' + id;
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
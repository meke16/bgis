<?php
include '../connect.php';
include 'session.php';

$self = $_SERVER['PHP_SELF'];

// Handle AJAX requests
if (isset($_GET['get_teacher'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Get teacher info with all assignments
    $teacher = [];
    $sql = "SELECT * FROM teachers WHERE id = '$id' AND role = 'teacher'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        
        // Get all assignments for this teacher
        $sql = "SELECT * FROM teacher_assignments WHERE teacher_id = '$id'";
        $result = $conn->query($sql);
        $teacher['assignments'] = [];
        
        while ($row = $result->fetch_assoc()) {
            $teacher['assignments'][] = $row;
        }
    }
    
    echo json_encode($teacher);
    exit();
}
if (isset($_GET['get_subjects'])) {
    $result = $conn->query("SELECT id, name FROM subjects");
    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    echo json_encode($subjects);
    exit;
}

if (isset($_GET['check_username'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    
    $sql = "SELECT id FROM teachers WHERE username = '$username' AND id != '".($_GET['edit_id'] ?? 0)."'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<span class='text-danger'>Username already exists!</span>";
    } else {
        echo "<span class='text-success'>Username available</span>";
    }
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
        // Handle file upload
        $photo_path = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $filename;
            
            // Check if image file is actual image
            $check = getimagesize($_FILES['photo']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                    $photo_path = $target_file;
                }
            }
        }
    // Get form data
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $sex = $_POST['sex'];
    $name = ucwords(strtolower($_POST['name']));
    $contact = $_POST['contact'];
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;

    // Validate inputs
    $errors = [];
    if (strlen($username) < 6) {
        $errors[] = 'Username must be at least 6 characters long.';
    }
    
    if ($edit_id == 0 && empty($_POST['password'])) {
        $errors[] = 'Password is required for new teachers.';
    } elseif (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            if ($edit_id > 0) {
                // Update existing teacher
                $sql = "UPDATE teachers SET 
                        username = '$username', 
                        name = '$name', 
                        sex = '$sex', 
                        contact = '$contact'";
                
                if (!empty($password)) {
                    $sql .= ", password = '$password'";
                }
                if (!empty($photo_path)) {
                    $sql .= ", photo = '$photo_path'";
                }
                
                $sql .= " WHERE id = $edit_id";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Error updating teacher: " . $conn->error);
                }
                
                // Delete existing assignments
                if (!$conn->query("DELETE FROM teacher_assignments WHERE teacher_id = $edit_id")) {
                    throw new Exception("Error deleting assignments: " . $conn->error);
                }
            } else {
                // Insert new teacher
                $sql = "INSERT INTO teachers 
                       (username, password, name, sex, contact, photo) 
                       VALUES ('$username', '$password', '$name', '$sex', '$contact', '$photo_path')";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Error creating teacher: " . $conn->error);
                }
                
                $edit_id = $conn->insert_id;
            }
            
            // Process assignments
            if (isset($_POST['assignments'])) {
                foreach ($_POST['assignments'] as $assignment) {
                    $subject_id = intval($assignment['subject_id']);
                    $grade = intval($assignment['grade']);
                    $section = mysqli_real_escape_string($conn, trim($assignment['section']));
                    
                    $sql = "INSERT INTO teacher_assignments 
                           (teacher_id, subject_id, grade, section)
                           VALUES ($edit_id, $subject_id, $grade, '$section')";
                    
                    if (!$conn->query($sql)) {
                        throw new Exception("Error creating assignment: " . $conn->error);
                    }
                }
            }
            
            $conn->commit();
            $_SESSION['success'] = $edit_id > 0 ? "Teacher updated successfully" : "New teacher created successfully";
            header("Location: $self");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Handle delete request
if (isset($_GET['deleteid'])) {
    $id = intval($_GET['deleteid']);
    $conn->begin_transaction();
    
    try {
        // First delete assignments
        if (!$conn->query("DELETE FROM teacher_assignments WHERE teacher_id = $id")) {
            throw new Exception("Error deleting assignments: " . $conn->error);
        }
        
        // Then delete teacher
        if ($conn->query("DELETE FROM teachers WHERE id = $id AND role = 'teacher'")) {
            $_SESSION['success'] = "Teacher deleted successfully";
        } else {
            throw new Exception("Error deleting teacher: " . $conn->error);
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: $self");
    exit();
}
// Search functionality
$searchQuery = '';
if (isset($_POST['search']) && !empty(trim($_POST['search_query']))) {
    $searchQuery = trim($_POST['search_query']);
    $searchQuery = ucwords(strtolower($searchQuery));
}
// Get all teachers with their assignments
$sql = "SELECT t.*, 
       (SELECT GROUP_CONCAT(CONCAT('Grade ', ta.grade, ' - ', ta.section, ' (', s.name, ')') SEPARATOR '<br>') 
        FROM teacher_assignments ta 
        LEFT JOIN subjects s ON ta.subject_id = s.id 
        WHERE ta.teacher_id = t.id) AS assignments_info
       FROM teachers t
       WHERE t.role = 'teacher'
       AND (name LIKE '%$searchQuery%')
       ORDER BY t.name";

$result = $conn->query($sql);
$num = 0;
?>
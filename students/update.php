<?php
session_start();
include 'session.php';
include '../connect.php';
$id = $_GET['updateid'];

$sql = "SELECT * from `students` where id=$id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$name = $row['name'];
$sex = $row['sex'];
$grade = $row['grade'];
$section = $row['section'];
$username = $row['username'];
$phone = $row['phone'];
$photo = $row['photo'];


// Check if the form is submitted
if (isset($_POST['submit'])) {
    // Get the form data
    $name = $_POST['name'];
    $sex = $_POST['sex'];
    $grade = $_POST['grade'];
    $section = $_POST['section'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];

    // Capitalize gender
    $name = ucfirst(strtolower($name));
    $section = ucfirst(strtolower($section));
        // Handle file upload
        $new_photo = $photo; // Keep the existing photo by default
    
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            
            if (in_array($file_ext, $allowed_ext)) {
                // Delete old photo if it exists
                if (!empty($photo) && file_exists($photo)) {
                    unlink($photo);
                }
                
                $new_filename = uniqid('photo_', true) . '.' . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                    $new_photo = $target_file;
                } else {
                    $_SESSION['error'] = 'Error uploading file.';
                }
            } else {
                $_SESSION['error'] = 'Only JPG, JPEG, PNG files are allowed for photo.';
            }
        }


    // Validate username and password length
    if (strlen($username) < 6) {
        echo "<script>alert('Username must be at least 6 characters long.');</script>";
    } 
    elseif (strlen($password) < 6) {
        echo "<script>alert('Password must be at least 6 characters long.');</script>";
    } else {

        // First check if username exists for another user
        $check_sql = "SELECT id FROM students WHERE username = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $username, $id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $username_error = "Username already exists!";
        } else {
            // Update the data in the 'students' table using prepared statement
            $sql = "UPDATE `students` SET name=?, sex=?, grade=?, section=?, 
                username=?, password=?, phone=? , photo=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            $options = [
                // Increase the bcrypt cost from 12 to 13.
                    'cost' => 13,
                ];
            $hash_pwd = password_hash($password, PASSWORD_ARGON2ID, $options);
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssss",
                $name,
                $sex,
                $grade,
                $section,
                $username,
                $hash_pwd,
                $phone,
                $new_photo,
                $id
            );
            $result = mysqli_stmt_execute($stmt);
            // Check if the query was successful
            if ($result) {
                header("Location: student.php?grade=$grade");
                exit();
            } else {
                die(mysqli_error($conn));
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="update.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container">
        <!-- Form to update student data -->
        <div class="header">
            <h2 class="text-primary">Update Student Data</h2>
            <?php echo $section ?>
            <p class="lead">Please fill in the details below to update the student record.</p>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label for="name">Name of Student</label>
                <input type="text" class="form-control" id="name" placeholder="Enter Student's Name" name="name" autocomplete="off" value="<?php echo htmlspecialchars($name) ?>" required>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Gender</label>
                <select class="form-select" name="sex" required>
                    <option value="Male" <?php echo ($sex == 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?php echo ($sex == 'Female') ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="grade">grade</label>
                <select class="form-select" id="grade" name="grade" required>
                    <?php for($i=9; $i<=12; $i++): ?>
                        <option value="<?= $i; ?>" <?= ($grade == $i) ? 'selected' : ''; ?>>Grade <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="section">Section</label> 
                <select class="form-select" name="section" required>
                    <?php $selectedSection = $section; foreach(range('A', 'Z') as $sectionOption) : ?>
                        <option value="<?= $sectionOption ?>" <?= (isset($selectedSection) && $sectionOption === $selectedSection) ? 'selected' : '' ?>>Section: <?= $sectionOption ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="username" class="form-label">Username (min 6 chars)</label>
                <input type="text" class="form-control" id="username" name="username" required minlength="6" autocomplete="off" value="<?php echo   htmlspecialchars($username) ?>">
                <div class="invalid-feedback">Username must be at least 6 characters</div>
                <div id="usernameStatus" class="mt-1"></div>
                <?php if (isset($username_error)): ?>
                    <div class="text-danger"><?php  echo $username_error ?></div>
                    <?php unset($username_error); ?>
                <?php endif; ?>
            </div>
            <div class="form-group mb-3">
                <label for="password" class="form-label">Password (min 6 chars)</label>
                <input type="text" class="form-control" id="password" name="password" required minlength="6" autocomplete="off" placeholder="enter new password..">
                <div class="invalid-feedback">Password must be at least 6 characters</div>
            </div>
            <div class="form-group mb-3">
                <label for="phone">Phone Number</label>
                <input type="text" class="form-control" id="phone" placeholder="Enter Phone Number" name="phone" autocomplete="off" value="<?php echo htmlspecialchars($phone) ?>" required>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Photo (Optional)</label>
                <input type="file" class="form-control" name="photo" id="photo" accept="image/jpeg, image/png">
                <div class="form-text">Only JPG/PNG images accepted</div>
                <?php if (!empty($photo)): ?>
                    <div class="mt-2">
                        <p>Current Photo:</p>
                        <img src="<?php echo htmlspecialchars($photo) ?>" alt="Current Photo" style="max-width: 200px; max-height: 200px; border: none; border-radius: 10px;">
                    </div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Update</button>
        </form>
    </div>

    <footer>
        <p>© 2025 Your Company. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#username').on('input', function() {
                var username = $(this).val();
                var id = <?php echo $id ?>;

                if (username.length > 5) { // Only check after 3 characters
                    $.post('check_username.php', {
                        username: username,
                        id: id
                    }, function(data) {
                        $('#usernameStatus').html(data);
                    });
                } else {
                    $('#usernameStatus').html('');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Validate form before submission
            $('form').on('submit', function(e) {
                var username = $('#username').val();
                var password = $('#password').val();

                if (username.length < 6) {
                    alert('Username must be at least 6 characters long.');
                    e.preventDefault();
                    return false;
                }

                if (password.length < 6) {
                    alert('Password must be at least 6 characters long.');
                    e.preventDefault();
                    return false;
                }

                return true;
            });

            // Real-time validation feedback
            $('#username').on('input', function() {
                if ($(this).val().length < 6) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                }
            });

            $('#password').on('input', function() {
                if ($(this).val().length < 6) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                }
            });
        });
    </script>
</body>

</html>
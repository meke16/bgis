<?php
include '../connect.php';
//include("session.php");

// Handle AJAX username check request
if (isset($_GET['check_username'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    $sql = "SELECT id FROM students WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<span class='text-danger'>Username already exists!</span>";
    } else { 
        if(strlen($username) >=6 ) {
        echo "<span class='text-success'>Username available</span>";
        }
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

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $sex = $_POST['sex'];
    $grade = $_POST['grade'];
    $section = $_POST['section'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $name = ucwords(strtolower($name));

    // Validate username and password length
    if (strlen($username) < 6) {
        echo "<script>window.location.href='register$grade.php'; alert('Username must be at least 6 characters long.');</script>";
    } elseif (strlen($password) < 6) {
        echo "<script>window.location.href='register$grade.php';alert('Password must be at least 6 characters long.');</script>";
    } else {
        // Check if username exists
        $sql_check = "SELECT * FROM `students` WHERE `username` = '$username'";
        $result = $conn->query($sql_check);

    if ($result->num_rows > 0) {
        echo "<script>alert('Username already exists! Please choose a different username.');</script>";
    } else {
        $stmt =$conn->prepare( "INSERT INTO students (name, sex,grade, section, username, password, phone,photo ) 
                VALUES (?, ?, ?, ?, ?, ? ,?,?);");
        $options = [
            // Increase the bcrypt cost from 12 to 13.
            'cost' => 13,
        ];
        $hash_pwd =  password_hash($password, PASSWORD_ARGON2ID, $options);

     $stmt->bind_param("ssssssss", $name, $sex,$grade, $section, $username, $hash_pwd ,$phone,$photo_path);

        if ($stmt->execute()) {
            $_SESSION['success'] = "New record created successfully";
            header("Location: registerAll.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }        
    }
}
}


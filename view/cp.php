<?php
session_start();
include 'session.php';
require '../connect.php';

// Redirect if not logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

$id = $_SESSION['student_id'];
$error = '';
$success = '';
$password= $conn->query("SELECT password FROM students where id=$id")->fetch_row()[0];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required";
    }
    // elseif ($current_password == $new_password) {
    //     $error = "no changes are made.password same with before! If you are okay with that you can back to your profile withou change has maded.";
    // }
     elseif ($new_password !== $confirm_password) {
        $error = "New passwords don't match";
    } else {
        $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($db_password);
        $stmt->fetch();
        $stmt->close();
        
        if (password_verify($current_password, $db_password)) {
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
            $new_password = password_hash($new_password,PASSWORD_ARGON2I);
            $stmt->bind_param('si', $new_password, $id);
            
            if ($stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to update password";
            }
            $stmt->close();
        } else {
            $error = "Current password is incorrect";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-container">
            <h2 class="text-center mb-4">Change Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <!-- placeholder="your current password is: <?php echo $password  ?>" -->
                    <input   type="text" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                    <a href="index.php" class="btn btn-success">Back-To-Profile</a>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
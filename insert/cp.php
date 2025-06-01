<?php
require '../connect.php';

// Redirect if not logged in
if (!isset( $_SESSION['user']['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_SESSION['user']['id'];
$error = '';
$success = '';
$password= $conn->query("SELECT password FROM teachers where id=$id")->fetch_row()[0];

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
        $stmt = $conn->prepare("SELECT password FROM teachers WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($db_password);
        $stmt->fetch();
        $stmt->close();
        
        if (password_verify($current_password, $db_password)) {
            $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
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

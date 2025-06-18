<?php
// Set session expiration time (in seconds)
$_SESSION['session_timeout'] = 1800; 


// Check if session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $_SESSION['session_timeout']) {
    // Destroy the session if expired
    session_unset();
    session_destroy();
    // Redirect to the login page (index.php) with a session expired message
    header("Location: ../index.php?session_expired=true");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();


// // Initialize session if not already set
// if (!isset($_SESSION['user'])) {
//     $_SESSION['user'] = [
//         'authenticated' => false,
//         'is_admin' => false,
//         'id' => null,
//         'name' => '',
//         // other default values
//     ];
// }

// // Only set admin values if this is an admin login
// function setAdminSession($admin_id, $admin_name) {
//     $_SESSION['user'] = [
//         'authenticated' => true,
//         'is_admin' => true,
//         'id' => $admin_id,
//         'name' => $admin_name,
//         // other admin data
//     ];
// }

// // For regular student login
// function setStudentSession($student_id, $student_name) {
//     $_SESSION['user'] = [
//         'authenticated' => true,
//         'is_admin' => false,
//         'id' => $student_id,
//         'name' => $student_name,
//         // other student data
//     ];
// }
// // Check if the user is logged in

// if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {

//     // Redirect to the login page if not logged in

//     header("Location: ../index.php");

//     exit();

// }

// // Set session expiration time (in seconds)
// $session_timeout = 10; // 30 minutes

// // Check if the user is logged in
// if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
//     // Redirect to the login page (index.php) if not logged in
//     header("Location: index.php");
//     exit();
// }

// // Check if session has expired
// if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
//     // Destroy the session if expired
//     session_unset();
//     session_destroy();
//     // Redirect to the login page (index.php) with a session expired message
//     header("Location: index.php?session_expired=true");
//     exit();
// }

// // Update last activity time
// $_SESSION['last_activity'] = time();

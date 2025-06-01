
<?php 
session_start();
// Set session expiration time (in seconds)
$session_timeout = 1800; // 30 minutes

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to the login page (index.php) if not logged in
    header("Location: index.php");
    exit();
}

// Check if session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    // Destroy the session if expired
    session_unset();
    session_destroy();
    // Redirect to the login page (index.php) with a session expired message
    header("Location: index.php?session_expired=true");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
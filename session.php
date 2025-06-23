<?php
// Set session expiration time (in seconds)
$_SESSION['session_timeout'] = 1800; 


// Check if session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $_SESSION['session_timeout']) {
    // Destroy the session if expired
    session_unset();
    session_destroy();
    // Redirect to the login page (index.php) with a session expired message
    header("Location: index.php?msg=session_expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>

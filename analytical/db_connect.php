<?php

// Database configuration

$db_host = 'localhost';

$db_name = 'project1';

$db_user = 'root';

$db_pass = '';



try {

    // Create PDO connection

    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



} catch (PDOException $e) {

    // Styling the error message

    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; font-family: Arial, sans-serif;">';

    echo '<h2>Database Connection Failed!</h2>';

    echo '<p>We are experiencing technical difficulties. Please try again later.</p>';

    echo '<p>If the problem persists, please contact the system developer:</p>';

    echo '<p><strong>Contact:</strong>+251913174924</p>';

    echo '<p><strong>Telegram:</strong>@amrane16</p>';

    echo '<p><strong>Email:</strong> <a href="mailto:amdtwh@example.com">habtamucherinet40@gmail.com</a></p>';

    echo '</div>';

    die();

}


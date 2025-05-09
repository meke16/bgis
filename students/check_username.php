<?php

include '../connect.php';



$username = $_POST['username'];

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;



// Check if username exists (excluding current user)

$sql = "SELECT id FROM students WHERE username = ? AND id != ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "si", $username, $id);

mysqli_stmt_execute($stmt);

mysqli_stmt_store_result($stmt);



if (mysqli_stmt_num_rows($stmt) > 0) {

    echo "<span class='text-danger'>Username already exists!</span>";

} else {

    echo "<span class='text-success'>Username available</span>";

}



mysqli_stmt_close($stmt);

mysqli_close($conn);

?>
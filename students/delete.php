<?php
include '../connect.php';

if (isset($_GET['deleteid'])) {
    $id = $_GET['deleteid'];
    // Prepare delete query
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Return a success response
        echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully']);
    } else {
        // Return an error response
        echo json_encode(['status' => 'error', 'message' => 'Error deleting item']);
    }

    $stmt->close();
} else {
    // Return an error response if delete4id is not set
    echo json_encode(['status' => 'error', 'message' => 'No ID provided for deletion']);
}

$conn->close();
?>
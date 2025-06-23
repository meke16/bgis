<?php
session_start();
include 'session.php';
require_once '../connect.php';

$teacher_id = $_SESSION['user']['id'];
$message = '';
$error = '';

// ===== DELETE logic =====
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM mark_types WHERE id = $delete_id AND teacher_id = $teacher_id");
    $message = "Mark type deleted successfully!";
}

// ===== EDIT logic =====
if (isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
    $type = trim($_POST['edit_type']);
    $max = intval($_POST['edit_max']);

    if (!empty($type) && $max > 0) {
        $stmt = $conn->prepare("UPDATE mark_types SET type_name = ?, max_mark = ? WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("siii", $type, $max, $id, $teacher_id);
        $stmt->execute();
        $stmt->close();
        $message = "Mark type updated successfully!";
    } else {
        $error = "Please enter valid values for edit.";
    }
}

// ===== ADD logic =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST['update_id'])) {
    $type = trim($_POST['new_type']);
    $max = intval($_POST['max_mark']);

    if (!empty($type) && $max > 0) {
        $stmt = $conn->prepare("INSERT INTO mark_types (teacher_id, type_name, max_mark) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $teacher_id, $type, $max);
        $stmt->execute();
        $stmt->close();
        $message = "Mark type added successfully!";
    } else {
        $error = "Please enter a valid mark type name and max value.";
    }
}

// Fetch all mark types for the teacher
$types = mysqli_query($conn, "SELECT * FROM mark_types WHERE teacher_id = $teacher_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mark Type CRUD Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

    <h2 class="mb-4">Mark Type Management</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Add Form -->
    <form action="" method="POST" class="mb-4" autocomplete="off">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <input type="text" name="new_type" class="form-control" placeholder="New Mark Type" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="max_mark" class="form-control" placeholder="Max Mark" required min="1">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Add Mark Type</button>
                <a href="td.php" class="btn btn-warning">Back Home</a>
            </div>
        </div>
    </form>

    <!-- Table CRUD Display -->
    <table class="table table-bordered table-striped align-middle text-center">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Mark Type</th>
                <th>Max Mark</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while ($row = mysqli_fetch_assoc($types)): ?>
                <tr>
                    <form method="POST" action="">
                        <input type="hidden" name="update_id" value="<?= $row['id'] ?>">
                        <td><?= $i++ ?></td>
                        <td>
                            <input type="text" name="edit_type" value="<?= htmlspecialchars($row['type_name']) ?>" class="form-control" required>
                        </td>
                        <td>
                            <input type="number" name="edit_max" value="<?= $row['max_mark'] ?>" class="form-control" required min="1">
                        </td>
                        <td>
                            <button type="submit" class="btn btn-success btn-sm me-2">Update</button>
                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this?')" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </form>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>

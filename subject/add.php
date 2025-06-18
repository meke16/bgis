<?php
include 'config.php';
// Handle form submission for adding a new subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    if (!empty($name) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
        $success = $stmt->execute([$name, $description]);
        header("location: add.php?msg=added");
        exit();
    }
}

// Handle editing a subject
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    if (!empty($name) && !empty($description)) {
        $stmt = $pdo->prepare("UPDATE subjects SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        header("Location: add.php?msg=updated");
        exit();
    }
}

// Handle deletion of a subject
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Check if subject is used
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_marks WHERE subject_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        header("Location: add.php?msg=linked");
        exit();
    } else {
        // Safe to delete
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: add.php?msg=deleted");
        exit();
    }
}

// Fetch all subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
<!-- Alert Message -->
<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] == 'linked'): ?>
        <div class="alert alert-danger text-center"> Cannot delete this subject. It is linked to student marks.</div>
    <?php elseif ($_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success text-center">Subject deleted successfully.</div>
    <?php elseif($_GET['msg'] === 'added'): ?>
        <div class="alert alert-success text-center">Subject added successfully.</div>
    <?php elseif($_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success text-center">Subject updated successfully.</div>
    <?php endif; ?>
<?php endif; ?>

    <h2 class="mb-4">School Subject Management</h2>
    <!-- Add or Edit Subject Form -->
    <div class="card mb-4">
        <div class="card-header"><?= isset($subject) ? 'Edit' : 'Add' ?> Subject</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?= isset($subject) ? 'edit' : 'add' ?>">
                <?php if (isset($subject)): ?>
                    <input type="hidden" name="id" value="<?= $subject['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label for="name" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= isset($subject) ? htmlspecialchars($subject['name']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Subject Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?= isset($subject) ? htmlspecialchars($subject['description']) : '' ?></textarea>
                </div>
                <a style="position: absolute; right: 10px;" class="btn btn-primary" href="../home.php">back-home</a>
                <button type="submit" class="btn btn-success" ><?= isset($subject) ? 'Update Subject' : 'Add Subject' ?></button>
            </form>
        </div>
    </div>

    <!-- Subjects List -->
    <div class="card">
        <div class="card-header">Subject List</div>
        <div class="card-body">
            <?php if ($subjects): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>List</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $num = 1; ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?= $num++; ?></td>
                                <td><?= htmlspecialchars($subject['name']) ?></td>
                                <td><?= htmlspecialchars($subject['description']) ?></td>
                                <td>
                                    <a href="add.php?edit=<?= $subject['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="add.php?delete=<?= $subject['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this subject?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No subjects found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="script.js"></script>
</body>
</html>

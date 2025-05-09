<?php
// Database connection
$host = 'localhost';
$dbname = 'project1';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission for adding a new subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    if (!empty($name) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
        $success = $stmt->execute([$name, $description]);
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
        header("Location: add.php");
        exit;
    }
}

// Handle deletion of a subject
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: add.php");
    exit;
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
                <button class="btn btn-primary text-bold"><a class="text-light" href="../home.php">back-home</a></button>
                <button type="submit" class="btn btn-success"><?= isset($subject) ? 'Update Subject' : 'Add Subject' ?></button>
                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success mt-3">Subject added/updated successfully!</div>
                <?php elseif (isset($success) && !$success): ?>
                    <div class="alert alert-danger mt-3">Failed to add/update subject.</div>
                <?php endif; ?>
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
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?= htmlspecialchars($subject['id']) ?></td>
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
</body>
</html>

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

// Handle form submission for adding a new semesters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_current = isset($_POST['is_current']) ? 1 : 0;

    if (!empty($name) && !empty($start_date) && !empty($end_date)) {
        $stmt = $pdo->prepare("INSERT INTO semesters (name, start_date, end_date, is_current) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$name, $start_date, $end_date, $is_current]);
    }
}

// Handle editing a semesters
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM semesters WHERE id = ?");
    $stmt->execute([$id]);
    $semesters = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_current = isset($_POST['is_current']) ? 1 : 0;

    if (!empty($name) && !empty($start_date) && !empty($end_date)) {
        $stmt = $pdo->prepare("UPDATE semesters SET name = ?, start_date = ?, end_date = ?, is_current = ? WHERE id = ?");
        $stmt->execute([$name, $start_date, $end_date, $is_current, $id]);
        header("Location: sem.php");
        exit;
    }
}

// Handle deletion of a semesters
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM semesters WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: sem.php");
    exit;
}

// Fetch all semesterss
$semesterss = $pdo->query("SELECT * FROM semesters ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage semesterss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">School semesters Management</h2>

    <!-- Add or Edit semesters Form -->
    <div class="card mb-4">
        <div class="card-header"><?= isset($semesters) ? 'Edit' : 'Add' ?> semesters</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?= isset($semesters) ? 'edit' : 'add' ?>">
                <?php if (isset($semesters)): ?>
                    <input type="hidden" name="id" value="<?= $semesters['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label for="name" class="form-label">semesters Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= isset($semesters) ? htmlspecialchars($semesters['name']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= isset($semesters) ? $semesters['start_date'] : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= isset($semesters) ? $semesters['end_date'] : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="is_current" class="form-label">Is Active</label>
                    <input type="checkbox" id="is_current" name="is_current" <?= isset($semesters) && $semesters['is_current'] ? 'checked' : '' ?>>
                </div>
                <button class="btn btn-primary text-bold"><a class="text-light" href="../home.php">Back Home</a></button>
                <button type="submit" class="btn btn-success"><?= isset($semesters) ? 'Update semesters' : 'Add semesters' ?></button>
                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success mt-3">semesters added/updated successfully!</div>
                <?php elseif (isset($success) && !$success): ?>
                    <div class="alert alert-danger mt-3">Failed to add/update semesters.</div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- semesterss List -->
    <div class="card">
        <div class="card-header">semesters List</div>
        <div class="card-body">
            <?php if ($semesterss): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Is Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semesterss as $semesters): ?>
                            <tr>
                                <td><?= htmlspecialchars($semesters['id']) ?></td>
                                <td><?= htmlspecialchars($semesters['name']) ?></td>
                                <td><?= htmlspecialchars($semesters['start_date']) ?></td>
                                <td><?= htmlspecialchars($semesters['end_date']) ?></td>
                                <td><?= $semesters['is_current'] ? 'Active' : 'Inactive' ?></td>
                                <td>
                                    <a href="sem.php?edit=<?= $semesters['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <a href="sem.php?delete=<?= $semesters['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this semesters?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No semesterss found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

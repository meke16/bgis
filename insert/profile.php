<?php
session_start();
include 'cp.php';
require_once '../connect.php';

// Check authentication
if (!isset($_SESSION['user']['authenticated']) || $_SESSION['user']['authenticated'] !== true) {
    header("Location: index.php");
    exit();
}

// Check role
if ($_SESSION['user']['role'] !== 'teacher') {
    header("Location: unauthorized.php");
    exit();
}

$teacher_id = $_SESSION['user']['id'];

// Fetch the teacher's photo path from the database
$query = "SELECT photo FROM teachers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Handle photo upload
if (isset($_POST['upload_photo'])) {
    if (isset($_FILES['photo'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        $uploadDir = '../uploads/';

        if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxFileSize) {
                $fileExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $newFileName = uniqid('teacher_') . '.' . $fileExt;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                    // Update photo in the database
                    $updateStmt = $conn->prepare("UPDATE teachers SET photo = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $destination, $teacher_id);
                    if ($updateStmt->execute()) {
                        $_SESSION['success'] = "Profile photo updated successfully.";
                        header("Location: profile.php");
                        exit();
                    } else {
                        $_SESSION['error'] = "Error updating database.";
                    }
                } else {
                    $_SESSION['error'] = "Error uploading file.";
                }
            } else {
                $_SESSION['error'] = "Invalid file type or size (max 10MB allowed).";
            }
        } else {
            $_SESSION['error'] = "File upload error: " . $_FILES['photo']['error'];
        }
    }
}

// Handle photo deletion
if (isset($_POST['delete_photo'])) {
    $updateStmt = $conn->prepare("UPDATE teachers SET photo = NULL WHERE id = ?");
    $updateStmt->bind_param("i", $teacher_id);

    if ($updateStmt->execute()) {
        $_SESSION['success'] = "Profile photo removed successfully.";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Error removing photo from database.";
    }
}

// Default photo if none is set
$photo = $teacher['photo'] ?? 'default-profile.jpg';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #ef4444;
            --bg-light: #f8f9fa;
            --bg-dark: #1a1d24;
            --card-light: #ffffff;
            --card-dark: #252a33;
            --text-light: #212529;
            --text-dark: #f8f9fa;
            --border-light: #dee2e6;
            --border-dark: #495057;
            --placeholder-light: #6c757d;
            --placeholder-dark: #adb5bd;
        }

        [data-bs-theme="dark"] {
            --bs-body-bg: var(--bg-dark);
            --bs-body-color: var(--text-dark);
            --bs-border-color: var(--border-dark);
            --bs-card-bg: var(--card-dark);
            --bs-secondary-bg: #2c313a;
            --placeholder-color: var(--placeholder-dark);
        }

        [data-bs-theme="light"] {
            --bs-body-bg: var(--bg-light);
            --bs-body-color: var(--text-light);
            --bs-border-color: var(--border-light);
            --bs-card-bg: var(--card-light);
            --bs-secondary-bg: #e9ecef;
            --placeholder-color: var(--placeholder-light);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var( --bs-secondary-bg);
            color: var(--bs-body-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--bs-card-bg);
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .profile-img-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1rem;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--bs-card-bg);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
        }

        .profile-img-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: var(--bs-secondary-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--placeholder-color);
            font-size: 3rem;
        }

        .section-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--primary);
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary);
        }

        .btn-upload {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-upload:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-delete {
            background-color: var(--danger);
            border-color: var(--danger);
        }

        .btn-delete:hover {
            opacity: 0.9;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--placeholder-color);
            z-index: 5;
        }

        .password-input-group {
            position: relative;
        }

        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem;
                margin: 1rem;
            }
            
            .profile-img-container {
                width: 120px;
                height: 120px;
            }
        }
        #form1,#form2 {
            display: none;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Flash messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <h2>Profile Management</h2>
            <p class="text-muted">Update your profile photo and password</p>
        </div>
        <a href="td.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-1"></i> 
        </a>

        <div class="row">
            <div class="col-md-6" >
                <h3 class="section-title">Profile Photo</h3>
                <button class="btn btn-primary" onclick="show1()">
                    update photo
                </button>
                <div id="form2">
                <div class="profile-img-container">
                    <?php if (!empty($photo) && file_exists($photo)): ?>
                        <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-img" alt="Profile Photo">
                    <?php else: ?>
                        <div class="profile-img-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" enctype="multipart/form-data" class="mt-3">
                    <div class="mb-3">
                        <label for="photo" class="form-label">Upload new photo</label>
                        <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
                        <div class="form-text">JPG, PNG or GIF (Max 10MB)</div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="upload_photo" class="btn btn-upload btn-sm">
                            <i class="fas fa-upload me-1"></i> Upload
                        </button>
                        
                        <?php if (!empty($photo) && $photo !== 'default-profile.jpg'): ?>
                            <button type="submit" name="delete_photo" class="btn btn-delete btn-sm">
                                <i class="fas fa-trash me-1"></i> Remove
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                </div>
            </div>

            <div class="col-md-6">
                <h3 class="section-title">Change Password</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                    <button id="btn-show" class="btn btn-info" onclick="show();">
                        click here
                    </button>
                <form method="POST" id="form1" >
                    <div class="mb-3 password-input-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <i class="fas fa-eye password-toggle mt-3" onclick="togglePassword('current_password')"></i>
                    </div>
                    <div class="mb-3 password-input-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <i class="fas fa-eye password-toggle mt-3" onclick="togglePassword('new_password')"></i>
                    </div>
                    <div class="mb-3 password-input-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye password-toggle mt-3" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i> Change Password
                        </button>
                        <a href="td.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Theme toggle button -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon" id="themeIcon"></i>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function show1() {
            var photo = document.getElementById("form2")
            if (photo.style.display === "none") {
                photo.style.display = "block";
            } else {
                photo.style.display = "none";
            }      
        }
        function show(){
            var form = document.getElementById("form1")
            if (form.style.display === "none") {
                form.style.display = "block"; // Show the form
            } else {
                form.style.display = "none"; // Hide the form
            }
        }
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    new bootstrap.Alert(alert).close();
                });
            }, 3000);

            // Initialize theme from cookie
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const themeIcon = document.getElementById('themeIcon');
            
            if (currentTheme === 'dark') {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }

            // Password toggle functionality
            window.togglePassword = function(id) {
                const input = document.getElementById(id);
                const icon = input.nextElementSibling;
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            };

            // Theme toggle functionality
            document.getElementById('themeToggle').addEventListener('click', function() {
                const html = document.documentElement;
                const themeIcon = document.getElementById('themeIcon');
                const newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                
                // Update theme
                html.setAttribute('data-bs-theme', newTheme);
                
                // Update icon
                if (newTheme === 'dark') {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                } else {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                }
                
                // Save preference in cookie (expires in 30 days)
                document.cookie = `theme=${newTheme}; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Lax`;
            });
        });
    </script>
</body>
</html>
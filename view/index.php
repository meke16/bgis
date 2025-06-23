<?php
session_start();
include 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_name'] = $student['name'];
            header('Location: dashboard.php');
            exit();
        } else {
            header('Location: index.php?msg=error');
            exit();
        }
    } catch (PDOException $e) {
        header('Location: index.php?loginerror');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #0056b3;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --text-color: #495057;
            --border-color: #dee2e6;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            --input-focus-shadow: rgba(0, 123, 255, 0.25);
            --background-transition: background-color 0.3s ease;
            --text-transition: color 0.3s ease;
            --box-shadow-transition: box-shadow 0.3s ease;
        }

        [data-bs-theme="dark"] {
            --primary-color: #64b5f6;
            --secondary-color: #42a5f5;
            --light-gray: #343a40;
            --dark-gray: #f8f9fa;
            --text-color: #e9ecef;
            --border-color: #6c757d;
            --box-shadow: 0 0.5rem 1rem rgba(255, 255, 255, 0.1);
            --input-focus-shadow: rgba(100, 181, 246, 0.25);
            --body-bg: #212529;
            --card-bg: #343a40;
        }

        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
            transition: var(--background-transition);
            color: var(--text-color);
        }

        [data-bs-theme="dark"] body {
            background-color: var(--body-bg);
            background-image: linear-gradient(135deg, #37474f 0%, #263238 100%);
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            background-color: white;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
        }

        [data-bs-theme="dark"] .login-container {
            background-color: var(--card-bg);
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo img {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            height: 150px;
        }

        .logo h2 {
            color: var(--dark-gray);
            font-weight: 600;
            margin-bottom: 5px;
            transition: var(--text-transition);
        }

        [data-bs-theme="dark"] .logo h2 {
            color: var(--dark-gray);
        }

        .logo p {
            color: #6c757d;
            font-size: 0.95rem;
            transition: var(--text-transition);
        }

        [data-bs-theme="dark"] .logo p {
            color: #adb5bd;
        }

        .form-floating {
            margin-bottom: 1.95rem;
        }

        .form-floating label {
            color: #6c757d;
            transition: var(--text-transition);
        }

        [data-bs-theme="dark"] .form-floating label {
            color: #adb5bd;
        }

        .form-control {
            border-radius: 10px;
            padding: 16px 12px;
            border: 1px solid var(--border-color);
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease, color 0.3s ease;
            background-color: white;
            color: var(--text-color);
        }

        [data-bs-theme="dark"] .form-control {
            background-color: #495057;
            border-color: #6c757d;
            color: #e9ecef;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem var(--input-focus-shadow);
        }

        .btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: background-color 0.3s ease;
            width: 100%;
            color: white;
        }

        .btn-login:hover {
            background-color: var(--secondary-color);
        }
        .footer-links {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
            color: var(--text-color);
            border: none;
        }

        [data-bs-theme="dark"] .input-group-text {
            color: #e9ecef;
        }

        .password-toggle {
            cursor: pointer;
            background-color: transparent;
            color: var(--text-color);
            position: absolute;
            right: 10px;
            bottom: 16px;
            font-size: 20px;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .with-icon {
            border-left: none;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            font-size: 1.2rem;
            color: var(--text-color);
            transition: color 0.3s ease;
        }

        .theme-toggle:focus {
            outline: none;
        }

        [data-bs-theme="dark"] .theme-toggle {
            color: rgb(57, 139, 220);
        }
    </style>
</head>

<body data-bs-theme="light">
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    <div class="login-container">
        <div class="logo">
            <img src="../logo/bg.jpg" alt="School Logo">
            <h2>Student Login</h2>
            <p class="text-muted">Enter your credentials to access your account</p>
        </div>
        <!-- Alert Message -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] == 'error'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>Invalid username or password
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['msg'] == 'loginerror'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>Login Error
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div> <?php elseif ($_GET['msg'] === 'logout'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>You have been logged out successfully
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div> <?php endif; ?>
        <?php endif; ?>


        <form action="index.php" method="POST" autocomplete="off">
            <div class="form-floating mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control with-icon" id="username" name="username" placeholder="Username" required>
                </div>
            </div>
            <div class="form-floating">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control with-icon" id="password" name="password" placeholder="Password" required> 
                </div>
                <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    // auto remove for alert Message 
        setTimeout(() => {
            const alertBox = document.querySelector('.alert');
            if (alertBox) alertBox.remove();

            // Remove `?msg=...` from URL
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url.pathname);
        }, 3000);
        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-bs-theme', newTheme);

            const icon = themeToggle.querySelector('i');
            if (newTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
    </script>
</body>

</html>
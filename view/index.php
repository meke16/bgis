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
            echo '<div class="error">Invalid username or password</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error">Login error: ' . $e->getMessage() . '</div>';
    }
}
 ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Information System - Grade 9 Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #f1c40f;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            min-height: 100vh;
        }
        
        .login-container {
            max-width: 420px;
            margin: 80px auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            background-color: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .logo h2 {
            color: var(--dark-gray);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 16px 12px;
            border: 1px solid #e0e0e0;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.2);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            background-color: var(--secondary-color);
        }
        
        .divider {
            position: relative;
            margin: 2rem 0;
            text-align: center;
            color: #6c757d;
        }
        
        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .divider span {
            position: relative;
            z-index: 2;
            background-color: white;
            padding: 0 15px;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
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
        }
        
        .password-toggle {
            cursor: pointer;
            background-color: transparent;
            border-left: none;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .with-icon {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h2>Student Information System</h2>
                <p>student Portal</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>Invalid username or password
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>You have been logged out successfully
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form action="index.php" method="POST" autocomplete="off">
                <div class="form-floating">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control with-icon" id="username" name="username" placeholder="Username" required>
                    </div>
                </div>
                
                <div class="form-floating">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control with-icon" id="password" name="password" placeholder="Password" required>
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
                
                <!-- <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div> -->
            </form>
            
            <!-- <div class="divider">
                <span>OR</span>
            </div>
            
            <div class="footer-links">
                <a href="admincontact.php"><i class="fas fa-question-circle me-1"></i>Forgot password?</a> · 
                <a href="admincontact.php"><i class="fas fa-envelope me-1"></i>Contact support</a>
            </div> -->
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
    </script>
</body>
</html>
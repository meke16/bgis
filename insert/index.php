<?php
session_start();

// Include database configuration
require_once 'connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = "Please enter both username and password.";
    } else {
        $inputUsername = trim($_POST['username']);
        $inputPassword = trim($_POST['password']);

        try {
            // Get user from database
            $stmt = $conn->prepare("SELECT t.id, t.username, t.password, t.name, 
                                  ta.subject_id, ta.grade, ta.section, s.name AS subject_name
                                  FROM teachers t
                                  LEFT JOIN teacher_assignments ta ON t.id = ta.teacher_id
                                  LEFT JOIN subjects s ON ta.subject_id = s.id
                                  WHERE t.username = ? AND t.role = 'teacher'");
            $stmt->bind_param("s", $inputUsername);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $user = $rows[0]; // base user data
            
                if (password_verify($inputPassword, $user['password'])) {
                    session_regenerate_id(true);
            
                    // Collect all assignments
                    $assignments = [];
                    foreach ($rows as $row) {
                        $assignments[] = [
                            'subject_id' => $row['subject_id'],
                            'subject_name' => $row['subject_name'],
                            'grade' => $row['grade'],
                            'section' => $row['section']
                        ];
                    }
            
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'authenticated' => true,
                        'role' => 'teacher',
                        'assignments' => $assignments,
                        'LAST_ACTIVITY' => time()
                    ];
            
                    header("Location: td.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --light-gray: #e9ecef;
            --border-radius: 6px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --error-color: #e74c3c;
            --success-color: #2ecc71;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .login-header img {
            height: 80px;
            margin-bottom: 1rem;
        }
        
        .form-floating .input-group-text {
            background-color: var(--light-gray);
            border-right: none;
        }
        
        .form-control.with-icon {
            border-left: none;
        }
        
        .password-toggle {
            cursor: pointer;
            background-color: var(--light-gray);
            border-left: none;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            padding: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
        }
        
        .error-message {
            color: var(--error-color);
            font-size: 0.9rem;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../logo/bgis.png" alt="School Logo" class="img-fluid">
            <h2>Teacher Login</h2>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-floating mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control with-icon" id="username" name="username" 
                           placeholder="Username" required autofocus>
                </div>
            </div>
            
            <div class="form-floating mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control with-icon" id="password" name="password" 
                           placeholder="Password" required>
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100 py-2">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
            
            <div class="footer-text mt-3">
                <p>Forgot password? <a href="forgot_password.php">Reset here</a></p>
                <p>Don't have an account? Contact administration</p>
            </div>
        </form>
    </div>

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
    </script>
</body>
</html>
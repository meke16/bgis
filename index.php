<?php
session_start();
include('connect.php');
require 'functions/functions.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = test_input($_POST["username"]);
    $password = test_input($_POST["password"]);
    
    // Prepare the query
    $stmt = $conn->prepare("SELECT * FROM admin  LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
        // Now, verify the password (since it's hashed in the database)
        if (password_verify($password, $user["password"]) && $user['username'] == $username) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = true;
            $_SESSION['msg'] = "logged in successfully";
            echo "<script> window.location.href='home.php'; alert('logged in  successfully!'); </script>";
            exit();
        } else if($user['username'] != $username) {
            $error = "Invalid username";
        } 
        else {
            $error = "Invalid Password";
        }
    } 
$admin_info = $conn->query("SELECT * FROM admin ")->fetch_assoc();
if(isset($admin_info)) {
    $admin_username = $admin_info["username"];
} else {
    $admin_username = "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Student Management System</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        
        .login-container {
            display: flex;
            max-width: 1200px;
            width: 90%;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .login-illustration {
            flex: 1;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            text-align: center;
        }
        
        .login-illustration img {
            max-width: 80%;
            margin-bottom: 30px;
        }
        
        .login-illustration h2 {
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .login-form {
            flex: 1;
            background-color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            height: 150px;
        }
        
        .login-form h1 {
            color: var(--dark-color);
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.2rem;
        }
        
        .login-form p {
            color: #7f8c8d;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
            border: 3px solid rgb(189, 186, 8);

        }
        .form-control input {
            border-color:rgb(221, 91, 16);
            border: 3px solid rgb(189, 186, 8);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #95a5a6;
            font-size: 1.2rem;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        
        .forgot-password a {
            color: #95a5a6;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: var(--primary-color);
        }
        
        .footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            color: var(--dark-color);
            font-size: 0.8rem;
        }
        
        .alert {
            margin-bottom: 20px;
        }
        
        @media (max-width: 992px) {
            .login-illustration {
                display: none;
            }
            
            .login-container {
                width: 100%;
                max-width: 500px;
            }
        }
         @media (min-width: 1200px) {
            .login-container {
                max-width: 1000px;
                width: 100%;
                max-height: 90vhlo;
            }
        }
                .x {
                    position: absolute; 
                    right:10px; 
                    font-size: 25px;
                    color: black;
                    cursor: pointer;
                }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-illustration">
            <img src="https://img.icons8.com/clouds/300/000000/school.png" alt="School Illustration">
            <h2>Student Management System</h2>
            <p>Comprehensive platform for managing student data, grades, and administration</p>
        </div>
        
        <div class="login-form">
            <div class="logo">
                <!-- Replace with your actual logo -->
                <img src="logo/bg.jpg" alt="School Logo">
            </div>
            
            <h1>Admin Portal</h1>
            <p>Please enter your credentials to access the dashboard</p>
            
        <?php if (!empty($error)): ?>
            <div id="alert" class="alert alert-danger mb-3">
                <?php echo htmlspecialchars($error); ?>
                <span class="x" onclick="document.getElementById('alert').style.display = 'none'">X</span>
            </div>
        <?php endif; ?>   
            <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
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
                
                <!-- <div class="forgot-password">
                    <a href="#.php">Forgot Password?</a>
                </div> -->
                <?php if(!empty($admin_username)) :?>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <?php endif; ?>
            <div class="text-center mt-3">
                <?php
                if(empty($admin_username)) {
                   echo" <p> You Don't have Register , Yet? <a href='register.php'>Register here</a></p> ";
                } else {
                     "<button class='btn btn-primary'>
                    <i class='fas fa-sign-up-alt'></i> Admin Have Registered Already!.
                </button>";
                }
                ?>
            </div>
            </form>
        </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
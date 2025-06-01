<?php
session_start();  
include_once('connect.php');
require 'functions/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = test_input($_POST["name"]);
    $sex = test_input($_POST["sex"]);
    $contact = test_input($_POST["contact"]);
    $username = test_input($_POST["username"]);
    $password = test_input($_POST["password"]);

    // Check if an admin already exists
    $check = $conn->query("SELECT * FROM admin LIMIT 1");
    
    if ($check->num_rows > 0) {
        // tell existing admin message
        echo "<script> window.location.href='index.php'; alert('Admin have registered already!'); </script>";
    } else {
        // Insert new admin (only if none exists)
        $stmt = $conn->prepare("INSERT INTO admin (name, sex, contact, username, password) VALUES (?, ?, ?, ?, ?)");

        // $options = [
        //     'cost' => 25
        // ];
        $hashedPwd = password_hash($password, PASSWORD_BCRYPT);
        // echo "hashed pwd:".$password;
        $stmt->bind_param("sssss", $name, $sex, $contact, $username, $hashedPwd);
        
        if ($stmt->execute()) {
            echo "<script>alert('Admin account created successfully!'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Registration failed. Please try again.')</script>";
        }
        $conn->close();
        $stmt = null;
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration | Student Management System</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Use the same styles as your login page */
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
        
        .register-container {
            height: 100vh; 
            max-width: 800px;
            width: 75%;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            overflow-y: auto; 
            background-color: white;
            padding: 40px;
        }
        
        .logo {
            background-color:rgb(139, 148, 143);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            height: 150px;
            border-radius: 60%;
        }
        
        h1 {
            color: var(--dark-color);
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.2rem;
        }
        
        p {
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
            padding: 12px 15px;
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
        }
        
        .btn-register {
            background-color: var(--success-color);
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
        
        .btn-register:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            color: var(--dark-color);
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
             <img src="logo/bg.jpg" alt="School Logo">
        </div>
        
        <h1>Admin Registration</h1>
        <p>Create a new admin account</p>
        
        <form action="<?php $_SERVER['PHP_SELF'] ?>" method="POST">
               <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label for="sex">Gender</label>
                <select class="form-control" id="sex" name="sex" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter contact number" required>
            </div>
            
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
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Register
            </button>
            
            <!-- <div class="text-center mt-3">
                <p>Already have an account? <a href="index.php">Login here</a></p>
            </div> -->
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
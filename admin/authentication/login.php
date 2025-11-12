<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../dashboard/index.php");
    exit();
}

// Handle form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded login check
    if ($username === 'admin' && $password === 'admin@123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        // Set last login time
        $_SESSION['last_login'] = date('Y-m-d H:i:s');
        
        header("Location: ../dashboard/index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
date_default_timezone_set('Asia/Kolkata');
// Time-based greeting
$hour = date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | T2/T3 Assessment System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Styles -->
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --primary-light: #4cc9f0;
            --secondary: #7209b7;
            --accent: #f72585;
            --dark: #1f1f1f;
            --light: #f8f9fa;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --gray-dark: #343a40;
            --gray: #6c757d;
            --gray-light: #f1f3f5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            overflow: hidden;
        }
        
        .login-page {
            height: 100vh;
            display: flex;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(58, 12, 163, 0.1));
        }
        
        .login-left {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }
        
        .login-right {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .login-card {
            width: 100%;
            max-width: 450px;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            z-index: -1;
        }
        
        .login-card::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            z-index: -1;
        }
        
        .login-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: var(--gray);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray-dark);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: var(--gray-light);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
        }
        
        .btn-login:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(67, 97, 238, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
        }
        
        .error-message {
            background-color: rgba(239, 71, 111, 0.1);
            color: var(--danger);
            padding: 0.8rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .error-message i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        
        /* Left side illustration styles */
        .illustration-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .illustration-text {
            color: white;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .illustration-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .illustration-text p {
            font-size: 1.1rem;
            max-width: 80%;
            margin: 0 auto 2rem;
            opacity: 0.9;
        }
        
        .feature-list {
            list-style: none;
            margin: 0 auto;
            max-width: 80%;
            text-align: left;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            opacity: 0;
            animation: fadeIn 0.5s forwards;
        }
        
        .feature-item:nth-child(1) {
            animation-delay: 0.3s;
        }
        
        .feature-item:nth-child(2) {
            animation-delay: 0.6s;
        }
        
        .feature-item:nth-child(3) {
            animation-delay: 0.9s;
        }
        
        .feature-item i {
            background-color: rgba(255, 255, 255, 0.2);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1rem;
        }
        
        /* Pattern background */
        .pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.05) 5%, transparent 5%),
                radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.05) 5%, transparent 5%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.05) 8%, transparent 8%),
                radial-gradient(circle at 30% 70%, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.05) 3%, transparent 3%),
                radial-gradient(circle at 70% 30%, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.05) 3%, transparent 3%);
            background-size: 200px 200px;
        }
        
        .animated-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }
        
        .shape-1 {
            width: 120px;
            height: 120px;
            top: 20%;
            left: 10%;
            animation-duration: 25s;
        }
        
        .shape-2 {
            width: 80px;
            height: 80px;
            top: 60%;
            left: 20%;
            animation-duration: 20s;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            top: 40%;
            right: 15%;
            animation-duration: 30s;
            animation-delay: 1s;
        }
        
        .shape-4 {
            width: 60px;
            height: 60px;
            bottom: 10%;
            right: 30%;
            animation-duration: 18s;
        }
        
        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            opacity: 0.8;
            transition: opacity 0.3s;
            z-index: 10;
        }
        
        .back-to-home:hover {
            opacity: 1;
        }
        
        .back-to-home i {
            margin-right: 5px;
        }
        
        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(-10px, 20px) rotate(90deg);
            }
            50% {
                transform: translate(10px, 40px) rotate(180deg);
            }
            75% {
                transform: translate(30px, 10px) rotate(270deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .login-page {
                flex-direction: column;
            }
            
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 1rem;
                min-height: 100vh;
            }
            
            .login-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <!-- Left side - Illustration & Features -->
        <div class="login-left">
            <a href="../../index.php" class="back-to-home">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            <div class="pattern"></div>
            <div class="animated-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
                <div class="shape shape-4"></div>
            </div>
            <div class="illustration-container">
                <div class="illustration-text">
                    <h2>T2/T3 Assessment System</h2>
                    <p>Advanced administrative tools for managing student assessments, faculty assignments, and academic operations.</p>
                    
                    <ul class="feature-list">
                        <li class="feature-item">
                            <i class="fas fa-user-shield"></i>
                            <span>Comprehensive user management for students and faculty</span>
                        </li>
                        <li class="feature-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Academic structure setup with departments, sections, and courses</span>
                        </li>
                        <li class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Real-time assessment tracking and analytics</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Right side - Login Form -->
        <div class="login-right">
            <div class="login-card">
                <div class="login-header">
                    <h1><?php echo $greeting; ?>, Admin</h1>
                    <p>Sign in to your account to access the admin dashboard</p>
                </div>
                
                <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <form method="post" id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user icon"></i>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock icon"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Login button animation
        const loginBtn = document.getElementById('loginBtn');
        const loginForm = document.getElementById('loginForm');
        
        if (loginBtn && loginForm) {
            loginBtn.addEventListener('click', function(e) {
                if (loginForm.checkValidity()) {
                    e.preventDefault();
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
                    setTimeout(() => {
                        loginForm.submit();
                    }, 1500);
                }
            });
        }
        
        // Form input focus effects
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.icon').style.color = '#4361ee';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.icon').style.color = '#6c757d';
            });
        });
        
        // Password visibility toggle
        const passwordField = document.getElementById('password');
        if (passwordField) {
            const toggleBtn = document.createElement('i');
            toggleBtn.className = 'fas fa-eye';
            toggleBtn.style.position = 'absolute';
            toggleBtn.style.right = '15px';
            toggleBtn.style.top = '50%';
            toggleBtn.style.transform = 'translateY(-50%)';
            toggleBtn.style.cursor = 'pointer';
            toggleBtn.style.color = '#6c757d';
            
            passwordField.parentElement.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.className = 'fas fa-eye-slash';
                } else {
                    passwordField.type = 'password';
                    this.className = 'fas fa-eye';
                }
            });
        }
    });
    </script>
</body>
</html>
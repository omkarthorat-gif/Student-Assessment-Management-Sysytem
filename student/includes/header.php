<?php
// student/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/t2_t3_assessment/student/assets/css/style.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4bb543;
            --info: #4895ef;
            --warning: #f9c74f;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-dark: #343a40;
            --gray: #6c757d;
            --gray-light: #f1f3f5;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            overflow-x: hidden;
        }
        
        .student-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(to bottom, #3a86ff, #1a56cc);
            color: white;
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
            position: fixed;
            height: 100vh;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .sidebar .nav-link {
            padding: 12px 20px;
            color: rgba(255,255,255,0.85);
            border-radius: 5px;
            margin: 4px 12px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.15);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content-wrapper {
            width: calc(100% - 280px);
            margin-left: 280px;
            transition: all 0.3s;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .top-navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-toggler {
            border: none;
            background: transparent;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .main-content {
            padding: 30px;
            flex-grow: 1;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        /* Student Portal Specific Styles */
        .subject-card {
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .subject-card:hover {
            border-left-color: var(--secondary);
        }
        
        .assessment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: rgba(249, 199, 79, 0.2);
            color: #d68102;
        }
        
        .status-submitted {
            background-color: rgba(72, 149, 239, 0.2);
            color: #0d6efd;
        }
        
        .status-assessed {
            background-color: rgba(75, 181, 67, 0.2);
            color: #198754;
        }
        
        .upload-box {
            border: 2px dashed var(--gray-light);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-box:hover {
            border-color: var(--primary);
        }
        
        .upload-box i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 15px;
        }
        
        .file-preview {
            margin-top: 15px;
            padding: 10px;
            background-color: var(--gray-light);
            border-radius: 5px;
            display: none;
        }
        
        .marks-table th {
            background-color: var(--gray-light);
        }
        
        /* Progress steps for document status */
        .progress-steps {
            display: flex;
            margin: 20px 0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step:not(:last-child):after {
            content: "";
            position: absolute;
            top: 25px;
            left: 50%;
            width: 100%;
            height: 2px;
            background-color: var(--gray-light);
            z-index: 0;
        }
        
        .step.active:not(:last-child):after {
            background-color: var(--primary);
        }
        
        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
            z-index: 1;
        }
        
        .step.active .step-icon {
            background-color: var(--primary);
            color: white;
        }
        
        .step-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                margin-left: -280px;
            }
            
            .content-wrapper {
                width: 100%;
                margin-left: 0;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .content-wrapper.active {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }
    </style>
</head>
<body>
<div class="student-wrapper">
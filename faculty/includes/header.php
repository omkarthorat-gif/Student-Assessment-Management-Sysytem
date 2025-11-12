<!-- header.php -->
<?php
// header.php
session_start(); // Ensure session is started
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Portal - Vignan University</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary: #1a4b8c;
            --secondary: #2673dd;
            --white: #ffffff;
            --gray: #6c757d;
            --gray-light: #f5f7fb;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray-light);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .faculty-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Ensure consistency with sidebar styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            position: fixed;
            height: 100vh;
            transition: all 0.3s ease-in-out;
            z-index: 1000;
        }

        .content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all 0.3s ease-in-out;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-navbar {
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            z-index: 999;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .navbar-toggler {
            display: none; /* Hidden by default, shown in mobile */
            border: none;
            background: transparent;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }

            .content-wrapper.active {
                margin-left: 250px;
                width: calc(100% - 250px);
            }

            .top-navbar {
                left: 0;
            }

            .navbar-toggler {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="faculty-wrapper">
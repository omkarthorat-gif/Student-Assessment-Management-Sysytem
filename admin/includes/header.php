<?php
// admin/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #1a4b8c 0%, #2673dd 100%);
            color: #fff;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 5px;
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
        
        .vignan-logo {
            max-width: 200px;
            margin: 5px auto;
            display: block;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 0 20px 20px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-left: 3px solid #4dabf7;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-left: 3px solid #ffffff;
            font-weight: bold;
        }
        
        .sidebar .nav-link i.menu-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover i.menu-icon {
            transform: scale(1.2);
        }
        
        .sidebar .nav-link i.dropdown-icon {
            font-size: 12px;
            transition: transform 0.3s;
        }
        
        .sidebar .nav-link.collapsed i.dropdown-icon {
            transform: rotate(-90deg);
        }
        
        .submenu {
            padding-left: 25px;
            list-style: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .submenu.show {
            max-height: 500px;
        }
        
        .submenu .nav-link {
            padding-left: 15px;
            font-size: 0.9rem;
        }
        
        .content-wrapper {
            margin-left: 250px;
            transition: all 0.3s ease-in-out;
            width: calc(100% - 250px);
            padding-top: 60px;
        }
        
        .top-navbar {
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            transition: all 0.3s ease-in-out;
            z-index: 999;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            background: #4dabf7;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
            transition: all 0.3s ease;
            color: white;
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
            background: #0d6efd;
        }
        
        .dropdown-menu {
            min-width: 200px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
            border-radius: 0.5rem;
        }
        
        .dropdown-menu .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
        }
        
        .dropdown-menu .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .nav-item {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }
        
        .nav-item:nth-child(1) { animation-delay: 0.1s; }
        .nav-item:nth-child(2) { animation-delay: 0.2s; }
        .nav-item:nth-child(3) { animation-delay: 0.3s; }
        .nav-item:nth-child(4) { animation-delay: 0.4s; }
        .nav-item:nth-child(5) { animation-delay: 0.5s; }
        .nav-item:nth-child(6) { animation-delay: 0.6s; }
        .nav-item:nth-child(7) { animation-delay: 0.7s; }
        .nav-item:nth-child(8) { animation-delay: 0.8s; }
        .nav-item:nth-child(9) { animation-delay: 0.9s; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }
            
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            
            .top-navbar {
                left: 0;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .content-wrapper.active {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
        }
        
        /* Dashboard Styles */
        .stats-card {
            padding: 20px;
            border-radius: 10px;
            color: white;
            height: 100%;
        }
        
        .stats-card i {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stats-card .count {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stats-card .title {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .stats-card.blue {
            background: linear-gradient(to right, #4361ee, #3a0ca3);
        }
        
        .stats-card.purple {
            background: linear-gradient(to right, #7209b7, #560bad);
        }
        
        .stats-card.green {
            background: linear-gradient(to right, #38b000, #008000);
        }
        
        .stats-card.orange {
            background: linear-gradient(to right, #ff9e00, #ff6b00);
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
    </style>
</head>
<body>
<div class="admin-wrapper">
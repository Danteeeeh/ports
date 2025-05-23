<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../adminlogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
        }

        .allaroundcontainer {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: white;
            padding: 2rem;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .sidebar small {
            color: var(--text-secondary);
            display: block;
            margin-bottom: 2rem;
        }

        .logo {
            margin: 2rem 0;
            text-align: center;
        }

        .logo img {
            max-width: 120px;
            height: auto;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar button {
            background: transparent;
            border: none;
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 1rem;
            color: var(--text-primary);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .sidebar button:hover {
            background: var(--primary-color);
            color: white;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem;
        }

        .top-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 1.25rem 2.5rem;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.95);
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            width: 100%;
            justify-content: space-between;
        }

        .welcome-message {
            font-size: 1.125rem;
            color: var(--text-primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .welcome-message::before {
            content: '👋';
            animation: wave 2s infinite;
            display: inline-block;
        }

        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-20deg); }
            75% { transform: rotate(20deg); }
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            position: relative;
        }

        .search-input {
            padding: 0.75rem 1.25rem;
            padding-left: 2.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 9999px;
            width: 250px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="%23666666"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>');
            background-repeat: no-repeat;
            background-position: 0.75rem center;
            background-size: 1.25rem;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1), 0 2px 4px -1px rgba(37, 99, 235, 0.06);
            width: 300px;
        }

        .search-input::placeholder {
            color: #94a3b8;
        }

        .notif {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1f5f9;
            transition: all 0.3s ease;
        }

        .notif::before {
            content: '';
            position: absolute;
            top: 8px;
            right: 10px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid white;
        }

        .notif:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        .notif:hover svg {
            color: white;
        }

        .notif svg {
            width: 20px;
            height: 20px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .top-bar input {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            width: 300px;
            font-size: 0.875rem;
        }

        .notif {
            font-size: 1.5rem;
            cursor: pointer;
            position: relative;
        }

        .notif::after {
            content: "";
            position: absolute;
            top: 0;
            right: -5px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        /* Content section styles */
        .content-section {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .content-section.active {
            display: block;
        }

        .content-section h1,
        .content-section h2 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 400px;
        }

        form input {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        form button {
            padding: 0.75rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        form button:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="allaroundcontainer">
        <aside class="sidebar">
            <h3>Admin Dashboard</h3>
            <small>SMS Account</small>
            <div class="logo">
                <img src="../assets/images/bcplogo.png" alt="Logo" />
            </div>
            <nav>
                <button onclick="showSection('dashboard')">Dashboard</button>
                <button onclick="showSection('teacher')">Teachers</button>
                <button onclick="showSection('studentclasses')">Student/Classes</button>
                <button onclick="showSection('settings')">Settings</button>
                <button onclick="showSection('feature')">Features</button>
                <button onclick="window.location.href='logoutadmin.php'">Logout</button>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="header-content">
                    <div class="welcome-message">
                        Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?>
                    </div>
                    <div class="header-controls">
                        <input type="text" class="search-input" placeholder="Search anything..." />
                        <div class="notif">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                    </div>
                </div>
            </header>

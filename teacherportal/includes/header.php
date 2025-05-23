<?php
// Note: Session and teacher data are already handled in teacherdashboard.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal - SIA System</title>
    <style>
        :root {
            /* Colors */
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --purple-50: #f5f3ff;
            --purple-600: #7c3aed;
            --blue-50: #eff6ff;
            --blue-600: #2563eb;
            --red-50: #fef2f2;
            --red-600: #dc2626;
            
            /* Custom Colors */
            --text-primary: var(--gray-800);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: var(--text-primary);
            line-height: 1.5;
            background: var(--gray-50);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 1rem;
            background: var(--gray-50);
        }

        /* Top Bar Styles */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: white;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 2rem;
            border-radius: 0.5rem;
        }

        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.375rem;
            width: 300px;
            font-size: 0.875rem;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }

        .notif {
            padding: 0.5rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notif:hover {
            background: var(--gray-100);
        }

        /* Content Section Styles */
        .content-section {
            display: none;
            padding: 1.5rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .content-section.active {
            display: block;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0.5rem 0;
        }

        .stat-label {
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .data-table th {
            background: var(--gray-50);
            font-weight: 500;
            color: var(--gray-700);
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <header class="top-bar">
                <input type="text" placeholder="Search..." class="search-input">
                <div class="notif">🔔</div>
            </header>
            <div class="content">

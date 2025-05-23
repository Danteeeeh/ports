<?php
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../studentlogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - BCP</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
            
            /* Shadows */
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            
            /* Transitions */
            --transition-all: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Logo Styles */
        .logo-container {
            padding: 24px;
            margin-bottom: 12px;
            text-align: center;
        }

        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            transition: var(--transition-all);
            box-shadow: var(--shadow-sm);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--gray-100);
        }

        .logo:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .logo img {
            width: 85%;
            height: 85%;
            object-fit: contain;
            transition: var(--transition-all);
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        .logo::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                var(--purple-50) 0%,
                var(--blue-50) 100%
            );
            opacity: 0;
            transition: var(--transition-all);
        }

        .logo:hover::after {
            opacity: 0.5;
        }

        .dashboard-title {
            margin-top: 16px;
            text-align: center;
        }

        .dashboard-title h1 {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .dashboard-title p {
            font-size: 14px;
            color: var(--gray-700);
            margin: 4px 0 0 0;
        }

        /* Enhanced Button Base Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            color: var(--gray-700);
            position: relative;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .btn:hover::before {
            opacity: 1;
        }

        /* Icon Styles */
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
        }

        .btn svg {
            width: 16px;
            height: 16px;
            transition: var(--transition-all);
        }

        /* Button Variants */
        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray-50);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-100);
            color: var(--gray-800);
        }

        .btn-outline {
            border: 1px solid var(--gray-200);
            background: transparent;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Enhanced Navigation Menu */
        .nav-menu {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0 1rem;
        }

        /* Enhanced Navigation Button Styles */
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 14px 20px;
            border-radius: 16px;
            border: none;
            background: transparent;
            color: var(--gray-700);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-align: left;
            -webkit-font-smoothing: antialiased;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: inherit;
        }

        .nav-btn:hover::before {
            opacity: 1;
        }

        .nav-btn .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .nav-btn:hover .btn-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-btn svg {
            width: 18px;
            height: 18px;
            stroke-width: 2;
            transition: transform 0.3s ease;
        }

        .nav-btn:hover svg {
            transform: scale(1.1);
        }

        .nav-btn span:not(.btn-icon) {
            font-weight: 500;
            letter-spacing: 0.01em;
        }

        /* Button Variants */
        .nav-btn.btn-secondary {
            background: var(--gray-50);
        }

        .nav-btn.btn-secondary:hover {
            background: var(--gray-100);
            transform: translateY(-1px);
        }

        .nav-btn.btn-outline {
            background: white;
            border: 1px solid var(--gray-200);
        }

        .nav-btn.btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
        }

        .nav-btn.btn-purple {
            background: var(--purple-50);
            color: var(--purple-600);
        }

        .nav-btn.btn-purple:hover {
            background: var(--purple-600);
            color: white;
            transform: translateY(-1px);
        }

        .nav-btn.btn-purple:hover .btn-icon {
            background: rgba(255, 255, 255, 0.9);
        }

        .nav-btn.btn-blue {
            background: var(--blue-50);
            color: var(--blue-600);
        }

        .nav-btn.btn-blue:hover {
            background: var(--blue-600);
            color: white;
            transform: translateY(-1px);
        }

        .nav-btn.btn-blue:hover .btn-icon {
            background: rgba(255, 255, 255, 0.9);
        }

        .nav-btn.btn-red {
            background: var(--red-50);
            color: var(--red-600);
            margin-top: 1rem;
        }

        .nav-btn.btn-red:hover {
            background: var(--red-600);
            color: white;
            transform: translateY(-1px);
        }

        .nav-btn.btn-red:hover .btn-icon {
            background: rgba(255, 255, 255, 0.9);
        }

        /* Active State */
        .nav-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .nav-btn.active .btn-icon {
            background: rgba(255, 255, 255, 0.9);
        }

        .nav-btn.active:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
        }

        .nav-btn svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
            transition: var(--transition-all);
        }

        .nav-btn:hover svg {
            transform: scale(1.1);
        }

        /* Enhanced Color Variants */
        .btn-purple {
            background: var(--purple-50);
            color: var(--purple-600);
            box-shadow: 0 2px 4px rgba(124, 58, 237, 0.06);
        }

        .btn-purple:hover {
            background: var(--purple-600);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.15);
        }

        .btn-purple:active {
            transform: translateY(0);
        }

        .btn-blue {
            background: var(--blue-50);
            color: var(--blue-600);
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.06);
        }

        .btn-blue:hover {
            background: var(--blue-600);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .btn-blue:active {
            transform: translateY(0);
        }

        .btn-red {
            background: var(--red-50);
            color: var(--red-600);
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.06);
        }

        .btn-red:hover {
            background: var(--red-600);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
        }

        .btn-red:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: var(--gray-50);
            color: var(--gray-700);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        .btn-secondary:hover {
            background: var(--gray-100);
            color: var(--gray-800);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        /* Size Variants */
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-lg {
            padding: 12px 20px;
            font-size: 16px;
        }

        /* Loading State */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading:after {
            content: '';
            width: 14px;
            height: 14px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: button-loading 0.6s linear infinite;
        }

        @keyframes button-loading {
            to {
                transform: rotate(360deg);
            }
        }

        /* Disabled State */
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Group Buttons */
        .btn-group {
            display: inline-flex;
            border-radius: 8px;
            overflow: hidden;
        }

        .btn-group .btn {
            border-radius: 0;
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .btn-group .btn:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .btn-group .btn:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            border-right: none;
        }
    </style>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo-container">
                <div class="logo">
                    <img src="../assets/images/bcplogo.png" alt="BCP Logo" />
                </div>
                <div class="dashboard-title">
                    <h1>Student Portal</h1>
                    <p>Welcome, <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Student'; ?></p>
                </div>
            </div>
            <nav class="nav-menu" role="navigation" aria-label="Main navigation">
                <button data-page="dashboard" onclick="window.location.href='studentdashboard.php'" class="nav-btn btn-secondary" aria-label="Go to Dashboard">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </button>
                <button data-page="profile" onclick="window.location.href='smsprofile.php'" class="nav-btn btn-outline" aria-label="View Profile">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <span>Profile</span>
                </button>
                <button data-page="progress" onclick="window.location.href='studentprogress.php'" class="nav-btn btn-purple" aria-label="View Student Progress">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20V10"/>
                            <path d="M18 20V4"/>
                            <path d="M6 20v-4"/>
                        </svg>
                    </span>
                    <span>Student Progress</span>
                </button>
                <button data-page="tasks" onclick="window.location.href='upcomingtasks.php'" class="nav-btn btn-blue" aria-label="View Upcoming Tasks">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 6h13"/>
                            <path d="M8 12h13"/>
                            <path d="M8 18h13"/>
                            <path d="M3 6h.01"/>
                            <path d="M3 12h.01"/>
                            <path d="M3 18h.01"/>
                        </svg>
                    </span>
                    <span>Upcoming Tasks</span>
                </button>
                <button data-page="concerns" onclick="window.location.href='Concern.php'" class="nav-btn btn-blue" aria-label="View Concerns">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </span>
                    <span>Concerns</span>
                </button>
                <button onclick="window.location.href='logout.php'" class="nav-btn btn-red" aria-label="Logout">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                    </span>
                    <span>Logout</span>
                </button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <input type="text" placeholder="Search..." class="search-input">
                <div class="notif">🔔</div>
            </header>

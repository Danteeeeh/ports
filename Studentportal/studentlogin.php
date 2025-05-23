<?php
session_start();

// Clear any existing session if not logged in as student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    session_destroy();
    session_start();
}

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$error = '';
$success = '';

// Redirect if already logged in as student
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student') {
    header('Location: studentdashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Sanitize inputs
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $username = trim($username);
    
    // Remove 'Ld' prefix if it exists
    $username = preg_replace('/^Ld/i', '', $username);
    
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Add debugging info
            error_log("Login attempt for username: {$username}");
            
            if ($auth->login($username, $password)) {
                // Verify user is a student
                if ($_SESSION['user_role'] === 'student') {
                    // Set last login time
                    $_SESSION['last_login'] = date('Y-m-d H:i:s');
                    
                    // Redirect to student dashboard
                    header('Location: studentdashboard.php');
                    exit();
                } else {
                    $error = 'Invalid student credentials';
                    error_log("User role is not student: {$_SESSION['user_role']}");
                    session_destroy();
                    session_start();
                }
            } else {
                $error = 'Invalid username or password';
                error_log("Auth->login() failed for username: {$username}");
            }
        } catch (Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            $error = 'System error occurred. Please try again later.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Student Login - SIA System</title>
    <meta name="description" content="Student login portal for Student Information and Attendance System">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3f37c9;
            --primary-light: #4895ef;
            --admin-color: #ef476f;
            --admin-dark: #d90429;
            --student-color: #06d6a0;
            --student-dark: #059669;
            --teacher-color: #118ab2;
            --teacher-dark: #073b4c;
            --bg-color: #f7fafc;
            --card-bg: #ffffff;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --error-color: #dc2626;
            --error-bg: #fee2e2;
            --success-color: #16a34a;
            --success-bg: #dcfce7;
            --warning-color: #ca8a04;
            --warning-bg: #fef9c3;
            --sidebar-width: 280px;
            --header-height: 70px;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #06d6a0 0%, #059669 100%);
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 1000px;
            display: flex;
            overflow: hidden;
        }

        .login-left {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right {
            flex: 1;
            background: linear-gradient(135deg, #06d6a0 0%, #059669 100%);
            padding: 3rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../assets/images/pattern.svg') center/cover;
            opacity: 0.1;
        }

        .school-logo {
            width: 80px;
            height: auto;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .school-logo:hover {
            transform: scale(1.05);
        }

        .welcome-text {
            margin-bottom: 2rem;
        }

        .welcome-text h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .welcome-text p {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.5;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
            max-width: 400px;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #06d6a0 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-group {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group input:focus {
            border-color: #06d6a0;
            box-shadow: 0 0 0 3px rgba(6, 214, 160, 0.1);
            outline: none;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            transition: all 0.3s ease;
        }

        .form-group input:focus + i {
            color: #06d6a0;
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #06d6a0 0%, #059669 100%);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(6, 214, 160, 0.2);
            background: #059669;
        }

        .login-button:active {
            transform: translateY(-1px);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: #06d6a0;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-color);
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }

        .form-hint {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .show-password {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
            cursor: pointer;
            user-select: none;
            margin-top: -1rem;
        }

        .show-password input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            margin: 0;
            accent-color: var(--student-color);
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .login-footer a {
            color: #06d6a0;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }

        .features-list {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 1.25rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .feature-item:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .feature-item i {
            font-size: 1.5rem;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }
        
        .feature-item h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .feature-item p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .error-message, .success-message {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error-message {
            background: var(--error-bg);
            border: 1px solid #fecaca;
            color: var(--error-color);
        }
        
        .error-message i {
            color: var(--error-color);
        }
        
        .success-message {
            background: var(--success-bg);
            border: 1px solid #bbf7d0;
            color: var(--success-color);
        }
        
        .success-message i {
            color: var(--success-color);
        }
        
        @media (max-width: 768px) {
            .login-card {
                flex-direction: column;
            }
            
            .login-right {
                display: none;
            }
            
            .login-left {
                padding: 2rem;
            }
        }
    </style>
    </style>
    <script>
        function togglePassword() {
            var x = document.getElementById("password");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</head>
<body>
    <div class="particles-container" id="particles-js"></div>
    <div class="login-page">
        <div class="login-card">
            <div class="login-left">
                <img src="../assets/images/bcplogo.png" alt="School Logo" class="school-logo">
                <div class="welcome-text">
                    <h1>Welcome Back!</h1>
                    <p>Please sign in to access your student portal</p>
                </div>
                
                <div class="login-form">
                    <?php if (!empty($error)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <input type="text" name="username" placeholder="Enter Student ID" required>
                            <i class="fas fa-id-card"></i>
                        </div>
                        <span class="form-hint">Enter your student username (e.g., student, student2)</span>
                        
                        <div class="form-group">
                            <input type="password" name="password" id="password" placeholder="Password" required>
                            <i class="fas fa-lock"></i>
                        </div>
                        
                        <div class="form-group">
                            <label class="show-password">
                                <input type="checkbox" onclick="togglePassword()"> Show Password
                            </label>
                        </div>
                        
                        <button type="submit" name="login" class="login-button">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Sign In</span>
                        </button>
                    </form>
                    
                    <div class="form-footer">
                        <p>Forgot your password? <a href="reset_password.php">Reset it here</a></p>
                    </div>
                </div>
                
                <div class="login-footer">
                    <p>Need help? <a href="#">Contact Support</a></p>
                    <p style="margin-top: 0.5rem;"><a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
                </div>
            </div>
            
            <div class="login-right">
                <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">Student Portal</h2>
                <p style="margin-bottom: 2rem; opacity: 0.9;">Access all your academic information in one place</p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-graduation-cap"></i>
                        <div>
                            <h3>Academic Records</h3>
                            <p>View your grades and academic progress</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <h3>Attendance Tracking</h3>
                            <p>Monitor your class attendance</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h3>Class Schedule</h3>
                            <p>Access your daily class schedule</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                particles: {
                    number: { value: 80, density: { enable: true, value_area: 800 } },
                    color: { value: '#ffffff' },
                    shape: { type: 'circle' },
                    opacity: { value: 0.5, random: false },
                    size: { value: 3, random: true },
                    line_linked: { enable: true, distance: 150, color: '#ffffff', opacity: 0.4, width: 1 },
                    move: { enable: true, speed: 2, direction: 'none', random: false, straight: false, out_mode: 'out', bounce: false }
                },
                interactivity: {
                    detect_on: 'canvas',
                    events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' }, resize: true },
                    modes: { repulse: { distance: 100, duration: 0.4 }, push: { particles_nb: 4 } }
                },
                retina_detect: true
            });
        });
    </script>
</body>
</html>
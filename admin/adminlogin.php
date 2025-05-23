<?php
session_start();

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Clear any existing session if not logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    session_destroy();
    session_start();
}

// Redirect if already logged in as admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: admindashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        if ($auth->login($username, $password)) {
            // Verify user is an admin
            if ($_SESSION['user_role'] === 'admin') {
                header('Location: admindashboard.php');
                exit();
            } else {
                $error_message = 'Invalid admin credentials';
                session_destroy();
            }
        } else {
            $error_message = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Admin Login - SIA System</title>
    <meta name="description" content="Admin login portal for Student Information and Attendance System">
</head>
<body>
    <div class="logincontainer">
        <div class="left-panel">
            <img src="../assets/images/bcplogo.png" alt="School Logo" class="logo" />
            <h2>Welcome Back, Admin!</h2>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form class="login-form" method="POST" action="adminlogin.php">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter your username" class="input-box" required />
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter your password" class="input-box" required />
                </div>
                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i> Sign In to Dashboard
                </button>
            </form>
            <div class="login-footer">
                <p>Need help? Contact your system administrator</p>
            </div>
        </div>
        <div class="right-panel">
            <h1 class="system-name">Grade Central System</h1>
            <p class="system-description">Access and manage student information, grades, and attendance records through our comprehensive administrative dashboard.</p>
            <i class="fas fa-graduation-cap fa-3x" style="margin-top: 30px;"></i>
        </div>
    </div>
</body>
</html>
<?php
// Direct login script for student account
session_start();

require_once 'includes/Database.php';
require_once 'includes/Auth.php';

// Clear any existing session
session_destroy();
session_start();

// Create database connection
$db = new Database();
$auth = new Auth($db);

// Student credentials
$username = "student";
$password = "student123";

echo "<h1>Direct Student Login</h1>";

// Attempt login
if ($auth->login($username, $password)) {
    echo "<p style='color:green'>Login successful!</p>";
    echo "<p>You are now logged in as: " . $_SESSION['username'] . "</p>";
    echo "<p>User role: " . $_SESSION['user_role'] . "</p>";
    
    echo "<p><a href='Studentportal/studentdashboard.php'>Go to Student Dashboard</a></p>";
} else {
    echo "<p style='color:red'>Login failed!</p>";
    
    // Check if user exists
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>User exists in database.</p>";
        
        // Test password verification
        if (password_verify($password, $user['password'])) {
            echo "<p style='color:green'>Password is correct, but login still failed.</p>";
        } else {
            echo "<p style='color:red'>Password is incorrect.</p>";
        }
    } else {
        echo "<p style='color:red'>User not found in database.</p>";
    }
}
?>

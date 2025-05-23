<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the session cookie parameters
$params = session_get_cookie_params();

// Unset all session variables
$_SESSION = array();

// Clear the session cookie by setting it to expire in the past
setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
);

// Destroy the session
session_destroy();

// Clear any other cookies if they exist
setcookie('remember_me', '', time() - 42000, '/');
setcookie('student_id', '', time() - 42000, '/');
setcookie('PHPSESSID', '', time() - 42000, '/');

// Redirect to login page with a logout message
header('Location: teacherlogin.php?msg=logout_success');
exit();

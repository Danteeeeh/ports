<?php
// Simple script to test student login
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/Database.php';
require_once 'includes/Auth.php';

// Create database connection
$db = new Database();
$auth = new Auth($db);

echo "<h1>Student Login Test</h1>";

// Check database connection
if ($db->getConnection()) {
    echo "<p style='color:green'>Database connection successful</p>";
    
    // Check if users table exists
    $query = "SELECT * FROM users WHERE role = 'student' LIMIT 1";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo "<p>Found student user: " . $student['username'] . " (ID: " . $student['user_id'] . ")</p>";
        
        // Test login with student123
        $testUsername = $student['username'];
        $testPassword = "student123";
        
        echo "<p>Testing login with:<br>Username: $testUsername<br>Password: $testPassword</p>";
        
        // Add debugging to the login process
        try {
            // First query the users table
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            if (!$stmt) {
                echo "<p style='color:red'>Prepare failed: " . $db->getConnection()->error . "</p>";
            } else {
                $stmt->bind_param('s', $testUsername);
                if (!$stmt->execute()) {
                    echo "<p style='color:red'>Execute failed: " . $stmt->error . "</p>";
                } else {
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        echo "<p>User found in database</p>";
                        
                        // Test password verification
                        if (password_verify($testPassword, $user['password'])) {
                            echo "<p style='color:green'>Password verification successful</p>";
                            
                            // Check user details
                            $details = null;
                            $role = $user['role'];
                            $user_id = $user['user_id'];
                            
                            // Get user details based on role
                            $table = '';
                            $id_field = '';
                            
                            switch($role) {
                                case 'student':
                                    $table = 'students';
                                    $id_field = 'student_id';
                                    break;
                                default:
                                    echo "<p style='color:red'>Invalid role: $role</p>";
                                    break;
                            }
                            
                            if ($table) {
                                $detailsQuery = "SELECT * FROM {$table} WHERE {$id_field} = ?";
                                $stmt = $db->prepare($detailsQuery);
                                if (!$stmt) {
                                    echo "<p style='color:red'>Failed to prepare details query: " . $db->getConnection()->error . "</p>";
                                } else {
                                    $stmt->bind_param('s', $user_id);
                                    if (!$stmt->execute()) {
                                        echo "<p style='color:red'>Failed to execute details query: " . $stmt->error . "</p>";
                                    } else {
                                        $detailsResult = $stmt->get_result();
                                        if ($detailsResult && $detailsResult->num_rows > 0) {
                                            $details = $detailsResult->fetch_assoc();
                                            echo "<p style='color:green'>User details found</p>";
                                            
                                            // Now try the actual login
                                            if ($auth->login($testUsername, $testPassword)) {
                                                echo "<p style='color:green'>Login successful!</p>";
                                                echo "<h3>Session Data:</h3>";
                                                echo "<pre>";
                                                print_r($_SESSION);
                                                echo "</pre>";
                                            } else {
                                                echo "<p style='color:red'>Auth->login() failed despite password verification succeeding</p>";
                                            }
                                        } else {
                                            echo "<p style='color:red'>No user details found in $table table for ID: $user_id</p>";
                                            
                                            // Check if the table exists
                                            $tableCheckQuery = "SHOW TABLES LIKE '$table'";
                                            $tableResult = $db->query($tableCheckQuery);
                                            if ($tableResult && $tableResult->num_rows > 0) {
                                                echo "<p>Table '$table' exists</p>";
                                                
                                                // Check table structure
                                                $structureQuery = "DESCRIBE $table";
                                                $structureResult = $db->query($structureQuery);
                                                if ($structureResult) {
                                                    echo "<p>Table structure:</p>";
                                                    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Key</th></tr>";
                                                    while ($field = $structureResult->fetch_assoc()) {
                                                        echo "<tr><td>" . $field['Field'] . "</td><td>" . $field['Type'] . "</td><td>" . $field['Key'] . "</td></tr>";
                                                    }
                                                    echo "</table>";
                                                }
                                            } else {
                                                echo "<p style='color:red'>Table '$table' does not exist!</p>";
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            echo "<p style='color:red'>Password verification failed</p>";
                            echo "<p>Stored hash: " . $user['password'] . "</p>";
                            
                            // Generate a new hash for comparison
                            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                            echo "<p>New hash for '$testPassword': $newHash</p>";
                        }
                    } else {
                        echo "<p style='color:red'>No active user found with username: $testUsername</p>";
                    }
                }
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>No student users found in database</p>";
    }
} else {
    echo "<p style='color:red'>Database connection failed</p>";
}
?>

<?php
// Debug script to check database connection and verify login credentials
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/Database.php';
require_once 'includes/Auth.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = new Database();
    
    if ($db->getConnection()) {
        echo "<p style='color:green'>✓ Database connection successful</p>";
        
        // Test query to check if users table exists and has data
        $query = "SELECT COUNT(*) as count FROM users";
        $result = $db->query($query);
        
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>Total users in database: " . $row['count'] . "</p>";
            
            // Check student users specifically
            $query = "SELECT * FROM users WHERE role = 'student'";
            $result = $db->query($query);
            
            if ($result && $result->num_rows > 0) {
                echo "<h3>Student Users:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>User ID</th><th>Username</th><th>Role</th><th>Status</th></tr>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['user_id'] . "</td>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>" . $row['role'] . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Test login with student123 password
                echo "<h3>Login Test:</h3>";
                
                $auth = new Auth($db);
                $testUsername = "student";
                $testPassword = "student123";
                
                echo "<p>Testing login with: <br>";
                echo "Username: " . $testUsername . "<br>";
                echo "Password: " . $testPassword . "</p>";
                
                if ($auth->login($testUsername, $testPassword)) {
                    echo "<p style='color:green'>✓ Login successful!</p>";
                    
                    // Show session variables
                    echo "<h4>Session Variables:</h4>";
                    echo "<pre>";
                    print_r($_SESSION);
                    echo "</pre>";
                    
                    // Clean up session
                    session_destroy();
                } else {
                    echo "<p style='color:red'>✗ Login failed</p>";
                    
                    // Check if user exists
                    $query = "SELECT * FROM users WHERE username = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('s', $testUsername);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result && $result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        echo "<p>User exists in database. Stored password hash:</p>";
                        echo "<pre>" . $user['password'] . "</pre>";
                        
                        // Test password verification directly
                        if (password_verify($testPassword, $user['password'])) {
                            echo "<p style='color:green'>✓ Password verification successful</p>";
                        } else {
                            echo "<p style='color:red'>✗ Password verification failed</p>";
                        }
                    } else {
                        echo "<p style='color:red'>✗ User not found in database</p>";
                    }
                }
            } else {
                echo "<p style='color:red'>✗ No student users found in database</p>";
            }
        } else {
            echo "<p style='color:red'>✗ Failed to query users table: " . $db->getConnection()->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/Database.php';

function checkDatabase() {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Database Check Results:</h2>";
    
    // Check MySQL version and connection info
    echo "<h3>MySQL Information:</h3>";
    echo "Server version: " . $conn->server_info . "<br>";
    echo "Client version: " . $conn->client_info . "<br>";
    echo "Host info: " . $conn->host_info . "<br><br>";
    
    // Check if database exists
    $result = $conn->query("SELECT DATABASE()");
    $row = $result->fetch_row();
    echo "Current database: " . $row[0] . "<br><br>";
    
    // Check tables
    $tables = ['users', 'admin_users', 'teachers', 'students', 'subjects', 'attendance', 'enrolled_subjects', 'grades'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "Table '$table': " . ($result->num_rows > 0 ? "EXISTS" : "MISSING") . "<br>";
        
        if ($result->num_rows > 0) {
            // Check number of records
            $count = $conn->query("SELECT COUNT(*) as count FROM $table");
            $row = $count->fetch_assoc();
            echo "Records in '$table': " . $row['count'] . "<br>";
            
            // Show table structure
            $structure = $conn->query("DESCRIBE $table");
            echo "Structure:<br>";
            echo "<pre>";
            while ($row = $structure->fetch_assoc()) {
                echo $row['Field'] . " - " . $row['Type'] . 
                     ($row['Key'] ? " (" . $row['Key'] . ")" : "") . 
                     ($row['Extra'] ? " " . $row['Extra'] : "") . "\n";
            }
            echo "</pre><br>";
        }
    }
    
    // Check specific accounts
    echo "<h3>Checking Default Accounts:</h3>";
    
    // Check admin account
    $result = $conn->query("SELECT * FROM users WHERE role = 'admin'");
    echo "Admin accounts found: " . $result->num_rows . "<br>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Admin details:<br>";
            echo "- Username: " . $row['username'] . "<br>";
            echo "- User ID: " . $row['user_id'] . "<br>";
            echo "- Status: " . $row['status'] . "<br>";
            echo "- Password hash length: " . strlen($row['password']) . "<br>";
            
            // Check admin_users table
            $admin_result = $conn->query("SELECT * FROM admin_users WHERE admin_id = '{$row['user_id']}'");
            if ($admin_result && $admin_result->num_rows > 0) {
                $admin = $admin_result->fetch_assoc();
                echo "- Found in admin_users table: YES<br>";
                echo "- Name: " . $admin['first_name'] . " " . $admin['last_name'] . "<br>";
            } else {
                echo "- Found in admin_users table: NO<br>";
            }
        }
    }
    
    // Check teacher account
    $result = $conn->query("SELECT * FROM users WHERE role = 'teacher'");
    echo "<br>Teacher accounts found: " . $result->num_rows . "<br>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Teacher details:<br>";
            echo "- Username: " . $row['username'] . "<br>";
            echo "- User ID: " . $row['user_id'] . "<br>";
            echo "- Status: " . $row['status'] . "<br>";
            echo "- Password hash length: " . strlen($row['password']) . "<br>";
            
            // Check teachers table
            $teacher_result = $conn->query("SELECT * FROM teachers WHERE teacher_id = '{$row['user_id']}'");
            if ($teacher_result && $teacher_result->num_rows > 0) {
                $teacher = $teacher_result->fetch_assoc();
                echo "- Found in teachers table: YES<br>";
                echo "- Name: " . $teacher['first_name'] . " " . $teacher['last_name'] . "<br>";
            } else {
                echo "- Found in teachers table: NO<br>";
            }
        }
    }
    
    // Check student account
    $result = $conn->query("SELECT * FROM users WHERE role = 'student'");
    echo "<br>Student accounts found: " . $result->num_rows . "<br>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Student details:<br>";
            echo "- Username: " . $row['username'] . "<br>";
            echo "- User ID: " . $row['user_id'] . "<br>";
            echo "- Status: " . $row['status'] . "<br>";
            echo "- Password hash length: " . strlen($row['password']) . "<br>";
            
            // Check students table
            $student_result = $conn->query("SELECT * FROM students WHERE student_id = '{$row['user_id']}'");
            if ($student_result && $student_result->num_rows > 0) {
                $student = $student_result->fetch_assoc();
                echo "- Found in students table: YES<br>";
                echo "- Name: " . $student['first_name'] . " " . $student['last_name'] . "<br>";
            } else {
                echo "- Found in students table: NO<br>";
            }
        }
    }
}

// Run the check
checkDatabase();
?>

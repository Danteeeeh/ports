<?php
// Generate password hash for "student123"
$password = "student123";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password\n";
echo "Hash: $hash\n";

// Connect to database
require_once 'includes/Database.php';
$db = new Database();

// Update all student passwords
try {
    // Start transaction
    $db->beginTransaction();
    
    // Get all student user IDs
    $query = "SELECT user_id FROM users WHERE role = 'student'";
    $result = $db->query($query);
    
    if ($result) {
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $user_id = $row['user_id'];
            
            // Update password for this student
            $updateQuery = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->bind_param('ss', $hash, $user_id);
            
            if ($stmt->execute()) {
                $count++;
                echo "Updated password for user ID: $user_id\n";
            } else {
                echo "Failed to update password for user ID: $user_id\n";
            }
        }
        
        echo "Successfully updated passwords for $count students\n";
    } else {
        echo "Failed to retrieve student users\n";
    }
    
    // Commit transaction
    $db->commit();
    
    echo "All student passwords have been reset to 'student123'\n";
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
?>

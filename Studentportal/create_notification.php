<?php
require_once '../includes/Database.php';

function createNotification($studentId, $title, $message, $type = 'info') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "INSERT INTO notifications (student_id, title, message, type) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $studentId, $title, $message, $type);
    
    return $stmt->execute();
}

// Example usage:
// createNotification($studentId, 'New Grade Posted', 'Your Math grade has been updated', 'info');
// createNotification($studentId, 'Assignment Due Soon', 'You have an assignment due tomorrow', 'warning');
// createNotification($studentId, 'Perfect Score!', 'Congratulations on your perfect score!', 'success');
?>

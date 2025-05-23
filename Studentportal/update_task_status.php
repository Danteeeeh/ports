<?php
session_start();
require_once '../Database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$task_id = $data['task_id'] ?? null;
$status = $data['status'] ?? null;

if (!$task_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Update task status
    $sql = "UPDATE tasks SET status = ? WHERE id = ? AND student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $status, $task_id, $_SESSION['student_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'status' => $status,
            'message' => 'Task status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update task status'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
?>

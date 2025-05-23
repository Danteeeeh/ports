<?php
session_start();
require_once '../includes/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get notifications for the student
$sql = "SELECT * FROM notifications 
        WHERE student_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'type' => $row['type'],
        'is_read' => (bool)$row['is_read'],
        'created_at' => $row['created_at']
    ];
}

// Mark notifications as read
if (!empty($notifications)) {
    $sql = "UPDATE notifications SET is_read = TRUE 
            WHERE student_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['student_id']);
    $stmt->execute();
}

echo json_encode($notifications);
?>

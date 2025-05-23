<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

header('Content-Type: application/json');

if (!isset($_GET['subject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject ID is required']);
    exit();
}

$subject_id = $_GET['subject'];
$teacher_id = $_SESSION['user_id'];

// Verify that this teacher teaches this subject
$sql = "SELECT 1 FROM teacher_subjects 
        WHERE teacher_id = ? AND subject_id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("ss", $teacher_id, $subject_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get all students enrolled in this subject
$sql = "SELECT s.student_id, s.first_name, s.last_name, s.course, s.year_level
        FROM students s
        JOIN student_subjects ss ON s.student_id = ss.student_id
        WHERE ss.subject_id = ?
        ORDER BY s.last_name, s.first_name";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("s", $subject_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($students);

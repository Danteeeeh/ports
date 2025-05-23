<?php
session_start();
require_once '../Database.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Not authenticated');
}

// Get resource ID
$resource_id = $_GET['id'] ?? null;

if (!$resource_id) {
    header('HTTP/1.1 400 Bad Request');
    exit('Resource ID is required');
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get resource details
    $sql = "SELECT * FROM resources WHERE id = ? AND (student_id = ? OR student_id IS NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $resource_id, $_SESSION['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $resource = $result->fetch_assoc();

    if (!$resource) {
        header('HTTP/1.1 404 Not Found');
        exit('Resource not found');
    }

    // Update download count
    $sql = "UPDATE resources SET download_count = download_count + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();

    // Set headers for download
    $file_path = $resource['file_path'];
    $file_name = basename($file_path);
    $file_size = filesize($file_path);
    $mime_type = mime_content_type($file_path);

    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output file contents
    readfile($file_path);
    exit;

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('An error occurred while downloading the resource');
}
?>

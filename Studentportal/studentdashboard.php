<?php
session_start();

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Debug session data
error_log('Session data: ' . print_r($_SESSION, true));
?>
<style>
/* Announcement Box Styles */
.announcement-box {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-radius: 10px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.announcement-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
}

.module-schedule h3 {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    color: #e2e8f0;
}

.schedule-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    overflow: hidden;
}

.schedule-table th,
.schedule-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.schedule-table th {
    background: rgba(0, 0, 0, 0.2);
    font-weight: 500;
    color: #e2e8f0;
}

.schedule-table tr:last-child td {
    border-bottom: none;
}

.schedule-table tr:hover td {
    background: rgba(255, 255, 255, 0.05);
}

/* Statement Box Styles */
.statement-box {
    background: #4a5568;
    border-radius: 10px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
}

.statement-box h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: white;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 0.5rem;
}

.statement-content {
    color: #e2e8f0;
    line-height: 1.6;
}

.statement-content p {
    margin-bottom: 1rem;
}

.statement-content p:last-child {
    margin-bottom: 0;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .announcement-box,
    .statement-box {
        padding: 1.5rem;
    }

    .schedule-table th,
    .schedule-table td {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
}
</style>
<?php

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: studentlogin.php');
    exit();
}

require_once dirname(__FILE__) . '/../includes/Database.php';
// Create database connection
$conn = new Database();

try {
    // Get student information with summary data
    $sql = "SELECT s.*, 
           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.status = 'present') as present_count,
           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id) as total_attendance,
           (SELECT COUNT(*) FROM enrolled_subjects e WHERE e.student_id = s.student_id) as subject_count,
           (SELECT ROUND(AVG(grade), 2) FROM grades g WHERE g.student_id = s.student_id) as gpa
           FROM students s WHERE s.student_id = ?";
    if (!($stmt = $conn->prepare($sql))) {
        throw new Exception('Failed to prepare student query');
    }
    $stmt->bind_param("s", $_SESSION['student_id']);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    if (!$student) {
        // If student data not found, destroy session and redirect
        session_destroy();
        header('Location: studentlogin.php');
        exit();
    }

    // Get upcoming events
    $sql = "SELECT * FROM events 
           WHERE (student_id IS NULL OR student_id = ?) 
           AND date >= CURDATE() 
           ORDER BY date ASC LIMIT 5";
    if (!($stmt = $conn->prepare($sql))) {
        throw new Exception('Failed to prepare events query');
    }
    $stmt->bind_param("s", $_SESSION['student_id']);
    $stmt->execute();
    $upcoming_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get GPA trend with subject details
    $sql = "SELECT g.semester, 
           ROUND(AVG(g.grade), 2) as average_grade,
           COUNT(DISTINCT g.subject_id) as subjects_count,
           GROUP_CONCAT(DISTINCT s.subject_name) as subjects
           FROM grades g
           JOIN subjects s ON g.subject_id = s.subject_id
           WHERE g.student_id = ? 
           GROUP BY g.semester 
           ORDER BY g.semester ASC LIMIT 5";
    if (!($stmt = $conn->prepare($sql))) {
        throw new Exception('Failed to prepare GPA query');
    }
    $stmt->bind_param("s", $_SESSION['student_id']);
    $stmt->execute();
    $gpa_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get attendance summary
    $sql = "SELECT 
           COUNT(*) as total_classes,
           SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
           SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
           FROM attendance 
           WHERE student_id = ? 
           AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    if (!($stmt = $conn->prepare($sql))) {
        throw new Exception('Failed to prepare attendance query');
    }
    $stmt->bind_param("s", $_SESSION['student_id']);
    $stmt->execute();
    $attendance_summary = $stmt->get_result()->fetch_assoc();

    // Get recent attendance
    $sql = "SELECT a.*, s.subject_name 
           FROM attendance a 
           JOIN subjects s ON a.subject_id = s.subject_id 
           WHERE a.student_id = ? 
           ORDER BY a.date DESC LIMIT 5";
    if (!($stmt = $conn->prepare($sql))) {
        throw new Exception('Failed to prepare recent attendance query');
    }
    $stmt->bind_param("s", $_SESSION['student_id']);
    $stmt->execute();
    $recent_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error_message = "An error occurred while loading your dashboard. Please try again later.";
}

$pageTitle = "Student Dashboard";
include 'includes/header.php';
?>

<div class="dashboard-content">
    <!-- Announcement Section -->
    <div class="announcement-box">
        <div class="announcement-header">
            <h2><i class="fas fa-bullhorn"></i> IMPORTANT ANNOUNCEMENT</h2>
        </div>
        <div class="module-schedule">
            <h3>The Module Grading for Weeks 1 to 5 has been scheduled with the following dates:</h3>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Opening</th>
                        <th>Closing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Week 1</td>
                        <td>Mar 3, 2025</td>
                        <td>Mar 10, 2025</td>
                    </tr>
                    <tr>
                        <td>Week 2</td>
                        <td>Mar 10, 2025</td>
                        <td>Mar 22, 2025</td>
                    </tr>
                    <tr>
                        <td>Week 3</td>
                        <td>Mar 17, 2025</td>
                        <td>Mar 29, 2025</td>
                    </tr>
                    <tr>
                        <td>Week 4</td>
                        <td>Mar 24, 2025</td>
                        <td>Apr 5, 2025</td>
                    </tr>
                    <tr>
                        <td>Week 5</td>
                        <td>Apr 13, 2025</td>
                        <td>Apr 14, 2025</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Statement Section -->
    <div class="statement-box">
        <h2>Statement</h2>
        <div class="statement-content">
            <p>The school is committed to providing quality education and ensuring the safety of our students during these challenging times. We understand the importance of maintaining academic excellence while adapting to the current situation.</p>
            <p>Please regularly check your dashboard for updates and announcements regarding your modules, schedules, and other important information.</p>
        </div>
    </div>
</div>

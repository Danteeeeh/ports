<?php
session_start();

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Debug session data
error_log('Session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: studentlogin.php');
    exit();
}

// Create database connection
$db = new Database();
$auth = new Auth($db);

// Get student ID from session
$student_id = $_SESSION['user_id'];

try {
    // Get student information
    $sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if (!$student) {
        // If student data not found, destroy session and redirect
        session_destroy();
        header('Location: studentlogin.php?error=invalid_account');
        exit();
    }
    
    // Get basic student stats
    $attendance_sql = "SELECT 
                        COUNT(*) as total_classes,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                      FROM attendance 
                      WHERE student_id = ?";
    $stmt = $db->prepare($attendance_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $attendance = $stmt->get_result()->fetch_assoc();
    
    // Get enrolled subjects
    $subjects_sql = "SELECT s.* FROM subjects s 
                    JOIN enrolled_subjects e ON s.subject_id = e.subject_id 
                    WHERE e.student_id = ?";
    $stmt = $db->prepare($subjects_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent grades
    $grades_sql = "SELECT g.*, s.subject_name 
                  FROM grades g 
                  JOIN subjects s ON g.subject_id = s.subject_id 
                  WHERE g.student_id = ? 
                  ORDER BY g.date_added DESC LIMIT 5";
    $stmt = $db->prepare($grades_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent attendance
    $recent_attendance_sql = "SELECT a.*, s.subject_name 
                             FROM attendance a 
                             JOIN subjects s ON a.subject_id = s.subject_id 
                             WHERE a.student_id = ? 
                             ORDER BY a.date DESC LIMIT 5";
    $stmt = $db->prepare($recent_attendance_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $recent_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error_message = "An error occurred while loading your dashboard. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SIA System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3f37c9;
            --primary-light: #4895ef;
            --student-color: #06d6a0;
            --student-dark: #059669;
            --bg-color: #f7fafc;
            --card-bg: #ffffff;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--student-color) 0%, var(--student-dark) 100%);
            color: white;
            padding: 2rem 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header img {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
        }
        
        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-title h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-color);
        }
        
        .dashboard-title p {
            margin: 0.5rem 0 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        
        .user-details h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }
        
        .user-details p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
        }
        
        .attendance-icon {
            background: rgba(6, 214, 160, 0.1);
            color: var(--student-color);
        }
        
        .grades-icon {
            background: rgba(239, 71, 111, 0.1);
            color: #ef476f;
        }
        
        .subjects-icon {
            background: rgba(255, 209, 102, 0.1);
            color: #ffd166;
        }
        
        .events-icon {
            background: rgba(17, 138, 178, 0.1);
            color: #118ab2;
        }
        
        .stat-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-light);
            margin: 0;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-description {
            font-size: 0.8rem;
            color: var(--text-light);
            margin: 0;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }
        
        .card-action {
            font-size: 0.8rem;
            color: var(--student-color);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .card-action i {
            margin-left: 0.25rem;
        }
        
        .attendance-list, .grades-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .attendance-item, .grade-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .attendance-item:last-child, .grade-item:last-child {
            border-bottom: none;
        }
        
        .attendance-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        
        .status-present {
            background: #06d6a0;
        }
        
        .status-absent {
            background: #ef476f;
        }
        
        .status-late {
            background: #ffd166;
        }
        
        .attendance-details, .grade-details {
            flex: 1;
        }
        
        .attendance-subject, .grade-subject {
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0 0 0.25rem;
        }
        
        .attendance-date, .grade-date {
            font-size: 0.8rem;
            color: var(--text-light);
            margin: 0;
        }
        
        .grade-value {
            font-size: 1rem;
            font-weight: 600;
            margin-left: 1rem;
        }
        
        .grade-a {
            color: #06d6a0;
        }
        
        .grade-b {
            color: #118ab2;
        }
        
        .grade-c {
            color: #ffd166;
        }
        
        .grade-d, .grade-f {
            color: #ef476f;
        }
        
        .subjects-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .subject-card {
            background: rgba(6, 214, 160, 0.05);
            border-radius: 8px;
            padding: 1rem;
            border-left: 3px solid var(--student-color);
        }
        
        .subject-name {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
        }
        
        .subject-details {
            font-size: 0.8rem;
            color: var(--text-light);
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/bcplogo.png" alt="School Logo">
                <h2>Student Portal</h2>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-calendar-alt"></i> Attendance</a></li>
                <li><a href="#"><i class="fas fa-book"></i> Subjects</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Grades</a></li>
                <li><a href="#"><i class="fas fa-clipboard-list"></i> Assignments</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="studentlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <div class="dashboard-header">
                    <div class="dashboard-title">
                        <h1>Student Dashboard</h1>
                        <p>Welcome back, <?php echo $student['first_name'] . ' ' . $student['last_name']; ?></p>
                    </div>
                    
                    <div class="user-info">
                        <img src="../assets/images/avatar.png" alt="User Avatar">
                        <div class="user-details">
                            <h3><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h3>
                            <p><?php echo $student['course'] . ' - Year ' . $student['year_level']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon attendance-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <p class="stat-title">Attendance Rate</p>
                        </div>
                        <?php 
                        $attendance_rate = 0;
                        if (isset($attendance['total_classes']) && $attendance['total_classes'] > 0) {
                            $attendance_rate = round(($attendance['present_count'] / $attendance['total_classes']) * 100);
                        }
                        ?>
                        <h2 class="stat-value"><?php echo $attendance_rate; ?>%</h2>
                        <p class="stat-description">Based on <?php echo $attendance['total_classes'] ?? 0; ?> total classes</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon grades-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <p class="stat-title">Average Grade</p>
                        </div>
                        <?php 
                        $average_grade = 0;
                        $total_grades = 0;
                        $grade_sum = 0;
                        
                        if (isset($grades) && is_array($grades)) {
                            foreach ($grades as $grade) {
                                $grade_sum += $grade['grade'];
                                $total_grades++;
                            }
                            
                            if ($total_grades > 0) {
                                $average_grade = round($grade_sum / $total_grades, 2);
                            }
                        }
                        ?>
                        <h2 class="stat-value"><?php echo $average_grade; ?></h2>
                        <p class="stat-description">Based on <?php echo $total_grades; ?> grades</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon subjects-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <p class="stat-title">Enrolled Subjects</p>
                        </div>
                        <h2 class="stat-value"><?php echo count($subjects ?? []); ?></h2>
                        <p class="stat-description">Current semester</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon events-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <p class="stat-title">Upcoming Events</p>
                        </div>
                        <h2 class="stat-value">0</h2>
                        <p class="stat-description">Next 7 days</p>
                    </div>
                </div>
                
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Attendance</h3>
                            <a href="#" class="card-action">View All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        
                        <?php if (isset($recent_attendance) && count($recent_attendance) > 0): ?>
                            <ul class="attendance-list">
                                <?php foreach ($recent_attendance as $attendance): ?>
                                    <li class="attendance-item">
                                        <div class="attendance-status status-<?php echo strtolower($attendance['status']); ?>"></div>
                                        <div class="attendance-details">
                                            <h4 class="attendance-subject"><?php echo $attendance['subject_name']; ?></h4>
                                            <p class="attendance-date"><?php echo date('M d, Y', strtotime($attendance['date'])); ?></p>
                                        </div>
                                        <span class="attendance-badge"><?php echo ucfirst($attendance['status']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No attendance records found.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Grades</h3>
                            <a href="#" class="card-action">View All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        
                        <?php if (isset($grades) && count($grades) > 0): ?>
                            <ul class="grades-list">
                                <?php foreach ($grades as $grade): 
                                    $grade_class = 'grade-f';
                                    if ($grade['grade'] >= 90) {
                                        $grade_class = 'grade-a';
                                    } elseif ($grade['grade'] >= 80) {
                                        $grade_class = 'grade-b';
                                    } elseif ($grade['grade'] >= 70) {
                                        $grade_class = 'grade-c';
                                    } elseif ($grade['grade'] >= 60) {
                                        $grade_class = 'grade-d';
                                    }
                                ?>
                                    <li class="grade-item">
                                        <div class="grade-details">
                                            <h4 class="grade-subject"><?php echo $grade['subject_name']; ?></h4>
                                            <p class="grade-date"><?php echo date('M d, Y', strtotime($grade['date_added'])); ?></p>
                                        </div>
                                        <span class="grade-value <?php echo $grade_class; ?>"><?php echo $grade['grade']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No grades found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-card" style="margin-top: 1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title">Enrolled Subjects</h3>
                    </div>
                    
                    <?php if (isset($subjects) && count($subjects) > 0): ?>
                        <div class="subjects-list">
                            <?php foreach ($subjects as $subject): ?>
                                <div class="subject-card">
                                    <h4 class="subject-name"><?php echo $subject['subject_name']; ?></h4>
                                    <p class="subject-details">
                                        <i class="fas fa-user"></i> <?php echo $subject['teacher_id'] ?? 'Not assigned'; ?><br>
                                        <i class="fas fa-clock"></i> <?php echo $subject['schedule'] ?? 'TBA'; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No subjects enrolled.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

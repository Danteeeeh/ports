<?php
session_start();
require_once '../includes/Database.php';

// Check if user is logged in as teacher
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: teacherlogin.php");
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get teacher information
$teacher_id = $_SESSION['user_id'];

// Combined query for user and teacher details
$sql = "SELECT u.*, t.* 
        FROM users u
        JOIN teachers t ON u.user_id = t.teacher_id
        WHERE u.user_id = ? AND u.role = 'teacher'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    session_destroy();
    header("Location: teacherlogin.php?error=invalid_user");
    exit();
}

// Get statistics in a single query
$stats = [];
$sql = "SELECT 
    (SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ?) as total_subjects,
    (SELECT COUNT(DISTINCT s.student_id) 
     FROM teacher_subjects ts 
     JOIN student_subjects s ON ts.subject_id = s.subject_id 
     WHERE ts.teacher_id = ?) as total_students,
    (SELECT COUNT(*) FROM tasks WHERE teacher_id = ? AND status = 'active') as total_tasks,
    (SELECT COUNT(*) 
     FROM task_submissions ts 
     JOIN tasks t ON ts.task_id = t.task_id 
     WHERE t.teacher_id = ? AND ts.status = 'submitted') as pending_submissions";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $teacher_id, $teacher_id, $teacher_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats = $result ?: [];

// Get assigned subjects
$sql = "SELECT s.subject_name, 
        COUNT(DISTINCT ss.student_id) as student_count,
        COUNT(t.task_id) as task_count 
        FROM subjects s 
        LEFT JOIN teacher_subjects ts ON s.subject_id = ts.subject_id 
        LEFT JOIN student_subjects ss ON s.subject_id = ss.subject_id 
        LEFT JOIN tasks t ON s.subject_id = t.subject_id 
        WHERE ts.teacher_id = ?
        GROUP BY s.subject_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent tasks
$sql = "SELECT t.task_id, t.title, t.status, t.due_date, s.subject_name,
        COUNT(ts.submission_id) as submission_count 
        FROM tasks t 
        LEFT JOIN subjects s ON t.subject_id = s.subject_id 
        LEFT JOIN task_submissions ts ON t.task_id = ts.task_id 
        WHERE t.teacher_id = ? 
        GROUP BY t.task_id
        ORDER BY t.created_at DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$recent_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .dashboard-container {
            padding: 2rem;
            margin-left: 250px;
            transition: margin 0.3s;
        }

        .welcome-section {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-draft { background: #fef3c7; color: #92400e; }

        @media (max-width: 768px) {
            .dashboard-container {
                margin-left: 0;
                padding: 1rem;
            }
            
            .welcome-section h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="dashboard-container">
        <div class="welcome-section">
            <h1 class="text-2xl font-bold">Welcome, <?= htmlspecialchars($teacher['first_name']) ?>!</h1>
            <p class="opacity-90">Your teaching dashboard</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue-100 text-blue-600">📚</div>
                <div>
                    <div class="text-2xl font-bold"><?= $stats['total_subjects'] ?></div>
                    <div class="text-sm text-gray-600">Subjects</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green-100 text-green-600">👥</div>
                <div>
                    <div class="text-2xl font-bold"><?= $stats['total_students'] ?></div>
                    <div class="text-sm text-gray-600">Students</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple-100 text-purple-600">✅</div>
                <div>
                    <div class="text-2xl font-bold"><?= $stats['total_tasks'] ?></div>
                    <div class="text-sm text-gray-600">Active Tasks</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-yellow-100 text-yellow-600">⏳</div>
                <div>
                    <div class="text-2xl font-bold"><?= $stats['pending_submissions'] ?></div>
                    <div class="text-sm text-gray-600">Pending Submissions</div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <h2 class="text-xl font-bold mb-4">Recent Tasks</h2>
                <?php foreach ($recent_tasks as $task): ?>
                    <div class="task-item">
                        <div>
                            <div class="font-medium"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="text-sm text-gray-600">
                                <?= htmlspecialchars($task['subject_name']) ?> • 
                                <?= $task['submission_count'] ?> submissions
                            </div>
                        </div>
                        <span class="status-badge <?= $task['status'] === 'active' ? 'status-active' : 'status-draft' ?>">
                            <?= ucfirst($task['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h2 class="text-xl font-bold mb-4">Your Subjects</h2>
                <?php foreach ($subjects as $subject): ?>
                    <div class="task-item">
                        <div>
                            <div class="font-medium"><?= htmlspecialchars($subject['subject_name']) ?></div>
                            <div class="text-sm text-gray-600">
                                <?= $subject['student_count'] ?> students • 
                                <?= $subject['task_count'] ?> tasks
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? $_GET['id'] : null;
$teacher_id = $_SESSION['user_id'];

if (!$subject_id) {
    header('Location: subjects.php');
    exit();
}

// Get subject details with student list
$sql = "SELECT s.*, 
               COUNT(DISTINCT ss.student_id) as student_count,
               COUNT(DISTINCT t.task_id) as task_count
        FROM subjects s
        LEFT JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
        LEFT JOIN student_subjects ss ON s.subject_id = ss.subject_id
        LEFT JOIN tasks t ON s.subject_id = t.subject_id
        WHERE s.subject_id = ? AND ts.teacher_id = ?
        GROUP BY s.subject_id";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("ss", $subject_id, $teacher_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    header('Location: subjects.php');
    exit();
}

// Get enrolled students
$sql = "SELECT st.*, ss.created_at as enrollment_date,
               (SELECT COUNT(ts.submission_id) FROM task_submissions ts 
                JOIN tasks t ON ts.task_id = t.task_id
                WHERE ts.student_id = st.student_id 
                AND t.subject_id = ?) as submissions_count
        FROM students st
        JOIN student_subjects ss ON st.student_id = ss.student_id
        WHERE ss.subject_id = ?
        ORDER BY st.last_name, st.first_name";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("ss", $subject_id, $subject_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent tasks
$sql = "SELECT t.*, 
               COUNT(ts.submission_id) as submission_count
        FROM tasks t
        LEFT JOIN task_submissions ts ON t.task_id = ts.task_id
        WHERE t.subject_id = ?
        GROUP BY t.task_id
        ORDER BY t.due_date DESC
        LIMIT 5";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("s", $subject_id);
$stmt->execute();
$recent_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject['subject_name']) ?> - Teacher Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .dashboard-container {
            padding: 2rem;
            margin-left: 250px;
        }

        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .subject-info {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .subject-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .subject-meta {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .subject-description {
            color: #4b5563;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .section {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .student-list {
            list-style: none;
            padding: 0;
        }

        .student-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-name {
            font-weight: 500;
            color: #1f2937;
        }

        .student-meta {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .task-list {
            list-style: none;
            padding: 0;
        }

        .task-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-title {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .task-meta {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-secondary {
            background: white;
            border: 1px solid #e5e7eb;
            color: #1f2937;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <h1 class="page-title"><?= htmlspecialchars($subject['subject_name']) ?></h1>
            <div>
                <a href="manage_tasks.php?subject=<?= $subject_id ?>" class="btn btn-primary">Manage Tasks</a>
                <a href="subjects.php" class="btn btn-secondary">Back to Subjects</a>
            </div>
        </div>

        <div class="subject-info">
            <div class="subject-name"><?= htmlspecialchars($subject['subject_name']) ?></div>
            <div class="subject-meta">
                Credits: <?= htmlspecialchars($subject['credits']) ?>
            </div>
            <div class="subject-description">
                <?= htmlspecialchars($subject['description']) ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $subject['student_count'] ?></div>
                <div class="stat-label">Enrolled Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $subject['task_count'] ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="main-content">
                <div class="section">
                    <h2 class="section-title">Enrolled Students</h2>
                    <ul class="student-list">
                        <?php foreach ($students as $student): ?>
                        <li class="student-item">
                            <div>
                                <div class="student-name">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                </div>
                                <div class="student-meta">
                                    Course: <?= htmlspecialchars($student['course']) ?> | 
                                    Year Level: <?= htmlspecialchars($student['year_level']) ?>
                                </div>
                            </div>
                            <div class="student-meta">
                                <?= $student['submissions_count'] ?> submissions
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="sidebar-content">
                <div class="section">
                    <h2 class="section-title">Recent Tasks</h2>
                    <ul class="task-list">
                        <?php foreach ($recent_tasks as $task): ?>
                        <li class="task-item">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="task-meta">
                                <span>Due: <?= date('M j, Y', strtotime($task['due_date'])) ?></span>
                                <span><?= $task['submission_count'] ?> submissions</span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top: 1rem;">
                        <a href="manage_tasks.php?subject=<?= $subject_id ?>" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                            View All Tasks
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

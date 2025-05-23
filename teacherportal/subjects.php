<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

// Get teacher information
$teacher_id = $_SESSION['user_id'];

// Get all subjects taught by the teacher with student counts and task counts
$sql = "SELECT s.subject_id, s.subject_name, s.description, s.credits,
               COUNT(DISTINCT ss.student_id) as student_count,
               COUNT(DISTINCT t.task_id) as task_count,
               GROUP_CONCAT(DISTINCT CONCAT(st.first_name, ' ', st.last_name)) as student_names
        FROM subjects s
        LEFT JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
        LEFT JOIN student_subjects ss ON s.subject_id = ss.subject_id
        LEFT JOIN students st ON ss.student_id = st.student_id
        LEFT JOIN tasks t ON s.subject_id = t.subject_id AND t.teacher_id = ?
        WHERE ts.teacher_id = ?
        GROUP BY s.subject_id";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("ss", $teacher_id, $teacher_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subjects = array_map(function ($subject) {
    $subject['student_names'] = explode(',', $subject['student_names']);
    return $subject;
}, $subjects);  
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects - Teacher Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .dashboard-container {
            padding: 2rem;
            margin-left: 250px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.875rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .subject-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .subject-header {
            background: #6366f1;
            color: white;
            padding: 1.5rem;
        }

        .subject-code {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-bottom: 0.25rem;
        }

        .subject-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .subject-description {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .subject-stats {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .subject-actions {
            padding: 1rem 1.5rem;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #1f2937;
            border: 1px solid #e5e7eb;
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
            <h1 class="page-title">Your Subjects</h1>
            <p class="text-gray-600">Manage your teaching subjects and view student information</p>
        </div>

        <div class="subjects-grid">
            <?php foreach ($subjects as $subject): ?>
            <div class="subject-card">
                <div class="subject-header">
                    <div class="subject-name"><?= htmlspecialchars($subject['subject_name']) ?></div>
                    <div class="subject-description"><?= htmlspecialchars($subject['description']) ?></div>
                    <div class="subject-credits">Credits: <?= htmlspecialchars($subject['credits']) ?></div>
                </div>
                <div class="subject-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $subject['student_count'] ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $subject['task_count'] ?></div>
                        <div class="stat-label">Tasks</div>
                    </div>
                </div>
                <div class="subject-actions">
                    <a href="view_subject.php?id=<?= $subject['subject_id'] ?>" class="btn btn-secondary">
                        View Details
                    </a>
                    <a href="manage_tasks.php?subject=<?= $subject['subject_id'] ?>" class="btn btn-primary">
                        Manage Tasks
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
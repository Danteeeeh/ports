<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

$teacher_id = $_SESSION['user_id'];

// Get all tasks for the teacher
$sql = "SELECT t.*, s.subject_name,
               COUNT(DISTINCT ts.submission_id) as submission_count,
               MAX(ts.created_at) as last_submission
        FROM tasks t
        JOIN subjects s ON t.subject_id = s.subject_id
        LEFT JOIN task_submissions ts ON t.task_id = ts.task_id
        WHERE t.teacher_id = ?
        GROUP BY t.task_id, t.subject_id, s.subject_name
        ORDER BY t.due_date DESC";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - Teacher Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .dashboard-container {
            padding: 2rem;
            margin-left: 250px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .task-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .task-card:hover {
            transform: translateY(-2px);
        }

        .task-header {
            background: #6366f1;
            color: white;
            padding: 1.5rem;
        }

        .task-subject {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-bottom: 0.25rem;
        }

        .task-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .task-due {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .task-body {
            padding: 1.5rem;
        }

        .task-description {
            color: #4b5563;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .task-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
        }

        .task-footer {
            padding: 1rem 1.5rem;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
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

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-upcoming {
            background: #fef3c7;
            color: #92400e;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-ended {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <h1 class="page-title">All Tasks</h1>
            <p class="text-gray-600">Manage and monitor all your tasks across subjects</p>
        </div>

        <div class="tasks-grid">
            <?php foreach ($tasks as $task): 
                $now = new DateTime();
                $due_date = new DateTime($task['due_date']);
                $status = '';
                $status_class = '';
                
                if ($now > $due_date) {
                    $status = 'Ended';
                    $status_class = 'status-ended';
                } elseif ($now->diff($due_date)->days <= 3) {
                    $status = 'Active';
                    $status_class = 'status-active';
                } else {
                    $status = 'Upcoming';
                    $status_class = 'status-upcoming';
                }
            ?>
            <div class="task-card">
                <div class="task-header">
                    <div class="task-subject"><?= htmlspecialchars($task['subject_name']) ?></div>
                    <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                    <div class="task-due">
                        Due: <?= date('F j, Y', strtotime($task['due_date'])) ?>
                        <span class="status-badge <?= $status_class ?>"><?= $status ?></span>
                    </div>
                </div>
                <div class="task-body">
                    <div class="task-description">
                        <?= nl2br(htmlspecialchars($task['description'])) ?>
                    </div>
                    <div class="task-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?= $task['submission_count'] ?></div>
                            <div class="stat-label">Submissions</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">
                                <?= $task['last_submission'] ? date('M j', strtotime($task['last_submission'])) : '-' ?>
                            </div>
                            <div class="stat-label">Last Submit</div>
                        </div>
                    </div>
                </div>
                <div class="task-footer">
                    <a href="view_submissions.php?task=<?= $task['task_id'] ?>" class="btn btn-primary">
                        View Submissions
                    </a>
                    <a href="manage_tasks.php?subject=<?= $task['subject_id'] ?>" class="btn btn-secondary">
                        Manage Task
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

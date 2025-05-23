<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

$teacher_id = $_SESSION['user_id'];
$subject_id = isset($_GET['subject']) ? $_GET['subject'] : null;

if (!$subject_id) {
    header('Location: subjects.php');
    exit();
}

// Verify teacher has access to this subject
$sql = "SELECT s.* FROM subjects s
        JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
        WHERE ts.teacher_id = ? AND s.subject_id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("ss", $teacher_id, $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    header('Location: subjects.php');
    exit();
}

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_task') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $due_date = $_POST['due_date'];
        $task_id = uniqid();
        
        $sql = "INSERT INTO tasks (task_id, subject_id, teacher_id, title, description, due_date)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("ssssss", $task_id, $subject_id, $teacher_id, $title, $description, $due_date);
        
        if ($stmt->execute()) {
            $success = "Task created successfully!";
        } else {
            $error = "Error creating task";
        }
    } elseif ($_POST['action'] === 'delete_task' && isset($_POST['task_id'])) {
        $task_id = $_POST['task_id'];
        
        // First delete all submissions
        $sql = "DELETE FROM task_submissions WHERE task_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("s", $task_id);
        $stmt->execute();
        
        // Then delete the task
        $sql = "DELETE FROM tasks WHERE task_id = ? AND teacher_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("ss", $task_id, $teacher_id);
        
        if ($stmt->execute()) {
            $success = "Task deleted successfully!";
        } else {
            $error = "Error deleting task";
        }
    }
}

// Get all tasks for this subject
$sql = "SELECT t.*,
               COUNT(DISTINCT ts.submission_id) as submission_count,
               COUNT(DISTINCT CASE WHEN ts.grade IS NOT NULL THEN ts.submission_id END) as graded_count
        FROM tasks t
        LEFT JOIN task_submissions ts ON t.task_id = ts.task_id
        WHERE t.subject_id = ? AND t.teacher_id = ?
        GROUP BY t.task_id
        ORDER BY t.due_date DESC";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("ss", $subject_id, $teacher_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tasks - <?= htmlspecialchars($subject['subject_name']) ?></title>
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
            align-items: flex-start;
        }

        .task-form {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-header {
            background: #6366f1;
            color: white;
            padding: 1.5rem;
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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
            cursor: pointer;
            border: none;
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

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #059669;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #dc2626;
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
            <div>
                <h1 class="page-title">Manage Tasks</h1>
                <p class="text-gray-600">
                    <?= htmlspecialchars($subject['subject_name']) ?> | 
                    <a href="view_subject.php?id=<?= $subject_id ?>">Back to Subject</a>
                </p>
            </div>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="task-form">
            <div class="form-header">
                <h2 class="form-title">Create New Task</h2>
            </div>
            <div class="form-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create_task">
                    
                    <div class="form-group">
                        <label class="form-label" for="title">Task Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="due_date">Due Date</label>
                        <input type="datetime-local" 
                               id="due_date" 
                               name="due_date" 
                               class="form-control"
                               min="<?= date('Y-m-d\TH:i') ?>"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Task</button>
                </form>
            </div>
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
                    <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                    <div class="task-due">
                        Due: <?= date('F j, Y g:i A', strtotime($task['due_date'])) ?>
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
                            <div class="stat-value"><?= $task['graded_count'] ?></div>
                            <div class="stat-label">Graded</div>
                        </div>
                    </div>
                </div>
                <div class="task-footer">
                    <a href="view_submissions.php?task=<?= $task['task_id'] ?>" class="btn btn-primary">
                        View Submissions
                    </a>
                    <form method="POST" style="display: inline;" 
                          onsubmit="return confirm('Are you sure you want to delete this task? This will also delete all submissions.');">
                        <input type="hidden" name="action" value="delete_task">
                        <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

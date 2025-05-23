<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

// Get task ID from URL
$task_id = isset($_GET['task']) ? $_GET['task'] : null;
$teacher_id = $_SESSION['user_id'];

if (!$task_id) {
    header('Location: subjects.php');
    exit();
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'grade' && isset($_POST['submission_id']) && isset($_POST['score'])) {
        $submission_id = $_POST['submission_id'];
        $score = $_POST['score'];
        $feedback = $_POST['feedback'];
        
        $sql = "UPDATE task_submissions 
                SET score = ?, feedback = ?, graded_at = NOW() 
                WHERE submission_id = ? AND task_id IN (
                    SELECT task_id FROM tasks WHERE teacher_id = ?
                )";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("dsss", $score, $feedback, $submission_id, $teacher_id);
        
        if ($stmt->execute()) {
            header("Location: view_submissions.php?task=" . $task_id . "&graded=1");
            exit();
        }
    }
}

// Get task details and subject info
$sql = "SELECT t.*, s.subject_name, s.subject_id
        FROM tasks t
        JOIN subjects s ON t.subject_id = s.subject_id
        WHERE t.task_id = ? AND t.teacher_id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("ss", $task_id, $teacher_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header('Location: subjects.php');
    exit();
}

// Get all submissions for this task
$sql = "SELECT ts.*, 
               st.first_name, st.last_name, st.student_id,
               st.course, st.year_level
        FROM task_submissions ts
        JOIN students st ON ts.student_id = st.student_id
        WHERE ts.task_id = ?
        ORDER BY ts.created_at DESC";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("s", $task_id);
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - <?= htmlspecialchars($task['title']) ?></title>
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

        .task-info {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .task-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .task-meta {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .task-description {
            color: #4b5563;
        }

        .submissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .submission-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .submission-header {
            background: #6366f1;
            color: white;
            padding: 1.5rem;
        }

        .student-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .student-info {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .submission-body {
            padding: 1.5rem;
        }

        .submission-meta {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .meta-label {
            color: #6b7280;
        }

        .meta-value {
            color: #1f2937;
            font-weight: 500;
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
            resize: vertical;
            min-height: 80px;
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

        .graded {
            border-left: 4px solid #059669;
        }

        .ungraded {
            border-left: 4px solid #f59e0b;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Task Submissions</h1>
                <p class="text-gray-600">
                    <?= htmlspecialchars($task['subject_name']) ?> | 
                    <a href="view_subject.php?id=<?= $task['subject_id'] ?>">Back to Subject</a>
                </p>
            </div>
            <a href="manage_tasks.php?subject=<?= $task['subject_id'] ?>" class="btn btn-secondary">
                Back to Tasks
            </a>
        </div>

        <?php if (isset($_GET['graded'])): ?>
        <div class="alert alert-success">
            Submission has been graded successfully!
        </div>
        <?php endif; ?>

        <div class="task-info">
            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
            <div class="task-meta">
                Due: <?= date('F j, Y', strtotime($task['due_date'])) ?> | 
                Total Submissions: <?= count($submissions) ?>
            </div>
            <div class="task-description">
                <?= nl2br(htmlspecialchars($task['description'])) ?>
            </div>
        </div>

        <div class="submissions-grid">
            <?php foreach ($submissions as $submission): ?>
            <div class="submission-card <?= $submission['grade'] ? 'graded' : 'ungraded' ?>">
                <div class="submission-header">
                    <div class="student-name">
                        <?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']) ?>
                    </div>
                    <div class="student-info">
                        <?= htmlspecialchars($submission['course']) ?> | 
                        Year <?= htmlspecialchars($submission['year_level']) ?>
                    </div>
                </div>

                <div class="submission-body">
                    <div class="submission-meta">
                        <div class="meta-item">
                            <span class="meta-label">Submitted</span>
                            <span class="meta-value">
                                <?= date('M j, Y g:i A', strtotime($submission['created_at'])) ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Status</span>
                            <span class="meta-value">
                                <?= isset($submission['grade']) && $submission['grade'] !== null ? 'Graded' : 'Pending' ?>
                            </span>
                        </div>
                        <?php if (!isset($submission['grade']) || $submission['grade'] === null): ?>
                        <div class="meta-item">
                            <span class="meta-label">Score</span>
                            <span class="meta-value"><?= isset($submission['grade']) && $submission['grade'] !== null ? $submission['grade'] . '%' : '-' ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!isset($submission['grade']) || $submission['grade'] === null): ?>
                    <form method="POST" class="grade-form">
                        <input type="hidden" name="action" value="grade_submission">
                        <input type="hidden" name="submission_id" value="<?= $submission['submission_id'] ?>">
                        <div class="form-group">
                            <label class="form-label" for="grade_<?= $submission['submission_id'] ?>">Score</label>
                            <input type="number" id="grade_<?= $submission['submission_id'] ?>" 
                                   name="grade" class="form-control" 
                                   min="0" max="100" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="feedback_<?= $submission['submission_id'] ?>">Feedback</label>
                            <textarea id="feedback_<?= $submission['submission_id'] ?>" 
                                      name="feedback" 
                                      class="form-control"></textarea>
                        </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Submit Grade
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="feedback">
                        <div class="form-label">Feedback</div>
                        <div class="task-description">
                            <?= nl2br(htmlspecialchars($submission['feedback'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

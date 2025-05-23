<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

$teacher_id = $_SESSION['user_id'];

// Get all subjects taught by the teacher
$sql = "SELECT s.*, COUNT(DISTINCT ss.student_id) as student_count
        FROM subjects s
        JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
        LEFT JOIN student_subjects ss ON s.subject_id = ss.subject_id
        WHERE ts.teacher_id = ?
        GROUP BY s.subject_id";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get selected subject's students and their grades if a subject is selected
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : null;
$students = [];

if ($selected_subject) {
    $sql = "SELECT s.*, 
                   COALESCE(AVG(ts.grade), 0) as average_grade,
                   COUNT(DISTINCT t.task_id) as total_tasks,
                   COUNT(DISTINCT ts.submission_id) as submitted_tasks,
                   COUNT(DISTINCT CASE WHEN ts.grade IS NOT NULL THEN ts.submission_id END) as graded_tasks
            FROM students s
            JOIN student_subjects ss ON s.student_id = ss.student_id
            LEFT JOIN task_submissions ts ON s.student_id = ts.student_id
            LEFT JOIN tasks t ON ts.task_id = t.task_id AND t.subject_id = ss.subject_id
            WHERE ss.subject_id = ?
            GROUP BY s.student_id
            ORDER BY s.last_name, s.first_name";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("s", $selected_subject);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades - Teacher Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .dashboard-container {
            padding: 2rem;
            margin-left: 250px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .subject-select {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .grades-container {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .grades-header {
            background: #6366f1;
            color: white;
            padding: 1.5rem;
        }

        .grades-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .grades-meta {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .grades-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
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

        .grades-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grades-table th,
        .grades-table td {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
        }

        .grades-table th {
            background: #f9fafb;
            font-weight: 500;
            text-align: left;
        }

        .grades-table tr:hover {
            background: #f9fafb;
        }

        .grade-value {
            font-weight: 600;
        }

        .grade-good {
            color: #059669;
        }

        .grade-warning {
            color: #d97706;
        }

        .grade-poor {
            color: #dc2626;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-value {
            height: 100%;
            background: #6366f1;
            border-radius: 3px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <h1 class="page-title">Grades</h1>
            <p class="text-gray-600">View and manage student grades</p>
        </div>

        <div class="subject-select">
            <div class="form-group">
                <label class="form-label" for="subject">Select Subject</label>
                <select id="subject" class="form-control" onchange="window.location.href='?subject=' + this.value">
                    <option value="">Choose a subject...</option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['subject_id'] ?>" 
                            <?= $selected_subject === $subject['subject_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['subject_name']) ?> 
                        (<?= $subject['student_count'] ?> students)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($selected_subject && count($students) > 0): ?>
        <div class="grades-container">
            <div class="grades-header">
                <h2 class="grades-title">
                    <?= htmlspecialchars($subjects[array_search($selected_subject, array_column($subjects, 'subject_id'))]['subject_name']) ?>
                </h2>
                <div class="grades-meta">
                    <?= count($students) ?> students enrolled
                </div>
            </div>

            <div class="grades-body">
                <?php
                $total_average = array_sum(array_column($students, 'average_grade')) / count($students);
                $passing_students = count(array_filter($students, fn($s) => $s['average_grade'] >= 75));
                $completion_rate = array_sum(array_column($students, 'submitted_tasks')) / 
                                 (count($students) * $students[0]['total_tasks']) * 100;
                ?>
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-value"><?= number_format($total_average, 1) ?></div>
                        <div class="stat-label">Class Average</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $passing_students ?></div>
                        <div class="stat-label">Passing Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= number_format($completion_rate, 1) ?>%</div>
                        <div class="stat-label">Task Completion</div>
                    </div>
                </div>

                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course & Year</th>
                            <th>Tasks Submitted</th>
                            <th>Average Score</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $grade_class = '';
                            if ($student['average_grade'] >= 85) {
                                $grade_class = 'grade-good';
                            } elseif ($student['average_grade'] >= 75) {
                                $grade_class = 'grade-warning';
                            } else {
                                $grade_class = 'grade-poor';
                            }
                            
                            $progress = ($student['submitted_tasks'] / $student['total_tasks']) * 100;
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['course']) ?> - 
                                Year <?= htmlspecialchars($student['year_level']) ?>
                            </td>
                            <td>
                                <?= $student['submitted_tasks'] ?>/<?= $student['total_tasks'] ?>
                                (<?= $student['graded_tasks'] ?> graded)
                            </td>
                            <td>
                                <span class="grade-value <?= $grade_class ?>">
                                    <?= number_format($student['average_grade'], 1) ?>
                                </span>
                            </td>
                            <td style="width: 200px;">
                                <div class="progress-bar">
                                    <div class="progress-value" style="width: <?= $progress ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif ($selected_subject): ?>
        <div class="grades-container">
            <div class="grades-body" style="text-align: center; padding: 3rem;">
                <p>No students enrolled in this subject.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

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

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_attendance') {
        $subject_id = $_POST['subject_id'];
        $date = $_POST['date'];
        $students = $_POST['students'];
        
        // Start transaction
        $db->getConnection()->begin_transaction();
        
        try {
            foreach ($students as $student_id => $status) {
                $attendance_id = uniqid();
                $sql = "INSERT INTO attendance (id, subject_id, student_id, date, status, teacher_id)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE status = ?";
                $stmt = $db->getConnection()->prepare($sql);
                $stmt->bind_param("sssssss", 
                    $attendance_id,
                    $subject_id, 
                    $student_id, 
                    $date, 
                    $status, 
                    $teacher_id,
                    $status
                );
                $stmt->execute();
            }
            
            $db->getConnection()->commit();
            $success = true;
        } catch (Exception $e) {
            $db->getConnection()->rollback();
            $error = "Error marking attendance: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Teacher Portal</title>
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

        .attendance-form {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            background: #6366f1;
            color: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-body {
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

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
        }

        .attendance-table th {
            background: #f9fafb;
            font-weight: 500;
            text-align: left;
        }

        .attendance-table tr:hover {
            background: #f9fafb;
        }

        .status-select {
            padding: 0.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.25rem;
            width: 100px;
        }

        .present {
            color: #059669;
        }

        .absent {
            color: #dc2626;
        }

        .late {
            color: #d97706;
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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <h1 class="page-title">Attendance</h1>
            <p class="text-gray-600">Mark and manage student attendance</p>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            Attendance has been marked successfully!
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="subject-select">
            <div class="form-group">
                <label class="form-label" for="subject">Select Subject</label>
                <select id="subject" class="form-control" onchange="loadStudents(this.value)">
                    <option value="">Choose a subject...</option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['subject_id'] ?>">
                        <?= htmlspecialchars($subject['subject_name']) ?> 
                        (<?= $subject['student_count'] ?> students)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="attendance-form" style="display: none;" class="attendance-form">
            <div class="form-header">
                <h2 class="form-title">Mark Attendance</h2>
            </div>
            <div class="form-body">
                <form method="POST" id="mark-attendance">
                    <input type="hidden" name="action" value="mark_attendance">
                    <input type="hidden" name="subject_id" id="subject_id">

                    <div class="form-group">
                        <label class="form-label" for="date">Date</label>
                        <input type="date" id="date" name="date" class="form-control" required 
                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                    </div>

                    <div id="students-list">
                        <!-- Student list will be loaded here -->
                    </div>

                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function loadStudents(subjectId) {
        if (!subjectId) {
            document.getElementById('attendance-form').style.display = 'none';
            return;
        }

        document.getElementById('subject_id').value = subjectId;
        
        // Fetch students for this subject
        fetch(`get_students.php?subject=${subjectId}`)
            .then(response => response.json())
            .then(students => {
                const table = `
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${students.map(student => `
                                <tr>
                                    <td>${student.first_name} ${student.last_name}</td>
                                    <td>
                                        <select name="students[${student.student_id}]" class="status-select">
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="late">Late</option>
                                        </select>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                
                document.getElementById('students-list').innerHTML = table;
                document.getElementById('attendance-form').style.display = 'block';
            });
    }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

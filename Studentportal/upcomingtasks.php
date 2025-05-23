<?php
session_start();

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Check if user is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: studentlogin.php');
    exit();
}

$db = new Database();
$studentId = $_SESSION['student_id'];

// Create subjects table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create student_subjects table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS student_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50),
    subject_id INT,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
)");

// Create tasks table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS tasks (
    task_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50),
    subject_id INT,
    week INT,
    title VARCHAR(200),
    description TEXT,
    start_date DATETIME,
    due_date DATETIME,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
)");

// Insert default subjects if they don't exist
$defaultSubjects = [
    'Financial Management',
    'Information Management',
    'IT ELECTIVE 2',
    'NETWORKING 3',
    'SYSTEM INTEGRATION AND ARCHITECTURE',
    'TEAM SPORTS',
    'THE LIFE AND WORKS OF DR. RIZAL',
    'WEB DEVELOPMENT'
];

foreach ($defaultSubjects as $subject) {
    $db->query("INSERT IGNORE INTO subjects (subject_name) VALUES ('" . $db->escape($subject) . "')");
}

// Get student's subjects (now with error handling)
$subjects = [];
$sql = "SELECT DISTINCT s.subject_name 
        FROM subjects s 
        LEFT JOIN student_subjects ss ON s.subject_id = ss.subject_id 
        WHERE ss.student_id = ? OR ss.student_id IS NULL";
if ($stmt = $db->prepare($sql)) {
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // If query fails, use default subjects
    $subjects = array_map(function($name) {
        return ['subject_name' => $name];
    }, $defaultSubjects);
}

// Get tasks for the selected subject (with error handling)
$tasks = [];
$currentSubject = isset($_GET['subject']) ? $_GET['subject'] : 'SYSTEM INTEGRATION AND ARCHITECTURE';

$sql = "SELECT t.week, t.title, t.description, t.start_date, t.due_date, t.status 
        FROM tasks t 
        JOIN subjects s ON t.subject_id = s.subject_id 
        WHERE t.student_id = ? AND s.subject_name = ? 
        ORDER BY t.week ASC, t.start_date ASC";
if ($stmt = $db->prepare($sql)) {
    $stmt->bind_param('ss', $studentId, $currentSubject);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Insert sample tasks if none exist
if (empty($tasks)) {
    // First, ensure the subject exists
    $result = $db->query("SELECT subject_id FROM subjects WHERE subject_name = '" . 
                       $db->escape($currentSubject) . "' LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $subjectId = $result->fetch_object()->subject_id;
    } else {
        // Insert the subject if it doesn't exist
        $db->query("INSERT INTO subjects (subject_name) VALUES ('" . $db->escape($currentSubject) . "')");
        $subjectId = $db->getConnection()->insert_id;
    }
    
    // Only proceed if we have a valid subject ID
    if ($subjectId) {
        $sampleTasks = [
        [
            'week' => 1,
            'title' => 'Assignment',
            'description' => 'Create a Prototype for your Student Grading System',
            'start_date' => '2025-03-22 12:00:00',
            'due_date' => '2025-04-25 11:59:00',
            'status' => 'Pending'
        ],
        [
            'week' => 4,
            'title' => 'Project',
            'description' => 'Create a Prototype for your Student Grading Book System',
            'start_date' => '2025-03-22 12:00:00',
            'due_date' => '2025-04-25 11:59:00',
            'status' => 'Completed'
        ]
    ];
    
        foreach ($sampleTasks as $task) {
            $db->query("INSERT INTO tasks (student_id, subject_id, week, title, description, start_date, due_date, status) 
                        VALUES ('$studentId', $subjectId, {$task['week']}, '" . 
                        $db->escape($task['title']) . "', '" . 
                        $db->escape($task['description']) . "', '" . 
                        $task['start_date'] . "', '" . 
                        $task['due_date'] . "', '" . 
                        $task['status'] . "')");
        }
    }
    
    // Fetch the newly inserted tasks
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('ss', $studentId, $currentSubject);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="tasks-container">
    <div class="subjects-sidebar">
        <h2>SUBJECTS</h2>
        <div class="subject-list">
            <div class="subject-item active">Financial Management</div>
            <div class="subject-item">Information Management</div>
            <div class="subject-item">IT ELECTIVE 2</div>
            <div class="subject-item">NETWORKING 3</div>
            <div class="subject-item">SYSTEM INTEGRATION AND ARCHITECTURE</div>
            <div class="subject-item">TEAM SPORTS</div>
            <div class="subject-item">THE LIFE AND WORKS OF DR. RIZAL</div>
            <div class="subject-item">WEB DEVELOPMENT</div>
        </div>
    </div>

    <div class="tasks-content">
        <div class="tasks-header">
            <h2>TASK FOR PRELIM</h2>
        </div>

        <div class="tasks-list">
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Weeks</th>
                        <th>Title</th>
                        <th>Descriptions</th>
                        <th>Start Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['week']); ?></td>
                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                        <td><?php echo htmlspecialchars($task['description']); ?></td>
                        <td><?php echo date('M d, Y - h:ia', strtotime($task['start_date'])); ?></td>
                        <td><?php echo date('M d, Y - h:ia', strtotime($task['due_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($task['status']); ?>">
                                <?php echo htmlspecialchars($task['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.tasks-container {
    display: flex;
    gap: 2rem;
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
    height: calc(100vh - 80px);
}

.subjects-sidebar {
    width: 300px;
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.subjects-sidebar h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #1e293b;
}

.subject-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.subject-item {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
    color: #475569;
}

.subject-item:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.subject-item.active {
    background: #4f46e5;
    color: white;
}

.tasks-content {
    flex: 1;
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
}

.tasks-header {
    margin-bottom: 2rem;
}

.tasks-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    background: #e0e7ff;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: inline-block;
}

.tasks-table {
    width: 100%;
    border-collapse: collapse;
}

.tasks-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 500;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
}

.tasks-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #475569;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.completed {
    background: #dcfce7;
    color: #166534;
}

.status-badge.closed {
    background: #e2e8f0;
    color: #475569;
}

@media (max-width: 1024px) {
    .tasks-container {
        flex-direction: column;
    }

    .subjects-sidebar {
        width: 100%;
    }

    .subject-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 768px) {
    .tasks-container {
        padding: 1rem;
    }

    .tasks-table {
        display: block;
        overflow-x: auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for subject items
    document.querySelectorAll('.subject-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.subject-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            // Here you would typically load tasks for the selected subject via AJAX
            // For now, we'll just reload the page with the selected subject
            window.location.href = `?subject=${encodeURIComponent(this.textContent.trim())}`;
        });
    });
});
</script>

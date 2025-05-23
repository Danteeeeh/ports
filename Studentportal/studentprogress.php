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

// Get student's courses and grades
$sql = "SELECT s.subject_name, g.grade, g.semester 
        FROM grades g 
        JOIN subjects s ON g.subject_id = s.subject_id 
        WHERE g.student_id = ? 
        ORDER BY g.semester DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get attendance records for the calendar
$sql = "SELECT date, status FROM attendance WHERE student_id = ? AND MONTH(date) = MONTH(CURRENT_DATE())";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Convert attendance records to a format suitable for the calendar
$attendanceMap = [];
foreach ($attendance as $record) {
    $date = date('j', strtotime($record['date']));
    $attendanceMap[$date] = $record['status'];
}

include 'includes/header.php';
?>

<div class="progress-container">
    <!-- Course Selection -->
    <div class="course-selection">
        <h2>COURSES/SUBJECTS</h2>
        <select id="courseSelect" class="course-select">
            <option value="SYSTEM INTEGRATION AND ARCHITECTURE">SYSTEM INTEGRATION AND ARCHITECTURE</option>
            <!-- Add more courses dynamically from database -->
        </select>
    </div>

    <!-- Tasks & Attendance Section -->
    <div class="tasks-attendance">
        <h3>Tasks & Attendance</h3>
        <div class="task-categories">
            <div class="task-category">✓ Preliminary</div>
            <div class="task-category">Midterm</div>
            <div class="task-category">Finals</div>
        </div>

        <!-- Grades & Score Section -->
        <div class="grades-section">
            <h3>Grades & Score</h3>
            <div class="semester-list">
                <div class="semester active">1st Semester 2023-2024</div>
                <div class="semester">2nd Semester 2023-2024</div>
                <div class="semester">1st Semester 2024-2025</div>
                <div class="semester">2nd Semester 2024-2025</div>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="progress-section">
        <h3>Progress</h3>
        <div class="progress-items">
            <div class="progress-item">
                <label>Assignments</label>
                <div class="progress-bar">
                    <div class="progress" style="width: 75%;"></div>
                </div>
                <span class="progress-value">75%</span>
            </div>
            <div class="progress-item">
                <label>Quizzes</label>
                <div class="progress-bar">
                    <div class="progress" style="width: 85%;"></div>
                </div>
                <span class="progress-value">85%</span>
            </div>
            <div class="progress-item">
                <label>Projects</label>
                <div class="progress-bar">
                    <div class="progress" style="width: 60%;"></div>
                </div>
                <span class="progress-value">60%</span>
            </div>
            <div class="progress-item">
                <label>Activities</label>
                <div class="progress-bar">
                    <div class="progress" style="width: 90%;"></div>
                </div>
                <span class="progress-value">90%</span>
            </div>
        </div>
    </div>

    <!-- Attendance Calendar -->
    <div class="attendance-section">
        <h3>Attendance</h3>
        <div class="calendar">
            <div class="calendar-header">
                <h4>March 2025</h4>
            </div>
            <div class="calendar-grid">
                <div class="weekdays">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>
                <div class="days">
                    <?php
                    $month = date('n');
                    $year = date('Y');
                    $firstDay = mktime(0,0,0,$month,1,$year);
                    $daysInMonth = date('t', $firstDay);
                    $firstDayOfWeek = date('w', $firstDay);

                    // Add empty cells for days before the first of the month
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        echo "<div class='day empty'></div>";
                    }

                    // Add cells for each day of the month
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $class = 'day';
                        if (isset($attendanceMap[$day])) {
                            $class .= ' ' . strtolower($attendanceMap[$day]);
                        }
                        echo "<div class='$class'>$day</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-container {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.course-selection {
    margin-bottom: 2rem;
}

.course-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background-color: white;
    font-size: 1rem;
}

.task-categories {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.task-category {
    padding: 0.5rem 1rem;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    cursor: pointer;
}

.task-category.active {
    background-color: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.semester-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.semester {
    padding: 0.75rem;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    cursor: pointer;
}

.semester.active {
    background-color: #4f46e5;
    color: white;
}

.progress-section {
    margin-top: 2rem;
}

.progress-items {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.progress-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.progress-bar {
    flex: 1;
    height: 0.75rem;
    background-color: #e2e8f0;
    border-radius: 1rem;
    overflow: hidden;
}

.progress {
    height: 100%;
    background-color: #4f46e5;
    border-radius: 1rem;
}

.progress-value {
    min-width: 3rem;
    text-align: right;
}

.attendance-section {
    margin-top: 2rem;
}

.calendar {
    background-color: white;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.calendar-header {
    padding: 1rem;
    background-color: #4f46e5;
    color: white;
    text-align: center;
}

.weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.weekdays div {
    padding: 0.5rem;
    font-weight: 500;
}

.days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.day {
    padding: 0.75rem;
    text-align: center;
    border: 1px solid #e2e8f0;
}

.day.present {
    background-color: #dcfce7;
    color: #166534;
}

.day.absent {
    background-color: #fee2e2;
    color: #991b1b;
}

.day.late {
    background-color: #fef3c7;
    color: #92400e;
}

.day.empty {
    background-color: #f8fafc;
}

@media (max-width: 768px) {
    .progress-container {
        padding: 1rem;
    }

    .task-categories {
        flex-wrap: wrap;
    }

    .progress-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .progress-value {
        text-align: left;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for task categories
    document.querySelectorAll('.task-category').forEach(category => {
        category.addEventListener('click', function() {
            document.querySelectorAll('.task-category').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Add click handlers for semesters
    document.querySelectorAll('.semester').forEach(semester => {
        semester.addEventListener('click', function() {
            document.querySelectorAll('.semester').forEach(s => s.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>

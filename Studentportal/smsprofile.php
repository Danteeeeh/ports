<?php
session_start();
require_once '../includes/Database.php';

if (!isset($_SESSION['student_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: studentlogin.php');
    exit();
}

$db = new Database();
$studentId = $_SESSION['student_id'];
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

include 'includes/header.php';
?>

<section class="profile-section">
    <h2>PROFILE</h2>
    <div class="profile-container">
        <div class="profile-picture">
            <img src="../assets/images/default-avatar.png" alt="Profile Picture">
        </div>
        <div class="profile-fields">
            <div class="field-row">
                <div class="field-group">
                    <label>First Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($student['first_name'] ?? ''); ?>" readonly>
                </div>
                <div class="field-group">
                    <label>Middle Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>" readonly>
                </div>
            </div>
            <div class="field-row">
                <div class="field-group">
                    <label>Last Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($student['last_name'] ?? ''); ?>" readonly>
                </div>
                <div class="field-group">
                    <label>Suffix</label>
                    <input type="text" value="<?php echo htmlspecialchars($student['suffix'] ?? ''); ?>" readonly>
                </div>
            </div>
            <div class="field-row">
                <div class="field-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($student['username'] ?? ''); ?>" readonly>
                </div>
                <div class="field-group">
                    <label>Role</label>
                    <input type="text" value="Student" readonly>
                </div>
            </div>
            <div class="field-row">
                <div class="field-group">
                    <label>Student ID</label>
                    <input type="text" value="<?php echo htmlspecialchars($student['student_id'] ?? ''); ?>" readonly>
                </div>
                <div class="field-group">
                    <label>School</label>
                    <input type="text" value="Bestlink College of the Philippines" readonly>
                </div>
            </div>
        </div>
    </div>
    <div class="profile-right">
        <div class="field-row">
            <div class="field-group">
                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($student['username'] ?? ''); ?>" readonly>
            </div>
            <div class="field-group">
                <label>Gmail Account</label>
                <input type="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" readonly>
            </div>
        </div>
        <div class="field-row">
            <div class="field-group">
                <label>Contact Number</label>
                <input type="tel" value="<?php echo htmlspecialchars($student['contact_number'] ?? ''); ?>" readonly>
            </div>
            <div class="field-group">
                <label>Civil Status</label>
                <input type="text" value="<?php echo htmlspecialchars($student['civil_status'] ?? ''); ?>" readonly>
            </div>
        </div>
        <div class="field-row">
            <div class="field-group">
                <label>Birthday</label>
                <input type="text" value="<?php echo htmlspecialchars($student['birthday'] ?? ''); ?>" readonly>
            </div>
            <div class="field-group">
                <label>Address</label>
                <input type="text" value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>" readonly>
            </div>
        </div>
    </div>
</section>

<?php
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->requireAuth();
$auth->requireRole('teacher');

$teacher_id = $_SESSION['user_id'];

// Get teacher information
$sql = "SELECT t.*, u.email, u.username
        FROM teachers t
        JOIN users u ON t.teacher_id = u.user_id
        WHERE t.teacher_id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get teaching load
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

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $contact_number = $_POST['contact_number'];
        $department = $_POST['department'];
        
        $sql = "UPDATE teachers 
                SET first_name = ?, last_name = ?, contact_number = ?, department = ?
                WHERE teacher_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("sssss", $first_name, $last_name, $contact_number, $department, $teacher_id);
        
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            // Refresh teacher data
            $stmt = $db->getConnection()->prepare("SELECT t.*, u.email, u.username
                                                 FROM teachers t
                                                 JOIN users u ON t.teacher_id = u.user_id
                                                 WHERE t.teacher_id = ?");
            $stmt->bind_param("s", $teacher_id);
            $stmt->execute();
            $teacher = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Error updating profile";
        }
    } elseif ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match";
        } else {
            // Verify current password
            $sql = "SELECT password FROM users WHERE user_id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param("s", $teacher_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = $db->getConnection()->prepare($sql);
                $stmt->bind_param("ss", $hashed_password, $teacher_id);
                
                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password";
                }
            } else {
                $error = "Current password is incorrect";
            }
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
    <title>Profile - Teacher Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .dashboard-container {
            padding: 2rem;
            margin-left: 250px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .profile-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            background: #6366f1;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #4f46e5;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 600;
            color: white;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .profile-body {
            padding: 1.5rem;
        }

        .info-group {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: #1f2937;
            font-weight: 500;
        }

        .form-section {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-header {
            background: #f9fafb;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
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

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
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

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .subject-card {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .subject-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .subject-meta {
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <h1 class="page-title">Profile</h1>
            <p class="text-gray-600">Manage your account settings</p>
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

        <div class="profile-grid">
            <div>
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)) ?>
                        </div>
                        <div class="profile-name">
                            <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                        </div>
                        <div class="profile-role">Teacher</div>
                    </div>
                    <div class="profile-body">
                        <div class="info-group">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= htmlspecialchars($teacher['email']) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Department</div>
                            <div class="info-value"><?= htmlspecialchars($teacher['department']) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?= htmlspecialchars($teacher['contact_number']) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Teaching Load</div>
                            <div class="info-value"><?= count($subjects) ?> subjects</div>
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-top: 2rem;">
                    <div class="form-header">
                        <h3 class="form-title">Teaching Load</h3>
                    </div>
                    <div class="form-body">
                        <div class="subjects-grid">
                            <?php foreach ($subjects as $subject): ?>
                            <div class="subject-card">
                                <div class="subject-name">
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </div>
                                <div class="subject-meta">
                                    <?= $subject['student_count'] ?> students
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="form-section">
                    <div class="form-header">
                        <h3 class="form-title">Update Profile</h3>
                    </div>
                    <div class="form-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label class="form-label" for="first_name">First Name</label>
                                <input type="text" 
                                       id="first_name" 
                                       name="first_name" 
                                       class="form-control"
                                       value="<?= htmlspecialchars($teacher['first_name']) ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input type="text" 
                                       id="last_name" 
                                       name="last_name" 
                                       class="form-control"
                                       value="<?= htmlspecialchars($teacher['last_name']) ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="contact_number">Contact Number</label>
                                <input type="text" 
                                       id="contact_number" 
                                       name="contact_number" 
                                       class="form-control"
                                       value="<?= htmlspecialchars($teacher['contact_number']) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="department">Department</label>
                                <input type="text" 
                                       id="department" 
                                       name="department" 
                                       class="form-control"
                                       value="<?= htmlspecialchars($teacher['department']) ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-header">
                        <h3 class="form-title">Change Password</h3>
                    </div>
                    <div class="form-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       class="form-control"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password</label>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-control"
                                       required>
                            </div>

                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

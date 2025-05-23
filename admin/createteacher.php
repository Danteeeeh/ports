<?php
session_start();

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: adminlogin.php');
    exit();
}

$db = new Database();
$auth = new Auth($db);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = filter_input(INPUT_POST, 'teacher_username', FILTER_SANITIZE_STRING);
    $password = isset($_POST['teacher_password']) ? trim($_POST['teacher_password']) : '';
    $fullname = filter_input(INPUT_POST, 'teacher_fullname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'teacher_email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'teacher_subject', FILTER_SANITIZE_STRING);

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required fields';
    } else {
        try {
            // Check if username already exists
            $checkUser = $db->query("SELECT * FROM users WHERE username = '$username'");
            if ($checkUser && $checkUser->num_rows > 0) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Begin transaction for data integrity
                if (!$db->beginTransaction()) {
                    throw new Exception('Could not start transaction: ' . $db->getLastError());
                }
                
                try {
                    // Insert into users table using the improved insert method
                    $userData = [
                        'username' => $username,
                        'password' => $hashedPassword,
                        'role' => 'teacher',
                        'status' => 'active'
                    ];
                    
                    $insertUser = $db->insert('users', $userData);
                    
                    if ($insertUser) {
                        // Get the last inserted ID using our new method
                        $userId = $db->getLastInsertId();
                        
                        if (!$userId) {
                            throw new Exception('Could not get last insert ID');
                        }
                        
                        // Insert into teachers table
                        $teacherData = [
                            'user_id' => $userId,
                            'fullname' => $fullname,
                            'email' => $email,
                            'subject' => $subject
                        ];
                        
                        $insertTeacher = $db->insert('teachers', $teacherData);
                        
                        if ($insertTeacher) {
                            // Log the activity
                            $admin = $_SESSION['username'];
                            $logData = [
                                'user_id' => $userId,
                                'activity_type' => 'account_creation',
                                'description' => 'Teacher account created by admin: ' . $admin
                            ];
                            
                            $db->insert('activity_logs', $logData);
                            
                            // Commit the transaction
                            if (!$db->commit()) {
                                throw new Exception('Failed to commit transaction: ' . $db->getLastError());
                            }
                            
                            $success = 'Teacher account created successfully!';
                        } else {
                            throw new Exception('Failed to create teacher profile: ' . $db->getLastError());
                        }
                    } else {
                        throw new Exception('Failed to create user account: ' . $db->getLastError());
                    }
                } catch (Exception $e) {
                    // Rollback the transaction if any part fails
                    $db->rollback();
                    throw $e; // Re-throw to be caught by the outer catch block
                }
            }
        } catch (Exception $e) {
            $error = 'System error occurred. Please try again later.';
            error_log('Teacher Creation Error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Teacher Account - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --success-color: #16a34a;
            --success-bg: #dcfce7;
            --error-color: #dc2626;
            --error-bg: #fee2e2;
            --border-radius: 0.5rem;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .header h1 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin: 0;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            color: var(--text-primary);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .back-button:hover {
            background: #e2e8f0;
        }

        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-hint {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .submit-button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-color);
            border: 1px solid #fecaca;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .required::after {
            content: '*';
            color: var(--error-color);
            margin-left: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create Teacher Account</h1>
            <a href="admindashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <div class="form-container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="createteacher.php">
                <div class="form-group">
                    <label for="teacher_username" class="form-label required">Username</label>
                    <input type="text" id="teacher_username" name="teacher_username" class="form-control" required>
                    <span class="form-hint">This will be used for login purposes</span>
                </div>

                <div class="form-group">
                    <label for="teacher_password" class="form-label required">Password</label>
                    <input type="password" id="teacher_password" name="teacher_password" class="form-control" required>
                    <span class="form-hint">Minimum 8 characters recommended</span>
                </div>

                <div class="form-group">
                    <label for="teacher_fullname" class="form-label required">Full Name</label>
                    <input type="text" id="teacher_fullname" name="teacher_fullname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="teacher_email" class="form-label">Email Address</label>
                    <input type="email" id="teacher_email" name="teacher_email" class="form-control">
                </div>

                <div class="form-group">
                    <label for="teacher_subject" class="form-label">Subject/Department</label>
                    <input type="text" id="teacher_subject" name="teacher_subject" class="form-control">
                </div>

                <button type="submit" class="submit-button">
                    <i class="fas fa-user-plus"></i>
                    Create Teacher Account
                </button>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('teacher_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password should be at least 8 characters long for security.');
            }
        });
    </script>
</body>
</html>

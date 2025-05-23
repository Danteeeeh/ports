<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// First connect without selecting database
$conn = new mysqli('localhost', 'root', '');

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS siasystem");
$conn->select_db("siasystem");

// Now require the Database class which will use siasystem
require_once 'includes/Database.php';

function setupDatabase() {
    global $conn;
    
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Drop tables in correct order (reverse of creation order)
    $tables_to_drop = [
        'task_submissions',  // Drop child tables first
        'tasks',
        'student_subjects',
        'teacher_subjects',
        'students',          // Drop tables with foreign keys
        'teachers',
        'admin_users',
        'subjects',          // Drop independent tables
        'users'
    ];
    
    foreach ($tables_to_drop as $table) {
        $sql = "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS $table; SET FOREIGN_KEY_CHECKS=1;";
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
            echo "$table table dropped successfully.<br>";
        } else {
            echo "Error dropping $table table: " . $conn->error . "<br>";
        }
    }
    
    // 1. First create users table (for authentication)
    $sql = "CREATE TABLE users (
        user_id VARCHAR(50) PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'teacher', 'student') NOT NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        email VARCHAR(100) UNIQUE,
        profile_image VARCHAR(255),
        last_login DATETIME,
        login_attempts INT DEFAULT 0,
        reset_token VARCHAR(255),
        reset_token_expires DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "Users table created successfully.<br>";
        
        // Create admin_users table
        $sql = "CREATE TABLE IF NOT EXISTS admin_users (
            admin_id VARCHAR(50) PRIMARY KEY,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            role_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        if ($conn->query($sql)) {
            echo "Admin users table created successfully.<br>";
        } else {
            echo "Error creating admin_users table: " . $conn->error . "<br>";
        }
        
        // Create teachers table
        $sql = "CREATE TABLE IF NOT EXISTS teachers (
            teacher_id VARCHAR(50) PRIMARY KEY,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            department VARCHAR(100),
            specialization VARCHAR(100),
            qualification VARCHAR(100),
            office_hours VARCHAR(255),
            contact_number VARCHAR(20),
            emergency_contact VARCHAR(20),
            bio TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        if ($conn->query($sql)) {
            echo "Teachers table created successfully.<br>";
        } else {
            echo "Error creating teachers table: " . $conn->error . "<br>";
        }
        
        // Create students table
        $sql = "CREATE TABLE IF NOT EXISTS students (
            student_id VARCHAR(50) PRIMARY KEY,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            course VARCHAR(50),
            year_level INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        if ($conn->query($sql)) {
            echo "Students table created successfully.<br>";
        } else {
            echo "Error creating students table: " . $conn->error . "<br>";
        }
        
        // Create test accounts for all user types
        $test_accounts = [
            [
                'id' => 'ADMIN001',
                'username' => 'admin',
                'password' => 'admin123',
                'role' => 'admin',
                'first_name' => 'System',
                'last_name' => 'Administrator'
            ],
            [
                'id' => 'TCHR001',
                'username' => 'teacher',
                'password' => 'teacher123',
                'role' => 'teacher',
                'first_name' => 'John',
                'last_name' => 'Smith'
            ],
            [
                'id' => 'STD001',
                'username' => 'student',
                'password' => 'student123',
                'role' => 'student',
                'first_name' => 'Jane',
                'last_name' => 'Doe'
            ]
        ];

        foreach ($test_accounts as $account) {
            // Create user account
            $password_hash = password_hash($account['password'], PASSWORD_DEFAULT);
            
            // Generate email based on username
            $email = $account['username'] . '@siasystem.edu';
            
            $sql = "INSERT INTO users (user_id, username, password, role, email, profile_image) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    username = VALUES(username),
                    password = VALUES(password),
                    role = VALUES(role),
                    email = VALUES(email),
                    profile_image = VALUES(profile_image)";
            $stmt = $conn->prepare($sql);
            $profile_image = '../assets/images/default-avatar.png';
            $stmt->bind_param("ssssss", 
                $account['id'], 
                $account['username'], 
                $password_hash, 
                $account['role'],
                $email,
                $profile_image
            );
            
            if ($stmt->execute()) {
                echo "Created/Updated {$account['role']} account:<br>";
                echo "Username: {$account['username']}<br>";
                echo "Password: {$account['password']}<br><br>";
                
                // Add role-specific details
                switch ($account['role']) {
                    case 'admin':
                        $sql = "INSERT INTO admin_users (admin_id, first_name, last_name, role_description) 
                                VALUES (?, ?, ?, 'Super Admin')
                                ON DUPLICATE KEY UPDATE 
                                first_name = VALUES(first_name),
                                last_name = VALUES(last_name)";
                        break;
                        
                    case 'teacher':
                        $sql = "INSERT INTO teachers (teacher_id, first_name, last_name, department, specialization, qualification, office_hours, contact_number, emergency_contact, bio) 
                                VALUES (?, ?, ?, 'Computer Science', 'Software Engineering', 'MSc', 'Monday to Friday, 8am-5pm', '1234567890', '1234567890', 'Teacher Bio')
                                ON DUPLICATE KEY UPDATE 
                                first_name = VALUES(first_name),
                                last_name = VALUES(last_name)";
                        break;
                        
                    case 'student':
                        $sql = "INSERT INTO students (student_id, first_name, last_name, course, year_level) 
                                VALUES (?, ?, ?, 'BSIT', 1)
                                ON DUPLICATE KEY UPDATE 
                                first_name = VALUES(first_name),
                                last_name = VALUES(last_name)";
                        break;
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", 
                    $account['id'], 
                    $account['first_name'], 
                    $account['last_name']
                );
                $stmt->execute();
            } else {
                echo "Error creating {$account['role']} user: " . $stmt->error . "<br>";
            }
        }
        
        // Check if we have any teacher users
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            // Create default teacher account
            $teacher_id = 'TCHR001';
            $teacher_password = password_hash('teacher123', PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (user_id, username, password, role, email, profile_image) VALUES (?, ?, ?, 'teacher', ?, ?)";
            $stmt = $conn->prepare($sql);
            $teacher_username = 'teacher';
            $teacher_email = 'teacher@siasystem.edu';
            $profile_image = '../assets/images/default-avatar.png';
            $stmt->bind_param("sssss", $teacher_id, $teacher_username, $teacher_password, $teacher_email, $profile_image);
            
            if ($stmt->execute()) {
                echo "Default teacher account created:<br>";
                echo "Username: teacher<br>";
                echo "Password: teacher123<br>";
            }
        }
    } else {
        echo "Error creating users table: " . $conn->error . "<br>";
    }
    
    // Create subjects table
    $sql = "CREATE TABLE IF NOT EXISTS subjects (
        subject_id VARCHAR(50) PRIMARY KEY,
        subject_name VARCHAR(100) NOT NULL,
        description TEXT,
        credits INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "Subjects table created successfully.<br>";
    } else {
        echo "Error creating subjects table: " . $conn->error . "<br>";
    }
    
    // Create teacher_subjects table
    $sql = "CREATE TABLE IF NOT EXISTS teacher_subjects (
        teacher_id VARCHAR(50),
        subject_id VARCHAR(50),
        academic_year VARCHAR(20),
        semester VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (teacher_id, subject_id),
        FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Teacher subjects table created successfully.<br>";
    } else {
        echo "Error creating teacher_subjects table: " . $conn->error . "<br>";
    }
    
    // Create student_subjects table
    $sql = "CREATE TABLE IF NOT EXISTS student_subjects (
        student_id VARCHAR(50),
        subject_id VARCHAR(50),
        academic_year VARCHAR(20),
        semester VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (student_id, subject_id),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Student subjects table created successfully.<br>";
    } else {
        echo "Error creating student_subjects table: " . $conn->error . "<br>";
    }
    
    // Create tasks table
    $sql = "CREATE TABLE IF NOT EXISTS tasks (
        task_id VARCHAR(50) PRIMARY KEY,
        teacher_id VARCHAR(50),
        subject_id VARCHAR(50),
        title VARCHAR(255) NOT NULL,
        description TEXT,
        due_date DATETIME,
        status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
        points INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Tasks table created successfully.<br>";
    } else {
        echo "Error creating tasks table: " . $conn->error . "<br>";
    }
    
    // Create task_submissions table
    $sql = "CREATE TABLE IF NOT EXISTS task_submissions (
        submission_id VARCHAR(50) PRIMARY KEY,
        task_id VARCHAR(50),
        student_id VARCHAR(50),
        submission_text TEXT,
        file_path VARCHAR(255),
        submitted_at DATETIME,
        status ENUM('pending', 'graded', 'late', 'resubmitted') DEFAULT 'pending',
        grade DECIMAL(5,2),
        feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Task submissions table created successfully.<br>";
        
        // Now that all tables are created, add sample data
        echo "<br>Adding sample data:<br>";
        
        // Add sample subjects
    $sample_subjects = [
        ['SUBJ001', 'Introduction to Programming', 'Basic concepts of programming using Python', 3],
        ['SUBJ002', 'Web Development', 'HTML, CSS, and JavaScript fundamentals', 3],
        ['SUBJ003', 'Database Management', 'SQL and database design principles', 3],
        ['SUBJ004', 'Data Structures', 'Advanced programming concepts and algorithms', 4],
        ['SUBJ005', 'Software Engineering', 'Software development lifecycle and methodologies', 4]
    ];
    
    $sql = "INSERT IGNORE INTO subjects (subject_id, subject_name, description, credits) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($sample_subjects as $subject) {
        $subject_id = $subject[0];
        $subject_name = $subject[1];
        $description = $subject[2];
        $credits = $subject[3];
        $stmt->bind_param("sssi", $subject_id, $subject_name, $description, $credits);
        $stmt->execute();
    }
    echo "Sample subjects added successfully.<br>";
    
    // Assign subjects to teacher
    $sql = "INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id, academic_year, semester) VALUES (?, ?, '2024-2025', '1st')";
    $stmt = $conn->prepare($sql);
    
    foreach ($sample_subjects as $subject) {
        $teacher_id = 'TCHR001';
        $subject_id = $subject[0];
        $stmt->bind_param("ss", $teacher_id, $subject_id);
        $stmt->execute();
    }
    echo "Subjects assigned to teacher successfully.<br>";
    
    // Add sample tasks
    $sample_tasks = [
        ['TASK001', 'TCHR001', 'SUBJ001', 'Python Basics Quiz', 'Complete the quiz on Python fundamentals', '2025-05-20 23:59:59', 'active', 100],
        ['TASK002', 'TCHR001', 'SUBJ001', 'Calculator Project', 'Create a simple calculator using Python', '2025-05-25 23:59:59', 'active', 150],
        ['TASK003', 'TCHR001', 'SUBJ002', 'Portfolio Website', 'Build your personal portfolio using HTML/CSS', '2025-05-22 23:59:59', 'active', 200],
        ['TASK004', 'TCHR001', 'SUBJ003', 'Database Design', 'Design a database for a library system', '2025-05-28 23:59:59', 'draft', 150],
        ['TASK005', 'TCHR001', 'SUBJ004', 'Sorting Algorithm', 'Implement and analyze different sorting algorithms', '2025-05-30 23:59:59', 'active', 180]
    ];
    
    $sql = "INSERT IGNORE INTO tasks (task_id, teacher_id, subject_id, title, description, due_date, status, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($sample_tasks as $task) {
        $task_id = $task[0];
        $teacher_id = $task[1];
        $subject_id = $task[2];
        $title = $task[3];
        $description = $task[4];
        $due_date = $task[5];
        $status = $task[6];
        $points = $task[7];
        $stmt->bind_param("sssssssi", $task_id, $teacher_id, $subject_id, $title, $description, $due_date, $status, $points);
        $stmt->execute();
    }
    echo "Sample tasks added successfully.<br>";
    
    // Add some students
    $sample_students = [
        ['STD002', 'student2', 'student123', 'Alice', 'Johnson'],
        ['STD003', 'student3', 'student123', 'Bob', 'Williams'],
        ['STD004', 'student4', 'student123', 'Carol', 'Brown'],
        ['STD005', 'student5', 'student123', 'David', 'Miller']
    ];
    
    foreach ($sample_students as $student) {
        // Create user account
        $password_hash = password_hash($student[2], PASSWORD_DEFAULT);
        
        $sql = "INSERT IGNORE INTO users (user_id, username, password, role) VALUES (?, ?, ?, 'student')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $student[0], $student[1], $password_hash);
        $stmt->execute();
        
        // Add student details
        $sql = "INSERT IGNORE INTO students (student_id, first_name, last_name, course, year_level) VALUES (?, ?, ?, 'BSIT', 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $student[0], $student[3], $student[4]);
        $stmt->execute();
        
        // Enroll students in subjects
        foreach ($sample_subjects as $subject) {
            $sql = "INSERT IGNORE INTO student_subjects (student_id, subject_id, academic_year, semester) VALUES (?, ?, '2024-2025', '1st')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $student[0], $subject[0]);
            $stmt->execute();
        }
    }
    echo "Sample students added and enrolled in subjects successfully.<br>";
    
    // Add some task submissions
    $sample_submissions = [
        ['SUB001', 'TASK001', 'STD002', 'Completed Python quiz', null, '2025-05-19 14:30:00', 'pending'],
        ['SUB002', 'TASK001', 'STD003', 'Completed Python quiz', null, '2025-05-19 15:45:00', 'pending'],
        ['SUB003', 'TASK002', 'STD002', 'Calculator implementation in Python', '/submissions/calc_std002.py', '2025-05-20 09:15:00', 'pending'],
        ['SUB004', 'TASK003', 'STD004', 'Portfolio website submission', '/submissions/portfolio_std004.zip', '2025-05-21 16:20:00', 'pending']
    ];
    
    $sql = "INSERT IGNORE INTO task_submissions (submission_id, task_id, student_id, submission_text, file_path, submitted_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($sample_submissions as $submission) {
        $submission_id = $submission[0];
        $task_id = $submission[1];
        $student_id = $submission[2];
        $submission_text = $submission[3];
        $file_path = $submission[4];
        $submitted_at = $submission[5];
        $status = $submission[6];
        $stmt->bind_param("sssssss", $submission_id, $task_id, $student_id, $submission_text, $file_path, $submitted_at, $status);
        $stmt->execute();
    }
        echo "Sample task submissions added successfully.<br>";
    } else {
        echo "Error creating task_submissions table: " . $conn->error . "<br>";
    }
    
    // 3. Create teachers table
    $sql = "DROP TABLE IF EXISTS teachers";
    $conn->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS teachers (
        teacher_id VARCHAR(50) PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        department VARCHAR(50) NOT NULL,
        specialization VARCHAR(100),
        qualification VARCHAR(100),
        office_hours TEXT,
        contact_number VARCHAR(20),
        emergency_contact VARCHAR(100),
        bio TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Teachers table created successfully.<br>";
        
        // Add default teacher details
        $sql = "INSERT IGNORE INTO teachers (teacher_id, first_name, last_name, department, specialization, qualification, office_hours, contact_number, emergency_contact, bio) 
                VALUES ('TCHR001', 'Default', 'Teacher', 'Computer Science', 'Software Engineering', 'MSc', 'Monday to Friday, 8am-5pm', '1234567890', '1234567890', 'Teacher Bio')";
        $conn->query($sql);
    }
    
    // 2. Create admin_users table
    $sql = "DROP TABLE IF EXISTS admin_users";
    $conn->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        admin_id VARCHAR(50) PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        role_description VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Admin users table created successfully.<br>";
        
        // Add default admin details
        $sql = "INSERT INTO admin_users (admin_id, first_name, last_name, role_description) 
                VALUES ('ADMIN001', 'System', 'Administrator', 'Super Admin')
                ON DUPLICATE KEY UPDATE 
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                role_description = VALUES(role_description)";
        if ($conn->query($sql)) {
            echo "Admin user details added successfully.<br>";
        } else {
            echo "Error adding admin details: " . $conn->error . "<br>";
        }
    }

    
    // 1. Create subjects table (base table)
    $sql = "CREATE TABLE IF NOT EXISTS subjects (
        subject_id VARCHAR(50) PRIMARY KEY,
        subject_code VARCHAR(20) UNIQUE NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        description TEXT,
        units INT NOT NULL,
        prerequisites TEXT,
        semester ENUM('1st', '2nd', 'summer') NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        max_students INT DEFAULT 40,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql)) {
        echo "Subjects table created successfully.<br>";
    }

    // Create tasks table
    $sql = "DROP TABLE IF EXISTS tasks";
    $conn->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS tasks (
        task_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        assigned_to VARCHAR(50),
        created_by VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Tasks table created successfully.<br>";
    }
    
    // Create concerns table
    $sql = "DROP TABLE IF EXISTS concerns";
    $conn->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS concerns (
        concern_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        raised_by VARCHAR(50),
        assigned_to VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (raised_by) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Concerns table created successfully.<br>";
    }
    
    // 4. Create students table
    $sql = "DROP TABLE IF EXISTS students";
    $conn->query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS students (
        student_id VARCHAR(50) PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        course VARCHAR(50) NOT NULL,
        year_level INT NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    if ($conn->query($sql)) {
        echo "Students table created successfully.<br>";
    }
    
    if ($conn->query($sql)) {
        echo "Students table created or already exists.<br>";
        
        // Check if we have any students
        $result = $conn->query("SELECT COUNT(*) as count FROM students");
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            // Create a test student account
            $student_id = '2023001';
            $password = password_hash('test123', PASSWORD_DEFAULT);
            
            // First create the user account
            $sql = "INSERT INTO users (user_id, username, password, role) VALUES (?, ?, ?, 'student')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $student_id, $student_id, $password);
            $stmt->execute();
            
            // Then create the student record
            $sql = "INSERT INTO students (student_id, first_name, last_name, course, year_level) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $first_name = 'Test';
            $last_name = 'Student';
            $course = 'BSIT';
            $year_level = 1;
            
            $stmt->bind_param("ssssi", $student_id, $first_name, $last_name, $course, $year_level);
            
            if ($stmt->execute()) {
                echo "Test student account created:<br>";
                echo "Student ID: 2023001<br>";
                echo "Password: test123<br>";
            } else {
                echo "Error creating test student: " . $stmt->error . "<br>";
            }
        } else {
            echo "Students table already has data.<br>";
        }
    } else {
        echo "Error creating students table: " . $conn->error . "<br>";
    }

    // Create attendance table
    $sql = "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_id VARCHAR(50) NOT NULL,
        student_id VARCHAR(50) NOT NULL,
        status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
        date DATE NOT NULL,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        UNIQUE KEY unique_attendance (subject_id, student_id, date)
    )";
    
    if ($conn->query($sql)) {
        echo "Attendance table created successfully.<br>";
        
        // Add sample attendance for the test student
        $sql = "INSERT IGNORE INTO attendance (student_id, subject_id, date, status) 
                VALUES ('2023001', 'SUBJ001', CURDATE(), 'present')";
        $conn->query($sql);
    } else {
        echo "Error creating attendance table: " . $conn->error . "<br>";
    }

    // Create enrolled_subjects table
    $sql = "CREATE TABLE IF NOT EXISTS enrolled_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20),
        subject_id VARCHAR(20),
        semester VARCHAR(20),
        school_year VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id),
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
        UNIQUE KEY unique_enrollment (student_id, subject_id, semester, school_year)
    )";
    
    if ($conn->query($sql)) {
        echo "Enrolled subjects table created successfully.<br>";
        
        // Add sample enrollment for the test student
        $sql = "INSERT IGNORE INTO enrolled_subjects (student_id, subject_id, semester, school_year) 
                VALUES ('2023001', 'SUBJ001', '1st', '2023-2024')";
        $conn->query($sql);
    } else {
        echo "Error creating enrolled_subjects table: " . $conn->error . "<br>";
    }

    // Create grades table
    $sql = "CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20),
        subject_id VARCHAR(20),
        grade DECIMAL(5,2),
        semester VARCHAR(20),
        school_year VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id),
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
        UNIQUE KEY unique_grade (student_id, subject_id, semester, school_year)
    )";
    
    if ($conn->query($sql)) {
        echo "Grades table created successfully.<br>";
        
        // Add sample grade for the test student
        $sql = "INSERT IGNORE INTO grades (student_id, subject_id, grade, semester, school_year) 
                VALUES ('2023001', 'SUBJ001', 85.50, '1st', '2023-2024')";
        $conn->query($sql);
    } else {
        echo "Error creating grades table: " . $conn->error . "<br>";
    }

    // Create events table
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        date DATE NOT NULL,
        start_time TIME,
        end_time TIME,
        location VARCHAR(255),
        event_type ENUM('academic', 'exam', 'holiday', 'other') DEFAULT 'other',
        student_id VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id)
    )";
    
    if ($conn->query($sql)) {
        echo "Events table created successfully.<br>";
        
        // Add sample events
        $sql = "INSERT IGNORE INTO events (title, description, date, start_time, end_time, event_type, student_id) VALUES 
                ('Midterm Exam', 'CS101 Midterm Examination', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:00:00', '12:00:00', 'exam', '2023001'),
                ('Project Deadline', 'Submit final project for CS101', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '23:59:59', '23:59:59', 'academic', '2023001'),
                ('Semester Break', 'End of first semester', DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL, NULL, 'holiday', NULL)";
        $conn->query($sql);
    } else {
        echo "Error creating events table: " . $conn->error . "<br>";
    }
}

// Run the setup
setupDatabase();

// Verify database and tables
echo "<br>Verifying database structure:<br>";
$conn->select_db("siasystem");

// Check users table
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<br>Users table structure:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}<br>";
    }
} else {
    echo "Error: Users table not found<br>";
}

// Check teachers table
$result = $conn->query("DESCRIBE teachers");
if ($result) {
    echo "<br>Teachers table structure:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}<br>";
    }
} else {
    echo "Error: Teachers table not found<br>";
}

// Check if teacher account exists
$result = $conn->query("SELECT user_id, username, email, role FROM users WHERE role = 'teacher'");
if ($result) {
    echo "<br>Teacher accounts:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['user_id']}, Username: {$row['username']}, Email: {$row['email']}, Role: {$row['role']}<br>";
    }
} else {
    echo "Error: No teacher accounts found<br>";
}

echo "<br>You can now try to login with these accounts:<br><br>";
echo "Admin Account:<br>";
echo "Username: admin<br>";
echo "Password: admin123<br><br>";
echo "Teacher Account:<br>";
echo "Username: teacher<br>";
echo "Password: teacher123<br><br>";
echo "Student Account:<br>";
echo "Student ID: 2023001<br>";
echo "Password: test123<br>";
?>

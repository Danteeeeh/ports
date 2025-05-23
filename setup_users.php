<?php
require_once 'includes/Database.php';

$db = new Database();

// Create users table if not exists
$db->query("CREATE TABLE IF NOT EXISTS users (
    user_id VARCHAR(50) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create teachers table if not exists
$db->query("CREATE TABLE IF NOT EXISTS teachers (
    teacher_id VARCHAR(50) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    department VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id)
)");

// Create students table if not exists
$db->query("CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(50) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    program VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id)
)");

// Create default admin user
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$db->query("INSERT IGNORE INTO users (user_id, username, password, role) 
            VALUES ('ADMIN001', 'admin', '$adminPassword', 'admin')");

// Create sample teacher users
$teacherPassword = password_hash('teacher123', PASSWORD_DEFAULT);
$teachers = [
    ['TCH001', 'teacher1', 'John', 'Smith', 'Mathematics'],
    ['TCH002', 'teacher2', 'Mary', 'Johnson', 'Science'],
    ['TCH003', 'teacher3', 'Robert', 'Wilson', 'English']
];

foreach ($teachers as $teacher) {
    // Insert into users table
    $db->query("INSERT IGNORE INTO users (user_id, username, password, role) 
                VALUES ('{$teacher[0]}', '{$teacher[1]}', '$teacherPassword', 'teacher')");
    
    // Insert into teachers table
    $db->query("INSERT IGNORE INTO teachers (teacher_id, first_name, last_name, department) 
                VALUES ('{$teacher[0]}', '{$teacher[2]}', '{$teacher[3]}', '{$teacher[4]}')");
}

// Create sample student users
$studentPassword = password_hash('student123', PASSWORD_DEFAULT);
$students = [
    ['STD001', 'student1', 'Alice', 'Brown', '1st Year', 'A'],
    ['STD002', 'student2', 'Bob', 'Davis', '2nd Year', 'B'],
    ['STD003', 'student3', 'Carol', 'Evans', '3rd Year', 'A']
];

foreach ($students as $student) {
    // Insert into users table
    $db->query("INSERT IGNORE INTO users (user_id, username, password, role) 
                VALUES ('{$student[0]}', '{$student[1]}', '$studentPassword', 'student')");
    
    // Insert into students table
    $db->query("INSERT IGNORE INTO students (student_id, first_name, last_name, year_level, section) 
                VALUES ('{$student[0]}', '{$student[2]}', '{$student[3]}', '{$student[4]}', '{$student[5]}')");
}

echo "User setup completed successfully!\n";
echo "\nDefault Credentials:\n";
echo "Admin - Username: admin, Password: admin123\n";
echo "Teachers - Username: teacher1/teacher2/teacher3, Password: teacher123\n";
echo "Students - Username: student1/student2/student3, Password: student123\n";
?>

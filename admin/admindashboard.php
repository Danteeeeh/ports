<?php
session_start();

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: adminlogin.php');
    exit();
}

// Get admin details
$admin = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <script src="script.js" defer></script>
  <style>
   
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      display: flex;
      background-color: #f4f7f6;
    }


    .sidebar {
      background: #2c3e50;
      color: white;
      width: 250px;
      padding: 20px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .sidebar h3 {
      font-size: 24px;
      margin-bottom: 20px;
      color: white;
      text-align: center;
    }

    .sidebar small {
      color: #95a5a6;
      display: block;
      text-align: center;
    }

    .logo {
      display: flex;
      justify-content: center;
      margin-bottom: 30px;
    }

    .logo img {
      width: 150px;
      height: auto;
      border-radius: 10px;
    }


    .sidebar nav button {
      display: block;
      width: 100%;
      margin: 10px 0;
      background: #34495e;
      color: white;
      border: none;
      padding: 15px;
      font-size: 18px;
      text-align: left;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }


    .sidebar nav button:hover {
      background: #1abc9c;
      transform: scale(1.05);
    }

    .sidebar nav button:focus {
      outline: none;
    }

    
    .main-content {
      flex: 1;
      margin-left: 270px; 
      padding: 30px;
    }


    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }


    .content-section {
      display: none;
    }

    .content-section.active {
      display: block;
    }


    form input {
      display: block;
      margin: 10px 0;
      padding: 10px;
      width: 100%;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: #f4f4f4;
    }

    form button {
      padding: 10px 20px;
      background: #3498db;
      color: white;
      border: none;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    form button:hover {
      background: #2980b9;
    }

  
    h2 {
      font-size: 24px;
      color: #333;
    }

   


  </style>
</head>
<body>

  <div class="allaroundcontainer">
    <?php
    require_once 'includes/header.php';
    ?>

    <main class="main-content">

      <?php
      // Get statistics from database
      require_once '../includes/Database.php';
      $db = new Database();

      // Get total number of teachers
      $teacherCount = $db->query("SELECT COUNT(*) as count FROM teachers")->fetch_object()->count;

      // Get total number of students
      $studentCount = $db->query("SELECT COUNT(*) as count FROM students")->fetch_object()->count;

      // Get total number of subjects
      $subjectCount = $db->query("SELECT COUNT(*) as count FROM subjects")->fetch_object()->count;

      // Get recent activities (from users table for now)
      $recentActivities = $db->query(
          "SELECT 
              'login' as type, 
              CONCAT('User ', username, ' logged in') as title, 
              last_login as created_at, 
              status 
          FROM users 
          WHERE last_login IS NOT NULL 
          ORDER BY last_login DESC LIMIT 5"
      );
      
      $recentActivities = $recentActivities ? $recentActivities->fetch_all(MYSQLI_ASSOC) : [];
      ?>

      <section id="dashboard" class="content-section active">
        <h1>Welcome to the Admin Dashboard</h1>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon teachers">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3>Total Teachers</h3>
                    <p class="stat-value"><?php echo $teacherCount; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon students">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3>Total Students</h3>
                    <p class="stat-value"><?php echo $studentCount; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon subjects">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3>Total Subjects</h3>
                    <p class="stat-value"><?php echo $subjectCount; ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="recent-activities">
            <div class="section-header">
                <h2>Recent Activities</h2>
            </div>
            <div class="activities-list">
                <?php foreach ($recentActivities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $activity['type']; ?>">
                        <?php if ($activity['type'] === 'task'): ?>📝<?php else: ?>💬<?php endif; ?>
                    </div>
                    <div class="activity-details">
                        <h4><?php echo htmlspecialchars($activity['title']); ?></h4>
                        <p><?php echo date('M d, Y', strtotime($activity['created_at'])); ?></p>
                        <span class="status-badge <?php echo strtolower($activity['status']); ?>">
                            <?php echo htmlspecialchars($activity['status']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
      </section>

      <section id="teacher" class="content-section">
        <h2>Teachers Management</h2>
        
        <div class="section-actions">
          <a href="createteacher.php" class="action-button">
            <i class="fas fa-user-plus"></i>
            Add New Teacher
          </a>
        </div>
        
        <!-- Teacher List -->
        <div class="data-table-container">
          <div class="table-header">
            <h3>Teacher List</h3>
            <div class="table-actions">
              <div class="search-wrapper">
                <input type="text" id="teacherSearch" placeholder="Search teachers..." class="search-input">
                <i class="fas fa-search"></i>
              </div>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Username</th>
                  <th>Subject</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Get teachers from database
                $teachers = $db->query("SELECT t.*, u.username, u.status FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.role = 'teacher' ORDER BY t.id DESC");
                
                if ($teachers && $teachers->num_rows > 0) {
                  while ($teacher = $teachers->fetch_object()) {
                    echo "<tr>";
                    echo "<td>{$teacher->id}</td>";
                    echo "<td>{$teacher->fullname}</td>";
                    echo "<td>{$teacher->username}</td>";
                    echo "<td>{$teacher->subject}</td>";
                    echo "<td><span class='status-badge " . strtolower($teacher->status) . "'>{$teacher->status}</span></td>";
                    echo "<td class='actions'>";
                    echo "<button class='action-icon edit' title='Edit'><i class='fas fa-edit'></i></button>";
                    echo "<button class='action-icon delete' title='Delete'><i class='fas fa-trash-alt'></i></button>";
                    echo "</td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='6' class='no-data'>No teachers found</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="studentclasses" class="content-section">
        <h2>Students Management</h2>
        
        <div class="section-actions">
          <a href="createstudent.php" class="action-button">
            <i class="fas fa-user-plus"></i>
            Add New Student
          </a>
        </div>
        
        <!-- Student List -->
        <div class="data-table-container">
          <div class="table-header">
            <h3>Student List</h3>
            <div class="table-actions">
              <div class="search-wrapper">
                <input type="text" id="studentSearch" placeholder="Search students..." class="search-input">
                <i class="fas fa-search"></i>
              </div>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Username</th>
                  <th>Class</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Get students from database
                $students = $db->query("SELECT s.*, u.username, u.status FROM students s JOIN users u ON s.user_id = u.id WHERE u.role = 'student' ORDER BY s.id DESC");
                
                if ($students && $students->num_rows > 0) {
                  while ($student = $students->fetch_object()) {
                    echo "<tr>";
                    echo "<td>{$student->id}</td>";
                    echo "<td>{$student->fullname}</td>";
                    echo "<td>{$student->username}</td>";
                    echo "<td>{$student->class}</td>";
                    echo "<td><span class='status-badge " . strtolower($student->status) . "'>{$student->status}</span></td>";
                    echo "<td class='actions'>";
                    echo "<button class='action-icon edit' title='Edit'><i class='fas fa-edit'></i></button>";
                    echo "<button class='action-icon delete' title='Delete'><i class='fas fa-trash-alt'></i></button>";
                    echo "</td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='6' class='no-data'>No students found</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="settings" class="content-section">
        <h2>Settings and Profile</h2>
      </section>

      <section id="feature" class="content-section">
        <h2>Feature Section</h2>
      </section>

      <!-- Add styles for stats and activities -->
      <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon.teachers { background: #e0f2fe; color: #0284c7; }
        .stat-icon.students { background: #fef3c7; color: #d97706; }
        .stat-icon.subjects { background: #dcfce7; color: #16a34a; }

        .stat-icon svg {
            width: 24px;
            height: 24px;
        }

        .stat-details h3 {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .recent-activities {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .activities-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: #f8fafc;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .activity-icon.task { background: #dcfce7; }
        .activity-icon.concern { background: #fef3c7; }

        .activity-details h4 {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .activity-details p {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        /* Teacher management styles */
        .section-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }
        
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--primary-color);
            color: white;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .action-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .data-table-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-header h3 {
            font-size: 1.125rem;
            color: var(--text-primary);
            margin: 0;
        }
        
        .table-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .search-wrapper {
            position: relative;
        }
        
        .search-wrapper .search-input {
            padding: 0.5rem 1rem;
            padding-left: 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            width: 250px;
        }
        
        .search-wrapper i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
        }
        
        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 500;
        }
        
        .data-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .data-table tbody tr:hover {
            background: #f1f5f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge.active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-badge.inactive {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 0.25rem;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .action-icon.edit {
            color: #0284c7;
        }
        
        .action-icon.delete {
            color: #dc2626;
        }
        
        .action-icon:hover {
            background: #f1f5f9;
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.in_progress { background: #e0f2fe; color: #0369a1; }
      </style>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show the selected section
            document.getElementById(sectionId).classList.add('active');
        }
        
        // Wait for the DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Teacher search functionality
            const teacherSearch = document.getElementById('teacherSearch');
            if (teacherSearch) {
                teacherSearch.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const teacherTable = document.querySelector('#teacher .data-table');
                    const rows = teacherTable.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchValue)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
            
            // Student search functionality
            const studentSearch = document.getElementById('studentSearch');
            if (studentSearch) {
                studentSearch.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const studentTable = document.querySelector('#studentclasses .data-table');
                    const rows = studentTable.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchValue)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
<?php require_once 'includes/footer.php'; ?>

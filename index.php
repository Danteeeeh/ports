<?php
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

// Initialize Database and Auth
$db = new Database();
$auth = new Auth($db);

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    // Redirect based on user role
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: admin/admindashboard.php');
            break;
        case 'student':
            header('Location: Studentportal/studentdashboard.php');
            break;
        case 'teacher':
            header('Location: teacherportal/teacherdashboard.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCP Student Information System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            text-align: center;
            background: linear-gradient(135deg, #f6f9fc 0%, #edf2f7 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }
        
        .welcome-box {
            background-color: #fff;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, #ef476f, #06d6a0, #118ab2);
        }
        
        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        p {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .portal-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .button {
            display: inline-block;
            text-align: center;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            border: none;
            border-radius: 0.75rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 0 10px;
        }
        
        .button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .button i {
            font-size: 1.25rem;
        }
        
        .admin {
            background: #ef476f;
            color: white;
        }
        
        .student {
            background: #06d6a0;
            color: white;
        }
        
        .teacher {
            background: #118ab2;
            color: white;
        }
        
        .footer {
            margin-top: 3rem;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .portal-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .button {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="particles-container" id="particles-js"></div>
    <div class="container">
        <div class="welcome-box">
            <img src="assets/images/bcplogo.png" alt="BCP Logo" class="logo">
            <h1>Welcome to BCP Student Information System</h1>
            <p>Access your academic information, attendance records, and more through our comprehensive portal system.</p>
            <div class="portal-buttons">
                <a href="admin/adminlogin.php" class="button admin">
                    <i class="fas fa-user-shield"></i>&nbsp;
                    Admin Portal
                </a>
                <a href="Studentportal/studentlogin.php" class="button student">
                    <i class="fas fa-user-graduate"></i>&nbsp;
                    Student Portal
                </a>
                <a href="teacherportal/teacherlogin.php" class="button teacher">
                    <i class="fas fa-chalkboard-teacher"></i>&nbsp;
                    Teacher Portal
                </a>
            </div>
            <div class="footer">
                <p>© 2025 BCP Student Information System. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Particle.js Scripts -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                "particles": {
                    "number": {
                        "value": 80,
                        "density": {
                            "enable": true,
                            "value_area": 800
                        }
                    },
                    "color": {
                        "value": "#4361ee"
                    },
                    "shape": {
                        "type": "circle",
                        "stroke": {
                            "width": 0,
                            "color": "#000000"
                        },
                    },
                    "opacity": {
                        "value": 0.3,
                        "random": false,
                    },
                    "size": {
                        "value": 3,
                        "random": true,
                    },
                    "line_linked": {
                        "enable": true,
                        "distance": 150,
                        "color": "#4361ee",
                        "opacity": 0.2,
                        "width": 1
                    },
                    "move": {
                        "enable": true,
                        "speed": 2,
                        "direction": "none",
                        "random": false,
                        "straight": false,
                        "out_mode": "out",
                        "bounce": false,
                    }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": {
                        "onhover": {
                            "enable": true,
                            "mode": "grab"
                        },
                        "onclick": {
                            "enable": true,
                            "mode": "push"
                        },
                        "resize": true
                    },
                },
                "retina_detect": true
            });
        });
    </script>
</body>
</html>

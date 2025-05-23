<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($username, $password) {
        try {
            // First query the users table
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            if (!$stmt) {
                error_log('Prepare failed: ' . $this->db->getConnection()->error);
                return false;
            }

            $stmt->bind_param('s', $username);
            if (!$stmt->execute()) {
                error_log('Execute failed: ' . $stmt->error);
                return false;
            }

            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Get additional user details based on role
                    $details = $this->getUserDetails($user['user_id'], $user['role']);
                    
                    if ($details) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_details'] = $details;
                        
                        // Update last login
                        $this->updateLastLogin($user['user_id']);
                        
                        return true;
                    }
                } else {
                    error_log('Password verification failed for user: ' . $username);
                }
            } else {
                error_log('No active user found with username: ' . $username);
            }
            return false;
        } catch (Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            return false;
        }
    }

    private function getUserDetails($user_id, $role) {
        $table = '';
        $id_field = '';
        
        switch($role) {
            case 'admin':
                $table = 'admin_users';
                $id_field = 'admin_id';
                break;
            case 'teacher':
                $table = 'teachers';
                $id_field = 'teacher_id';
                break;
            case 'student':
                $table = 'students';
                $id_field = 'student_id';
                break;
            default:
                return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE {$id_field} = ?");
        if (!$stmt) return null;

        $stmt->bind_param('s', $user_id);
        if (!$stmt->execute()) return null;

        $result = $stmt->get_result();
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }

    private function updateLastLogin($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('s', $user_id);
            $stmt->execute();
        }
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['user_role'];

        // Get user from users table
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        if (!$stmt) return null;

        $stmt->bind_param('s', $user_id);
        if (!$stmt->execute()) return null;

        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) return null;

        $user = $result->fetch_assoc();
        $user['details'] = $this->getUserDetails($user_id, $role);

        return $user;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }

    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /SIASYSTEM/index.php');
            exit();
        }
    }

    public function requireRole($role) {
        $this->requireAuth();
        if ($_SESSION['user_role'] !== $role) {
            header('Location: /SIASYSTEM/unauthorized.php');
            exit();
        }
    }

    public function getUserType() {
        return $_SESSION['user_role'] ?? null;
    }
}

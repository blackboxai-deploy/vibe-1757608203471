<?php
require_once '../config/database.php';
require_once '../config/config.php';

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Register new user
    public function register($userData) {
        try {
            $query = "INSERT INTO users (username, email, password, first_name, last_name, phone, role) 
                      VALUES (:username, :email, :password, :first_name, :last_name, :phone, :role)";
            
            $stmt = $this->db->prepare($query);
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':phone', $userData['phone']);
            $stmt->bindParam(':role', $userData['role']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }
    
    // Login user
    public function login($email, $password) {
        try {
            $query = "SELECT id, username, email, password, first_name, last_name, role, status 
                      FROM users WHERE email = :email AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    // Start session and store user data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    
                    return true;
                }
            }
            return false;
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    // Logout user
    public function logout() {
        session_destroy();
        return true;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Check user role
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    // Check if email exists
    public function emailExists($email) {
        try {
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Check if username exists
    public function usernameExists($username) {
        try {
            $query = "SELECT id FROM users WHERE username = :username";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Get current user data
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        try {
            $query = "SELECT u.*, v.id as vendor_id, v.store_name, v.status as vendor_status 
                      FROM users u 
                      LEFT JOIN vendors v ON u.id = v.user_id 
                      WHERE u.id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Require login
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit();
        }
    }
    
    // Require specific role
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('Location: /index.php?error=access_denied');
            exit();
        }
    }
    
    // Generate CSRF token
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF token
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Create global auth instance
$auth = new Auth($db);
?>
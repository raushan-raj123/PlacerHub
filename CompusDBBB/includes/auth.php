<?php
/**
 * Authentication System
 * CompusDB Management System
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $full_name, $phone = null) {
        try {
            // Check if user already exists
            if ($this->userExists($username, $email)) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Validate password strength
            if (!$this->validatePassword($password)) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, full_name, phone, role, status) 
                VALUES (?, ?, ?, ?, ?, 'user', 'active')
            ");
            
            $stmt->execute([$username, $email, $hashedPassword, $full_name, $phone]);
            $userId = $this->db->lastInsertId();
            
            // Create default user settings
            $this->createDefaultUserSettings($userId);
            
            // Log activity
            $this->logActivity($userId, 'user_registered', 'users', $userId);
            
            // Create welcome notification
            $this->createNotification($userId, 'Welcome to CompusDB!', 'Your account has been created successfully. Welcome to the system!', 'success');
            
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $userId];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Login user
     */
    public function login($username, $password, $remember = false) {
        try {
            // Check login attempts
            if ($this->isAccountLocked($username)) {
                return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts'];
            }
            
            // Get user
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, full_name, role, status, profile_picture 
                FROM users 
                WHERE (username = ? OR email = ?) AND status = 'active'
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                $this->recordFailedLogin($username);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Clear failed login attempts
            $this->clearFailedLogins($username);
            
            // Create session
            $sessionToken = $this->createSession($user['id'], $remember);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['session_token'] = $sessionToken;
            $_SESSION['last_activity'] = time();
            
            // Log activity
            $this->logActivity($user['id'], 'user_login', 'users', $user['id']);
            
            return [
                'success' => true, 
                'message' => 'Login successful',
                'user' => $user,
                'redirect' => $user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            // Remove session from database
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$_SESSION['session_token']]);
        }
        
        // Log activity
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'user_logout', 'users', $_SESSION['user_id']);
        }
        
        // Destroy session
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        // Validate session token
        $stmt = $this->db->prepare("
            SELECT id FROM user_sessions 
            WHERE session_token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['session_token']]);
        
        if (!$stmt->fetch()) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Check if user has admin role
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->db->prepare("
            SELECT id, username, email, full_name, phone, profile_picture, role, created_at 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Get current password
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (!$this->validatePassword($newPassword)) {
                return ['success' => false, 'message' => 'New password does not meet requirements'];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Log activity
            $this->logActivity($userId, 'password_changed', 'users', $userId);
            
            // Create notification
            $this->createNotification($userId, 'Password Changed', 'Your password has been successfully updated.', 'success');
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to change password: ' . $e->getMessage()];
        }
    }
    
    // Private helper methods
    
    private function userExists($username, $email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        return $stmt->fetch() !== false;
    }
    
    private function validatePassword($password) {
        return strlen($password) >= PASSWORD_MIN_LENGTH &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }
    
    private function createSession($userId, $remember = false) {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = $remember ? date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)) : date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, 
            $sessionToken, 
            $_SERVER['REMOTE_ADDR'] ?? '', 
            $_SERVER['HTTP_USER_AGENT'] ?? '', 
            $expiresAt
        ]);
        
        return $sessionToken;
    }
    
    private function createDefaultUserSettings($userId) {
        $stmt = $this->db->prepare("
            INSERT INTO user_settings (user_id, theme_mode, notification_pref, language) 
            VALUES (?, 'light', ?, 'en')
        ");
        $defaultNotifications = json_encode([
            'email_notifications' => true,
            'push_notifications' => true,
            'ticket_updates' => true,
            'system_alerts' => true
        ]);
        $stmt->execute([$userId, $defaultNotifications]);
    }
    
    private function isAccountLocked($username) {
        // Implementation for account locking logic
        return false; // Simplified for now
    }
    
    private function recordFailedLogin($username) {
        // Implementation for recording failed login attempts
    }
    
    private function clearFailedLogins($username) {
        // Implementation for clearing failed login attempts
    }
    
    private function logActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $tableName,
                $recordId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    private function createNotification($userId, $title, $message, $type = 'info') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $message, $type]);
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }
    }
}

// Authentication middleware functions
function requireLogin() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireAdmin() {
    $auth = new Auth();
    if (!$auth->isAdmin()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

function redirectIfLoggedIn() {
    $auth = new Auth();
    if ($auth->isLoggedIn()) {
        $redirect = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php';
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}
?>

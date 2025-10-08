<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

class AdminAuth {
    private $db;
    
    public function __construct() {
        $this->db = new SQLite3('admin.db');
        $this->initDatabase();
    }
    
    private function initDatabase() {
        // Create admin users table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                role VARCHAR(20) DEFAULT 'admin',
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME,
                login_attempts INTEGER DEFAULT 0,
                locked_until DATETIME
            )
        ");
        
        // Create admin sessions table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                user_agent TEXT,
                FOREIGN KEY (admin_id) REFERENCES admin_users (id)
            )
        ");
        
        // Create default admin user if none exists
        $this->createDefaultAdmin();
    }
    
    private function createDefaultAdmin() {
        $count = $this->db->querySingle("SELECT COUNT(*) FROM admin_users");
        
        if ($count == 0) {
            $username = 'admin';
            $email = 'admin@attralecommerce.com';
            $password = 'Admin@123'; // Change this in production
            $name = 'Administrator';
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO admin_users (username, email, password_hash, name, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bindValue(1, $username);
            $stmt->bindValue(2, $email);
            $stmt->bindValue(3, $passwordHash);
            $stmt->bindValue(4, $name);
            $stmt->bindValue(5, 'super_admin');
            $stmt->execute();
            
            error_log("Default admin user created: $username / $password");
        }
    }
    
    public function handleRequest() {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'login':
                return $this->login($input);
            case 'logout':
                return $this->logout($input);
            case 'verify_token':
                return $this->verifyToken($input);
            case 'refresh_token':
                return $this->refreshToken($input);
            default:
                return $this->error('Invalid action');
        }
    }
    
    private function login($data) {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            return $this->error('Username and password are required');
        }
        
        // Check if account is locked
        $stmt = $this->db->prepare("
            SELECT * FROM admin_users 
            WHERE (username = ? OR email = ?) AND is_active = 1
        ");
        $stmt->bindValue(1, $username);
        $stmt->bindValue(2, $username);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) {
            return $this->error('Invalid credentials');
        }
        
        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return $this->error('Account is temporarily locked due to too many failed attempts');
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->incrementLoginAttempts($user['id']);
            return $this->error('Invalid credentials');
        }
        
        // Reset login attempts on successful login
        $this->resetLoginAttempts($user['id']);
        
        // Create session
        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->db->prepare("
            INSERT INTO admin_sessions (admin_id, token, expires_at, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bindValue(1, $user['id']);
        $stmt->bindValue(2, $token);
        $stmt->bindValue(3, $expiresAt);
        $stmt->bindValue(4, $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->bindValue(5, $_SERVER['HTTP_USER_AGENT'] ?? '');
        $stmt->execute();
        
        // Update last login
        $stmt = $this->db->prepare("
            UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?
        ");
        $stmt->bindValue(1, $user['id']);
        $stmt->execute();
        
        return $this->success([
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ]);
    }
    
    private function logout($data) {
        $token = $data['token'] ?? '';
        
        if ($token) {
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE token = ?");
            $stmt->bindValue(1, $token);
            $stmt->execute();
        }
        
        return $this->success(['message' => 'Logged out successfully']);
    }
    
    private function verifyToken($data) {
        $token = $data['token'] ?? '';
        
        if (empty($token)) {
            return $this->error('Token is required');
        }
        
        $stmt = $this->db->prepare("
            SELECT au.*, as.token, as.expires_at 
            FROM admin_users au 
            JOIN admin_sessions as ON au.id = as.admin_id 
            WHERE as.token = ? AND as.expires_at > CURRENT_TIMESTAMP AND au.is_active = 1
        ");
        $stmt->bindValue(1, $token);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$result) {
            return $this->error('Invalid or expired token');
        }
        
        return $this->success([
            'user' => [
                'id' => $result['id'],
                'username' => $result['username'],
                'email' => $result['email'],
                'name' => $result['name'],
                'role' => $result['role']
            ]
        ]);
    }
    
    private function refreshToken($data) {
        $token = $data['token'] ?? '';
        
        if (empty($token)) {
            return $this->error('Token is required');
        }
        
        // Verify current token
        $stmt = $this->db->prepare("
            SELECT au.*, as.token 
            FROM admin_users au 
            JOIN admin_sessions as ON au.id = as.admin_id 
            WHERE as.token = ? AND as.expires_at > CURRENT_TIMESTAMP AND au.is_active = 1
        ");
        $stmt->bindValue(1, $token);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$result) {
            return $this->error('Invalid or expired token');
        }
        
        // Generate new token
        $newToken = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update session
        $stmt = $this->db->prepare("
            UPDATE admin_sessions 
            SET token = ?, expires_at = ? 
            WHERE token = ?
        ");
        $stmt->bindValue(1, $newToken);
        $stmt->bindValue(2, $expiresAt);
        $stmt->bindValue(3, $token);
        $stmt->execute();
        
        return $this->success([
            'token' => $newToken,
            'expires_at' => $expiresAt
        ]);
    }
    
    private function incrementLoginAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE admin_users 
            SET login_attempts = login_attempts + 1,
                locked_until = CASE 
                    WHEN login_attempts >= 4 THEN datetime('now', '+15 minutes')
                    ELSE locked_until
                END
            WHERE id = ?
        ");
        $stmt->bindValue(1, $userId);
        $stmt->execute();
    }
    
    private function resetLoginAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE admin_users 
            SET login_attempts = 0, locked_until = NULL 
            WHERE id = ?
        ");
        $stmt->bindValue(1, $userId);
        $stmt->execute();
    }
    
    private function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    private function success($data = []) {
        return json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function error($message) {
        return json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Handle the request
$auth = new AdminAuth();
echo $auth->handleRequest();
?>

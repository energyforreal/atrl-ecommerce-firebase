<?php
/**
 * 🛡️ ATTRAL Site Access Control
 * Manages testing environment access restrictions
 */

class SiteAccessControl {
    
    private $config;
    private $sessionKey = 'attral_admin_access';
    private $configFile;
    
    public function __construct() {
        $this->configFile = __DIR__ . '/config/access-control.json';
        $this->loadConfig();
        $this->startSession();
    }
    
    /**
     * Load access control configuration
     */
    private function loadConfig() {
        $defaultConfig = [
            'maintenance_mode' => true,
            'allowed_ips' => [],
            'admin_password' => 'Rakeshmurali@10', // Updated to match admin login
            'maintenance_message' => 'Site is under maintenance for testing',
            'bypass_paths' => [
                '/admin-login.html',
                '/site-access-control.php',
                '/maintenance.html'
            ]
        ];
        
        if (file_exists($this->configFile)) {
            $savedConfig = json_decode(file_get_contents($this->configFile), true);
            if ($savedConfig) {
                $this->config = array_merge($defaultConfig, $savedConfig);
            } else {
                $this->config = $defaultConfig;
            }
        } else {
            $this->config = $defaultConfig;
            $this->saveConfig();
        }
    }
    
    /**
     * Save configuration to file
     */
    private function saveConfig() {
        $dir = dirname($this->configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT));
    }
    
    /**
     * Start session if not already started
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
            session_start();
        }
    }
    
    /**
     * Get visitor's real IP address
     */
    public function getRealIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check if current IP is allowed
     */
    public function isIPAllowed($ip = null) {
        if ($ip === null) {
            $ip = $this->getRealIP();
        }
        
        // Allow localhost
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }
        
        // Check against whitelist
        foreach ($this->config['allowed_ips'] as $allowedIP) {
            if ($this->ipInRange($ip, $allowedIP)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        
        return ($ip & $mask) === $subnet;
    }
    
    /**
     * Check if path should bypass restrictions
     */
    public function shouldBypassPath($path) {
        foreach ($this->config['bypass_paths'] as $bypassPath) {
            if (strpos($path, $bypassPath) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has admin access
     */
    public function hasAdminAccess() {
        return isset($_SESSION[$this->sessionKey]) && $_SESSION[$this->sessionKey] === true;
    }
    
    /**
     * Grant admin access
     */
    public function grantAdminAccess() {
        $_SESSION[$this->sessionKey] = true;
    }
    
    /**
     * Revoke admin access
     */
    public function revokeAdminAccess() {
        unset($_SESSION[$this->sessionKey]);
    }
    
    /**
     * Verify admin password
     */
    public function verifyAdminPassword($password) {
        return password_verify($password, $this->config['admin_password']) || 
               $password === $this->config['admin_password']; // Fallback for plain text
    }
    
    /**
     * Main access control check
     */
    public function checkAccess() {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Allow bypass paths
        if ($this->shouldBypassPath($currentPath)) {
            return true;
        }
        
        // If maintenance mode is off, allow all
        if (!$this->config['maintenance_mode']) {
            return true;
        }
        
        // Check admin access
        if ($this->hasAdminAccess()) {
            return true;
        }
        
        // Check IP whitelist
        if ($this->isIPAllowed()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Redirect to maintenance page
     */
    public function redirectToMaintenance() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $maintenanceUrl = $protocol . '://' . $host . '/maintenance.html';
        
        if (!headers_sent()) {
            header('Location: ' . $maintenanceUrl, true, 302);
            exit;
        }
    }
    
    /**
     * Update configuration
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        $this->saveConfig();
        return true;
    }
    
    /**
     * Get current configuration
     */
    public function getConfig() {
        // Don't expose password in config
        $safeConfig = $this->config;
        unset($safeConfig['admin_password']);
        return $safeConfig;
    }
    
    /**
     * Add IP to whitelist
     */
    public function addAllowedIP($ip) {
        if (!in_array($ip, $this->config['allowed_ips'])) {
            $this->config['allowed_ips'][] = $ip;
            $this->saveConfig();
            return true;
        }
        return false;
    }
    
    /**
     * Remove IP from whitelist
     */
    public function removeAllowedIP($ip) {
        $key = array_search($ip, $this->config['allowed_ips']);
        if ($key !== false) {
            unset($this->config['allowed_ips'][$key]);
            $this->config['allowed_ips'] = array_values($this->config['allowed_ips']);
            $this->saveConfig();
            return true;
        }
        return false;
    }
    
    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenanceMode() {
        $this->config['maintenance_mode'] = !$this->config['maintenance_mode'];
        $this->saveConfig();
        return $this->config['maintenance_mode'];
    }
}

// Handle AJAX requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    
    $accessControl = new SiteAccessControl();
    $action = $_POST['action'];
    
    switch ($action) {
        case 'login':
            $password = $_POST['password'] ?? '';
            if ($accessControl->verifyAdminPassword($password)) {
                $accessControl->grantAdminAccess();
                echo json_encode(['success' => true, 'message' => 'Login successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
            }
            break;
            
        case 'logout':
            $accessControl->revokeAdminAccess();
            echo json_encode(['success' => true, 'message' => 'Logged out']);
            break;
            
        case 'toggle_maintenance':
            if ($accessControl->hasAdminAccess()) {
                $newStatus = $accessControl->toggleMaintenanceMode();
                echo json_encode(['success' => true, 'maintenance_mode' => $newStatus]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            }
            break;
            
        case 'add_ip':
            if ($accessControl->hasAdminAccess()) {
                $ip = $_POST['ip'] ?? '';
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $accessControl->addAllowedIP($ip);
                    echo json_encode(['success' => true, 'message' => 'IP added']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid IP']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            }
            break;
            
        case 'remove_ip':
            if ($accessControl->hasAdminAccess()) {
                $ip = $_POST['ip'] ?? '';
                $accessControl->removeAllowedIP($ip);
                echo json_encode(['success' => true, 'message' => 'IP removed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            }
            break;
            
        case 'get_config':
            if ($accessControl->hasAdminAccess()) {
                echo json_encode(['success' => true, 'config' => $accessControl->getConfig()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

// Handle regular page requests only if not in CLI mode
if (php_sapi_name() !== 'cli') {
    $accessControl = new SiteAccessControl();

    // Check if this is an admin page
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin-access.html') !== false) {
        // Admin access page - check if user is logged in
        if (!$accessControl->hasAdminAccess()) {
            if (!headers_sent()) {
                header('Location: /admin-login.html');
                exit;
            }
        }
        // Continue to show admin page
    } else {
        // Regular page - check access
        if (!$accessControl->checkAccess()) {
            $accessControl->redirectToMaintenance();
        }
    }
}
?>

<?php
/**
 * CSRF Protection Middleware
 * Validates CSRF tokens for state-changing operations
 * 
 * Security Issues Addressed:
 * - Prevents Cross-Site Request Forgery attacks
 * - Validates CSRF tokens for POST/PUT/DELETE requests
 * - Generates and validates secure tokens
 * - Provides automatic token regeneration
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/MiddlewareInterface.php';

class CsrfMiddleware implements MiddlewareInterface
{
    private $user;
    private $pdo;
    
    public function __construct($pdo = null)
    {
        // Use provided PDO or try to get from global scope
        if ($pdo !== null) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
        $this->user = $this->getCurrentUser();
    }
    
    /**
     * Handle CSRF validation
     */
    public function handle($params, $next)
    {
        
        // Only validate CSRF for state-changing operations
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return $next($params);
        }
        
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->handleAuthenticationFailure();
            return false;
        }
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->handleCsrfFailure();
            return false;
        }
        
        // Log successful CSRF validation
        $this->logCsrfSuccess();
        
        return $next($params);
    }
    
    /**
     * Get current user from session
     */
    private function getCurrentUser()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, role, department_id 
                FROM users 
                WHERE id = ?
            ");

            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user;

        } catch (Exception $e) {
            error_log("CsrfMiddleware getCurrentUser Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user is authenticated
     */
    private function isAuthenticated()
    {
        return $this->user !== null;
    }
    
    /**
     * Validate CSRF token
     */
    private function validateCsrfToken()
    {
        // Get token from POST data or GET parameters
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        // Check if session token exists
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Use hash_equals for secure comparison
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate new CSRF token
     */
    public static function generateToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Regenerate CSRF token
     */
    public static function regenerateToken()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Get CSRF token for forms
     */
    public static function getToken()
    {
        return self::generateToken();
    }
    
    /**
     * Handle authentication failure
     */
    private function handleAuthenticationFailure()
    {
        $this->logSecurityViolation('authentication_required');
        
        if (class_exists('Flash')) {
            $flash = new Flash();
            $flash->error('Please log in to perform this action.');
        }
        
        $this->redirect('/login?error=unauthorized');
    }
    
    /**
     * Handle CSRF failure
     */
    private function handleCsrfFailure()
    {
        $this->logSecurityViolation('csrf_token_invalid');
        
        if (class_exists('Flash')) {
            $flash = new Flash();
            $flash->error('Invalid CSRF token. Please try again.');
        }
        
        // Regenerate token to prevent repeated attacks
        self::regenerateToken();
        
        // Redirect back to referrer or dashboard
        $referrer = $_SERVER['HTTP_REFERER'] ?? '/event-reports/dashboard';

        if (strpos($referrer, '?') !== false) {
            $referrer .= '&error=csrf_invalid';
        } else {
            $referrer .= '?error=csrf_invalid';
        }

        $this->redirect($referrer);
    }
    
    /**
     * Log successful CSRF validation
     */
    private function logCsrfSuccess()
    {
        error_log("CSRF Validation Success: User {$this->user['username']} (Role: {$this->user['role']}) passed CSRF validation");
    }
    
    /**
     * Log security violation
     */
    private function logSecurityViolation($type)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $username = $this->user ? $this->user['username'] : 'anonymous';
        $userRole = $this->user ? $this->user['role'] : 'none';
        
        error_log("CSRF Security Violation: Type: $type, User: $username (Role: $userRole), IP: $ip, URL: $url");
    }
    
    /**
     * Redirect helper
     */
    private function redirect($url)
    {
        header("Location: " . $url);
        exit;
    }
}
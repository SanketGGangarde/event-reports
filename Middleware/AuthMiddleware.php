<?php
/**
 * Authentication Middleware
 * Validates user authentication and session integrity
 * 
 * Security Issues Addressed:
 * - Prevents unauthenticated access to protected routes
 * - Validates session integrity and user existence
 * - Provides secure redirect handling
 * - Logs authentication failures for security monitoring
 */
require_once __DIR__ . '/../core/Url.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/MiddlewareInterface.php';

class AuthMiddleware implements MiddlewareInterface
{
    private $pdo;
    private $user;
    
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
     * Handle authentication middleware
     */
    public function handle($params, $next)
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->handleAuthenticationFailure();
            return false;
        }
        
        // Validate session integrity
        if (!$this->validateSessionIntegrity()) {
            $this->handleSessionCompromise();
            return false;
        }
        
        // Log successful authentication
        $this->logAuthenticationSuccess();
        
        return $next($params);
    }
    
    /**
     * Get current user from session
     */
    private function getCurrentUser()
    {
        if (!isset($_SESSION['user_id'])) {
            error_log("AuthMiddleware: No user_id in session");
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, role, department_id, profile_image 
                FROM users 
                WHERE id = ?
            ");

            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                error_log("AuthMiddleware: User not found in database: " . $_SESSION['user_id']);
            }

            return $user;

        } catch (Exception $e) {
            error_log("AuthMiddleware getCurrentUser Error: " . $e->getMessage());
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
     * Validate session integrity
     */
    private function validateSessionIntegrity()
    {
        // Check if session user still exists in database
        if (!$this->user) {
            return false;
        }
        
        // Additional session validation can be added here
        // e.g., IP validation, session timeout, etc.
        
        return true;
    }
    
    /**
     * Handle authentication failure
     */
    private function handleAuthenticationFailure()
    {
        // Log authentication failure
        $this->logAuthenticationFailure('unauthorized_access');
        
        // Set flash message
        if (class_exists('Flash')) {
            $flash = new Flash();
            $flash->error('Please log in to access this page.');
        }
        
        // Redirect to login
        $this->redirect('/login?error=unauthorized');
    }
    
    /**
     * Handle potential session compromise
     */
    private function handleSessionCompromise()
    {
        // Log security incident
        $this->logSecurityIncident('session_compromise');
        
        // Destroy session
        session_destroy();
        
        // Redirect to login
        $this->redirect('/login?error=session_invalid');
    }
    
    /**
     * Log successful authentication
     */
    private function logAuthenticationSuccess()
    {
        if ($this->user) {
            error_log("Auth Success: User {$this->user['username']} (ID: {$this->user['id']}) authenticated successfully");
        }
    }
    
    /**
     * Log authentication failure
     */
    private function logAuthenticationFailure($reason)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        error_log("Auth Failure: IP: $ip, User-Agent: $userAgent, URL: $url, Reason: $reason");
    }
    
    /**
     * Log security incident
     */
    private function logSecurityIncident($type)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        error_log("Security Incident: Type: $type, IP: $ip, User-Agent: $userAgent, URL: $url");
    }
    
    /**
     * Redirect helper
     */
    private function redirect($url)
    {
        // Use Url helper if available, otherwise use relative path
        if (class_exists('Url')) {
            $fullUrl = Url::getBaseUrl() . $url;
        } else {
            $fullUrl = $url;
        }
        header("Location: " . $fullUrl);
        exit;
    }
}
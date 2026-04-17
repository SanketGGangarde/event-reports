<?php
/**
 * UUID Validation Middleware
 * Validates UUID format for resource IDs to prevent ID enumeration and injection
 * 
 * Security Issues Addressed:
 * - Prevents ID enumeration attacks
 * - Validates UUID format for security
 * - Prevents injection attacks through malformed IDs
 * - Ensures consistent UUID usage
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/MiddlewareInterface.php';

class UuidValidationMiddleware implements MiddlewareInterface
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
     * Handle UUID validation
     */
    public function handle($params, $next)
    {
        // Validate UUID parameters
        if (!$this->validateUuidParameters($params)) {
            $this->handleUuidValidationFailure();
            return false;
        }
        
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
            error_log("UuidValidationMiddleware getCurrentUser Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate UUID parameters
     */
    private function validateUuidParameters($params)
    {
        // Common UUID parameter names
        $uuidParams = ['id', 'checklist_id', 'user_id', 'department_id'];
        
        foreach ($uuidParams as $param) {
            if (isset($params[$param])) {
                $value = $params[$param];
                if (!$this->isValidUuid($value)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate UUID format
     */
    private function isValidUuid($uuid)
    {
        // UUID v4 format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        // where x is any hexadecimal digit and y is one of 8, 9, A or B
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
    
    /**
     * Handle UUID validation failure
     */
    private function handleUuidValidationFailure()
    {
        $this->logSecurityViolation('invalid_uuid_format');
        
        // Return 404 to prevent revealing valid UUID format
        http_response_code(404);
        require_once __DIR__ . '/../views/errors/404.php';
        exit;
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
        
        error_log("UUID Security Violation: Type: $type, User: $username (Role: $userRole), IP: $ip, URL: $url");
    }
}

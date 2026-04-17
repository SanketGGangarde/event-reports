<?php
/**
 * Audit Log Middleware
 * Logs all security-sensitive operations for monitoring and compliance
 * 
 * Security Issues Addressed:
 * - Provides security monitoring and incident investigation
 * - Logs all document access and modifications
 * - Tracks user activities for compliance
 * - Enables security audit trails
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/MiddlewareInterface.php';

class AuditLogMiddleware implements MiddlewareInterface
{
    private $pdo;
    private $user;
    private $operation;
    private $resourceType;
    
    /**
     * Constructor
     * 
     * @param string $operation Type of operation (view, create, update, delete, download)
     * @param string $resourceType Type of resource (document, file, user, department)
     */
    public function __construct($operation = 'view', $resourceType = 'unknown')
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->user = $this->getCurrentUser();
        $this->operation = $operation;
        $this->resourceType = $resourceType;
    }
    
    /**
     * Handle audit logging
     */
    public function handle($params, $next)
    {
        // Log the operation
        $this->logOperation($params);
        
        // Continue with the request
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
            error_log("AuditLogMiddleware getCurrentUser Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log the operation
     */
    private function logOperation($params)
    {
        try {
            // Get request details
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
            
            // Get user details
            $userId = $this->user ? $this->user['id'] : null;
            $username = $this->user ? $this->user['username'] : 'anonymous';
            $userRole = $this->user ? $this->user['role'] : 'none';
            $departmentId = $this->user ? $this->user['department_id'] : null;
            
            // Get resource ID
            $resourceId = $this->extractResourceId($params);
            
            // Create log entry
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs (
                    user_id,
                    username,
                    user_role,
                    department_id,
                    operation,
                    resource_type,
                    resource_id,
                    ip_address,
                    user_agent,
                    url,
                    request_method,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $username,
                $userRole,
                $departmentId,
                $this->operation,
                $this->resourceType,
                $resourceId,
                $ip,
                $userAgent,
                $url,
                $method
            ]);
            
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("AuditLogMiddleware Error: " . $e->getMessage());
        }
    }
    
    /**
     * Extract resource ID from parameters
     */
    private function extractResourceId($params)
    {
        // Check common parameter names
        if (isset($params['id'])) {
            return $params['id'];
        }
        
        if (isset($params['checklist_id'])) {
            return $params['checklist_id'];
        }
        
        if (isset($params['user_id'])) {
            return $params['user_id'];
        }
        
        if (isset($params['department_id'])) {
            return $params['department_id'];
        }
        
        return null;
    }
}
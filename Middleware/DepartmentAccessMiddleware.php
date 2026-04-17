<?php
/**
 * Department Access Middleware
 * Validates department-level access for HODs and department-specific operations
 * 
 * Security Issues Addressed:
 * - Prevents cross-department access by HODs
 * - Validates department ownership for operations
 * - Ensures HODs can only manage their own department
 * - Provides department filtering capabilities
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/MiddlewareInterface.php';

class DepartmentAccessMiddleware implements MiddlewareInterface
{
    private $pdo;
    private $user;
    private $operation;
    
    /**
     * Constructor
     * 
     * @param string $operation Operation type (view, create, update, delete)
     */
    public function __construct($operation = 'view')
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->user = $this->getCurrentUser();
        $this->operation = $operation;
    }
    
    /**
     * Handle department access validation
     */
    public function handle($params, $next)
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->handleAuthenticationFailure();
            return false;
        }
        
        // Principal can access all departments
        if ($this->user['role'] === 'principal') {
            return $next($params);
        }
        
        // HOD can only access their own department
        if ($this->user['role'] === 'hod') {
            if (!$this->validateHodDepartmentAccess($params)) {
                $this->handleAuthorizationFailure('department_access_denied');
                return false;
            }
            return $next($params);
        }
        
        // Coordinators cannot access department management
        if ($this->user['role'] === 'coordinator') {
            $this->handleAuthorizationFailure('insufficient_privileges');
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
            // Get database connection from global scope
            global $pdo;
            
            $stmt = $pdo->prepare("
                SELECT id, username, role, department_id 
                FROM users 
                WHERE id = ?
            ");

            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user;

        } catch (Exception $e) {
            error_log("DepartmentAccessMiddleware getCurrentUser Error: " . $e->getMessage());
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
     * Validate HOD department access
     */
    private function validateHodDepartmentAccess($params)
    {
        if (!$this->user || $this->user['role'] !== 'hod') {
            return false;
        }
        
        // For operations that require department ID parameter
        if (isset($params['department_id'])) {
            $requestedDepartmentId = $params['department_id'];
            return $this->user['department_id'] === $requestedDepartmentId;
        }
        
        // For operations that work with the user's own department
        return $this->user['department_id'] !== null;
    }
    
    /**
     * Handle authentication failure
     */
    private function handleAuthenticationFailure()
    {
        $this->logSecurityViolation('authentication_required');
        
        if (class_exists('Flash')) {
            $flash = new Flash();
            $flash->error('Please log in to access department management.');
        }
        
        $this->redirect('/login?error=unauthorized');
    }
    
    /**
     * Handle authorization failure
     */
    private function handleAuthorizationFailure($reason)
    {
        $this->logSecurityViolation($reason);
        
        if (class_exists('Flash')) {
            $flash = new Flash();
            $flash->error('You do not have permission to access this department.');
        }
        
        // Redirect based on user role
        if ($this->user['role'] === 'hod') {
            $this->redirect('/manage/departments');
        } else {
            $this->redirect('/dashboard');
        }
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
        $userDepartment = $this->user ? $this->user['department_id'] : 'none';
        
        error_log("Department Security Violation: Type: $type, User: $username (Role: $userRole, Dept: $userDepartment), IP: $ip, URL: $url");
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
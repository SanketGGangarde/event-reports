<?php
/**
 * Role Middleware
 * Validates user roles and permissions for route access
 * 
 * Security Issues Addressed:
 * - Prevents vertical privilege escalation
 * - Enforces role-based access control (RBAC)
 * - Validates required roles for specific operations
 * - Provides granular role checking capabilities
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/MiddlewareInterface.php';

class RoleMiddleware implements MiddlewareInterface
{
    private $pdo;
    private $user;
    private $requiredRoles;
    
    /**
     * Constructor
     * 
     * @param array|string $roles Required roles for access
     */
    public function __construct($roles)
    {
        // Database connection will be set when middleware is called
        $this->user = $this->getCurrentUser();
        $this->requiredRoles = is_array($roles) ? $roles : [$roles];
    }
    
    /**
     * Handle role validation middleware
     */
    public function handle($params, $next)
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->handleAuthenticationFailure();
            return false;
        }
        
        // Validate user role
        if (!$this->hasRequiredRole()) {
            $this->handleAuthorizationFailure();
            return false;
        }
        
        // Log successful role validation
        $this->logRoleValidationSuccess();
        
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
            error_log("RoleMiddleware getCurrentUser Error: " . $e->getMessage());
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
     * Check if user has required role
     */
    private function hasRequiredRole()
    {
        if (!$this->user) {
            return false;
        }
        
        $userRole = $this->user['role'];
        
        // Principal can access everything
        if ($userRole === 'principal') {
            return true;
        }
        
        // Check if user role is in required roles
        return in_array($userRole, $this->requiredRoles);
    }
    
    /**
     * Handle authentication failure
     */
    private function handleAuthenticationFailure()
    {
        // Log authentication failure
        $this->logSecurityViolation('authentication_required');
        
        // Set flash message
        if (class_exists('Flash')) {
            $flash = new Flash();
            $flash->error('Please log in to access this page.');
        }
        
        // Redirect to login
        $this->redirect('/login?error=unauthorized');
    }
    
    /**
     * Handle authorization failure
     */
    private function handleAuthorizationFailure()
    {
        // Log authorization failure
        $this->logSecurityViolation('insufficient_privileges');
        
        // Set flash message
        if (class_exists('Flash')) {
            $flash = new Flash();
            $flash->error('You do not have permission to access this page.');
        }
        
        // Redirect based on user role
        $this->redirectBasedOnRole();
    }
    
    /**
     * Redirect user based on their role
     */
    private function redirectBasedOnRole()
    {
        if ($this->user) {
            $role = $this->user['role'];
            switch ($role) {
                case 'principal':
                    $this->redirect('/dashboard');
                    break;
                case 'hod':
                    $this->redirect('/manage/departments');
                    break;
                case 'coordinator':
                    $this->redirect('/dashboard');
                    break;
                default:
                    $this->redirect('/login');
            }
        } else {
            $this->redirect('/login');
        }
    }
    
    /**
     * Log successful role validation
     */
    private function logRoleValidationSuccess()
    {
        if ($this->user) {
            $requiredRoles = implode(', ', $this->requiredRoles);
            error_log("Role Validation Success: User {$this->user['username']} (Role: {$this->user['role']}) has required roles: $requiredRoles");
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
        $requiredRoles = implode(', ', $this->requiredRoles);
        
        error_log("Security Violation: Type: $type, User: $username (Role: $userRole), Required Roles: $requiredRoles, IP: $ip, URL: $url");
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
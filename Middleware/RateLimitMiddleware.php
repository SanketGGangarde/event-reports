<?php
/**
 * Rate Limit Middleware
 * Prevents abuse and brute force attacks through request throttling
 * 
 * Security Issues Addressed:
 * - Prevents brute force attacks
 * - Limits request frequency to prevent abuse
 * - Protects against DoS attacks
 * - Provides configurable rate limiting
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/MiddlewareInterface.php';

class RateLimitMiddleware implements MiddlewareInterface
{
    private $pdo;
    private $maxRequests;
    private $timeWindow;
    private $key;
    
    /**
     * Constructor
     * 
     * @param int $maxRequests Maximum number of requests allowed
     * @param int $timeWindow Time window in seconds
     * @param string $key Rate limiting key (ip, user, route)
     */
    public function __construct($maxRequests = 60, $timeWindow = 60, $key = 'ip')
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        $this->key = $key;
    }
    
    /**
     * Handle rate limiting
     */
    public function handle($params, $next)
    {
        // Check if rate limit is exceeded
        if ($this->isRateLimited()) {
            $this->handleRateLimitExceeded();
            return false;
        }
        
        // Log the request
        $this->logRequest();
        
        return $next($params);
    }
    
    /**
     * Check if rate limit is exceeded
     */
    private function isRateLimited()
    {
        try {
            $key = $this->getRateLimitKey();
            $now = time();
            $windowStart = $now - $this->timeWindow;
            
            // Count requests in the current window
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM rate_limits 
                WHERE rate_key = ? AND created_at > FROM_UNIXTIME(?)
            ");
            $stmt->execute([$key, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] >= $this->maxRequests;
            
        } catch (Exception $e) {
            error_log("RateLimitMiddleware isRateLimited Error: " . $e->getMessage());
            return false; // Fail open on error
        }
    }
    
    /**
     * Log the request
     */
    private function logRequest()
    {
        try {
            $key = $this->getRateLimitKey();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
            
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (
                    rate_key,
                    ip_address,
                    user_agent,
                    url,
                    request_method,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$key, $ip, $userAgent, $url, $method]);
            
        } catch (Exception $e) {
            error_log("RateLimitMiddleware logRequest Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get rate limiting key
     */
    private function getRateLimitKey()
    {
        switch ($this->key) {
            case 'user':
                return $this->getUserKey();
            case 'route':
                return $this->getRouteKey();
            case 'ip':
            default:
                return $this->getIpKey();
        }
    }
    
    /**
     * Get IP-based rate limiting key
     */
    private function getIpKey()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return "ip:$ip";
    }
    
    /**
     * Get user-based rate limiting key
     */
    private function getUserKey()
    {
        if (isset($_SESSION['user_id'])) {
            return "user:{$_SESSION['user_id']}";
        }
        return $this->getIpKey(); // Fall back to IP if not authenticated
    }
    
    /**
     * Get route-based rate limiting key
     */
    private function getRouteKey()
    {
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        return "route:{$method}:{$url}";
    }
    
    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded()
    {
        $this->logSecurityViolation('rate_limit_exceeded');
        
        http_response_code(429);
        header('Retry-After: ' . $this->timeWindow);
        
        echo json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $this->timeWindow
        ]);
        
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
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $key = $this->getRateLimitKey();
        
        error_log("Rate Limit Violation: Type: $type, Key: $key, IP: $ip, Method: $method, URL: $url");
    }
}
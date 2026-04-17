<?php
/**
 * Base Controller for Event Management System
 * Provides common functionality for all controllers
 */

require_once __DIR__ . '/../init/session.php';
require_once __DIR__ . '/../init/_dbconnect.php';

class BaseController {
    protected $pdo;
    protected $conn;
    protected $user;
    protected $flash;

    public function __construct($pdo = null) {
        // DEBUG: Log BaseController constructor call
        error_log("BaseController::__construct() called");
        error_log("Session ID in BaseController: " . session_id());
        error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
        error_log("Session role: " . ($_SESSION['role'] ?? 'NOT SET'));
        
        // Database connections - accept PDO from router or use global
        if ($pdo !== null) {
            $this->pdo = $pdo;
        } else {
            global $pdo, $conn;
            $this->pdo = $pdo;
            $this->conn = $conn;
        }
        
        // User session data
        $this->user = $this->getCurrentUser();
        
        // DEBUG: Log user data
        error_log("BaseController user object: " . print_r($this->user, true));
    }
     
    protected function validateOrRedirect($validation, $path){
        if (!$validation['status']) {
            $_SESSION['errors'] = $validation['errors'];
            $_SESSION['old']    = $_POST;

            header('Location: ' . Url::to($path));
            exit;
        }

        return $validation['data'] ?? [];
    }
    /**
     * Get current user from session
     */
    protected function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
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

            return $user;

        } catch (Exception $e) {
            error_log("BaseController getCurrentUser Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated() {
        return $this->user !== null;
    }

    /**
     * Require principal role - redirects to login if not principal
     */
    protected function requirePrincipal() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
            $this->redirect('/login');
        }
    }

    /**
     * Check user role
     */
    protected function hasRole($role) {
        return $this->user && $this->user['role'] === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    protected function hasAnyRole($roles) {
        if (!$this->user) return false;
        return in_array($this->user['role'], (array)$roles);
    }

    /**
     * Redirect to URL
     */
    protected function redirect($url) {
        // DEBUG: Log all redirects
        error_log("=== REDIRECT CALLED ===");
        error_log("Redirecting to: " . $url);
        error_log("Session ID: " . session_id());
        error_log("Session data: " . print_r($_SESSION, true));
        
        header("Location: " . $url);
        exit;
    }

    /**
     * Redirect with errors and old input
     */
    protected function redirectWithErrors($url, $errors, $old = []) {
        $_SESSION['errors'] = $errors;
        if (!empty($old)) {
            $_SESSION['old'] = $old;
        }
        header("Location: " . $url);
        exit;
    }

    /**
     * Get base URL
     */
    protected function getBaseUrl() {
        return '';
    }

    /**
     * Render a view with data
     */
    protected function render($view, $data = []) {
        extract($data);
        
        // Set base URL for views
        $baseUrl = $this->getBaseUrl();
        
        // Include header
        require_once __DIR__ . '/../views/layouts/header.php';
        
        // Include main view
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "<h1>View not found: $view</h1>";
        }
        
        // Include footer
        require_once __DIR__ . '/../views/includes/footer.php';
    }

    /**
     * Render a view without layout (for AJAX or API responses)
     */
    protected function renderPartial($view, $data = []) {
        extract($data);
        
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "<h1>View not found: $view</h1>";
        }
    }

    /**
     * Initialize Flash class if not already initialized
     */
    private function initFlash() {
        if (!$this->flash) {
            $this->flash = new Flash();
        }
    }

    /**
     * Get flash messages
     */
    protected function getFlashMessages() {
        $this->initFlash();
        return $this->flash->getMessages();
    }

    /**
     * Set success message
     */
    protected function success($message) {
        $this->initFlash();
        $this->flash->success($message);
    }

    /**
     * Set error message
     */
    protected function error($message) {
        $this->initFlash();
        $this->flash->error($message);
    }

    /**
     * Set warning message
     */
    protected function warning($message) {
        $this->initFlash();
        $this->flash->warning($message);
    }

    /**
     * Set info message
     */
    protected function info($message) {
        $this->initFlash();
        $this->flash->info($message);
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrfToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
            if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                $this->error('Invalid CSRF token');
                return false;
            }
        }
        return true;
    }

    /**
     * Generate CSRF token
     */
    protected function getCsrfToken() {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * JSON response helper
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * 404 response
     */
    protected function notFound() {
        http_response_code(404);
        $this->render('errors/404');
        exit;
    }

    /**
     * 403 Forbidden response
     */
    protected function forbidden() {
        http_response_code(403);
        $this->render('errors/403');
        exit;
    }

    /**
     * Method not allowed response
     */
    protected function methodNotAllowed() {
        http_response_code(405);
        header('Allow: GET, POST, PUT, DELETE');
        $this->render('errors/405');
        exit;
    }
}

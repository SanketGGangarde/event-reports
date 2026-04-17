<?php
/**
 * URL Helper for Event Management System
 * Generates clean URLs based on route patterns
 */

/*
    This Url class is a URL generator and helper.
It  builds correct, clean, consistent links for your whole website.
*/

class Url {
    private static $baseUrl = '/event-reports';
    private static $routes = [];

    /**
     * Set the base URL
     */
    public static function setBaseUrl($url) {
        self::$baseUrl = $url;
    }

    /**
     * Get the base URL
     */
    public static function getBaseUrl() {
        return self::$baseUrl;
    }

    /**
     * Generate a URL for a named route
     */
    public static function to($routeName, $params = []) {
        // Define route patterns
        $patterns = [
            'home' => '/',
            'login' => '/login',
            'logout' => '/logout',
            'signup' => '/signup',
            'forgot-password' => '/forgot-password',
            'reset-password' => '/reset-password/{token}',
            'dashboard' => '/dashboard',
            'profile' => '/profile',
            'manage-departments' => '/manage/departments',
            'manage-hods' => '/manage/hods',
            'manage-coordinators' => '/manage/coordinators',
            'manage-events' => '/manage/events',
            'create-checklist' => '/documents/checklist',
            'create-event-report' => '/documents/event-report',
            'create-notice' => '/documents/notice',
            'create-invitation' => '/documents/invitation',
            'create-appreciation' => '/documents/appreciation',
            'view-document' => '/documents/view/{type}/{id}',
            'download-report' => '/documents/download/{id}',
            'download-word' => '/documents/download-word/{id}',
        ];

        if (!isset($patterns[$routeName])) {
            // For custom routes, ensure proper URL construction
            $url = ltrim($routeName, '/');
            return self::$baseUrl . '/' . $url;
        }

        $url = $patterns[$routeName];

        // Replace parameters
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        // Ensure proper URL construction without double slashes
        $baseUrl = rtrim(self::$baseUrl, '/');
        $url = ltrim($url, '/');
        
        return $baseUrl . '/' . $url;
    }

    /**
     * Generate current URL
     */
    public static function current() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return self::$baseUrl . $uri;
    }

    /**
     * Generate absolute URL
     */
    public static function absolute($path = '') {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . self::$baseUrl . $path;
    }

    /**
     * Check if current URL matches a pattern
     */
    public static function isActive($routeName, $params = []) {
        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $targetUrl = self::to($routeName, $params);
        
        // Remove base URL from current URL for comparison
        $baseUrl = self::getBaseUrl();
        if (strpos($currentUrl, $baseUrl) === 0) {
            $currentUrl = substr($currentUrl, strlen($baseUrl));
        }

        // Remove base URL from target URL
        if (strpos($targetUrl, $baseUrl) === 0) {
            $targetUrl = substr($targetUrl, strlen($baseUrl));
        }

        return $currentUrl === $targetUrl;
    }

    /**
     * Redirect to a named route
     */
    public static function redirect($routeName, $params = []) {
        $url = self::to($routeName, $params);
        header("Location: $url");
        exit;
    }

    /**
     * Generate CSRF token URL parameter
     */
    public static function withCsrf($url) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'csrf_token=' . urlencode($_SESSION['csrf_token'] ?? '');
    }

    /**
     * Generate POST form action URL with CSRF token
     */
    public static function formAction($routeName, $params = []) {
        return self::to($routeName, $params);
    }

    /**
     * Generate AJAX URL
     */
    public static function ajax($routeName, $params = []) {
        return self::to($routeName, $params);
    }
}
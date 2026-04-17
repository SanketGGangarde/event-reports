<?php
/**
 * CSRF Helper Functions
 * Provides easy-to-use functions for CSRF protection in forms
 */

require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

/**
 * Generate and return CSRF token
 */
function csrf_token() {
    return CsrfMiddleware::getToken();
}

/**
 * Generate CSRF token input field for forms
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Generate CSRF meta tag for AJAX requests
 */
function csrf_meta_tag() {
    return '<meta name="csrf-token" content="' . csrf_token() . '">';
}

/**
 * Validate CSRF token
 */
function csrf_validate() {
    return CsrfMiddleware::validateToken();
}

/**
 * Regenerate CSRF token
 */
function csrf_regenerate() {
    return CsrfMiddleware::regenerateToken();
}

/**
 * Check if request has valid CSRF token
 */
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_validate()) {
            return false;
        }
    }
    return true;
}

/**
 * CSRF protection for forms
 * Use this at the beginning of POST handlers
 */
function csrf_protect() {
    if (!csrf_check()) {
        // Log security violation
        error_log("CSRF Protection: Invalid token detected");
        
        // Return 403 Forbidden
        http_response_code(403);
        echo json_encode([
            'error' => 'CSRF Token Invalid',
            'message' => 'Please refresh the page and try again.'
        ]);
        exit;
    }
}

/**
 * Get CSRF token for JavaScript/AJAX
 */
function get_csrf_token_for_js() {
    return csrf_token();
}
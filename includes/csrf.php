<?php
/**
 * CSRF Protection
 * Prevents Cross-Site Request Forgery attacks
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token for forms
 */
function getCSRFTokenField() {
    $token = generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='{$token}'>";
}

/**
 * Verify CSRF from request
 */
function checkCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        jsonResponse(false, 'Invalid CSRF token');
        exit;
    }
}
?>
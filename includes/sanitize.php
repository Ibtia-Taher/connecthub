<?php
/**
 * Input Sanitization Helpers
 * Prevent XSS and SQL injection
 */

/**
 * Sanitize string input
 */
function sanitizeString($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize integer
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize URL
 */
function sanitizeURL($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * Prevent SQL injection (use with prepared statements)
 */
function escapeSQL($conn, $input) {
    return $conn->real_escape_string($input);
}

/**
 * Rate limiting check
 */
function checkRateLimit($action, $limit = 10, $period = 60) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    
    // Clean old entries
    if (isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            function($timestamp) use ($now, $period) {
                return ($now - $timestamp) < $period;
            }
        );
    } else {
        $_SESSION['rate_limit'][$key] = [];
    }
    
    // Check limit
    if (count($_SESSION['rate_limit'][$key]) >= $limit) {
        return false;
    }
    
    // Add current request
    $_SESSION['rate_limit'][$key][] = $now;
    
    return true;
}
?>
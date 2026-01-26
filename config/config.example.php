<?php
/**
 * Global Configuration Example
 * COPY this file to config.php and update APP_URL
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');

define('APP_NAME', 'ConnectHub');
define('APP_URL', 'http://localhost/connecthub'); // UPDATE THIS
define('APP_VERSION', '1.0.0');

define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

define('POSTS_PER_PAGE', 10);
define('COMMENTS_PER_PAGE', 20);

define('OTP_EXPIRY_MINUTES', 10);
define('OTP_LENGTH', 6);

define('SESSION_EXPIRY_HOURS', 24);
define('PASSWORD_MIN_LENGTH', 8);

function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}
?>
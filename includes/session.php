<?php
/**
 * Session Management
 * Handles user sessions and authentication checks
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Create user session after successful login
 * @param array $userData
 */
function createUserSession($userData) {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    
    // Store user data in session
    $_SESSION['user_id'] = $userData['user_id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['is_verified'] = $userData['is_verified'];
    $_SESSION['profile_pic'] = $userData['profile_pic'];
    $_SESSION['login_time'] = time();
    
    // Store session in database for tracking
    storeSessionInDB($userData['user_id']);
}

/**
 * Store session in database
 * @param int $userId
 */
function storeSessionInDB($userId) {
    $conn = getDBConnection();
    
    // Generate unique token
    $token = bin2hex(random_bytes(32));
    
    // Set expiry time
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . SESSION_EXPIRY_HOURS . ' hours'));
    
    // Get client info
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $query = "INSERT INTO sessions (user_id, token, expires_at, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?)";
    
    executeQuery($conn, $query, "issss", [$userId, $token, $expiresAt, $ipAddress, $userAgent]);
    
    $_SESSION['session_token'] = $token;
    
    closeDBConnection($conn);
}

/**
 * Check if user is authenticated
 * @return bool
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    // Check if session has expired (24 hours)
    if (isset($_SESSION['login_time'])) {
        $sessionAge = time() - $_SESSION['login_time'];
        $maxAge = SESSION_EXPIRY_HOURS * 3600; // Convert hours to seconds
        
        if ($sessionAge > $maxAge) {
            destroyUserSession();
            redirect(APP_URL . '/pages/login.php?error=session_expired');
            exit;
        }
    }
}

/**
 * Destroy user session (logout)
 */
function destroyUserSession() {
    // Remove session from database
    if (isset($_SESSION['session_token'])) {
        $conn = getDBConnection();
        $query = "DELETE FROM sessions WHERE token = ?";
        executeQuery($conn, $query, "s", [$_SESSION['session_token']]);
        closeDBConnection($conn);
    }
    
    // Clear session variables
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Get current logged-in user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'is_verified' => $_SESSION['is_verified'],
        'profile_pic' => $_SESSION['profile_pic']
    ];
}
?>
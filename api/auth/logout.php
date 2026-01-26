<?php
/**
 * User Logout API
 * Destroys user session
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';

// Destroy session
destroyUserSession();

// Redirect directly to login page (not JSON response)
header('Location: ' . APP_URL . '/pages/login.php?logged_out=1');
exit;
?>
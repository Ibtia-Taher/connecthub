<?php
/**
 * Get User Profile API
 * Fetches user profile data
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get user ID from query parameter
$userId = $_GET['user_id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    jsonResponse(false, 'Invalid user ID');
}

$userModel = new User();
$user = $userModel->getUserById($userId);

if (!$user) {
    jsonResponse(false, 'User not found');
}

// Don't send sensitive data
unset($user['password_hash']);

jsonResponse(true, 'Profile fetched successfully', ['user' => $user]);
?>
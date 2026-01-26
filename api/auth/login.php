<?php
/**
 * User Login API
 * Authenticates user and creates session
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../includes/session.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['username']) || empty($input['password'])) {
    jsonResponse(false, 'Username and password are required');
}

$userModel = new User();

// Verify credentials
$user = $userModel->verifyCredentials($input['username'], $input['password']);

if (!$user) {
    jsonResponse(false, 'Invalid username or password');
}

// Check if email is verified
if (!$user['is_verified']) {
    jsonResponse(false, 'Please verify your email before logging in', [
        'requires_verification' => true,
        'user_id' => $user['user_id']
    ]);
}

// Create session
createUserSession($user);

jsonResponse(true, 'Login successful!', [
    'user' => [
        'username' => $user['username'],
        'email' => $user['email'],
        'profile_pic' => $user['profile_pic']
    ],
    'redirect' => APP_URL . '/pages/feed.php'
]);
?>
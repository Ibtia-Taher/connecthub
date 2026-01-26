<?php
require_once __DIR__ . '/../../config/debug.php';
/**
 * User Registration API
 * Handles new user account creation
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../includes/email.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$requiredFields = ['username', 'email', 'password', 'phone', 'dob'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, "Field '$field' is required");
    }
}

// Validate username (alphanumeric, 3-20 characters)
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $input['username'])) {
    jsonResponse(false, 'Username must be 3-20 characters (letters, numbers, underscore only)');
}

// Validate email
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
}

// Validate password strength
if (strlen($input['password']) < PASSWORD_MIN_LENGTH) {
    jsonResponse(false, 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
}

// Validate phone (simple validation)
if (!preg_match('/^[0-9+\-\s()]{10,20}$/', $input['phone'])) {
    jsonResponse(false, 'Invalid phone number format');
}

// Validate date of birth (must be 13+ years old)
$dob = new DateTime($input['dob']);
$today = new DateTime();
$age = $today->diff($dob)->y;

if ($age < 13) {
    jsonResponse(false, 'You must be at least 13 years old to register');
}

// Check if username or email already exists
$userModel = new User();

if ($userModel->usernameExists($input['username'])) {
    jsonResponse(false, 'Username already taken');
}

if ($userModel->emailExists($input['email'])) {
    jsonResponse(false, 'Email already registered');
}

// Create user account
$userId = $userModel->createUser([
    'username' => $input['username'],
    'email' => $input['email'],
    'phone' => $input['phone'],
    'password' => $input['password'],
    'dob' => $input['dob']
]);

if (!$userId) {
    // Log the error for debugging
    error_log("Failed to create user account for email: " . $input['email']);
    
    // Check if it's a duplicate entry issue
    if ($userModel->emailExists($input['email'])) {
        jsonResponse(false, 'Email already exists. Did you mean to login?');
    }
    if ($userModel->usernameExists($input['username'])) {
        jsonResponse(false, 'Username already taken. Please try another.');
    }
    
    jsonResponse(false, 'Failed to create account. Please check logs/php-errors.log for details.');
}

// Generate and send OTP
$otpCode = generateOTP();
$userModel->storeOTP($userId, $otpCode, 'email');
sendOTPEmail($input['email'], $input['username'], $otpCode);

// Return success with user ID (for OTP verification step)
jsonResponse(true, 'Account created successfully! Please check your email for verification code.', [
    'user_id' => $userId,
    'email' => $input['email']
]);
?>
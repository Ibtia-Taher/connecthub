<?php
/**
 * OTP Verification API
 * Verifies email OTP and marks account as verified
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['user_id']) || empty($input['otp_code'])) {
    jsonResponse(false, 'User ID and OTP code are required');
}

$userModel = new User();

// Verify OTP
if ($userModel->verifyOTP($input['user_id'], $input['otp_code'])) {
    // Mark user as verified
    $userModel->markAsVerified($input['user_id']);
    
    jsonResponse(true, 'Email verified successfully! You can now log in.', [
        'verified' => true
    ]);
} else {
    jsonResponse(false, 'Invalid or expired OTP code');
}
?>
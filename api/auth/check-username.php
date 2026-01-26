<?php
/**
 * Real-time Username Availability Check
 * Returns whether username is available
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get username from query parameter
$username = $_GET['username'] ?? '';

// Validate username format
if (empty($username)) {
    jsonResponse(false, 'Username is required');
}

if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    jsonResponse(false, 'Invalid username format');
}

// Check availability
$userModel = new User();
$exists = $userModel->usernameExists($username);

if ($exists) {
    jsonResponse(false, 'Username already taken', ['available' => false]);
} else {
    jsonResponse(true, 'Username is available', ['available' => true]);
}
?>
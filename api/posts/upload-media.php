<?php
/**
 * Upload Post Media API
 * Handles image uploads for posts
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/image-helper.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Check if file was uploaded
if (!isset($_FILES['media'])) {
    jsonResponse(false, 'No file uploaded');
}

$userId = getCurrentUserId();
$file = $_FILES['media'];

// Validate image
$validation = validateImage($file);
if (!$validation['success']) {
    jsonResponse(false, $validation['message']);
}

// Generate unique filename
$timestamp = time();
$newFilename = "post_{$userId}_{$timestamp}.jpg";
$uploadPath = UPLOAD_DIR . $newFilename;

// Resize and save image (max 1200x1200 for posts)
if (!resizeImage($file['tmp_name'], $uploadPath, 1200, 1200)) {
    jsonResponse(false, 'Failed to process image');
}

jsonResponse(true, 'Image uploaded successfully', [
    'filename' => $newFilename,
    'url' => APP_URL . '/assets/images/uploads/' . $newFilename
]);
?>

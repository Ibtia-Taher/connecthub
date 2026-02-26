<?php
/**
 * Profile Picture Upload API
 * Handles avatar upload and processing
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/image-helper.php';
require_once __DIR__ . '/../../config/database.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in to upload a profile picture');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Check if file was uploaded
if (!isset($_FILES['avatar'])) {
    jsonResponse(false, 'No file uploaded');
}

$userId = getCurrentUserId();
$file = $_FILES['avatar'];

// Validate image
$validation = validateImage($file);
if (!$validation['success']) {
    jsonResponse(false, $validation['message']);
}

// Generate unique filename
$newFilename = generateUniqueFilename($file['name'], $userId);
$uploadPath = UPLOAD_DIR . $newFilename;

// Resize and save image
if (!resizeImage($file['tmp_name'], $uploadPath, 400, 400)) {
    jsonResponse(false, 'Failed to process image');
}

// Get user's current profile picture
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Delete old avatar
if ($user && $user['profile_pic']) {
    deleteOldAvatar($user['profile_pic']);
}

// Update database
$stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
$stmt->bind_param("si", $newFilename, $userId);

if (!$stmt->execute()) {
    // If database update fails, delete the uploaded file
    unlink($uploadPath);
    $stmt->close();
    closeDBConnection($conn);
    jsonResponse(false, 'Failed to update profile picture in database');
}

$stmt->close();

// Update session
$_SESSION['profile_pic'] = $newFilename;

closeDBConnection($conn);

jsonResponse(true, 'Profile picture updated successfully', [
    'filename' => $newFilename,
    'url' => APP_URL . '/assets/images/uploads/' . $newFilename
]);
?>
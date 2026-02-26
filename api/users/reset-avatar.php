<?php
/**
 * Reset Avatar to Default API
 * Resets user's profile picture to default avatar
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/image-helper.php';
require_once __DIR__ . '/../../config/database.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$userId = getCurrentUserId();
$conn = getDBConnection();

// Get user's current profile picture
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    closeDBConnection($conn);
    jsonResponse(false, 'User not found');
}

// Delete old avatar if it's not already default
if ($user['profile_pic'] !== 'default-avatar.png') {
    deleteOldAvatar($user['profile_pic']);
}

// Update database to default avatar
$stmt = $conn->prepare("UPDATE users SET profile_pic = 'default-avatar.png' WHERE user_id = ?");
$stmt->bind_param("i", $userId);

if (!$stmt->execute()) {
    $stmt->close();
    closeDBConnection($conn);
    jsonResponse(false, 'Failed to reset avatar');
}

$stmt->close();

// Update session
$_SESSION['profile_pic'] = 'default-avatar.png';

closeDBConnection($conn);

jsonResponse(true, 'Profile picture reset to default', [
    'filename' => 'default-avatar.png',
    'url' => APP_URL . '/assets/images/default-avatar.png'
]);
?>
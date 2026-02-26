<?php
/**
 * Toggle Like API
 * Handles like/dislike toggle
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/Like.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in to like posts');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($input['post_id'])) {
    jsonResponse(false, 'Post ID is required');
}

if (empty($input['like_type']) || !in_array($input['like_type'], ['like', 'dislike'])) {
    jsonResponse(false, 'Invalid like type');
}

$postId = (int)$input['post_id'];
$userId = getCurrentUserId();
$likeType = $input['like_type'];

// Toggle like
$likeModel = new Like();
$result = $likeModel->toggleLike($postId, $userId, $likeType);

if ($result['action'] === 'error') {
    jsonResponse(false, 'Failed to update like status');
}

jsonResponse(true, 'Like status updated', [
    'action' => $result['action'],
    'like_type' => $result['like_type'],
    'counts' => $result['counts']
]);
?>
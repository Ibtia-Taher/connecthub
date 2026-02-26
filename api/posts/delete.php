<?php
/**
 * Delete Post API
 * Allows users to delete their own posts
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/Post.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate post ID
if (empty($input['post_id'])) {
    jsonResponse(false, 'Post ID is required');
}

$postId = (int)$input['post_id'];
$userId = getCurrentUserId();

// Delete post
$postModel = new Post();
$result = $postModel->deletePost($postId, $userId);

if ($result) {
    jsonResponse(true, 'Post deleted successfully');
} else {
    jsonResponse(false, 'Failed to delete post. You may not have permission.');
}
?>
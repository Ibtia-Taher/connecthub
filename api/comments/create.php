<?php
/**
 * Create Comment API
 * Handles new comment creation
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/Comment.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in to comment');
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

if (empty($input['content']) || strlen(trim($input['content'])) === 0) {
    jsonResponse(false, 'Comment cannot be empty');
}

// Validate content length
if (strlen($input['content']) > 1000) {
    jsonResponse(false, 'Comment too long (max 1000 characters)');
}

$userId = getCurrentUserId();
$currentUser = getCurrentUser();

// Prepare comment data
$commentData = [
    'post_id' => (int)$input['post_id'],
    'user_id' => $userId,
    'content' => trim($input['content'])
];

// Create comment
$commentModel = new Comment();
$commentId = $commentModel->createComment($commentData);

if (!$commentId) {
    jsonResponse(false, 'Failed to create comment');
}

// Return comment with user info
jsonResponse(true, 'Comment added successfully', [
    'comment' => [
        'comment_id' => $commentId,
        'post_id' => $commentData['post_id'],
        'user_id' => $userId,
        'content' => $commentData['content'],
        'username' => $currentUser['username'],
        'profile_pic' => $currentUser['profile_pic'],
        'created_at' => date('Y-m-d H:i:s')
    ]
]);
?>
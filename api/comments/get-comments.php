<?php
/**
 * Get Comments API
 * Fetches comments for a specific post
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Comment.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get post ID
$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if ($postId <= 0) {
    jsonResponse(false, 'Invalid post ID');
}

// Get pagination parameters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : COMMENTS_PER_PAGE;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Get comments
$commentModel = new Comment();
$comments = $commentModel->getComments($postId, $limit, $offset);
$totalComments = $commentModel->getCommentCount($postId);

jsonResponse(true, 'Comments fetched successfully', [
    'comments' => $comments,
    'total_comments' => $totalComments
]);
?>
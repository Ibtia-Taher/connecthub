<?php
/**
 * Get Like Counts API
 * Fetches like/dislike counts for a post
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Like.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get post ID
$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if ($postId <= 0) {
    jsonResponse(false, 'Invalid post ID');
}

// Get counts
$likeModel = new Like();
$counts = $likeModel->getLikeCounts($postId);

jsonResponse(true, 'Counts fetched successfully', [
    'counts' => $counts
]);
?>
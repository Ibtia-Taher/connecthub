<?php
/**
 * Get Posts API
 * Fetches posts with pagination
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/Post.php';
require_once __DIR__ . '/../../models/Like.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : POSTS_PER_PAGE;

// Validate parameters
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = POSTS_PER_PAGE;

$offset = ($page - 1) * $limit;

// Get user ID if logged in
$currentUserId = isLoggedIn() ? getCurrentUserId() : null;

// Get posts
$postModel = new Post();
$posts = $postModel->getPosts($limit, $offset);

// Add user's like status for each post if logged in
if ($currentUserId) {
    $likeModel = new Like();
    foreach ($posts as &$post) {
        $post['user_like_status'] = $likeModel->getUserLikeStatus($post['post_id'], $currentUserId);
        $post['is_owner'] = ($post['user_id'] == $currentUserId);
    }
} else {
    foreach ($posts as &$post) {
        $post['user_like_status'] = null;
        $post['is_owner'] = false;
    }
}

// Get total count for pagination info
$totalPosts = $postModel->getTotalPostCount();
$totalPages = ceil($totalPosts / $limit);

jsonResponse(true, 'Posts fetched successfully', [
    'posts' => $posts,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_posts' => $totalPosts,
        'per_page' => $limit,
        'has_more' => $page < $totalPages
    ]
]);
?>
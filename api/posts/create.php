<?php
/**
 * Create Post API (WITH SENTIMENT ANALYSIS)
 * Handles new post creation (text, image, video)
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/Post.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in to create a post');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate content
if (empty($input['content']) || strlen(trim($input['content'])) === 0) {
    jsonResponse(false, 'Post content cannot be empty');
}

// Validate content length (max 5000 characters)
if (strlen($input['content']) > 5000) {
    jsonResponse(false, 'Post content too long (max 5000 characters)');
}

$userId = getCurrentUserId();

// Prepare post data
$postData = [
    'user_id' => $userId,
    'content' => trim($input['content']),
    'media_url' => $input['media_url'] ?? null,
    'media_type' => $input['media_type'] ?? null,
    'youtube_embed' => $input['youtube_embed'] ?? null
];

// Add sentiment score if provided
if (isset($input['sentiment_score'])) {
    $sentimentScore = floatval($input['sentiment_score']);
    // Validate sentiment score is between 0 and 1
    if ($sentimentScore >= 0 && $sentimentScore <= 1) {
        $postData['sentiment_score'] = $sentimentScore;
    }
}

// Validate YouTube embed if provided
if ($postData['youtube_embed']) {
    // Extract YouTube video ID from various URL formats
    $youtubeId = extractYoutubeId($postData['youtube_embed']);
    if ($youtubeId) {
        $postData['youtube_embed'] = $youtubeId;
        $postData['media_type'] = 'youtube';
    } else {
        jsonResponse(false, 'Invalid YouTube URL');
    }
}

// Create post
$postModel = new Post();
$postId = $postModel->createPost($postData);

if (!$postId) {
    jsonResponse(false, 'Failed to create post');
}

// Get the created post with user info
$post = $postModel->getPostById($postId);

jsonResponse(true, 'Post created successfully', [
    'post_id' => $postId,
    'post' => $post
]);

/**
 * Extract YouTube video ID from URL
 * @param string $url
 * @return string|false
 */
function extractYoutubeId($url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
        '/youtu\.be\/([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    // If it's already just the ID (11 characters)
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    
    return false;
}
?>
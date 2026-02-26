<?php
/**
 * Submit Rating API
 * Handles post rating submission (1-5 stars)
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/database.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in to rate posts');
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

if (!isset($input['rating']) || $input['rating'] < 1 || $input['rating'] > 5) {
    jsonResponse(false, 'Rating must be between 1 and 5');
}

$postId = (int)$input['post_id'];
$userId = getCurrentUserId();
$rating = (int)$input['rating'];

$conn = getDBConnection();

// Check if user already rated this post
$checkQuery = "SELECT rating_id FROM ratings WHERE post_id = ? AND user_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ii", $postId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing rating
    $stmt->close();
    $updateQuery = "UPDATE ratings SET rating_value = ? WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("iii", $rating, $postId, $userId);
    
    if (!$stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        jsonResponse(false, 'Failed to update rating');
    }
    
    $action = 'updated';
} else {
    // Insert new rating
    $stmt->close();
    $insertQuery = "INSERT INTO ratings (post_id, user_id, rating_value) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iii", $postId, $userId, $rating);
    
    if (!$stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        jsonResponse(false, 'Failed to submit rating');
    }
    
    $action = 'added';
}

$stmt->close();

// Get average rating
$avgQuery = "SELECT 
                AVG(rating_value) as average_rating,
                COUNT(*) as total_ratings
             FROM ratings 
             WHERE post_id = ?";

$stmt = $conn->prepare($avgQuery);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

closeDBConnection($conn);

jsonResponse(true, 'Rating ' . $action . ' successfully', [
    'average_rating' => round($stats['average_rating'], 1),
    'total_ratings' => (int)$stats['total_ratings'],
    'user_rating' => $rating
]);
?>
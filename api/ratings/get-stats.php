<?php
/**
 * Get Rating Stats API
 * Fetches rating statistics for a post
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/database.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get post ID
$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if ($postId <= 0) {
    jsonResponse(false, 'Invalid post ID');
}

$conn = getDBConnection();

// Get rating stats
$query = "SELECT 
            AVG(rating_value) as average_rating,
            COUNT(*) as total_ratings
          FROM ratings 
          WHERE post_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

// Get user's rating if logged in
$userRating = null;
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    $userQuery = "SELECT rating_value FROM ratings WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userRating = (int)$row['rating_value'];
    }
    
    $stmt->close();
}

closeDBConnection($conn);

jsonResponse(true, 'Rating stats fetched successfully', [
    'average_rating' => $stats['average_rating'] ? round($stats['average_rating'], 1) : 0,
    'total_ratings' => (int)$stats['total_ratings'],
    'user_rating' => $userRating
]);
?>
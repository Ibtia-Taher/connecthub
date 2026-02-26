<?php
/**
 * Get User Post Count API
 * Returns the number of posts by a specific user
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get user ID
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId <= 0) {
    jsonResponse(false, 'Invalid user ID');
}

// Get post count
$conn = getDBConnection();
$query = "SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
closeDBConnection($conn);

jsonResponse(true, 'Post count fetched successfully', [
    'post_count' => (int)$row['post_count']
]);
?>
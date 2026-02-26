<?php
/**
 * Simple GraphQL Endpoint (FIXED)
 * Read-only queries for posts and users
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['errors' => [['message' => 'Only POST requests accepted']]]);
    exit;
}

// Get query
$input = json_decode(file_get_contents('php://input'), true);
$query = $input['query'] ?? '';

// Remove whitespace and newlines for easier parsing
$cleanQuery = preg_replace('/\s+/', ' ', trim($query));

// Log query for debugging
error_log("GraphQL Query: " . $cleanQuery);

// Simple query parser (improved)
$result = executeGraphQLQuery($cleanQuery);

echo json_encode($result);

/**
 * Execute GraphQL query
 */
function executeGraphQLQuery($query) {
    // Check for posts query
    if (stripos($query, 'posts') !== false && stripos($query, 'posts(') !== false) {
        return getPosts($query);
    } 
    // Check for single user query
    elseif (stripos($query, 'user(') !== false) {
        return getUser($query);
    }
    // Check for users list query
    elseif (stripos($query, 'users') !== false) {
        return getUsers($query);
    } 
    else {
        return ['errors' => [['message' => 'Unknown query. Available queries: posts(limit: Int), user(id: Int), users(limit: Int)']]];
    }
}

/**
 * Get posts query
 */
function getPosts($query) {
    $conn = getDBConnection();
    
    // Extract limit from query
    $limit = 10;
    if (preg_match('/limit:\s*(\d+)/', $query, $matches)) {
        $limit = min((int)$matches[1], 50);
    }
    
    $sql = "SELECT 
                p.post_id,
                p.content,
                p.created_at,
                p.sentiment_score,
                u.user_id,
                u.username,
                u.profile_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id AND like_type = 'like') as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            ORDER BY p.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            'id' => (int)$row['post_id'],
            'content' => $row['content'],
            'createdAt' => $row['created_at'],
            'sentimentScore' => $row['sentiment_score'] ? (float)$row['sentiment_score'] : null,
            'likeCount' => (int)$row['like_count'],
            'commentCount' => (int)$row['comment_count'],
            'author' => [
                'id' => (int)$row['user_id'],
                'username' => $row['username'],
                'profilePic' => $row['profile_pic']
            ]
        ];
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
    return ['data' => ['posts' => $posts]];
}

/**
 * Get single user query
 */
function getUser($query) {
    // Extract user ID
    if (!preg_match('/id:\s*(\d+)/', $query, $matches)) {
        return ['errors' => [['message' => 'User ID required. Example: user(id: 1)']]];
    }
    
    $userId = (int)$matches[1];
    $conn = getDBConnection();
    
    $sql = "SELECT 
                user_id,
                username,
                email,
                profile_pic,
                bio,
                location,
                created_at,
                (SELECT COUNT(*) FROM posts WHERE user_id = ?) as post_count
            FROM users 
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['errors' => [['message' => 'User not found with ID: ' . $userId]]];
    }
    
    $row = $result->fetch_assoc();
    $user = [
        'id' => (int)$row['user_id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'profilePic' => $row['profile_pic'],
        'bio' => $row['bio'],
        'location' => $row['location'],
        'createdAt' => $row['created_at'],
        'postCount' => (int)$row['post_count']
    ];
    
    $stmt->close();
    closeDBConnection($conn);
    
    return ['data' => ['user' => $user]];
}

/**
 * Get users query
 */
function getUsers($query) {
    $conn = getDBConnection();
    
    // Extract limit
    $limit = 20;
    if (preg_match('/limit:\s*(\d+)/', $query, $matches)) {
        $limit = min((int)$matches[1], 100);
    }
    
    $sql = "SELECT 
                user_id,
                username,
                profile_pic,
                created_at
            FROM users 
            ORDER BY created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['user_id'],
            'username' => $row['username'],
            'profilePic' => $row['profile_pic'],
            'createdAt' => $row['created_at']
        ];
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
    return ['data' => ['users' => $users]];
}
?>
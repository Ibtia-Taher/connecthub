<?php
/**
 * Post Model
 * Handles all database operations related to posts
 */

require_once __DIR__ . '/../config/database.php';

class Post {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    /**
 * Create new post (WITH SENTIMENT SCORE)
 * @param array $postData
 * @return int|false Post ID on success, false on failure
 */
public function createPost($postData) {
    $query = "INSERT INTO posts (user_id, content, media_url, media_type, youtube_embed, sentiment_score) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $this->conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $this->conn->error);
        return false;
    }
    
    // Handle optional sentiment score
    $sentimentScore = $postData['sentiment_score'] ?? null;
    
    $stmt->bind_param(
        "issssd",
        $postData['user_id'],
        $postData['content'],
        $postData['media_url'],
        $postData['media_type'],
        $postData['youtube_embed'],
        $sentimentScore
    );
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $postId = $this->conn->insert_id;
    $stmt->close();
    
    return $postId;
}
    
    /**
     * Get posts with pagination
     * @param int $limit Number of posts per page
     * @param int $offset Starting position
     * @return array Posts with user info
     */
    public function getPosts($limit = 10, $offset = 0) {
        $query = "SELECT 
                    p.post_id,
                    p.user_id,
                    p.content,
                    p.media_url,
                    p.media_type,
                    p.youtube_embed,
                    p.sentiment_score,
                    p.created_at,
                    u.username,
                    u.profile_pic,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id AND like_type = 'like') as like_count,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id AND like_type = 'dislike') as dislike_count,
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
                    (SELECT AVG(rating_value) FROM ratings WHERE post_id = p.post_id) as average_rating,
                    (SELECT COUNT(*) FROM ratings WHERE post_id = p.post_id) as rating_count
                  FROM posts p
                  JOIN users u ON p.user_id = u.user_id
                  ORDER BY p.created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            // Round average rating to 1 decimal place
            $row['average_rating'] = $row['average_rating'] ? round($row['average_rating'], 1) : 0;
            $posts[] = $row;
        }

        $stmt->close();
        return $posts;
    }
    
    /**
 * Get posts by specific user
 * @param int $userId
 * @param int $limit
 * @param int $offset
 * @return array
 */
public function getUserPosts($userId, $limit = 10, $offset = 0) {
    $query = "SELECT 
                p.post_id,
                p.user_id,
                p.content,
                p.media_url,
                p.media_type,
                p.youtube_embed,
                p.sentiment_score,
                p.created_at,
                u.username,
                u.profile_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id AND like_type = 'like') as like_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id AND like_type = 'dislike') as dislike_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
                (SELECT AVG(rating_value) FROM ratings WHERE post_id = p.post_id) as average_rating,
                (SELECT COUNT(*) FROM ratings WHERE post_id = p.post_id) as rating_count
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.user_id = ?
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("iii", $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        // Round average rating to 1 decimal place
        $row['average_rating'] = $row['average_rating'] ? round($row['average_rating'], 1) : 0;
        $posts[] = $row;
    }
    
    $stmt->close();
    return $posts;
}
    
    /**
     * Get single post by ID
     * @param int $postId
     * @return array|false
     */
    public function getPostById($postId) {
        $query = "SELECT 
                    p.*,
                    u.username,
                    u.profile_pic
                  FROM posts p
                  JOIN users u ON p.user_id = u.user_id
                  WHERE p.post_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $post = $result->fetch_assoc();
            $stmt->close();
            return $post;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Delete post
     * @param int $postId
     * @param int $userId (to verify ownership)
     * @return bool
     */
    public function deletePost($postId, $userId) {
        // First verify the post belongs to the user
        $query = "SELECT user_id, media_url FROM posts WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        
        $post = $result->fetch_assoc();
        $stmt->close();
        
        // Check ownership
        if ($post['user_id'] !== $userId) {
            return false;
        }
        
        // Delete media file if exists
        if ($post['media_url']) {
            $mediaPath = __DIR__ . '/../assets/images/uploads/' . $post['media_url'];
            if (file_exists($mediaPath)) {
                unlink($mediaPath);
            }
        }
        
        // Delete post (comments and likes will be cascade deleted)
        $query = "DELETE FROM posts WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get total post count
     * @return int
     */
    public function getTotalPostCount() {
        $query = "SELECT COUNT(*) as total FROM posts";
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    /**
     * Update sentiment score
     * @param int $postId
     * @param float $score
     * @return bool
     */
    public function updateSentimentScore($postId, $score) {
        $query = "UPDATE posts SET sentiment_score = ? WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("di", $score, $postId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    public function __destruct() {
        closeDBConnection($this->conn);
    }
}
?>
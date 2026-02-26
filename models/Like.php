<?php
/**
 * Like Model
 * Handles all database operations related to likes/dislikes
 */

require_once __DIR__ . '/../config/database.php';

class Like {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    /**
     * Toggle like/dislike on a post
     * @param int $postId
     * @param int $userId
     * @param string $likeType ('like' or 'dislike')
     * @return array Status of the action
     */
    public function toggleLike($postId, $userId, $likeType) {
        // Check if user already liked/disliked this post
        $query = "SELECT like_id, like_type FROM likes 
                  WHERE post_id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User has already interacted with this post
            $existing = $result->fetch_assoc();
            $stmt->close();
            
            if ($existing['like_type'] === $likeType) {
                // Same type - remove the like/dislike
                return $this->removeLike($postId, $userId);
            } else {
                // Different type - update to new type
                return $this->updateLike($postId, $userId, $likeType);
            }
        } else {
            // No existing interaction - create new
            $stmt->close();
            return $this->addLike($postId, $userId, $likeType);
        }
    }
    
    /**
     * Add new like/dislike
     * @param int $postId
     * @param int $userId
     * @param string $likeType
     * @return array
     */
    private function addLike($postId, $userId, $likeType) {
        $query = "INSERT INTO likes (post_id, user_id, like_type) 
                  VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iis", $postId, $userId, $likeType);
        
        if ($stmt->execute()) {
            $stmt->close();
            return [
                'action' => 'added',
                'like_type' => $likeType,
                'counts' => $this->getLikeCounts($postId)
            ];
        }
        
        $stmt->close();
        return ['action' => 'error'];
    }
    
    /**
     * Update existing like type
     * @param int $postId
     * @param int $userId
     * @param string $likeType
     * @return array
     */
    private function updateLike($postId, $userId, $likeType) {
        $query = "UPDATE likes SET like_type = ? 
                  WHERE post_id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sii", $likeType, $postId, $userId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return [
                'action' => 'updated',
                'like_type' => $likeType,
                'counts' => $this->getLikeCounts($postId)
            ];
        }
        
        $stmt->close();
        return ['action' => 'error'];
    }
    
    /**
     * Remove like/dislike
     * @param int $postId
     * @param int $userId
     * @return array
     */
    private function removeLike($postId, $userId) {
        $query = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return [
                'action' => 'removed',
                'like_type' => null,
                'counts' => $this->getLikeCounts($postId)
            ];
        }
        
        $stmt->close();
        return ['action' => 'error'];
    }
    
    /**
     * Get like/dislike counts for a post
     * @param int $postId
     * @return array
     */
    public function getLikeCounts($postId) {
        $query = "SELECT 
                    SUM(CASE WHEN like_type = 'like' THEN 1 ELSE 0 END) as like_count,
                    SUM(CASE WHEN like_type = 'dislike' THEN 1 ELSE 0 END) as dislike_count
                  FROM likes 
                  WHERE post_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'like_count' => (int)$counts['like_count'],
            'dislike_count' => (int)$counts['dislike_count']
        ];
    }
    
    /**
     * Get user's like status for a post
     * @param int $postId
     * @param int $userId
     * @return string|null 'like', 'dislike', or null
     */
    public function getUserLikeStatus($postId, $userId) {
        $query = "SELECT like_type FROM likes 
                  WHERE post_id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['like_type'];
        }
        
        $stmt->close();
        return null;
    }
    
    public function __destruct() {
        closeDBConnection($this->conn);
    }
}
?>
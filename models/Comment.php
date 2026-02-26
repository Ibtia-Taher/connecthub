<?php
/**
 * Comment Model
 * Handles all database operations related to comments
 */

require_once __DIR__ . '/../config/database.php';

class Comment {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    /**
     * Create new comment
     * @param array $commentData
     * @return int|false Comment ID on success
     */
    public function createComment($commentData) {
        $query = "INSERT INTO comments (post_id, user_id, content) 
                  VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "iis",
            $commentData['post_id'],
            $commentData['user_id'],
            $commentData['content']
        );
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $commentId = $this->conn->insert_id;
        $stmt->close();
        
        return $commentId;
    }
    
    /**
     * Get comments for a post
     * @param int $postId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getComments($postId, $limit = 20, $offset = 0) {
        $query = "SELECT 
                    c.comment_id,
                    c.post_id,
                    c.user_id,
                    c.content,
                    c.created_at,
                    u.username,
                    u.profile_pic
                  FROM comments c
                  JOIN users u ON c.user_id = u.user_id
                  WHERE c.post_id = ?
                  ORDER BY c.created_at ASC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $postId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        
        $stmt->close();
        return $comments;
    }
    
    /**
     * Get comment count for a post
     * @param int $postId
     * @return int
     */
    public function getCommentCount($postId) {
        $query = "SELECT COUNT(*) as total FROM comments WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['total'];
    }
    
    /**
     * Delete comment
     * @param int $commentId
     * @param int $userId (to verify ownership)
     * @return bool
     */
    public function deleteComment($commentId, $userId) {
        // Verify ownership
        $query = "SELECT user_id FROM comments WHERE comment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $commentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        
        $comment = $result->fetch_assoc();
        $stmt->close();
        
        if ($comment['user_id'] !== $userId) {
            return false;
        }
        
        // Delete comment
        $query = "DELETE FROM comments WHERE comment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $commentId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    public function __destruct() {
        closeDBConnection($this->conn);
    }
}
?>
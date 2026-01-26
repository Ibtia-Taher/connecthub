<?php
/**
 * User Model
 * Handles all database operations related to users
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    /**
     * Check if username already exists
     * @param string $username
     * @return bool
     */
    public function usernameExists($username) {
        $query = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
        $result = executeQuery($this->conn, $query, "s", [$username]);
        
        if ($result && $result->num_rows > 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Check if email already exists
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        $query = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $result = executeQuery($this->conn, $query, "s", [$email]);
        
        if ($result && $result->num_rows > 0) {
            return true;
        }
        return false;
    }
    
/**
 * Create new user account
 * @param array $userData
 * @return int|false User ID on success, false on failure
 */
public function createUser($userData) {
    try {
        // Hash password securely
        $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);
        
        $query = "INSERT INTO users (username, email, phone, password_hash, date_of_birth) 
                  VALUES (?, ?, ?, ?, ?)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log("PREPARE FAILED: " . $this->conn->error);
            return false;
        }
        
        // Bind parameters
        $bindResult = $stmt->bind_param(
            "sssss", 
            $userData['username'],
            $userData['email'],
            $userData['phone'],
            $passwordHash,
            $userData['dob']
        );
        
        if (!$bindResult) {
            error_log("BIND FAILED: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        // Execute
        $executeResult = $stmt->execute();
        
        if (!$executeResult) {
            error_log("EXECUTE FAILED: " . $stmt->error);
            error_log("SQL Error Code: " . $stmt->errno);
            $stmt->close();
            return false;
        }
        
        // Get the new user ID
        $userId = $this->conn->insert_id;
        $stmt->close();
        
        error_log("User created successfully with ID: " . $userId);
        
        return $userId;
        
    } catch (Exception $e) {
        error_log("Exception in createUser: " . $e->getMessage());
        return false;
    }
}
    
    /**
     * Verify user credentials for login
     * @param string $username
     * @param string $password
     * @return array|false User data on success, false on failure
     */
    public function verifyCredentials($username, $password) {
        $query = "SELECT user_id, username, email, password_hash, is_verified, profile_pic 
                  FROM users WHERE username = ? OR email = ? LIMIT 1";
        
        $result = executeQuery($this->conn, $query, "ss", [$username, $username]);
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password against hash
            if (password_verify($password, $user['password_hash'])) {
                // Remove password hash from returned data
                unset($user['password_hash']);
                return $user;
            }
        }
        return false;
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|false
     */
    public function getUserById($userId) {
        $query = "SELECT user_id, username, email, phone, profile_pic, bio, 
                  date_of_birth, location, latitude, longitude, is_verified, created_at 
                  FROM users WHERE user_id = ? LIMIT 1";
        
        $result = executeQuery($this->conn, $query, "i", [$userId]);
        
        if ($result && $result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        return false;
    }
    
    /**
     * Update user verification status
     * @param int $userId
     * @return bool
     */
    public function markAsVerified($userId) {
        $query = "UPDATE users SET is_verified = 1 WHERE user_id = ?";
        $result = executeQuery($this->conn, $query, "i", [$userId]);
        return $result !== false;
    }
    
    /**
     * Store OTP for verification
     * @param int $userId
     * @param string $otpCode
     * @param string $otpType (email or phone)
     * @return bool
     */
    public function storeOTP($userId, $otpCode, $otpType = 'email') {
        // Set expiry time (10 minutes from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
        
        $query = "INSERT INTO otp_verifications (user_id, otp_code, otp_type, expires_at) 
                  VALUES (?, ?, ?, ?)";
        
        $result = executeQuery($this->conn, $query, "isss", [$userId, $otpCode, $otpType, $expiresAt]);
        return $result !== false;
    }
    
    /**
     * Verify OTP code
     * @param int $userId
     * @param string $otpCode
     * @return bool
     */
    public function verifyOTP($userId, $otpCode) {
        $query = "SELECT otp_id FROM otp_verifications 
                  WHERE user_id = ? AND otp_code = ? AND is_used = 0 
                  AND expires_at > NOW() 
                  ORDER BY created_at DESC LIMIT 1";
        
        $result = executeQuery($this->conn, $query, "is", [$userId, $otpCode]);
        
        if ($result && $result->num_rows === 1) {
            $otp = $result->fetch_assoc();
            
            // Mark OTP as used
            $updateQuery = "UPDATE otp_verifications SET is_used = 1 WHERE otp_id = ?";
            executeQuery($this->conn, $updateQuery, "i", [$otp['otp_id']]);
            
            return true;
        }
        return false;
    }
    
    public function __destruct() {
        closeDBConnection($this->conn);
    }
}
?>
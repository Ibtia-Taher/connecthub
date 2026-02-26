<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';

// Require login
requireLogin();

// Get profile user ID (default to logged-in user)
$profileUserId = $_GET['user_id'] ?? getCurrentUserId();
$currentUserId = getCurrentUserId();
$isOwnProfile = ($profileUserId == $currentUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/profile.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/feed.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="font-size: 24px; font-weight: bold;"><?php echo APP_NAME; ?></div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <a href="feed.php" style="color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; transition: background 0.3s;">Home</a>
            <a href="profile.php" style="color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; transition: background 0.3s;">Profile</a>
            <a href="<?php echo APP_URL; ?>/api/auth/logout.php" style="color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; transition: background 0.3s;">Logout</a>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <?php if ($isOwnProfile): ?>
            <button class="edit-profile-btn" onclick="window.location.href='edit-profile.php'">
                Edit Profile
            </button>
            <?php endif; ?>
            
            <div class="profile-avatar-section">
                <img id="profileAvatar" src="" alt="Profile" class="profile-avatar">
            </div>
            
            <h1 id="profileUsername" class="profile-username">Loading...</h1>
            <p id="profileEmail" class="profile-email"></p>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-number" id="postCount">0</span>
                    <span class="stat-label">Posts</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="followerCount">0</span>
                    <span class="stat-label">Followers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="followingCount">0</span>
                    <span class="stat-label">Following</span>
                </div>
            </div>
        </div>
        
        <div class="profile-body">
            <div class="profile-tabs">
                <button class="tab-button active" data-tab="about">About</button>
                <button class="tab-button" data-tab="posts">Posts</button>
            </div>
            
            <div id="aboutTab" class="tab-content">
                <div class="info-section">
                    <h3>Personal Information</h3>
                    <div class="info-row">
                        <span class="info-label">Bio:</span>
                        <span class="info-value" id="profileBio">Not provided</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location:</span>
                        <span class="info-value" id="profileLocation">Not provided</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value" id="profilePhone">Not provided</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date of Birth:</span>
                        <span class="info-value" id="profileDOB">Not provided</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value" id="profileJoined">-</span>
                    </div>
                </div>
            </div>
            
            <div id="postsTab" class="tab-content" style="display: none;">
                <div id="userPostsFeed" class="posts-feed">
                    <div class="loading-spinner">Loading posts...</div>
                </div>
                
                <button id="loadMoreUserPostsBtn" class="load-more-btn" style="display: none;">
                    Load More Posts
                </button>
                
                <div id="endOfUserPosts" style="display: none; text-align: center; padding: 20px; color: #666;">
                    No more posts
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pass data to JavaScript -->
    <script>
        const API_BASE = 'http://localhost/connecthub/api';
        const APP_URL = 'http://localhost/connecthub';
        const profileUserId = <?php echo json_encode($profileUserId); ?>;
        const currentUserId = <?php echo json_encode($currentUserId); ?>;
        const isOwnProfile = <?php echo json_encode($isOwnProfile); ?>;
        
        // For posts functionality
        window.currentUserId = currentUserId;
        window.currentUsername = '';
        window.currentUserAvatar = '';
        window.APP_URL = APP_URL;
        window.API_BASE = API_BASE;
    </script>
    
    <script src="<?php echo APP_URL; ?>/assets/js/profile-view.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/posts.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/comments.js"></script>
</body>
</html>
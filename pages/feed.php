<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';

// Require login to access this page
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - <?php echo APP_NAME; ?></title>
    <!-- Compiled SASS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/feed.css">
    <!-- TensorFlow.js for sentiment analysis -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/toxicity"></script>
    <!-- GSAP for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
        }
        .navbar-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .user-avatar-nav {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand"><?php echo APP_NAME; ?></div>
        <div class="navbar-menu">
            <a href="feed.php">
                <span>🏠</span>
                <span>Home</span>
            </a>
            <a href="profile.php">
                <img src="<?php echo APP_URL . '/assets/images/uploads/' . $user['profile_pic']; ?>" 
                     alt="Profile" 
                     class="user-avatar-nav">
                <span>Profile</span>
            </a>
            <a href="<?php echo APP_URL; ?>/api/auth/logout.php">
                <span>Logout</span>
            </a>
        </div>
    </nav>
    
    <!-- Feed Container -->
    <div class="feed-container">
        
        <!-- Create Post Box -->
        <div class="create-post-box">
            <h3>Create Post</h3>
            <textarea 
                id="postContent" 
                class="post-textarea" 
                placeholder="What's on your mind, <?php echo htmlspecialchars($user['username']); ?>?"
                maxlength="5000"
            ></textarea>
            
            <div id="mediaPreview" class="media-preview" style="display: none;">
                <img id="previewImage" src="" alt="Preview">
                <button type="button" id="removeMediaBtn" class="remove-media-btn">×</button>
            </div>
            
            <div id="youtubeInputContainer" style="display: none; margin-bottom: 10px;">
                <input 
                    type="text" 
                    id="youtubeInput" 
                    class="youtube-input" 
                    placeholder="Paste YouTube URL here (e.g., https://www.youtube.com/watch?v=...)"
                >
            </div>
            
            <div class="post-actions">
                <input type="file" id="mediaInput" accept="image/*" style="display: none;">
                <button type="button" id="addPhotoBtn" class="post-action-btn">
                    📷 Photo
                </button>
                <button type="button" id="addVideoBtn" class="post-action-btn">
                    🎥 YouTube
                </button>
                <button type="button" id="createPostBtn" class="post-action-btn primary">
                    Post
                </button>
            </div>
            
            <div id="createPostMessage" class="message" style="margin-top: 10px;"></div>
        </div>
        
        <!-- Posts Feed -->
        <div id="postsFeed" class="posts-feed">
            <div class="loading-spinner">Loading posts...</div>
        </div>
        
        <!-- Load More Button -->
        <button id="loadMoreBtn" class="load-more-btn" style="display: none;">
            Load More Posts
        </button>
        
        <div id="endOfFeed" style="display: none; text-align: center; padding: 20px; color: #666;">
            You've reached the end! 🎉
        </div>
    </div>
    
    <!-- GSAP for animations (LOAD FIRST!) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    
    <!-- Pass PHP data to JavaScript -->
    <script>
        window.currentUserId = <?php echo $user['user_id']; ?>;
        window.currentUsername = <?php echo json_encode($user['username']); ?>;
        window.currentUserAvatar = <?php echo json_encode($user['profile_pic']); ?>;
        window.APP_URL = '<?php echo APP_URL; ?>';
        window.API_BASE = '<?php echo APP_URL; ?>/api';
    </script>
    
    <script src="<?php echo APP_URL; ?>/assets/js/sentiment.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/animations.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/feed.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/posts.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/comments.js"></script>
</body>
</html>
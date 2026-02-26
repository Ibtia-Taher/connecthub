<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';

// Require login
requireLogin();

$currentUser = getCurrentUser();

// Fetch full user data
require_once __DIR__ . '/../models/User.php';
$userModel = new User();
$userData = $userModel->getUserById($currentUser['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/profile.css">
    
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Leaflet (OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .remove-avatar-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .remove-avatar-btn:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="font-size: 24px; font-weight: bold;"><?php echo APP_NAME; ?></div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <a href="feed.php" style="color: white; text-decoration: none; padding: 8px 16px;">Home</a>
            <a href="profile.php" style="color: white; text-decoration: none; padding: 8px 16px;">Profile</a>
            <a href="<?php echo APP_URL; ?>/api/auth/logout.php" style="color: white; text-decoration: none; padding: 8px 16px;">Logout</a>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header" style="padding: 30px;">
            <h1>Edit Your Profile</h1>
            <p>Update your information and preferences</p>
        </div>
        
        <div class="profile-body">
            <form id="editProfileForm" class="tab-content">
                
                <!-- Avatar Upload Section -->
                <div class="avatar-upload-section">
                    <h3>Profile Picture</h3>
                    <img id="avatarPreview" 
                         src="<?php echo APP_URL . '/assets/images/uploads/' . $userData['profile_pic']; ?>" 
                         alt="Profile Preview" 
                         class="avatar-preview"
                         data-original="<?php echo APP_URL . '/assets/images/uploads/' . $userData['profile_pic']; ?>">
                    
                    <div class="file-input-wrapper">
                        <input type="file" 
                               id="avatarInput" 
                               name="avatar" 
                               accept="image/*">
                        <label for="avatarInput" class="file-input-label">
                            Choose New Picture
                        </label>
                    </div>
                    
                    <?php if ($userData['profile_pic'] !== 'default-avatar.png'): ?>
                    <button type="button" id="removeAvatarBtn" class="remove-avatar-btn">
                        Remove Current Picture
                    </button>
                    <?php else: ?>
                    <button type="button" id="removeAvatarBtn" class="remove-avatar-btn" style="display: none;">
                        Remove Current Picture
                    </button>
                    <?php endif; ?>
                    
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">
                        JPG, PNG, GIF or WEBP. Max 5MB. Min 100x100px.
                    </p>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 2px solid #e0e0e0;">
                
                <!-- Bio Section -->
                <div class="edit-form-section">
                    <label for="bio">Bio</label>
                    <textarea 
                        id="bio" 
                        name="bio" 
                        placeholder="Tell us about yourself..."
                        maxlength="500"
                        autocomplete="off"
                    ><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                    <small style="color: #666;"><span id="bioCount">0</span>/500 characters</small>
                </div>
                
                <!-- Phone Section -->
                <div class="edit-form-section">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="+1234567890"
                        value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>"
                        autocomplete="tel"
                    >
                </div>
                
                <!-- Date of Birth Section -->
                <div class="edit-form-section">
                    <label for="dob">Date of Birth</label>
                    <input 
                        type="text" 
                        id="dob" 
                        name="dob" 
                        placeholder="Select your date of birth"
                        value="<?php echo htmlspecialchars($userData['date_of_birth'] ?? ''); ?>"
                        autocomplete="bday"
                    >
                    <small style="color: #666;">You must be at least 13 years old</small>
                </div>
                
                <!-- Location Section -->
                <div class="edit-form-section">
                    <label for="location">Location</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        placeholder="Search for a location or click on the map"
                        value="<?php echo htmlspecialchars($userData['location'] ?? ''); ?>"
                        autocomplete="address-line1"
                    >
                    <button type="button" id="searchLocationBtn" style="margin-top: 10px; padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Search Location
                    </button>
                    
                    <!-- OpenStreetMap -->
                    <div id="map" class="map-container" style="margin-top: 15px;"></div>
                    
                    <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($userData['latitude'] ?? ''); ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($userData['longitude'] ?? ''); ?>">
                    
                    <small style="color: #666; display: block; margin-top: 8px;">
                        Click on the map to set your location, or search for an address above
                    </small>
                </div>
                
                <!-- Message Display -->
                <div id="message" class="message"></div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" id="saveBtn" class="save-profile-btn" style="flex: 2;">
                        Save Changes
                    </button>
                    <button type="button" 
                            id="cancelBtn"
                            style="flex: 1; padding: 14px; background: #6c757d; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Pass PHP data to JavaScript -->
    <script>
        window.currentUserId = <?php echo $currentUser['user_id']; ?>;
        window.APP_URL = '<?php echo APP_URL; ?>';
        window.userData = <?php echo json_encode($userData); ?>;
    </script>
    
    <!-- Load config first -->
    <script src="<?php echo APP_URL; ?>/assets/js/config.js"></script>
    
    <!-- Then load other scripts -->
    <script src="<?php echo APP_URL; ?>/assets/js/image-upload.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/profile-leaflet.js"></script>
</body>
</html>
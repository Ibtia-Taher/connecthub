<?php
require_once __DIR__ . '/../config/config.php';

// If already logged in, redirect to feed
if (isLoggedIn()) {
    redirect(APP_URL . '/pages/feed.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Create your account and connect with friends</p>
            </div>
            
            <form id="registerForm" class="auth-form">
                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="johndoe" 
                        required
                        pattern="[a-zA-Z0-9_]{3,20}"
                        title="3-20 characters (letters, numbers, underscore only)"
                    >
                    <span id="usernameStatus" class="field-status"></span>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="john@example.com" 
                        required
                    >
                </div>
                
                <!-- Phone -->
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="+1234567890" 
                        required
                    >
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Min 8 characters" 
                        required
                        minlength="8"
                    >
                    <span id="passwordStrength" class="field-status"></span>
                </div>
                
                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirmPassword" 
                        name="confirmPassword" 
                        placeholder="Re-enter password" 
                        required
                    >
                </div>
                
                <!-- Date of Birth -->
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input 
                        type="date" 
                        id="dob" 
                        name="dob" 
                        required
                        max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>"
                    >
                    <small>You must be at least 13 years old</small>
                </div>
                <!-- Submit Button -->
                <button type="submit" id="submitBtn" class="btn-primary">
                    Create Account
                </button>
            
                <!-- Error/Success Messages -->
            <div id="message" class="message"></div>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<script src="<?php echo APP_URL; ?>/assets/js/auth.js"></script>

</body>
</html>
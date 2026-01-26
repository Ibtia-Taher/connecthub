<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/pages/feed.php');
}

$verifiedMessage = isset($_GET['verified']) ? 'Email verified! You can now log in.' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Welcome back! Please login to continue.</p>
            </div>
            
            <?php if ($verifiedMessage): ?>
            <div style="padding: 15px 30px; background: #d1fae5; color: #065f46; text-align: center;">
                <?php echo $verifiedMessage; ?>
            </div>
            <?php endif; ?>
            
            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="johndoe or john@example.com" 
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                </div>
                
                <button type="submit" id="submitBtn" class="btn-primary">
                    Login
                </button>
                
                <div id="message" class="message"></div>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
    
    <script>
    const API_BASE = 'http://localhost/connecthub/api';
    const loginForm = document.getElementById('loginForm');
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const messageDiv = document.getElementById('message');
        
        const formData = {
            username: document.getElementById('username').value.trim(),
            password: document.getElementById('password').value
        };
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';
        
        try {
            const response = await fetch(`${API_BASE}/auth/login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                messageDiv.textContent = data.message;
                messageDiv.className = 'message success';
                
                setTimeout(() => {
                    window.location.href = data.data.redirect;
                }, 1000);
            } else {
                messageDiv.textContent = data.message;
                messageDiv.className = 'message error';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
                
                // If requires verification, show link
                if (data.data && data.data.requires_verification) {
                    messageDiv.innerHTML += '<br><a href="verify-otp.php?user_id=' + 
                        data.data.user_id + '" style="color: #667eea;">Verify now</a>';
                }
            }
        } catch (error) {
            console.error('Login error:', error);
            messageDiv.textContent = 'An error occurred. Please try again.';
            messageDiv.className = 'message error';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
        }
    });
    </script>
</body>
</html>
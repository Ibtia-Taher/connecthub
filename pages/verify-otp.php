<?php
require_once __DIR__ . '/../config/config.php';

// Get user info from URL
$userId = $_GET['user_id'] ?? null;
$email = $_GET['email'] ?? null;

if (!$userId || !$email) {
    redirect(APP_URL . '/pages/register.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Verify Your Email</h1>
                <p>We sent a code to <?php echo htmlspecialchars($email); ?></p>
            </div>
            
            <form id="otpForm" class="auth-form">
                <input type="hidden" id="userId" value="<?php echo htmlspecialchars($userId); ?>">
                
                <div class="form-group">
                    <label for="otpCode">Enter 6-digit code</label>
                    <input 
                        type="text" 
                        id="otpCode" 
                        name="otpCode" 
                        placeholder="123456" 
                        required
                        maxlength="6"
                        pattern="[0-9]{6}"
                        autocomplete="off"
                        style="text-align: center; font-size: 24px; letter-spacing: 5px;"
                    >
                </div>
                
                <button type="submit" id="submitBtn" class="btn-primary">
                    Verify Email
                </button>
                
                <div id="message" class="message"></div>
                
                <div style="text-align: center; margin-top: 15px;">
                    <p style="color: #666;">Didn't receive code? 
                        <a href="#" id="resendLink" style="color: #667eea; font-weight: 600;">Resend</a>
                    </p>
                </div>
            </form>
            
            <div class="auth-footer">
                <p><a href="register.php">Back to registration</a></p>
            </div>
        </div>
    </div>
    
    <script>
    const API_BASE = 'http://localhost/connecthub/api';
    const otpForm = document.getElementById('otpForm');
    
    otpForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const messageDiv = document.getElementById('message');
        const userId = document.getElementById('userId').value;
        const otpCode = document.getElementById('otpCode').value;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verifying...';
        
        try {
            const response = await fetch(`${API_BASE}/auth/verify-otp.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, otp_code: otpCode })
            });
            
            const data = await response.json();
            
            if (data.success) {
                messageDiv.textContent = data.message;
                messageDiv.className = 'message success';
                
                setTimeout(() => {
                    window.location.href = 'login.php?verified=1';
                }, 2000);
            } else {
                messageDiv.textContent = data.message;
                messageDiv.className = 'message error';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Verify Email';
            }
        } catch (error) {
            console.error('Verification error:', error);
            messageDiv.textContent = 'An error occurred. Please try again.';
            messageDiv.className = 'message error';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Verify Email';
        }
    });
    </script>
</body>
</html>
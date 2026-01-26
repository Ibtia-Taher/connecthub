<?php
/**
 * Email Utility
 * Sends emails for OTP verification
 * 
 * NOTE: For production, use services like SendGrid, Mailgun, or AWS SES
 * This uses PHP's mail() function which requires server configuration
 */

/**
 * Send OTP email
 * @param string $toEmail
 * @param string $toName
 * @param string $otpCode
 * @return bool
 */
function sendOTPEmail($toEmail, $toName, $otpCode) {
    $subject = "Verify Your " . APP_NAME . " Account";
    
    // HTML email template
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                      color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; padding: 20px; text-align: center; 
                       border-radius: 8px; margin: 20px 0; border: 2px dashed #667eea; }
            .otp-code { font-size: 32px; font-weight: bold; color: #667eea; 
                        letter-spacing: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . APP_NAME . "</h1>
                <p>Welcome aboard! Let's verify your email.</p>
            </div>
            <div class='content'>
                <p>Hi <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                <p>Thank you for signing up! Please use the following OTP code to verify your email address:</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>" . $otpCode . "</div>
                </div>
                
                <p><strong>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</strong></p>
                <p>If you didn't create an account, please ignore this email.</p>
                
                <div class='footer'>
                    <p>&copy; 2026 " . APP_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . APP_NAME . " <noreply@connecthub.com>" . "\r\n";
    
    // Send email
    // NOTE: In development, this might not work without mail server configuration
    // For testing, you can use services like Mailtrap.io or log OTP to file instead
    
    // Development fallback: Log OTP to file
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        $logFile = __DIR__ . '/../logs/otp.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logMessage = date('Y-m-d H:i:s') . " - Email: $toEmail - OTP: $otpCode\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return true; // Simulate successful send
    }
    
    // Production: Actually send email
    return mail($toEmail, $subject, $message, $headers);
}

/**
 * Generate random OTP code
 * @param int $length
 * @return string
 */
function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}
?>
<?php
/**
 * Email Utility
 * Sends OTP emails via Gmail SMTP using PHPMailer
 *
 * Setup:
 *   composer require phpmailer/phpmailer
 *
 * Gmail Setup:
 *   1. Enable 2-Step Verification on your Google account
 *   2. Go to myaccount.google.com/apppasswords
 *   3. Generate an App Password for "Mail"
 *   4. Paste it in GMAIL_APP_PASSWORD below (or move to config)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ─── Gmail SMTP Credentials ───────────────────────────────────────────────────
// Move these to your config.php or a .env file to keep them out of source code
define('GMAIL_USERNAME',     'ibtiataher9@gmail.com');   // Your Gmail address
define('GMAIL_APP_PASSWORD', 'xhru merj wkmw dhiq');    // Gmail App Password (NOT your login password)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Send OTP email via Gmail SMTP
 *
 * @param string $toEmail  Recipient email address
 * @param string $toName   Recipient display name
 * @param string $otpCode  The OTP code to send
 * @return bool            True on success, false on failure
 */
function sendOTPEmail($toEmail, $toName, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // ── SMTP Configuration ──────────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USERNAME;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ── Sender & Recipient ──────────────────────────────────────────────
        $mail->setFrom(GMAIL_USERNAME, APP_NAME);
        $mail->addAddress($toEmail, $toName);

        // ── Email Content ───────────────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = "Verify Your " . APP_NAME . " Account";
        $mail->Body    = getOTPEmailTemplate($toName, $otpCode);
        $mail->AltBody = "Hi $toName, your OTP code is: $otpCode. It expires in " . OTP_EXPIRY_MINUTES . " minutes.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the error without exposing it to the user
        error_log("PHPMailer error for $toEmail: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Build the HTML email template
 *
 * @param string $toName
 * @param string $otpCode
 * @return string
 */
function getOTPEmailTemplate($toName, $otpCode) {
    return "
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
                    <p>&copy; " . date('Y') . " " . APP_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Generate a random numeric OTP code
 *
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
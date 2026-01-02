<?php
// Email Configuration
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Replace with your SMTP host
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'your-email@gmail.com'); // Replace with your email
define('SMTP_PASSWORD', 'your-app-password'); // Replace with your email password or app password
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
define('SMTP_FROM_EMAIL', 'noreply@mehedishop.com'); // Replace with your from email
define('SMTP_FROM_NAME', 'Mehedi Shop');

// Email Helper Functions

// Send email using SMTP
function sendEmail($to, $subject, $message, $isHTML = true) {
    // Check if PHPMailer is available
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        return sendEmailWithPHPMailer($to, $subject, $message, $isHTML);
    } else {
        // Fallback to PHP mail() function
        return sendEmailWithPHP($to, $subject, $message, $isHTML);
    }
}

// Send email using PHPMailer (if available)
function sendEmailWithPHPMailer($to, $subject, $message, $isHTML = true) {
    try {

        
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        
        // Sender and Recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Email Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        if (!$isHTML) {
            $mail->AltBody = strip_tags($message);
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
    }
}

// Send email using PHP mail() function (fallback)
function sendEmailWithPHP($to, $subject, $message, $isHTML = true) {
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    
    $success = mail($to, $subject, $message, $headers);
    
    if ($success) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send email'];
    }
}

// Send OTP email
function sendOTPEmail($to, $otp_code, $expiry_minutes = 15) {
    $subject = 'Password Reset OTP - Mehedi Shop';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #3399cc; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background-color: #f9f9f9; }
            .otp-box { background-color: #fff; border: 2px dashed #3399cc; padding: 20px; text-align: center; margin: 20px 0; }
            .otp-code { font-size: 32px; font-weight: bold; color: #3399cc; letter-spacing: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Mehedi Shop</h1>
                <h2>Password Reset Request</h2>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>You have requested to reset your password. Use the OTP code below to verify your identity:</p>
                
                <div class="otp-box">
                    <p style="margin: 0; color: #666;">Your OTP Code:</p>
                    <div class="otp-code">' . htmlspecialchars($otp_code) . '</div>
                </div>
                
                <p>Enter this code on the password reset page to continue.</p>
                
                <div class="warning">
                    <strong>⚠️ Important:</strong> This OTP code will expire in ' . $expiry_minutes . ' minutes. Do not share this code with anyone.
                </div>
                
                <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
                
                <p>Thank you,<br>Mehedi Shop Team</p>
            </div>
            <div class="footer">
                <p>This is an automated email. Please do not reply to this message.</p>
                <p>&copy; ' . date('Y') . ' Mehedi Shop. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($to, $subject, $message, true);
}

?>




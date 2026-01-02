# Forgot Password Setup Instructions

## Overview
The forgot password feature allows users to reset their password using a 6-digit OTP code sent to their email address.

## Database Setup

### 1. Create Password Resets Table
The `password_resets` table is already included in `database.sql`. If you need to add it manually:

```sql
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_otp (otp_code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Email Configuration

### 2. Configure SMTP Settings
Edit `config/email.php` and update the following SMTP settings:

```php
define('SMTP_HOST', 'smtp.gmail.com'); // Your SMTP host
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your email
define('SMTP_PASSWORD', 'your-app-password'); // Your email password
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
define('SMTP_FROM_EMAIL', 'noreply@mehedishop.com'); // From email
define('SMTP_FROM_NAME', 'Mehedi Shop'); // From name
```

### 3. Email Sending Options

**Option A: Using PHPMailer (Recommended)**
- Install PHPMailer via Composer: `composer require phpmailer/phpmailer`
- The system will automatically use PHPMailer if available

**Option B: Using PHP mail() Function**
- Works without additional dependencies
- May not work on all servers
- Less reliable than SMTP

## How It Works

### User Flow:
1. User clicks "Forgot Password?" on login page
2. User enters email address
3. System generates 6-digit OTP code
4. OTP code is sent to user's email
5. User enters OTP code on verify page
6. System validates OTP (expires in 15 minutes)
7. User enters new password
8. Password is updated and user can login

### Security Features:
- OTP expires after 15 minutes
- OTP can only be used once
- Rate limiting: Max 3 requests per email per hour
- Generic error messages (doesn't reveal if email exists)
- Password validation (minimum 6 characters)
- Secure password hashing using `password_hash()`

## Testing

### Test the Forgot Password Flow:
1. Go to login page
2. Click "Forgot Password?" link
3. Enter a registered email address
4. Check email for OTP code
5. Enter OTP code on verify page
6. Set new password
7. Login with new password

### Gmail Setup (If using Gmail SMTP):
1. Enable 2-Step Verification on your Google account
2. Generate an App Password:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this password in `config/email.php`

## Troubleshooting

### Email Not Sending:
- Check SMTP credentials are correct
- Verify firewall allows SMTP connections
- Check spam folder for OTP emails
- Enable error logging to see email errors
- Try using PHPMailer instead of mail()

### OTP Not Working:
- Check OTP hasn't expired (15 minutes)
- Verify OTP hasn't been used already
- Check rate limiting (max 3 per hour)
- Ensure database table is created correctly

### Database Issues:
- Verify `password_resets` table exists
- Check foreign key constraints are correct
- Ensure indexes are created for performance

## Files Created:
- `config/email.php` - Email configuration
- `includes/password_reset.php` - Password reset functions
- `forgot_password.php` - Forgot password page
- `verify_otp.php` - OTP verification page
- `reset_password.php` - Reset password page
- `login.php` - Updated with forgot password link

## Maintenance

### Cleanup Expired OTPs:
The system automatically cleans expired OTPs when:
- New password reset is requested
- OTP is validated
- Manual cleanup can be done with:
  ```sql
  DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1;
  ```

### Optional: Scheduled Cleanup
Create a cron job to clean expired OTPs daily:
```bash
0 0 * * * php /path/to/cleanup_otps.php
```




<?php
// Password Reset Helper Functions

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

// Generate 6-digit OTP code
function generateOTP($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Create password reset record
function createPasswordReset($conn, $user_id, $email) {
    // Clean expired OTPs first
    cleanExpiredOTPs($conn);
    
    // Check rate limiting (max 3 requests per email per hour)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM password_resets 
        WHERE email = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        AND used = 0
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['count'] >= 3) {
        return ['success' => false, 'error' => 'Too many reset requests. Please try again later.'];
    }
    
    // Generate OTP
    $otp_code = generateOTP();
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Mark all previous unused OTPs for this email as expired
    $stmt = $conn->prepare("
        UPDATE password_resets 
        SET used = 1 
        WHERE email = ? AND used = 0
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
    
    // Create new reset record
    $stmt = $conn->prepare("
        INSERT INTO password_resets (user_id, email, otp_code, expires_at) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $email, $otp_code, $expires_at);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'otp_code' => $otp_code];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => 'Failed to create reset request: ' . $error];
    }
}

// Validate OTP code
function validateOTP($conn, $email, $otp_code) {
    // Clean expired OTPs first
    cleanExpiredOTPs($conn);
    
    $stmt = $conn->prepare("
        SELECT * FROM password_resets 
        WHERE email = ? AND otp_code = ? AND used = 0 AND expires_at > NOW()
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $otp_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $reset = $result->fetch_assoc();
        $stmt->close();
        return ['success' => true, 'reset_id' => $reset['id'], 'user_id' => $reset['user_id']];
    } else {
        $stmt->close();
        return ['success' => false, 'error' => 'Invalid or expired OTP code.'];
    }
}

// Mark OTP as used
function markOTPAsUsed($conn, $reset_id) {
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $stmt->bind_param("i", $reset_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Clean expired OTPs
function cleanExpiredOTPs($conn) {
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
    $stmt->execute();
    $stmt->close();
}

// Reset user password
function resetUserPassword($conn, $user_id, $new_password) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Get user by email
function getUserByEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

?>




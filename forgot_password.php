<?php
$page_title = 'Forgot Password';
require_once 'includes/header.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

require_once 'config/database.php';
require_once 'includes/password_reset.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Handle resend OTP
if (isset($_GET['resend']) && isset($_GET['email'])) {
    $resend_email = sanitize($_GET['email']);
    $conn = getDBConnection();
    $user = getUserByEmail($conn, $resend_email);
    
    if ($user) {
        $result = createPasswordReset($conn, $user['id'], $resend_email);
        if ($result['success']) {
            $emailResult = sendOTPEmail($resend_email, $result['otp_code'], 15);
            if ($emailResult['success']) {
                $_SESSION['reset_email'] = $resend_email;
                $success = 'New OTP code has been sent to your email address.';
            } else {
                $error = 'Failed to send email. Please try again later.';
            }
        } else {
            $error = $result['error'];
        }
    } else {
        $error = 'Email address not found.';
    }
    closeDBConnection($conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $conn = getDBConnection();
        
        // Check if user exists (but show generic message for security)
        $user = getUserByEmail($conn, $email);
        
        if ($user) {
            // Create password reset request
            $result = createPasswordReset($conn, $user['id'], $email);
            
            if ($result['success']) {
                // Send OTP email
                $emailResult = sendOTPEmail($email, $result['otp_code'], 15);
                
                if ($emailResult['success']) {
                    // Store email in session for verification step
                    $_SESSION['reset_email'] = $email;
                    $success = 'OTP code has been sent to your email address. Please check your inbox.';
                } else {
                    $error = 'Failed to send email. Please try again later or contact support.';
                }
            } else {
                $error = $result['error'];
            }
        } else {
            // Generic message for security (don't reveal if email exists)
            $success = 'If an account exists with this email, an OTP code has been sent. Please check your inbox.';
        }
        
        closeDBConnection($conn);
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Forgot Password</h2>
                    <p class="text-center text-muted mb-4">
                        Enter your email address and we'll send you an OTP code to reset your password.
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <?php if (isset($_SESSION['reset_email'])): ?>
                                <div class="mt-3">
                                    <a href="verify_otp.php" class="btn btn-primary btn-sm">Enter OTP Code</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($success)): ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                   placeholder="Enter your email address" required autofocus>
                            <small class="text-muted">We'll send a 6-digit OTP code to this email.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Send OTP Code</button>
                    </form>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none me-3">
                            <i class="bi bi-arrow-left"></i> Back to Login
                        </a>
                        <a href="index.php" class="text-decoration-none">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>


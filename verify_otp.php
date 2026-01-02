<?php
$page_title = 'Verify OTP';
require_once 'includes/header.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

require_once 'config/database.php';
require_once 'includes/password_reset.php';
require_once 'includes/functions.php';

$error = '';
$email = $_SESSION['reset_email'] ?? $_GET['email'] ?? '';

// Redirect if no email
if (empty($email)) {
    redirect('forgot_password.php');
}

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = sanitize($_POST['otp_code'] ?? '');
    
    if (empty($otp_code)) {
        $error = 'Please enter the OTP code.';
    } elseif (!preg_match('/^\d{6}$/', $otp_code)) {
        $error = 'OTP code must be 6 digits.';
    } else {
        // Validate OTP
        $result = validateOTP($conn, $email, $otp_code);
        
        if ($result['success']) {
            // Store reset info in session for password reset page
            $_SESSION['reset_token'] = $result['reset_id'];
            $_SESSION['reset_user_id'] = $result['user_id'];
            $_SESSION['reset_email'] = $email;
            
            closeDBConnection($conn);
            redirect('reset_password.php');
        } else {
            $error = $result['error'];
        }
    }
}

// Get user info for display
$user = getUserByEmail($conn, $email);
closeDBConnection($conn);
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Verify OTP Code</h2>
                    <p class="text-center text-muted mb-4">
                        Enter the 6-digit OTP code sent to:<br>
                        <strong><?php echo htmlspecialchars($email); ?></strong>
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        
                        <div class="mb-3">
                            <label for="otp_code" class="form-label">OTP Code</label>
                            <input type="text" class="form-control text-center" id="otp_code" name="otp_code" 
                                   placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus
                                   style="font-size: 24px; letter-spacing: 10px;">
                            <small class="text-muted">Check your email for the 6-digit code. It expires in 15 minutes.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Verify OTP</button>
                    </form>
                    
                    <div class="text-center mb-3">
                        <a href="forgot_password.php?resend=1&email=<?php echo urlencode($email); ?>" class="text-decoration-none">
                            Resend OTP Code
                        </a>
                    </div>
                    
                    <div class="text-center">
                        <a href="forgot_password.php" class="text-decoration-none me-3">
                            <i class="bi bi-arrow-left"></i> Back to Forgot Password
                        </a>
                        <a href="login.php" class="text-decoration-none me-3">
                            <i class="bi bi-box-arrow-in-left"></i> Login
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

<script>
// Auto-format OTP input
document.getElementById('otp_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 6);
});
</script>

<?php require_once 'includes/footer.php'; ?>


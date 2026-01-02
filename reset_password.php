<?php
$page_title = 'Reset Password';
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

// Check if reset token exists in session
$reset_token = $_SESSION['reset_token'] ?? null;
$user_id = $_SESSION['reset_user_id'] ?? null;
$email = $_SESSION['reset_email'] ?? '';

if (!$reset_token || !$user_id) {
    redirect('forgot_password.php');
}

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Verify reset token is still valid
        $stmt = $conn->prepare("
            SELECT * FROM password_resets 
            WHERE id = ? AND user_id = ? AND email = ? AND used = 0 AND expires_at > NOW()
        ");
        $stmt->bind_param("iis", $reset_token, $user_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset_data = $result->fetch_assoc();
        $stmt->close();
        
        if ($reset_data) {
            // Reset password
            if (resetUserPassword($conn, $user_id, $new_password)) {
                // Mark OTP as used
                markOTPAsUsed($conn, $reset_token);
                
                // Clear reset session data
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_email']);
                
                closeDBConnection($conn);
                
                // Redirect to login with success message
                redirect('login.php?reset=success');
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        } else {
            $error = 'Reset token is invalid or expired. Please request a new password reset.';
        }
    }
}

// Get user info for display
$user = getUserById($conn, $user_id);
closeDBConnection($conn);
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Reset Password</h2>
                    <p class="text-center text-muted mb-4">
                        Enter your new password for:<br>
                        <strong><?php echo htmlspecialchars($user['email'] ?? $email); ?></strong>
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="resetForm">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Enter new password" required minlength="6">
                            <small class="text-muted">Password must be at least 6 characters long.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm new password" required minlength="6">
                        </div>
                        
                        <div id="passwordMatch" class="mb-3" style="display: none;"></div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Reset Password</button>
                    </form>
                    
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

<script>
// Password match validation
const form = document.getElementById('resetForm');
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const passwordMatch = document.getElementById('passwordMatch');

function checkPasswordMatch() {
    if (confirmPassword.value.length > 0) {
        if (newPassword.value !== confirmPassword.value) {
            passwordMatch.innerHTML = '<div class="alert alert-danger">Passwords do not match.</div>';
            passwordMatch.style.display = 'block';
        } else {
            passwordMatch.innerHTML = '<div class="alert alert-success">Passwords match.</div>';
            passwordMatch.style.display = 'block';
        }
    } else {
        passwordMatch.style.display = 'none';
    }
}

newPassword.addEventListener('input', checkPasswordMatch);
confirmPassword.addEventListener('input', checkPasswordMatch);

form.addEventListener('submit', function(e) {
    if (newPassword.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>


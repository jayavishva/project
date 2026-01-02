<?php
$page_title = 'My Profile';
require_once 'includes/header.php';
requireLogin();

$conn = getDBConnection();
$user = getUserById($conn, $_SESSION['user_id']);

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $phone, $address, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $user = getUserById($conn, $_SESSION['user_id']); // Refresh user data
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile.';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to change password.';
        }
    }
}

closeDBConnection($conn);
?>

<h2 class="mb-4">My Profile</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <!-- Profile Information -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5>Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="4"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <input type="text" class="form-control" 
                               value="<?php echo ucfirst($user['role']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Member Since</label>
                        <input type="text" class="form-control" 
                               value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card shadow">
            <div class="card-header">
                <h5>Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small class="text-muted">Password must be at least 6 characters long</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Quick Links -->
        <div class="card shadow">
            <div class="card-header">
                <h5>Quick Links</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="orders.php" class="btn btn-outline-primary w-100 text-start">
                            <i class="bi bi-bag"></i> My Orders
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="cart.php" class="btn btn-outline-primary w-100 text-start">
                            <i class="bi bi-cart"></i> Shopping Cart
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="index.php" class="btn btn-outline-primary w-100 text-start">
                            <i class="bi bi-house"></i> Continue Shopping
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>



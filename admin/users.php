<?php
$page_title = 'Manage Users';
require_once 'header.php';

$conn = getDBConnection();
$error = '';
$success = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user']) || isset($_POST['update_user'])) {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = sanitize($_POST['full_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $role = sanitize($_POST['role'] ?? 'customer');
        
        if (empty($username) || empty($email) || empty($full_name)) {
            $error = 'Username, email, and full name are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } else {
            if (isset($_POST['update_user'])) {
                // Update user
                $user_id = intval($_POST['user_id']);
                $existing_user = getUserById($conn, $user_id);
                
                if ($existing_user) {
                    // Check if username or email already exists (excluding current user)
                    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $stmt->bind_param("ssi", $username, $email, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = 'Username or email already exists.';
                    } else {
                        // Update user
                        if (!empty($password)) {
                            if (strlen($password) < 6) {
                                $error = 'Password must be at least 6 characters long.';
                            } else {
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $conn->prepare("
                                    UPDATE users 
                                    SET username = ?, email = ?, password = ?, full_name = ?, 
                                        phone = ?, address = ?, role = ? 
                                    WHERE id = ?
                                ");
                                $stmt->bind_param("sssssssi", $username, $email, $hashed_password, $full_name, $phone, $address, $role, $user_id);
                            }
                        } else {
                            // Update without password
                            $stmt = $conn->prepare("
                                UPDATE users 
                                SET username = ?, email = ?, full_name = ?, 
                                    phone = ?, address = ?, role = ? 
                                WHERE id = ?
                            ");
                            $stmt->bind_param("ssssssi", $username, $email, $full_name, $phone, $address, $role, $user_id);
                        }
                        
                        if (empty($error) && $stmt->execute()) {
                            $success = 'User updated successfully!';
                        } else if (empty($error)) {
                            $error = 'Failed to update user.';
                        }
                    }
                }
            } else {
                // Add new user
                if (empty($password)) {
                    $error = 'Password is required for new users.';
                } elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } else {
                    // Check if username or email already exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->bind_param("ss", $username, $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = 'Username or email already exists.';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("
                            INSERT INTO users (username, email, password, full_name, phone, address, role) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param("sssssss", $username, $email, $hashed_password, $full_name, $phone, $address, $role);
                        
                        if ($stmt->execute()) {
                            $success = 'User added successfully!';
                        } else {
                            $error = 'Failed to add user.';
                        }
                    }
                }
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Prevent deleting own account
        if ($user_id == $_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } else {
            $user = getUserById($conn, $user_id);
            
            if ($user) {
                // Check if user has orders
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $order_count = $result->fetch_assoc()['count'];
                
                if ($order_count > 0) {
                    $error = 'Cannot delete user with existing orders. User has ' . $order_count . ' order(s).';
                } else {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $success = 'User deleted successfully!';
                    } else {
                        $error = 'Failed to delete user.';
                    }
                }
            }
        }
    }
}

// Get users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_user = getUserById($conn, $edit_id);
}

closeDBConnection($conn);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Users</h2>
    <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Add/Edit User Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php if ($edit_user): ?>
                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($edit_user['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="customer" <?php echo ($edit_user['role'] ?? 'customer') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="admin" <?php echo ($edit_user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <?php echo $edit_user ? 'New Password (leave blank to keep current)' : 'Password *'; ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               <?php echo $edit_user ? '' : 'required'; ?>>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
            </div>
            
            <?php if ($edit_user): ?>
                <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h5>All Users</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" action="" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">(Current User)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mehedi_shop');

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Helper functions
// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
// Optionally accepts database connection for verification against database
// If $conn is provided, verifies user exists and has admin role in database
function isAdmin($conn = null, $force_verify = false) {
    // First check session variable
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    // If no database connection provided, use session check only (backward compatibility)
    if ($conn === null) {
        return $_SESSION['user_role'] === 'admin';
    }
    
    // Cache verification result to avoid excessive database queries
    // Cache expires after 5 minutes (300 seconds) or if force_verify is true
    $cache_key = 'admin_verified_' . $_SESSION['user_id'];
    $cache_expiry = 300; // 5 minutes
    
    if (!$force_verify && isset($_SESSION[$cache_key])) {
        $cache_data = $_SESSION[$cache_key];
        if (time() - $cache_data['timestamp'] < $cache_expiry) {
            return $cache_data['is_admin'];
        }
    }
    
    // Verify against database
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User doesn't exist in database - clear session cache
        unset($_SESSION[$cache_key]);
        return false;
    }
    
    $user = $result->fetch_assoc();
    $is_admin = $user['role'] === 'admin';
    
    // Update session role if it changed in database
    if ($_SESSION['user_role'] !== $user['role']) {
        $_SESSION['user_role'] = $user['role'];
    }
    
    // Cache the result
    $_SESSION[$cache_key] = [
        'is_admin' => $is_admin,
        'timestamp' => time()
    ];
    
    return $is_admin;
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Format price
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

// Get user by ID
function getUserById($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get product by ID
function getProductById($conn, $product_id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get cart count for user
function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Get cart total for user
function getCartTotal($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT SUM(c.quantity * p.price) as total 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Validate image upload
function validateImage($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    return ['success' => true];
}

// Upload image
function uploadImage($file, $uploadDir = 'uploads/products/') {
    $validation = validateImage($file);
    if (!$validation['success']) {
        return $validation;
    }
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

// Delete image file
function deleteImageFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        unlink($filepath);
    }
}

// Authentication helper functions
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Revalidate user session against database
// Refreshes session data and verifies user still exists with correct role
function revalidateUserSession($conn) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, username, email, full_name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User no longer exists - destroy session
        session_destroy();
        return false;
    }
    
    $user = $result->fetch_assoc();
    
    // Update session with current user data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    
    // Clear admin verification cache to force re-check
    $cache_key = 'admin_verified_' . $user_id;
    unset($_SESSION[$cache_key]);
    
    return true;
}

// Require admin
// Verifies user is logged in and has admin role (with database verification)
function requireAdmin() {
    requireLogin();
    
    // Get database connection for verification
    $conn = getDBConnection();
    
    // Revalidate session to ensure user still exists
    if (!revalidateUserSession($conn)) {
        closeDBConnection($conn);
        redirect('login.php');
    }
    
    // Verify admin role against database (force verification)
    if (!isAdmin($conn, true)) {
        closeDBConnection($conn);
        redirect('index.php');
    }
    
    closeDBConnection($conn);
}

// Check if user is logged in and redirect if already logged in
function requireGuest() {
    if (isLoggedIn()) {
        redirect('index.php');
    }
}

requireGuest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All required fields must be filled.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $conn = getDBConnection();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                // Clear form
                $username = $email = $full_name = $phone = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mehedi Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-flower1"></i> Mehedi Shop
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="login.php">Login</a>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Create Account</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Register</button>
                        </form>
                        
                        <p class="text-center">
                            Already have an account? <a href="login.php">Login here</a>
                        </p>
                        <p class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to Home
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

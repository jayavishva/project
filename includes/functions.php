<?php
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

// Format quantity display
function formatQuantity($value, $unit) {
    if (empty($value) || empty($unit) || $unit === 'None') {
        return null;
    }
    
    // Format value - remove trailing zeros if not needed
    $formatted_value = rtrim(rtrim(number_format($value, 2), '0'), '.');
    
    return $formatted_value . $unit;
}
?>



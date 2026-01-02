<?php
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
    require_once __DIR__ . '/../config/database.php';
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
?>



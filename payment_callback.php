<?php
// Payment Callback Handler
require_once 'config/payment.php';
require_once 'config/database.php';
require_once 'includes/payment_handler.php';
require_once 'includes/functions.php';

session_start();

$conn = getDBConnection();
$error = '';
$success = false;
$order_id = 0;

// Handle Razorpay callback
if (isset($_GET['razorpay_payment_id']) && isset($_GET['razorpay_order_id']) && isset($_GET['razorpay_signature'])) {
    $razorpay_payment_id = sanitize($_GET['razorpay_payment_id']);
    $razorpay_order_id = sanitize($_GET['razorpay_order_id']);
    $razorpay_signature = sanitize($_GET['razorpay_signature']);
    $order_id = intval($_GET['order_id'] ?? 0);
    
    // Verify payment
    $verification = verifyRazorpayPayment($razorpay_order_id, $razorpay_payment_id, $razorpay_signature);
    
    if ($verification['success'] && $order_id > 0) {
        // Verify order belongs to user
        $stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['user_id'] == $_SESSION['user_id']) {
            // Update order payment status
            if (updateOrderPaymentStatus($conn, $order_id, 'success', $razorpay_payment_id, $razorpay_order_id)) {
                // Update stock after successful payment
                $orderItemsStmt = $conn->prepare("
                    SELECT product_id, quantity 
                    FROM order_items 
                    WHERE order_id = ?
                ");
                $orderItemsStmt->bind_param("i", $order_id);
                $orderItemsStmt->execute();
                $itemsResult = $orderItemsStmt->get_result();
                
                while ($item = $itemsResult->fetch_assoc()) {
                    $stockStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stockStmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stockStmt->execute();
                    $stockStmt->close();
                }
                $orderItemsStmt->close();
                
                // Clear cart after successful payment
                $cartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $cartStmt->bind_param("i", $_SESSION['user_id']);
                $cartStmt->execute();
                $cartStmt->close();
                
                // Unset pending order ID
                unset($_SESSION['pending_order_id']);
                
                $success = true;
                closeDBConnection($conn);
                header('Location: payment_status.php?order_id=' . $order_id . '&status=success');
                exit;
            } else {
                $error = 'Failed to update order status.';
            }
        } else {
            $error = 'Invalid order.';
        }
        $stmt->close();
    } else {
        // Payment verification failed
        if ($order_id > 0) {
            updateOrderPaymentStatus($conn, $order_id, 'failed', $razorpay_payment_id, $razorpay_order_id);
        }
        $error = 'Payment verification failed. ' . ($verification['error'] ?? '');
    }
}
// Handle manual card payment form submission
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $payment_method = sanitize($_POST['payment_method'] ?? 'Card');
    $razorpay_order_id = sanitize($_POST['razorpay_order_id'] ?? '');
    
    // For manual card entry, we'll use Razorpay Checkout
    // This is just a fallback - in production, always use Razorpay's secure checkout
    if (!empty($razorpay_order_id)) {
        // Redirect to Razorpay checkout
        $order = getOrderById($conn, $order_id);
        if ($order && $order['user_id'] == $_SESSION['user_id']) {
            header('Location: payment_process.php?order_id=' . $order_id);
            exit;
        }
    } else {
        $error = 'Invalid payment request.';
    }
}

closeDBConnection($conn);

// Redirect to status page if payment failed
if (!empty($error) && $order_id > 0) {
    header('Location: payment_status.php?order_id=' . $order_id . '&status=failed&error=' . urlencode($error));
    exit;
}

// Default redirect
if (!$success) {
    redirect('checkout.php');
}

// Helper function to get order by ID
function getOrderById($conn, $order_id) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

?>


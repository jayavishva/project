<?php
// Payment Handler Functions

require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../config/database.php';

// Initialize Razorpay API
function getRazorpayApi() {
    if (!isRazorpayAvailable()) {
        return null;
    }
    
    try {
        // Try to use Composer autoload first
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        } elseif (file_exists(__DIR__ . '/razorpay/razorpay-php/src/Razorpay.php')) {
            // Manual include
            require_once __DIR__ . '/razorpay/razorpay-php/src/Razorpay.php';
        } else {
            // Try loading Razorpay SDK directly if available
            require_once __DIR__ . '/../vendor/razorpay/razorpay/src/Razorpay.php';
        }
        
        // Use namespace if available
        if (class_exists('Razorpay\Api\Api')) {
            $api = new \Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
            return $api;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Razorpay initialization error: " . $e->getMessage());
        return null;
    }
}

// Create Razorpay order
function createRazorpayOrder($amount, $receipt_id, $notes = []) {
    $api = getRazorpayApi();
    if (!$api) {
        return ['success' => false, 'error' => 'Payment gateway not available'];
    }
    
    try {
        $orderData = [
            'receipt' => $receipt_id,
            'amount' => $amount * 100, // Convert to paise
            'currency' => CURRENCY,
            'notes' => $notes
        ];
        
        $razorpayOrder = $api->order->create($orderData);
        
        return [
            'success' => true,
            'order_id' => $razorpayOrder['id'],
            'amount' => $razorpayOrder['amount'],
            'currency' => $razorpayOrder['currency']
        ];
    } catch (Exception $e) {
        error_log("Razorpay order creation error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Verify Razorpay payment signature
function verifyRazorpayPayment($razorpay_order_id, $razorpay_payment_id, $razorpay_signature) {
    $api = getRazorpayApi();
    if (!$api) {
        return ['success' => false, 'error' => 'Payment gateway not available'];
    }
    
    try {
        $attributes = [
            'razorpay_order_id' => $razorpay_order_id,
            'razorpay_payment_id' => $razorpay_payment_id,
            'razorpay_signature' => $razorpay_signature
        ];
        
        $api->utility->verifyPaymentSignature($attributes);
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Payment verification error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Update order payment status
function updateOrderPaymentStatus($conn, $order_id, $payment_status, $transaction_id = null, $razorpay_order_id = null) {
    $stmt = $conn->prepare("
        UPDATE orders 
        SET payment_status = ?, 
            payment_transaction_id = ?,
            razorpay_order_id = COALESCE(?, razorpay_order_id)
        WHERE id = ?
    ");
    
    $stmt->bind_param("sssi", $payment_status, $transaction_id, $razorpay_order_id, $order_id);
    
    if ($stmt->execute()) {
        // If payment successful, update order status to processing
        if ($payment_status === 'success') {
            $updateStmt = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending'");
            $updateStmt->bind_param("i", $order_id);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        $stmt->close();
        return true;
    }
    
    $stmt->close();
    return false;
}

// Process card payment via Razorpay
function processCardPayment($razorpay_order_id, $payment_id, $card_details, $amount) {
    $api = getRazorpayApi();
    if (!$api) {
        return ['success' => false, 'error' => 'Payment gateway not available'];
    }
    
    try {
        // For card payments, we typically use Razorpay's Checkout
        // The payment is processed on Razorpay's secure page
        // We just need to verify the payment signature
        return ['success' => true, 'requires_redirect' => true];
    } catch (Exception $e) {
        error_log("Card payment processing error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Generate payment form data for Razorpay Checkout
function generateRazorpayCheckoutData($order_id, $amount, $customer_name, $customer_email, $customer_contact) {
    $orderData = createRazorpayOrder($amount, 'order_' . $order_id, [
        'order_id' => $order_id,
        'customer_name' => $customer_name
    ]);
    
    if (!$orderData['success']) {
        return null;
    }
    
    return [
        'key' => RAZORPAY_KEY_ID,
        'amount' => $orderData['amount'],
        'currency' => CURRENCY,
        'name' => MERCHANT_NAME,
        'description' => 'Order #' . $order_id,
        'order_id' => $orderData['order_id'],
        'prefill' => [
            'name' => $customer_name,
            'email' => $customer_email,
            'contact' => $customer_contact
        ],
        'theme' => [
            'color' => '#3399cc'
        ]
    ];
}

?>


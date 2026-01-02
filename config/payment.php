<?php
// Payment Gateway Configuration

// Razorpay Configuration
define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_KEY_ID'); // Replace with your Razorpay Key ID
define('RAZORPAY_KEY_SECRET', 'YOUR_SECRET_KEY'); // Replace with your Razorpay Secret Key
define('RAZORPAY_MODE', 'test'); // 'test' or 'live'

// GPay/UPI Configuration
define('MERCHANT_UPI_ID', 'jayavishva3@oksbi'); // Replace with your UPI ID
define('MERCHANT_NAME', 'Mehedi Shop');
define('MERCHANT_PAYMENT_LINK', ''); // Optional: Razorpay payment link

// Payment Settings
define('CURRENCY', 'INR');
define('PAYMENT_TIMEOUT', 600); // 10 minutes in seconds

// Check if Razorpay SDK is available
function isRazorpayAvailable() {
    return file_exists(__DIR__ . '/../vendor/razorpay/razorpay/src/Razorpay.php') || 
           file_exists(__DIR__ . '/../includes/razorpay/razorpay-php/src/Razorpay.php') ||
           file_exists(__DIR__ . '/../vendor/autoload.php');
}

// Generate GPay/UPI payment link
function generateGPayLink($amount, $orderId, $description = '') {
    $upiLink = 'upi://pay?pa=' . urlencode(MERCHANT_UPI_ID) . 
               '&pn=' . urlencode(MERCHANT_NAME) . 
               '&am=' . urlencode(number_format($amount, 2, '.', '')) . 
               '&cu=' . CURRENCY;
    
    if (!empty($description)) {
        $upiLink .= '&tn=' . urlencode($description);
    }
    
    return $upiLink;
}

// Get payment gateway URL based on environment
function getPaymentGatewayUrl() {
    if (RAZORPAY_MODE === 'test') {
        return 'https://api.razorpay.com/v1/';
    } else {
        return 'https://api.razorpay.com/v1/';
    }
}

?>


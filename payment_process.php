<?php
// Payment Processing Handler
$page_title = 'Processing Payment';

require_once 'includes/header.php';
requireLogin();

require_once 'config/payment.php';
require_once 'config/database.php';
require_once 'includes/payment_handler.php';
require_once 'includes/functions.php';

$conn = getDBConnection();

// Get order ID from session or URL
$order_id = intval($_GET['order_id'] ?? $_SESSION['pending_order_id'] ?? 0);

if ($order_id <= 0) {
    closeDBConnection($conn);
    redirect('checkout.php');
}

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    closeDBConnection($conn);
    redirect('checkout.php');
}

$user = getUserById($conn, $_SESSION['user_id']);
$payment_method = $order['payment_method'];

closeDBConnection($conn);

// Generate Razorpay order if needed
$razorpay_data = null;
if (in_array($payment_method, ['GPay', 'Card', 'UPI'])) {
    if (isRazorpayAvailable()) {
        $razorpay_data = generateRazorpayCheckoutData(
            $order_id,
            $order['total_amount'],
            $user['full_name'],
            $user['email'],
            $order['phone']
        );
        
        if ($razorpay_data) {
            // Store razorpay_order_id in database
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?");
            $stmt->bind_param("si", $razorpay_data['order_id'], $order_id);
            $stmt->execute();
            closeDBConnection($conn);
        }
    } else {
        $error = 'Payment gateway is not configured. Please contact administrator.';
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Complete Your Payment</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="checkout.php" class="btn btn-secondary">Back to Checkout</a>
                        </div>
                    <?php else: ?>
                    <div class="text-center mb-4">
                        <h5>Order #<?php echo $order_id; ?></h5>
                        <h3 class="text-primary"><?php echo formatPrice($order['total_amount']); ?></h3>
                    </div>

                    <?php if ($payment_method === 'GPay'): ?>
                        <!-- GPay Payment -->
                        <div id="gpay-payment-section">
                            <div class="alert alert-info">
                                <h6>Pay with Google Pay</h6>
                                <p class="mb-2">Click the button below to open Google Pay</p>
                            </div>
                            
                            <?php if ($razorpay_data): ?>
                                <!-- Razorpay Checkout for GPay -->
                                <button id="razorpay-gpay-btn" class="btn btn-success btn-lg w-100 mb-3">
                                    <i class="bi bi-wallet2"></i> Pay with GPay via Razorpay
                                </button>
                            <?php endif; ?>
                            
                            <!-- Direct UPI Link -->
                            <div class="text-center mb-3">
                                <a href="<?php echo generateGPayLink($order['total_amount'], $order_id, 'Order #' . $order_id); ?>" 
                                   class="btn btn-outline-success btn-lg">
                                    <i class="bi bi-phone"></i> Open GPay App
                                </a>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    UPI ID: <?php echo MERCHANT_UPI_ID; ?><br>
                                    Amount: <?php echo formatPrice($order['total_amount']); ?>
                                </small>
                            </div>
                            
                            <div class="alert alert-warning mt-3">
                                <small>
                                    <strong>Note:</strong> After completing payment, please note your transaction ID and return to confirm your order.
                                </small>
                            </div>
                            
                            <div class="mt-4">
                                <a href="payment_status.php?order_id=<?php echo $order_id; ?>&method=gpay" class="btn btn-primary w-100">
                                    I've Completed Payment
                                </a>
                            </div>
                        </div>
                        
                    <?php elseif ($payment_method === 'Card'): ?>
                        <!-- Card Payment -->
                        <div id="card-payment-section">
                            <?php if ($razorpay_data): ?>
                                <!-- Razorpay Checkout -->
                                <button id="razorpay-checkout-btn" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="bi bi-credit-card"></i> Pay with Card (Secure)
                                </button>
                                
                                <div class="text-center">
                                    <small class="text-muted">
                                        You'll be redirected to a secure payment page to enter your card details.
                                    </small>
                                </div>
                                
                                <!-- Manual Card Form as Alternative -->
                                <hr class="my-4">
                                <h6 class="text-center">Or Enter Card Details</h6>
                            <?php endif; ?>
                            
                            <form id="card-payment-form" method="POST" action="payment_callback.php">
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                <input type="hidden" name="payment_method" value="Card">
                                <?php if ($razorpay_data): ?>
                                    <input type="hidden" name="razorpay_order_id" value="<?php echo htmlspecialchars($razorpay_data['order_id']); ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">Card Number *</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" 
                                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                                    <small class="text-muted">Enter 16-digit card number</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="card_name" class="form-label">Cardholder Name *</label>
                                    <input type="text" class="form-control" id="card_name" name="card_name" 
                                           placeholder="John Doe" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="card_expiry" class="form-label">Expiry Date (MM/YY) *</label>
                                        <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                                               placeholder="12/24" maxlength="5" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="card_cvv" class="form-label">CVV *</label>
                                        <input type="text" class="form-control" id="card_cvv" name="card_cvv" 
                                               placeholder="123" maxlength="4" required>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <small>
                                        <i class="bi bi-lock"></i> Your card details are secure and encrypted.
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-credit-card"></i> Pay <?php echo formatPrice($order['total_amount']); ?>
                                </button>
                            </form>
                        </div>
                        
                    <?php else: ?>
                        <!-- Other payment methods -->
                        <div class="alert alert-info">
                            Payment method: <?php echo htmlspecialchars($payment_method); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-center">
                        <a href="checkout.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Checkout
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($razorpay_data && in_array($payment_method, ['GPay', 'Card'])): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var razorpayOptions = <?php echo json_encode($razorpay_data); ?>;

<?php if ($payment_method === 'GPay'): ?>
document.getElementById('razorpay-gpay-btn').onclick = function(e) {
    e.preventDefault();
    razorpayOptions.handler = function(response) {
        // Redirect to callback page
        window.location.href = 'payment_callback.php?razorpay_payment_id=' + 
                              response.razorpay_payment_id + 
                              '&razorpay_order_id=' + response.razorpay_order_id + 
                              '&razorpay_signature=' + response.razorpay_signature +
                              '&order_id=<?php echo $order_id; ?>';
    };
    razorpayOptions.method = {
        upi: {
            flow: 'collect',
            vpa: '<?php echo MERCHANT_UPI_ID; ?>'
        },
        netbanking: true,
        wallet: true,
        card: false
    };
    
    var rzp = new Razorpay(razorpayOptions);
    rzp.open();
};
<?php else: ?>
document.getElementById('razorpay-checkout-btn').onclick = function(e) {
    e.preventDefault();
    razorpayOptions.handler = function(response) {
        // Redirect to callback page
        window.location.href = 'payment_callback.php?razorpay_payment_id=' + 
                              response.razorpay_payment_id + 
                              '&razorpay_order_id=' + response.razorpay_order_id + 
                              '&razorpay_signature=' + response.razorpay_signature +
                              '&order_id=<?php echo $order_id; ?>';
    };
    
    var rzp = new Razorpay(razorpayOptions);
    rzp.open();
};
<?php endif; ?>
</script>
<?php endif; ?>

<script src="assets/js/payment.js"></script>
<?php require_once 'includes/footer.php'; ?>


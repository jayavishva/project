<?php
// Payment Status Page
$page_title = 'Payment Status';
require_once 'includes/header.php';
requireLogin();

require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = getDBConnection();

$order_id = intval($_GET['order_id'] ?? 0);
$status = sanitize($_GET['status'] ?? '');
$error = sanitize($_GET['error'] ?? '');

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

closeDBConnection($conn);

// Determine status
$payment_status = $order['payment_status'] ?? 'pending';
if (!empty($status)) {
    $payment_status = $status;
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header <?php echo $payment_status === 'success' ? 'bg-success' : ($payment_status === 'failed' ? 'bg-danger' : 'bg-warning'); ?> text-white">
                    <h4 class="mb-0">
                        <?php if ($payment_status === 'success'): ?>
                            <i class="bi bi-check-circle"></i> Payment Successful
                        <?php elseif ($payment_status === 'failed'): ?>
                            <i class="bi bi-x-circle"></i> Payment Failed
                        <?php else: ?>
                            <i class="bi bi-clock"></i> Payment Pending
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($payment_status === 'success'): ?>
                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="text-success">Payment Successful!</h3>
                            <p class="text-muted">Your order has been confirmed and will be processed shortly.</p>
                        <?php elseif ($payment_status === 'failed'): ?>
                            <div class="mb-3">
                                <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="text-danger">Payment Failed</h3>
                            <p class="text-muted"><?php echo htmlspecialchars($error ?: 'Your payment could not be processed. Please try again.'); ?></p>
                        <?php else: ?>
                            <div class="mb-3">
                                <i class="bi bi-clock-fill text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="text-warning">Payment Pending</h3>
                            <p class="text-muted">Your payment is being processed. Please wait...</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Order Details</h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td><strong>Order ID:</strong></td>
                                    <td>#<?php echo $order_id; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Method:</strong></td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $payment_status === 'success' ? 'success' : ($payment_status === 'failed' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($payment_status); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if (!empty($order['payment_transaction_id'])): ?>
                                <tr>
                                    <td><strong>Transaction ID:</strong></td>
                                    <td><small><?php echo htmlspecialchars($order['payment_transaction_id']); ?></small></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if ($payment_status === 'success'): ?>
                            <a href="orders.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-receipt"></i> View Order Details
                            </a>
                        <?php elseif ($payment_status === 'failed'): ?>
                            <a href="payment_process.php?order_id=<?php echo $order_id; ?>" class="btn btn-danger btn-lg">
                                <i class="bi bi-arrow-clockwise"></i> Retry Payment
                            </a>
                        <?php else: ?>
                            <a href="payment_process.php?order_id=<?php echo $order_id; ?>" class="btn btn-warning btn-lg">
                                <i class="bi bi-arrow-clockwise"></i> Complete Payment
                            </a>
                        <?php endif; ?>
                        
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="bi bi-list"></i> View All Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>




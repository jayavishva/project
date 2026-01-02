<?php
$page_title = 'My Orders';
require_once 'includes/header.php';
requireLogin();

$conn = getDBConnection();
function canCancelOrder($order) {
    return in_array($order['status'], ['pending', 'processing']);
}


// Get order ID from query string if viewing specific order
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id > 0) {
    // View single order details
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        closeDBConnection($conn);
        redirect('orders.php');
    }
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image_path, p.image_url, p.quantity_value, p.quantity_unit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items = $stmt->get_result();
    
    closeDBConnection($conn);
    ?>
    
    <h2 class="mb-4">Order #<?php echo $order['id']; ?></h2>
    
    <?php if (isset($_GET['order_id'])): ?>
        <div class="alert alert-success">
            <h4>Order Placed Successfully!</h4>
            <p>Your order has been placed. Order ID: <strong>#<?php echo $order['id']; ?></strong></p>
            <p>We will contact you soon for delivery confirmation.</p>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Order Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $order['status'] === 'delivered' ? 'success' : 
                                ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                        ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    <p><strong>Payment Status:</strong> 
                        <span class="badge bg-<?php 
                            $payment_status = $order['payment_status'] ?? 'pending';
                            echo $payment_status === 'success' ? 'success' : 
                                ($payment_status === 'failed' ? 'danger' : 'warning'); 
                        ?>">
                            <?php echo ucfirst($payment_status); ?>
                        </span>
                    </p>
                    <?php if (!empty($order['refund_status']) && $order['refund_status'] !== 'none'): ?>
    <p><strong>Refund Status:</strong>
        <span class="badge bg-<?php
            echo $order['refund_status'] === 'completed' ? 'success' :
                 ($order['refund_status'] === 'failed' ? 'danger' : 'warning');
        ?>">
            <?php echo ucfirst($order['refund_status']); ?>
        </span>
    </p>

    <p><strong>Refund Amount:</strong>
        <?php echo formatPrice($order['refund_amount']); ?>
    </p>
<?php endif; ?>
<?php if ($order['refund_status'] === 'pending'): ?>
    <small class="text-muted">Refund will be credited within 3–5 working days.</small>
<?php endif; ?>


                    <?php if (!empty($order['payment_transaction_id'])): ?>
                    <p><strong>Transaction ID:</strong> <small><?php echo htmlspecialchars($order['payment_transaction_id']); ?></small></p>
                    <?php endif; ?>
                    <p><strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Shipping Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                    <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Order Items</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $order_items->fetch_assoc()): ?>
                        <?php 
                        $subtotal = $item['price'] * $item['quantity'];
                        $item_image_url = !empty($item['image_path']) ? $item['image_path'] : 
                                         (!empty($item['image_url']) ? $item['image_url'] : 'assets/images/default-product.jpg');
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($item_image_url); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="me-2"
                                     style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; vertical-align: middle;"
                                     onerror="this.src='assets/images/default-product.jpg'">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <?php 
                                $quantity_display = formatQuantity($item['quantity_value'] ?? null, $item['quantity_unit'] ?? null);
                                if ($quantity_display): ?>
                                    <br><small class="text-info"><?php echo htmlspecialchars($quantity_display); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo formatPrice($subtotal); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <tr class="table-active">
                        <th colspan="3">Total</th>
                        <th><?php echo formatPrice($order['total_amount']); ?></th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
    </div>
    
    <?php
} else {
    // List all orders
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $orders = $stmt->get_result();
    
    closeDBConnection($conn);
    ?>
    
    <h2 class="mb-4">My Orders</h2>
    
    <?php if ($orders->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] === 'delivered' ? 'success' : 
                                        ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $payment_method = htmlspecialchars($order['payment_method']);
                                $payment_class = 'secondary';
                                if (stripos($payment_method, 'COD') !== false) {
                                    $payment_class = 'warning';
                                } elseif (stripos($payment_method, 'UPI') !== false || stripos($payment_method, 'GPay') !== false) {
                                    $payment_class = 'success';
                                } elseif (stripos($payment_method, 'Card') !== false) {
                                    $payment_class = 'primary';
                                }
                                ?>
                                <span class="badge bg-<?php echo $payment_class; ?>"><?php echo $payment_method; ?></span>
                            </td>
                            <td>
                                <?php 
                                $payment_status = $order['payment_status'] ?? 'pending';
                                $payment_status_class = $payment_status === 'success' ? 'success' : 
                                                       ($payment_status === 'failed' ? 'danger' : 'warning');
                                ?>
                                <span class="badge bg-<?php echo $payment_status_class; ?>">
                                    <?php echo ucfirst($payment_status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="orders.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                    View Details
                                </a>

<?php if (canCancelOrder($order)): ?>
    <form method="POST" action="cancel_order.php" class="d-inline"
          onsubmit="return confirm('Are you sure you want to cancel this order?');">
        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
        <button type="submit" class="btn btn-sm btn-danger">
            Cancel
        </button>
    </form>
<?php endif; ?>



                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <h4>No orders yet</h4>
            <p>Start shopping to place your first order.</p>
            <a href="index.php" class="btn btn-primary">Browse Products</a>
        </div>
    <?php endif; ?>
<?php } ?>

<?php require_once 'includes/footer.php'; ?>



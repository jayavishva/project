<?php
$page_title = 'Manage Orders';
require_once 'header.php';

$conn = getDBConnection();
$error = '';
$success = '';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $success = 'Order status updated successfully!';
    } else {
        $error = 'Failed to update order status.';
    }
}

// Get order ID from query string if viewing specific order
$view_order_id = isset($_GET['view']) ? intval($_GET['view']) : 0;

if ($view_order_id > 0) {
    // View single order details
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name, u.email, u.phone as user_phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $view_order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        closeDBConnection($conn);
        redirect('orders.php');
    }
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image_path, p.image_url
        SELECT oi.*, p.name, p.image_path, p.image_url, p.quantity_value, p.quantity_unit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $view_order_id);
    $stmt->execute();
    $order_items = $stmt->get_result();
    
    closeDBConnection($conn);
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Order #<?php echo $order['id']; ?></h2>
        <a href="orders.php" class="btn btn-outline-secondary">Back to Orders</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
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
                    <p><strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
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
                    <?php if (!empty($order['payment_transaction_id'])): ?>
                    <p><strong>Transaction ID:</strong> <small><?php echo htmlspecialchars($order['payment_transaction_id']); ?></small></p>
                    <?php endif; ?>
                    <?php if (!empty($order['razorpay_order_id'])): ?>
                    <p><strong>Razorpay Order ID:</strong> <small><?php echo htmlspecialchars($order['razorpay_order_id']); ?></small></p>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="mt-3">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <div class="mb-3">
                            <label for="status" class="form-label">Order Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Customer Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                    <p><strong>Shipping Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
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
                        $item_image_url = !empty($item['image_path']) ? '../' . $item['image_path'] : 
                                         (!empty($item['image_url']) ? $item['image_url'] : '../assets/images/default-product.jpg');
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($item_image_url); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="me-2"
                                     style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; vertical-align: middle;"
                                     onerror="this.src='../assets/images/default-product.jpg'">
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
    
    <?php
} else {
    // List all orders
    $status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    
    $query = "SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id";
    if (!empty($status_filter)) {
        $query .= " WHERE o.status = ?";
    }
    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($status_filter)) {
        $stmt->bind_param("s", $status_filter);
    }
    $stmt->execute();
    $orders = $stmt->get_result();
    
    closeDBConnection($conn);
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Orders</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <!-- Status Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <?php if (!empty($status_filter)): ?>
                    <div class="col-md-2">
                        <a href="orders.php" class="btn btn-secondary">Clear Filter</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if ($orders->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Payment Method</th>
                                <th>Payment Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['full_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] === 'delivered' ? 'success' : 
                                                ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
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
                                        <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center">No orders found.</p>
            <?php endif; ?>
        </div>
    </div>
<?php } ?>

<?php require_once 'footer.php'; ?>


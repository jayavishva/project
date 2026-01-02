<?php
$page_title = 'Admin Dashboard';
require_once 'header.php';

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$stats['products'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['orders'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['users'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");

closeDBConnection($conn);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Admin Dashboard</h2>
    <a href="../index.php" class="btn btn-outline-primary">View Site</a>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Products</h5>
                <h2><?php echo $stats['products']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <h2><?php echo $stats['orders']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Customers</h5>
                <h2><?php echo $stats['users']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Total Revenue</h5>
                <h2><?php echo formatPrice($stats['revenue']); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Pending Orders Alert -->
<?php if ($stats['pending_orders'] > 0): ?>
    <div class="alert alert-warning">
        <strong><?php echo $stats['pending_orders']; ?></strong> pending orders need attention.
        <a href="orders.php" class="alert-link">View Orders</a>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="products.php" class="btn btn-primary me-2">Manage Products</a>
                <a href="orders.php" class="btn btn-success me-2">Manage Orders</a>
                <a href="users.php" class="btn btn-info me-2">Manage Users</a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card">
    <div class="card-header">
        <h5>Recent Orders</h5>
    </div>
    <div class="card-body">
        <?php if ($recent_orders->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
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
                                    <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No orders yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>


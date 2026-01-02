<?php
$page_title = 'Shopping Cart';
require_once 'includes/header.php';
requireLogin();

$conn = getDBConnection();
$error = '';
$success = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity < 1) {
            // Remove item
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
            $stmt->execute();
            $success = 'Item removed from cart.';
        } else {
            // Check stock
            $stmt = $conn->prepare("
                SELECT p.stock, c.product_id 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $item = $result->fetch_assoc();
                if ($quantity > $item['stock']) {
                    $error = 'Insufficient stock. Available: ' . $item['stock'];
                } else {
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
                    if ($stmt->execute()) {
                        $success = 'Cart updated successfully!';
                    } else {
                        $error = 'Failed to update cart.';
                    }
                }
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = intval($_POST['cart_id']);
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success = 'Item removed from cart.';
        } else {
            $error = 'Failed to remove item.';
        }
    }
}

// Get cart items
$stmt = $conn->prepare("
    SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image_path, p.image_url, p.stock, p.quantity_value, p.quantity_unit
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_items = $stmt->get_result();

$cart_total = getCartTotal($conn, $_SESSION['user_id']);

closeDBConnection($conn);
?>

<h2 class="mb-4">Shopping Cart</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($cart_items->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $cart_items->fetch_assoc()): ?>
                    <?php 
                    $subtotal = $item['price'] * $item['quantity'];
                    $image_url = !empty($item['image_path']) ? $item['image_path'] : 
                                 (!empty($item['image_url']) ? $item['image_url'] : 'assets/images/default-product.jpg');
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="cart-thumbnail me-3"
                                     onerror="this.src='assets/images/default-product.jpg'">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php 
                                    $quantity_display = formatQuantity($item['quantity_value'] ?? null, $item['quantity_unit'] ?? null);
                                    if ($quantity_display): ?>
                                        <br><small class="text-info">Quantity: <?php echo htmlspecialchars($quantity_display); ?></small>
                                    <?php endif; ?>
                                    <?php if ($item['quantity'] > $item['stock']): ?>
                                        <br><small class="text-danger">Only <?php echo $item['stock']; ?> available</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo formatPrice($item['price']); ?></td>
                        <td>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <div class="input-group" style="width: 120px;">
                                    <input type="number" name="quantity" class="form-control" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>" required>
                                    <button type="submit" name="update_cart" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                            </form>
                        </td>
                        <td><?php echo formatPrice($subtotal); ?></td>
                        <td>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Remove this item from cart?')">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td><strong><?php echo formatPrice($cart_total); ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
        </div>
        <div class="col-md-6 text-end">
            <a href="checkout.php" class="btn btn-primary btn-lg">Proceed to Checkout</a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">
        <h4>Your cart is empty</h4>
        <p>Start shopping to add items to your cart.</p>
        <a href="index.php" class="btn btn-primary">Browse Products</a>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>



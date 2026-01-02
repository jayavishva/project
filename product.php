<?php
$page_title = 'Product Details';
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$product_id = intval($_GET['id']);
$conn = getDBConnection();

$product = getProductById($conn, $product_id);

if (!$product || $product['status'] !== 'active') {
    closeDBConnection($conn);
    redirect('index.php');
}

$error = '';
$success = '';

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $error = 'Please login to add items to cart.';
    } else {
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if ($quantity < 1) {
            $error = 'Quantity must be at least 1.';
        } elseif ($quantity > $product['stock']) {
            $error = 'Insufficient stock. Available: ' . $product['stock'];
        } else {
            // Check if item already in cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update quantity
                $cart_item = $result->fetch_assoc();
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                if ($new_quantity > $product['stock']) {
                    $error = 'Cannot add more. Available stock: ' . $product['stock'];
                } else {
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
                    if ($stmt->execute()) {
                        $success = 'Cart updated successfully!';
                    } else {
                        $error = 'Failed to update cart.';
                    }
                }
            } else {
                // Add new item
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $_SESSION['user_id'], $product_id, $quantity);
                if ($stmt->execute()) {
                    $success = 'Product added to cart successfully!';
                } else {
                    $error = 'Failed to add product to cart.';
                }
            }
        }
    }
}

closeDBConnection($conn);
?>

<div class="row">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php?category=<?php echo urlencode($product['category']); ?>">
                    <?php echo htmlspecialchars($product['category']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <?php 
        $image_url = !empty($product['image_path']) ? $product['image_path'] : 
                     (!empty($product['image_url']) ? $product['image_url'] : 'assets/images/default-product.jpg');
        ?>
        <img src="<?php echo htmlspecialchars($image_url); ?>" 
             class="img-fluid rounded shadow" 
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             onerror="this.src='assets/images/default-product.jpg'">
    </div>
    <div class="col-md-6">
        <h1>
            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 class="product-name-icon me-2"
                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; vertical-align: middle;"
                 onerror="this.style.display='none';">
            <?php echo htmlspecialchars($product['name']); ?>
        </h1>
        <p class="text-muted">Category: <?php echo htmlspecialchars($product['category']); ?></p>
        <hr>
        <h2 class="text-primary"><?php echo formatPrice($product['price']); ?></h2>
        <?php 
        $quantity_display = formatQuantity($product['quantity_value'] ?? null, $product['quantity_unit'] ?? null);
        if ($quantity_display): ?>
            <p class="mb-2">
                <strong>Quantity:</strong> 
                <span class="badge bg-info"><?php echo htmlspecialchars($quantity_display); ?></span>
            </p>
        <?php endif; ?>
        <p class="mb-3">
            <strong>Stock:</strong> 
            <?php if ($product['stock'] > 0): ?>
                <span class="text-success"><?php echo $product['stock']; ?> available</span>
            <?php else: ?>
                <span class="text-danger">Out of stock</span>
            <?php endif; ?>
        </p>
        
        <div class="mb-4">
            <h4>Description</h4>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>
        
        <?php if ($product['stock'] > 0): ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" 
                           value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                </div>
                <?php if (isLoggedIn()): ?>
                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                    </button>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-lg w-100">
                        Login to Add to Cart
                    </a>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <button class="btn btn-secondary btn-lg w-100" disabled>Out of Stock</button>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>



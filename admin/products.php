<?php
$page_title = 'Manage Products';
require_once 'header.php';

$conn = getDBConnection();
$error = '';
$success = '';

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product']) || isset($_POST['update_product'])) {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category = sanitize($_POST['category'] ?? '');
        $quantity_value = !empty($_POST['quantity_value']) ? floatval($_POST['quantity_value']) : null;
        $quantity_unit = sanitize($_POST['quantity_unit'] ?? '');
        if ($quantity_unit === 'None' || empty($quantity_unit)) {
            $quantity_unit = null;
        }
        $status = sanitize($_POST['status'] ?? 'active');
        $image_url = sanitize($_POST['image_url'] ?? '');
        
        if (empty($name) || $price <= 0) {
            $error = 'Name and price are required.';
        } else {
            $image_path = '';
            
            // Handle image upload if provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadImage($_FILES['image']);
                if ($upload_result['success']) {
                    $image_path = $upload_result['path'];
                } else {
                    $error = $upload_result['message'];
                }
            }
            
            if (empty($error)) {
                if (isset($_POST['update_product'])) {
                    // Update product
                    $product_id = intval($_POST['product_id']);
                    $product = getProductById($conn, $product_id);
                    
                    if ($product) {
                        // Delete old image if new one uploaded
                        if (!empty($image_path) && !empty($product['image_path'])) {
                            deleteImageFile($product['image_path']);
                        }
                        
                        if (!empty($image_path)) {
                            $stmt = $conn->prepare("
                                UPDATE products 
                                SET name = ?, description = ?, price = ?, stock = ?, category = ?, 
                                    quantity_value = ?, quantity_unit = ?, status = ?, image_path = ?, image_url = ? 
                                WHERE id = ?
                            ");
                            $stmt->bind_param("ssddsdssi", $name, $description, $price, $stock, $category, $quantity_value, $quantity_unit, $status, $image_path, $image_url, $product_id);
                        } else {
                            $stmt = $conn->prepare("
                                UPDATE products 
                                SET name = ?, description = ?, price = ?, stock = ?, category = ?, 
                                    quantity_value = ?, quantity_unit = ?, status = ?, image_url = ? 
                                WHERE id = ?
                            ");
                            $stmt->bind_param("ssddsdsi", $name, $description, $price, $stock, $category, $quantity_value, $quantity_unit, $status, $image_url, $product_id);
                        }
                        
                        if ($stmt->execute()) {
                            $success = 'Product updated successfully!';
                        } else {
                            $error = 'Failed to update product.';
                        }
                    }
                } else {
                    // Add new product
                    $stmt = $conn->prepare("
                        INSERT INTO products (name, description, price, stock, category, quantity_value, quantity_unit, status, image_path, image_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("ssddsdssi", $name, $description, $price, $stock, $category, $quantity_value, $quantity_unit, $status, $image_path, $image_url);
                    
                    if ($stmt->execute()) {
                        $success = 'Product added successfully!';
                    } else {
                        $error = 'Failed to add product.';
                    }
                }
            }
        }
    } elseif (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        $product = getProductById($conn, $product_id);
        
        if ($product) {
            // Delete image file
            if (!empty($product['image_path'])) {
                deleteImageFile($product['image_path']);
            }
            
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                $success = 'Product deleted successfully!';
            } else {
                $error = 'Failed to delete product.';
            }
        }
    }
}

// Get products
$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_product = getProductById($conn, $edit_id);
}

closeDBConnection($conn);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Products</h2>
    <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Add/Edit Product Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($edit_product): ?>
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" 
                               value="<?php echo htmlspecialchars($edit_product['category'] ?? ''); ?>"
                               placeholder="e.g., Henna Powder, Cones, Accessories">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="price" class="form-label">Price (₹) *</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" 
                               value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" 
                               value="<?php echo $edit_product['stock'] ?? 0; ?>" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo ($edit_product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="quantity_value" class="form-label">Quantity Value</label>
                        <input type="number" step="0.01" class="form-control" id="quantity_value" name="quantity_value" 
                               value="<?php echo $edit_product['quantity_value'] ?? ''; ?>"
                               placeholder="e.g., 100">
                        <small class="text-muted">Numeric value (e.g., 100, 250.5)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="quantity_unit" class="form-label">Quantity Unit</label>
                        <select class="form-select" id="quantity_unit" name="quantity_unit">
                            <option value="None" <?php echo empty($edit_product['quantity_unit']) ? 'selected' : ''; ?>>None</option>
                            <option value="g" <?php echo ($edit_product['quantity_unit'] ?? '') === 'g' ? 'selected' : ''; ?>>g (grams)</option>
                            <option value="kg" <?php echo ($edit_product['quantity_unit'] ?? '') === 'kg' ? 'selected' : ''; ?>>kg (kilograms)</option>
                            <option value="ml" <?php echo ($edit_product['quantity_unit'] ?? '') === 'ml' ? 'selected' : ''; ?>>ml (milliliters)</option>
                            <option value="L" <?php echo ($edit_product['quantity_unit'] ?? '') === 'L' ? 'selected' : ''; ?>>L (liters)</option>
                            <option value="cones" <?php echo ($edit_product['quantity_unit'] ?? '') === 'cones' ? 'selected' : ''; ?>>cones</option>
                            <option value="pieces" <?php echo ($edit_product['quantity_unit'] ?? '') === 'pieces' ? 'selected' : ''; ?>>pieces</option>
                            <option value="packs" <?php echo ($edit_product['quantity_unit'] ?? '') === 'packs' ? 'selected' : ''; ?>>packs</option>
                            <option value="boxes" <?php echo ($edit_product['quantity_unit'] ?? '') === 'boxes' ? 'selected' : ''; ?>>boxes</option>
                        </select>
                        <small class="text-muted">Select unit type</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-control bg-light">
                            <strong>Preview:</strong> 
                            <span id="quantity_preview">
                                <?php 
                                if (!empty($edit_product['quantity_value']) && !empty($edit_product['quantity_unit'])) {
                                    echo formatQuantity($edit_product['quantity_value'], $edit_product['quantity_unit']);
                                } else {
                                    echo 'Not set';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="image" class="form-label">Upload Image</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <small class="text-muted">Leave empty to keep existing image or use URL below</small>
            </div>
            
            <div class="mb-3">
                <label for="image_url" class="form-label">Or Image URL</label>
                <input type="url" class="form-control" id="image_url" name="image_url" 
                       value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>"
                       placeholder="https://example.com/image.jpg">
                <small class="text-muted">Use either file upload or URL (URL takes precedence if both provided)</small>
            </div>
            
            <?php if ($edit_product): ?>
                <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            <?php endif; ?>
        </form>
        
        <script>
        // Update quantity preview
        function updateQuantityPreview() {
            const value = document.getElementById('quantity_value').value;
            const unit = document.getElementById('quantity_unit').value;
            const preview = document.getElementById('quantity_preview');
            
            if (value && unit && unit !== 'None') {
                // Format value
                let formattedValue = parseFloat(value);
                formattedValue = formattedValue.toFixed(2).replace(/\.?0+$/, '');
                preview.textContent = formattedValue + unit;
            } else {
                preview.textContent = 'Not set';
            }
        }
        
        document.getElementById('quantity_value').addEventListener('input', updateQuantityPreview);
        document.getElementById('quantity_unit').addEventListener('change', updateQuantityPreview);
        </script>
    </div>
</div>

<!-- Products List -->
<div class="card">
    <div class="card-header">
        <h5>All Products</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <?php 
                            $image_url = !empty($product['image_path']) ? '../' . $product['image_path'] : 
                                         (!empty($product['image_url']) ? $product['image_url'] : '../assets/images/default-product.jpg');
                            ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover;"
                                         onerror="this.src='../assets/images/default-product.jpg'">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo formatPrice($product['price']); ?></td>
                                <td>
                                    <?php 
                                    $quantity_display = formatQuantity($product['quantity_value'] ?? null, $product['quantity_unit'] ?? null);
                                    echo $quantity_display ? htmlspecialchars($quantity_display) : '<span class="text-muted">-</span>';
                                    ?>
                                </td>
                                <td><?php echo $product['stock']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <form method="POST" action="" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>


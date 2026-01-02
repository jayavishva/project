-- Mehedi E-commerce Database Schema

CREATE DATABASE IF NOT EXISTS mehedi_shop;
USE mehedi_shop;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(500),
    image_path VARCHAR(500),
    stock INT DEFAULT 0,
    category VARCHAR(100),
    quantity_value DECIMAL(10, 2) NULL,
    quantity_unit VARCHAR(50) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration: Add quantity columns to existing products table (run this if table already exists)
-- ALTER TABLE products ADD COLUMN quantity_value DECIMAL(10, 2) NULL AFTER category;
-- ALTER TABLE products ADD COLUMN quantity_unit VARCHAR(50) NULL AFTER quantity_value;

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'COD',
    payment_status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    payment_transaction_id VARCHAR(255) NULL,
    razorpay_order_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration: Add payment columns to existing orders table (run this if table already exists)
-- ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending' AFTER payment_method;
-- ALTER TABLE orders ADD COLUMN payment_transaction_id VARCHAR(255) NULL AFTER payment_status;
-- ALTER TABLE orders ADD COLUMN razorpay_order_id VARCHAR(255) NULL AFTER payment_transaction_id;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_otp (otp_code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password, full_name, role)
SELECT 'admin', 'admin@mehedishop.com',
'$2y$10$Q18rFW3N3wb/HVUM548NtOWB0IcikFgtRwbFjhuLyuZrdU/ufOFq2',
'Administrator', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);

/*-- Sample products
INSERT INTO products (name, description, price, stock, category, status) VALUES
('Natural Henna Powder', 'Pure natural henna powder for beautiful designs', 299.00, 50, 'Henna Powder', 'active'),
('Mehendi Cone Set', 'Professional mehendi cone set with 5 cones', 199.00, 30, 'Cones', 'active'),
('Henna Oil', 'Essential oil for darker and longer-lasting mehendi', 149.00, 40, 'Accessories', 'active'),
('Design Stencils', 'Reusable mehendi design stencils pack of 10', 249.00, 25, 'Accessories', 'active'),
('Premium Henna Powder', 'Premium quality henna powder with natural ingredients', 399.00, 35, 'Henna Powder', 'active');*/

-- Insert products into the products table
INSERT INTO products 
(name, description, price, image_url, image_path, stock, category, quantity_value, quantity_unit, status)
VALUES
('Natural Henna Powder', 'Pure natural henna powder for beautiful designs', 299.00, 
 Null, 'assets/images/HennaPowder.webp', 50, 'Henna Powder', 100, 'grams', 'active'),
('Mehendi Cone Set', 'Professional mehendi cone set with 5 cones', 199.00, 
 Null, 'assets/images/Mehendi_Cone_Set.jpg', 30, 'Cones', 5, 'cones', 'active'),
('Henna Oil', 'Essential oil for darker and longer-lasting mehendi', 149.00, 
  Null, 'assets/images/Henna_Oil.jpg', 40, 'Accessories', 50, 'ml', 'active'),
('Design Stencils', 'Reusable mehendi design stencils pack of 10', 249.00, 
 Null, 'assets/images/Design_Stencils.webp', 25, 'Accessories', 10, 'pieces', 'active'),
('Premium Henna Powder', 'Premium quality henna powder with natural ingredients', 399.00, 
  Null, 'assets/images/Premium_Henna_Powder.jpg', 35, 'Henna Powder', 100, 'grams', 'active');

ALTER TABLE orders
ADD COLUMN cancelled_at DATETIME NULL,
ADD COLUMN refund_status ENUM('none','pending','completed','failed') DEFAULT 'none',
ADD COLUMN refund_amount DECIMAL(10,2) DEFAULT 0.00;


ALTER TABLE orders
MODIFY payment_status ENUM('pending','success','failed') DEFAULT 'pending';

ALTER TABLE orders
ADD COLUMN cancelled_at DATETIME NULL AFTER status,
ADD COLUMN refund_status ENUM('none','pending','completed','failed') DEFAULT 'none' AFTER payment_status,
ADD COLUMN refund_amount DECIMAL(10,2) DEFAULT 0.00 AFTER refund_status,
ADD COLUMN refund_completed_at DATETIME NULL AFTER refund_amount;

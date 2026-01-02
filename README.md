# Mehedi E-commerce Website

A complete e-commerce website for selling Mehedi (Henna) products built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

- **User Authentication**: Registration, login, logout with secure password hashing
- **Product Management**: Browse products with search and category filters
- **Shopping Cart**: Add, update, and remove items from cart
- **Order System**: Place orders with Cash on Delivery (COD) payment
- **Admin Dashboard**: Complete admin panel for managing products, orders, and users
- **User Profile**: Update profile information and change password
- **Responsive Design**: Mobile-friendly interface using Bootstrap 5

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation

1. **Clone or download the project** to your web server directory (e.g., `htdocs`, `www`, or `public_html`)

2. **Create the database**:
   - Open phpMyAdmin or MySQL command line
   - Import the `database.sql` file to create the database and tables
   - Or run: `mysql -u root -p < database.sql`

3. **Configure database connection**:
   - Edit `config/database.php`
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'mehedi_shop');
     ```

4. **Set permissions**:
   - Make sure the `uploads/products/` directory is writable:
     ```bash
     chmod 755 uploads/products/
     ```
   - On Windows, ensure the folder has write permissions

5. **Default Admin Credentials**:
   - Username: `admin`
   - Password: `admin123`
   - **Important**: Change this password after first login!

6. **Access the website**:
   - Open your browser and navigate to: `http://localhost/project/` (adjust path as needed)

## Project Structure

```
project/
├── admin/              # Admin panel files
│   ├── index.php      # Admin dashboard
│   ├── products.php   # Product management
│   ├── orders.php     # Order management
│   └── users.php      # User management
├── assets/
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── images/        # Images and logos
├── config/
│   └── database.php   # Database configuration
├── includes/
│   ├── header.php     # Common header
│   ├── footer.php     # Common footer
│   ├── functions.php  # Helper functions
│   └── auth.php       # Authentication helpers
├── uploads/
│   └── products/      # Uploaded product images
├── index.php          # Homepage
├── product.php        # Product detail page
├── cart.php           # Shopping cart
├── checkout.php       # Checkout page
├── orders.php         # Order history
├── login.php          # Login page
├── register.php       # Registration page
├── profile.php        # User profile
└── database.sql       # Database schema
```

## Usage

### For Customers

1. **Register/Login**: Create an account or login with existing credentials
2. **Browse Products**: View products on the homepage, use search and filters
3. **View Product Details**: Click on any product to see full details
4. **Add to Cart**: Add products to cart with desired quantity
5. **Checkout**: Review cart and place order with shipping address
6. **Track Orders**: View order history and order details

### For Administrators

1. **Login**: Use admin credentials to access admin panel
2. **Dashboard**: View statistics and recent orders
3. **Manage Products**: Add, edit, or delete products
   - Upload product images or use image URLs
   - Set prices, stock, categories, and status
4. **Manage Orders**: View and update order status
5. **Manage Users**: View all registered users

## Product Image Options

The system supports two methods for product images:
1. **File Upload**: Upload images directly (JPG, PNG, GIF, WEBP - max 5MB)
2. **Image URL**: Provide a URL to an external image

## Security Features

- Password hashing using PHP `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- File upload validation
- Session-based authentication
- Role-based access control

## Customization

### Change Colors
Edit `assets/css/style.css` to customize the color scheme:
```css
:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    /* ... */
}
```

### Add Categories
Categories are free-form text fields. Common categories:
- Henna Powder
- Cones
- Accessories

### Modify Payment Methods
Currently supports Cash on Delivery (COD). To add more payment methods, modify `checkout.php` and `orders.php`.

## Troubleshooting

### Images not displaying
- Check file permissions on `uploads/products/` directory
- Verify image paths in database
- Ensure default product image exists at `assets/images/default-product.jpg`

### Database connection errors
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check database name matches in SQL file and config

### Session errors
- Ensure `session_start()` is called before any output
- Check PHP session configuration in `php.ini`

## Support

For issues or questions:
1. Check the code comments for explanations
2. Review PHP error logs
3. Ensure all requirements are met

## License

This project is provided as-is for educational and commercial use.

## Notes

- Remember to change the default admin password
- Regularly backup your database
- Keep PHP and MySQL updated for security
- Test file uploads work correctly on your server
- Consider adding SSL certificate for production use



# CRUD Operations Summary

This document lists all Create, Read, Update, Delete (CRUD) operations available in the Mehedi E-commerce website.

## ✅ Products CRUD Operations

**Location:** `admin/products.php`

### Create (Add Product)
- **Action:** Add new product to catalog
- **Fields:** Name, Description, Price, Stock, Category, Status, Image (upload or URL)
- **Access:** Admin only
- **Method:** POST form submission

### Read (View Products)
- **Action:** View all products in admin panel
- **Display:** Table with ID, Image, Name, Category, Price, Stock, Status
- **Access:** Admin only
- **Additional:** Products also visible to customers on homepage

### Update (Edit Product)
- **Action:** Modify existing product details
- **Fields:** All product fields can be updated
- **Access:** Admin only
- **Method:** Click "Edit" button, modify form, submit

### Delete (Remove Product)
- **Action:** Delete product from catalog
- **Safety:** Confirmation dialog before deletion
- **Access:** Admin only
- **Note:** Also deletes associated image file if uploaded

---

## ✅ Users CRUD Operations

**Location:** `admin/users.php`

### Create (Add User)
- **Action:** Create new user account
- **Fields:** Username, Email, Password, Full Name, Phone, Address, Role
- **Access:** Admin only
- **Validation:** 
  - Username and email must be unique
  - Password minimum 6 characters
  - Email format validation

### Read (View Users)
- **Action:** View all registered users
- **Display:** Table with ID, Username, Email, Full Name, Phone, Role, Join Date
- **Access:** Admin only
- **Additional:** Users can view their own profile

### Update (Edit User)
- **Action:** Modify user account details
- **Fields:** All user fields can be updated
- **Access:** Admin only
- **Password:** Optional (leave blank to keep current password)
- **Method:** Click "Edit" button, modify form, submit

### Delete (Remove User)
- **Action:** Delete user account
- **Safety:** 
  - Cannot delete own account
  - Cannot delete users with existing orders
  - Confirmation dialog required
- **Access:** Admin only

---

## ✅ Orders CRUD Operations

**Location:** `admin/orders.php` and `orders.php`

### Create (Place Order)
- **Action:** Customers place orders through checkout
- **Location:** `checkout.php`
- **Fields:** Shipping address, Phone, Payment method (COD)
- **Access:** Logged-in customers
- **Process:** 
  - Validates cart items
  - Checks stock availability
  - Creates order and order items
  - Deducts stock from products
  - Clears cart

### Read (View Orders)
- **Action:** View order details
- **Customer View:** `orders.php` - View own orders
- **Admin View:** `admin/orders.php` - View all orders
- **Display:** Order ID, Customer, Amount, Status, Date, Payment Method
- **Filter:** Admin can filter by status (pending, processing, shipped, delivered, cancelled)

### Update (Change Order Status)
- **Action:** Update order status
- **Location:** `admin/orders.php`
- **Status Options:**
  - Pending (default)
  - Processing
  - Shipped
  - Delivered
  - Cancelled
- **Access:** Admin only
- **Method:** Select status from dropdown, click "Update Status"

### Delete (Remove Order)
- **Not Available:** Orders are not deleted to maintain order history
- **Alternative:** Use "Cancelled" status instead
- **Note:** Order deletion would break referential integrity

---

## 📋 Cart Operations

**Location:** `cart.php`

### Create (Add to Cart)
- **Action:** Add product to shopping cart
- **Location:** `product.php` and `cart.php`
- **Fields:** Product ID, Quantity
- **Access:** Logged-in users only
- **Validation:** Checks stock availability

### Read (View Cart)
- **Action:** View cart contents
- **Display:** Product, Price, Quantity, Subtotal, Total
- **Access:** Logged-in users only

### Update (Update Cart)
- **Action:** Change quantity of items in cart
- **Method:** Modify quantity input, click update button
- **Validation:** Cannot exceed available stock
- **Access:** Logged-in users only

### Delete (Remove from Cart)
- **Action:** Remove item from cart
- **Method:** Click "Remove" button with confirmation
- **Access:** Logged-in users only

---

## 🔐 User Profile Operations

**Location:** `profile.php`

### Read (View Profile)
- **Action:** View own profile information
- **Display:** Username, Email, Full Name, Phone, Address, Account Type, Member Since
- **Access:** Logged-in users only

### Update (Edit Profile)
- **Action:** Update profile information
- **Fields:** Full Name, Phone, Address
- **Note:** Username and Email cannot be changed
- **Access:** Logged-in users only

### Update Password
- **Action:** Change account password
- **Fields:** Current Password, New Password, Confirm Password
- **Validation:** 
  - Current password must be correct
  - New password minimum 6 characters
  - Passwords must match
- **Access:** Logged-in users only

---

## 📊 Summary Table

| Entity | Create | Read | Update | Delete | Location |
|--------|--------|------|--------|--------|----------|
| **Products** | ✅ | ✅ | ✅ | ✅ | `admin/products.php` |
| **Users** | ✅ | ✅ | ✅ | ✅ | `admin/users.php` |
| **Orders** | ✅ | ✅ | ✅ | ❌* | `checkout.php`, `admin/orders.php` |
| **Cart Items** | ✅ | ✅ | ✅ | ✅ | `cart.php` |
| **User Profile** | N/A | ✅ | ✅ | N/A | `profile.php` |

*Orders cannot be deleted but can be cancelled (status change)

---

## 🔒 Access Control

- **Admin:** Full CRUD access to Products, Users, Orders
- **Customer:** 
  - Create/Read/Update/Delete own cart items
  - Create orders (checkout)
  - Read own orders
  - Read/Update own profile

---

## 🛡️ Security Features

All CRUD operations include:
- **Input Validation:** All inputs are sanitized and validated
- **SQL Injection Prevention:** Prepared statements used throughout
- **XSS Protection:** Output is escaped with `htmlspecialchars()`
- **Authorization Checks:** Role-based access control
- **Confirmation Dialogs:** Delete operations require confirmation
- **Data Integrity:** Foreign key constraints prevent orphaned records

---

## 📝 Notes

1. **Products:** Image files are automatically deleted when product is deleted
2. **Users:** Cannot delete own account or users with orders
3. **Orders:** Maintained for historical records, use status updates instead of deletion
4. **Cart:** Automatically cleared after successful order placement
5. **Profile:** Username and email are immutable for security reasons

All CRUD operations are fully functional and ready to use!



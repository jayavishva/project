# Payment Gateway Setup Instructions

## Razorpay Integration Setup

This document provides instructions for setting up Razorpay payment gateway integration.

### Prerequisites

1. Razorpay account - Sign up at https://razorpay.com
2. Razorpay API Keys (Key ID and Secret Key)
3. Merchant UPI ID (for GPay/UPI payments)
4. SSL certificate for production (HTTPS required)

### Step 1: Install Razorpay SDK

You have two options:

#### Option A: Using Composer (Recommended)
```bash
composer require razorpay/razorpay
```

#### Option B: Manual Installation
1. Download Razorpay PHP SDK from https://github.com/razorpay/razorpay-php
2. Extract and place the SDK in `includes/razorpay/razorpay-php/` directory

### Step 2: Configure Payment Settings

Edit `config/payment.php` and update the following:

```php
// Razorpay Configuration
define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_KEY_ID'); // Replace with your Key ID
define('RAZORPAY_KEY_SECRET', 'YOUR_SECRET_KEY'); // Replace with your Secret Key
define('RAZORPAY_MODE', 'test'); // 'test' or 'live'

// GPay/UPI Configuration
define('MERCHANT_UPI_ID', 'your-merchant@upi'); // Replace with your UPI ID
define('MERCHANT_NAME', 'Mehedi Shop'); // Your business name
```

### Step 3: Update Database Schema

Run the following SQL to add payment columns to the orders table:

```sql
ALTER TABLE orders 
ADD COLUMN payment_status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending' AFTER payment_method;

ALTER TABLE orders 
ADD COLUMN payment_transaction_id VARCHAR(255) NULL AFTER payment_status;

ALTER TABLE orders 
ADD COLUMN razorpay_order_id VARCHAR(255) NULL AFTER payment_transaction_id;
```

Or run the updated `database.sql` file.

### Step 4: Get Razorpay API Keys

1. Log in to Razorpay Dashboard: https://dashboard.razorpay.com
2. Go to Settings > API Keys
3. Generate Test Keys (for development) or Live Keys (for production)
4. Copy the Key ID and Secret Key
5. Update `config/payment.php` with your keys

### Step 5: Configure Webhooks (Optional but Recommended)

1. In Razorpay Dashboard, go to Settings > Webhooks
2. Add webhook URL: `https://yourdomain.com/payment_webhook.php`
3. Select events: `payment.authorized`, `payment.captured`, `payment.failed`
4. Save the webhook secret

### Step 6: Test the Integration

1. Use Razorpay test cards: https://razorpay.com/docs/payments/payments/test-card-details/
2. Test GPay redirect with test UPI ID
3. Verify payment status updates in orders

### Payment Methods Supported

1. **Cash on Delivery (COD)**: Works immediately, no setup needed
2. **GPay/UPI**: Requires UPI ID configuration
3. **Card Payments**: Processed via Razorpay secure checkout
4. **UPI**: Same as GPay, uses UPI payment link

### Security Notes

- Never commit API keys to version control
- Use environment variables or secure config files in production
- Enable HTTPS/SSL in production
- Regularly rotate API keys
- Monitor payment logs for suspicious activity

### Troubleshooting

**Issue: Razorpay SDK not found**
- Ensure SDK is installed in correct location
- Check `config/payment.php` path configuration

**Issue: Payment verification fails**
- Verify API keys are correct
- Check payment signature verification in `payment_callback.php`
- Ensure webhook URL is accessible (if using webhooks)

**Issue: GPay redirect not working**
- Verify UPI ID format is correct
- Test UPI link manually
- Check device compatibility (mobile vs desktop)

### Support

- Razorpay Documentation: https://razorpay.com/docs/
- Razorpay Support: support@razorpay.com




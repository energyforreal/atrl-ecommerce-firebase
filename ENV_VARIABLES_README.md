# Environment Variables Configuration

## Required Environment Variables

To properly secure your application, set these environment variables in your hosting environment (Hostinger cPanel, server configuration, or `.htaccess`).

### Razorpay Configuration
```
RAZORPAY_KEY_ID=rzp_live_your_key_id_here
RAZORPAY_KEY_SECRET=your_secret_key_here
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret_here
```

### SMTP Configuration (Brevo)
```
SMTP_HOST=smtp-relay.brevo.com
SMTP_PORT=587
SMTP_USERNAME=your_smtp_username
SMTP_PASSWORD=your_smtp_password
SMTP_SECURE=tls
SMTP_ALT_PORT=2525
```

### Email Configuration
```
MAIL_FROM=info@attral.in
MAIL_FROM_NAME=ATTRAL Electronics
EMAIL_PRIMARY=phpmailer
EMAIL_FALLBACK=brevo
```

### CORS Configuration
```
ALLOWED_ORIGINS=https://attral.in,https://www.attral.in
```

## How to Set Environment Variables

### Option 1: Hostinger cPanel
1. Log into Hostinger cPanel
2. Go to "Advanced" → "Environment Variables"
3. Add each variable with its value
4. Save changes

### Option 2: .htaccess (if supported)
Create a `.htaccess` file in `static-site/api/` directory:

```apache
SetEnv RAZORPAY_KEY_ID "rzp_live_your_key_id_here"
SetEnv RAZORPAY_KEY_SECRET "your_secret_key_here"
SetEnv RAZORPAY_WEBHOOK_SECRET "your_webhook_secret_here"
# Add other variables...
```

### Option 3: php.ini (if you have access)
Add to php.ini:
```ini
; Environment variables
env[RAZORPAY_KEY_ID] = "rzp_live_your_key_id_here"
env[RAZORPAY_KEY_SECRET] = "your_secret_key_here"
env[RAZORPAY_WEBHOOK_SECRET] = "your_webhook_secret_here"
```

## Security Notes

⚠️ **IMPORTANT**: Never commit real credentials to version control!

1. Remove hardcoded credentials from `static-site/api/config.php`
2. Use environment variables for all sensitive data
3. Keep `.env` files (if created) in `.gitignore`
4. Rotate credentials if they were exposed in version control

## Verifying Configuration

Create a test file `test_env.php` in `static-site/api/`:

```php
<?php
echo "RAZORPAY_KEY_ID: " . (getenv('RAZORPAY_KEY_ID') ? 'SET ✓' : 'NOT SET ✗') . "\n";
echo "RAZORPAY_KEY_SECRET: " . (getenv('RAZORPAY_KEY_SECRET') ? 'SET ✓' : 'NOT SET ✗') . "\n";
echo "RAZORPAY_WEBHOOK_SECRET: " . (getenv('RAZORPAY_WEBHOOK_SECRET') ? 'SET ✓' : 'NOT SET ✗') . "\n";
?>
```

Access via browser and verify all variables show "SET ✓".

**Delete this test file after verification!**


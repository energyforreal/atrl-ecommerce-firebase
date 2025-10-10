# 🚀 ATTRAL E-Commerce Deployment Checklist

**System**: ATTRAL E-Commerce Platform  
**Environment**: Hostinger Shared Hosting  
**Date**: October 10, 2025  
**Version**: 1.0 (Post-Diagnostic Fixes)

---

## Pre-Deployment Checklist

### 📋 Phase 1: Configuration Files (CRITICAL)

- [ ] **Verify config.php exists** in `/api/` directory
  - [ ] Contains valid `RAZORPAY_KEY_ID` (starts with `rzp_live_` for production)
  - [ ] Contains valid `RAZORPAY_KEY_SECRET` (NOT the test key)
  - [ ] Contains valid `RAZORPAY_WEBHOOK_SECRET` (from Razorpay dashboard)
  - [ ] Contains SMTP credentials (Brevo)
    - [ ] `SMTP_HOST` = smtp-relay.brevo.com
    - [ ] `SMTP_USERNAME` = your Brevo SMTP username
    - [ ] `SMTP_PASSWORD` = your Brevo SMTP password
    - [ ] `SMTP_PORT` = 587
  - [ ] Contains `MAIL_FROM` = info@attral.in
  - [ ] Contains `MAIL_FROM_NAME` = ATTRAL Electronics

- [ ] **Verify firebase-service-account.json** exists in `/api/` directory
  - [ ] File contains valid service account JSON from Firebase console
  - [ ] Project ID is `e-commerce-1d40f`
  - [ ] Contains `private_key` field
  - [ ] Contains `client_email` field
  - [ ] File permissions set to 600 (secure)

- [ ] **Verify js/config.js** has correct settings
  - [ ] `RAZORPAY_KEY_ID` = `rzp_live_RKD5kwFAOZ05UD` (production key)
  - [ ] `API_BASE_URL` = `https://attral.in` (your production domain)
  - [ ] Firebase config matches your project

- [ ] **Verify .htaccess** or web server config allows:
  - [ ] PHP execution in `/api/` directory
  - [ ] CORS headers configured
  - [ ] Clean URLs (optional but recommended)

---

### 📋 Phase 2: File Upload (6 Modified Files)

Upload these 6 files that were modified:

- [ ] **Upload: static-site/order-success.html**
  - Changes: Cart clearing + diagnostics added
  - Size: ~1283 lines
  - Path: `/public_html/static-site/order-success.html`

- [ ] **Upload: static-site/order.html**
  - Changes: Payment diagnostics added
  - Size: ~2265 lines
  - Path: `/public_html/static-site/order.html`

- [ ] **Upload: static-site/api/firestore_order_manager_rest.php**
  - Changes: PRIMARY SYSTEM documentation
  - Size: ~845 lines
  - Path: `/public_html/static-site/api/firestore_order_manager_rest.php`

- [ ] **Upload: static-site/api/firestore_order_manager.php**
  - Changes: DEPRECATED warning added
  - Size: ~878 lines
  - Path: `/public_html/static-site/api/firestore_order_manager.php`

- [ ] **Upload: static-site/api/order_manager.php**
  - Changes: FALLBACK warning added
  - Size: ~996 lines
  - Path: `/public_html/static-site/api/order_manager.php`

- [ ] **Upload: static-site/api/webhook.php**
  - Changes: Enhanced logging
  - Size: ~248 lines
  - Path: `/public_html/static-site/api/webhook.php`

---

### 📋 Phase 3: Dependencies Verification

- [ ] **PHP Version**
  - [ ] PHP 7.4 or higher (check Hostinger control panel)
  - [ ] Recommended: PHP 8.1 or 8.2

- [ ] **PHP Extensions Enabled** (required for REST API)
  - [ ] cURL (`php_curl`)
  - [ ] JSON (`php_json`)
  - [ ] OpenSSL (`php_openssl`)
  - [ ] MBString (`php_mbstring`)
  - [ ] Check via: Create `/api/phpinfo.php` with `<?php phpinfo();`

- [ ] **Composer Dependencies** (optional - only if using SDK version)
  - [ ] If using SDK: Run `composer install` in `/api/` directory
  - [ ] Verify `/api/vendor/` directory exists
  - [ ] Note: REST API version does NOT require Composer ✅

- [ ] **PHPMailer** (for emails)
  - [ ] Verify `/api/vendor/phpmailer/` exists OR
  - [ ] Vendor folder uploaded with dependencies

- [ ] **Directory Permissions**
  - [ ] `/api/` directory: 755
  - [ ] `/api/.cache/` directory: 700 (create if missing)
  - [ ] `/api/invoices/` directory: 755 (create if missing)
  - [ ] `/api/logs/` directory: 755 (create if missing)
  - [ ] `firebase-service-account.json`: 600 (secure)
  - [ ] `config.php`: 600 (secure)

---

### 📋 Phase 4: Firestore Configuration

- [ ] **Firebase Project Setup**
  - [ ] Project ID: `e-commerce-1d40f`
  - [ ] Firestore database enabled (Native mode, not Datastore)
  - [ ] Service account has Firestore permissions

- [ ] **Firestore Collections** (create if missing)
  - [ ] `orders` - Primary order storage
  - [ ] `coupons` - Coupon definitions
  - [ ] `affiliates` - Affiliate information
  - [ ] `affiliate_commissions` - Commission tracking
  - [ ] `addresses` - User shipping addresses
  - [ ] `users` - User profiles
  - [ ] `products` - Product catalog (optional)

- [ ] **Firestore Security Rules**
  - [ ] Orders: Read/write with proper authentication
  - [ ] Coupons: Read by authenticated users, write by admin only
  - [ ] Users can read/write their own data

- [ ] **Firestore Indexes** (create if needed)
  - [ ] `orders` collection:
    - [ ] Index on `razorpayPaymentId` (ascending)
    - [ ] Index on `uid` + `createdAt` (composite)
  - [ ] `coupons` collection:
    - [ ] Index on `code` (ascending)
  - [ ] `affiliates` collection:
    - [ ] Index on `code` (ascending)

---

### 📋 Phase 5: Razorpay Configuration

- [ ] **Razorpay Account**
  - [ ] Account activated for live payments
  - [ ] KYC verification completed
  - [ ] Bank account linked for settlements

- [ ] **API Keys**
  - [ ] Live Key ID: `rzp_live_RKD5kwFAOZ05UD`
  - [ ] Live Key Secret: Stored securely in config.php
  - [ ] Test keys removed from production config

- [ ] **Webhook Setup**
  - [ ] Webhook URL: `https://attral.in/api/webhook.php`
  - [ ] Event: `payment.captured` enabled
  - [ ] Secret: Matches `RAZORPAY_WEBHOOK_SECRET` in config.php
  - [ ] Test webhook from Razorpay dashboard
  - [ ] Verify webhook receives and processes events

- [ ] **Payment Methods Enabled**
  - [ ] UPI
  - [ ] Cards (Debit/Credit)
  - [ ] Net Banking
  - [ ] Wallets (optional)

---

### 📋 Phase 6: Email Configuration (Brevo)

- [ ] **Brevo Account**
  - [ ] Account active and verified
  - [ ] Sender email verified (info@attral.in)
  - [ ] SMTP credentials generated

- [ ] **Email Settings**
  - [ ] SMTP host: `smtp-relay.brevo.com`
  - [ ] SMTP port: 587
  - [ ] SMTP security: STARTTLS
  - [ ] Daily sending limit not exceeded

- [ ] **Email Templates Working**
  - [ ] Order confirmation email formatted correctly
  - [ ] Invoice email with attachment working
  - [ ] Affiliate commission email working

- [ ] **DNS Records** (for better deliverability)
  - [ ] SPF record configured for attral.in
  - [ ] DKIM configured (via Brevo)
  - [ ] DMARC policy set (optional but recommended)

---

### 📋 Phase 7: Security Hardening

- [ ] **Sensitive Files Protected**
  - [ ] `config.php` not accessible via browser (returns 403 or 404)
  - [ ] `firebase-service-account.json` not accessible via browser
  - [ ] `.env` files (if any) not accessible
  - [ ] `vendor/` directory protected or outside web root

- [ ] **HTTPS Enabled**
  - [ ] SSL certificate installed and valid
  - [ ] All pages load via HTTPS
  - [ ] Mixed content warnings resolved
  - [ ] HSTS header enabled (optional)

- [ ] **Error Reporting Configured**
  - [ ] Production mode: `display_errors = 0`
  - [ ] Production mode: `error_reporting = E_ALL`
  - [ ] Production mode: `log_errors = 1`
  - [ ] Error log file location known and monitored

- [ ] **File Upload Protection**
  - [ ] Upload directories have proper permissions
  - [ ] File type validation in place
  - [ ] Maximum file size configured

---

### 📋 Phase 8: Testing on Staging/Production

#### 🧪 Test 1: Basic Order Flow (15 minutes)

- [ ] Navigate to https://attral.in/shop.html
- [ ] Add a product to cart
- [ ] Click "Proceed to Checkout"
- [ ] Fill in customer information
- [ ] Apply a valid coupon (optional)
- [ ] Click "Pay with Razorpay"
- [ ] Complete payment using test card:
  - Card: 4111 1111 1111 1111
  - CVV: Any 3 digits
  - Expiry: Any future date
  - **OR** use live payment if ready

**Verify**:
- [ ] Redirects to order-success.html (NOT cart.html)
- [ ] Order details displayed correctly
- [ ] Cart badge shows "0" items
- [ ] Email received with order confirmation
- [ ] Invoice attached to email

**Check Logs**:
- [ ] Browser console shows diagnostic logs
- [ ] No "🚫 BLOCKED redirect" messages
- [ ] Shows "🛒 Cart cleared" message

#### 🧪 Test 2: Firestore Verification (10 minutes)

- [ ] Open Firebase Console: https://console.firebase.google.com
- [ ] Navigate to Firestore Database
- [ ] Check `orders` collection
- [ ] Verify new order document exists with:
  - [ ] Correct order number (ATRL-XXXX format)
  - [ ] Razorpay order ID
  - [ ] Razorpay payment ID
  - [ ] Customer information
  - [ ] Product information
  - [ ] Pricing data
  - [ ] Status = "confirmed"
  - [ ] `uid` field (if user was logged in)
  - [ ] `createdAt` timestamp

#### 🧪 Test 3: Coupon Tracking (10 minutes)

- [ ] Place order with coupon code
- [ ] Check Firestore `coupons` collection
- [ ] Verify coupon document has:
  - [ ] `usageCount` incremented by 1
  - [ ] `payoutUsage` incremented (₹300 for affiliate, 1 for regular)
  - [ ] `updatedAt` timestamp updated

- [ ] Check subcollection `orders/{orderId}/couponIncrements/`
  - [ ] Guard document exists with hash ID
  - [ ] Contains coupon code and payment ID

- [ ] Refresh order-success page multiple times
- [ ] Verify counters DON'T increment again (idempotency working)

#### 🧪 Test 4: Affiliate Commission (10 minutes)

*Only if testing affiliate flow*

- [ ] Use affiliate link with code: `https://attral.in/index.html?ref=AFFILIATE_CODE`
- [ ] Complete purchase
- [ ] Check Firestore `affiliate_commissions` collection
- [ ] Verify commission record created with:
  - [ ] Affiliate ID
  - [ ] Order number
  - [ ] Commission amount (₹300)
  - [ ] Status = "pending"
- [ ] Verify affiliate receives commission email

#### 🧪 Test 5: Error Handling (15 minutes)

- [ ] Test with invalid coupon code
  - [ ] Verify error message shown to user
  - [ ] Verify order still processes successfully

- [ ] Test with expired coupon
  - [ ] Verify appropriate error message
  - [ ] Verify order still processes

- [ ] Refresh order-success page 5 times rapidly
  - [ ] Verify order NOT duplicated in Firestore
  - [ ] Verify no errors in console

- [ ] Test payment failure scenario
  - [ ] Cancel Razorpay modal
  - [ ] Verify stays on order.html
  - [ ] Verify cart NOT cleared

---

### 📋 Phase 9: Monitoring & Alerts Setup

- [ ] **Server Logs Accessible**
  - [ ] Know how to access Hostinger error logs
  - [ ] Bookmark error log location
  - [ ] Set up log rotation if needed

- [ ] **Email Monitoring**
  - [ ] Brevo dashboard bookmarked
  - [ ] Delivery rate dashboard accessible
  - [ ] Bounce/complaint notifications enabled

- [ ] **Firestore Monitoring**
  - [ ] Firebase console bookmarked
  - [ ] Usage quota dashboard accessible
  - [ ] Set up budget alerts (optional)

- [ ] **Razorpay Monitoring**
  - [ ] Payment dashboard accessible
  - [ ] Settlement account verified
  - [ ] Webhook logs accessible

- [ ] **Set Up Alerts** (optional but recommended)
  - [ ] Email alert for failed orders
  - [ ] Alert for high Firestore error rate
  - [ ] Alert for email bounces
  - [ ] Daily summary report

---

### 📋 Phase 10: Backup & Recovery

- [ ] **Database Backup**
  - [ ] Firestore automatic backups enabled
  - [ ] Export current Firestore data (baseline)
  - [ ] Store export in secure location

- [ ] **Code Backup**
  - [ ] Full codebase committed to Git
  - [ ] Tag current version: `git tag v1.0-post-fixes`
  - [ ] Push to remote repository

- [ ] **Configuration Backup**
  - [ ] `config.php` backed up securely (encrypted)
  - [ ] `firebase-service-account.json` backed up securely
  - [ ] Document all environment variables

- [ ] **Recovery Plan Documented**
  - [ ] Know how to restore from Firestore backup
  - [ ] Know how to rollback code deployment
  - [ ] Have emergency contact for Hostinger support

---

### 📋 Phase 11: Performance Optimization

- [ ] **Caching Configured**
  - [ ] `/api/.cache/` directory exists with 700 permissions
  - [ ] Token caching enabled in firestore_rest_client.php
  - [ ] Coupon validation cache working (5-minute TTL)

- [ ] **Asset Optimization**
  - [ ] Images compressed (product images)
  - [ ] CSS/JS minified (optional)
  - [ ] Gzip compression enabled on server

- [ ] **Database Optimization**
  - [ ] Firestore indexes created (see below)
  - [ ] Query patterns optimized

---

### 📋 Phase 12: Security Final Check

- [ ] **Sensitive Files NOT Accessible**
  - [ ] Test: `https://attral.in/api/config.php` returns 403/404
  - [ ] Test: `https://attral.in/api/firebase-service-account.json` returns 403/404
  - [ ] Test: `https://attral.in/api/vendor/` returns 403/404

- [ ] **SQL Injection Protection**
  - [ ] All database queries use prepared statements ✅ (already done)

- [ ] **XSS Protection**
  - [ ] User input sanitized before display
  - [ ] HTML special chars encoded

- [ ] **CSRF Protection** (optional for now)
  - [ ] Consider adding for admin forms

- [ ] **Rate Limiting** (optional)
  - [ ] Consider adding to prevent API abuse

---

## Deployment Steps

### 🚀 Step 1: Upload Files to Hostinger

Using Hostinger File Manager or FTP:

```bash
# Connect to your Hostinger account via FTP
# Navigate to public_html/static-site/

# Upload modified files:
1. order-success.html
2. order.html
3. api/firestore_order_manager_rest.php
4. api/firestore_order_manager.php
5. api/order_manager.php
6. api/webhook.php
```

**Verify Upload**:
- [ ] All files uploaded successfully
- [ ] File sizes match local files
- [ ] No upload errors in FTP log

---

### 🚀 Step 2: Verify Configuration Files

```bash
# Check these files exist on server:
/public_html/static-site/api/config.php
/public_html/static-site/api/firebase-service-account.json
/public_html/static-site/js/config.js
```

**Test Configuration**:
- [ ] Create `/api/test-config.php`:
```php
<?php
require 'config.php';
echo json_encode([
    'razorpay_configured' => isset($cfg['RAZORPAY_KEY_ID']),
    'smtp_configured' => isset($cfg['SMTP_HOST']),
    'firebase_exists' => file_exists(__DIR__ . '/firebase-service-account.json')
]);
```

- [ ] Visit `https://attral.in/api/test-config.php`
- [ ] Verify all return `true`
- [ ] **DELETE test-config.php after testing** (security)

---

### 🚀 Step 3: Test Firestore Connectivity

**Create Test Script**: `/api/test-firestore.php`

```php
<?php
require_once 'firestore_rest_client.php';

try {
    $client = new FirestoreRestClient(
        'e-commerce-1d40f',
        __DIR__ . '/firebase-service-account.json',
        true
    );
    
    // Test write
    $testData = [
        'test' => true,
        'timestamp' => firestoreTimestamp(),
        'source' => 'deployment_test'
    ];
    
    $result = $client->writeDocument('_test_connection', $testData);
    
    // Test read
    $readResult = $client->getDocument('_test_connection', $result['id']);
    
    // Cleanup
    $client->deleteDocument('_test_connection', $result['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Firestore REST API working correctly',
        'test_doc_id' => $result['id']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Visit `https://attral.in/api/test-firestore.php`
- [ ] Verify returns `"success": true`
- [ ] **DELETE test-firestore.php after testing** (security)

---

### 🚀 Step 4: Test Email System

**Create Test Script**: `/api/test-email.php`

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/vendor/autoload.php';
$cfg = include __DIR__ . '/config.php';

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $cfg['SMTP_HOST'];
$mail->SMTPAuth = true;
$mail->Username = $cfg['SMTP_USERNAME'];
$mail->Password = $cfg['SMTP_PASSWORD'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('info@attral.in', 'ATTRAL Test');
$mail->addAddress('YOUR_EMAIL@example.com'); // Replace with your email
$mail->Subject = 'Test Email - ' . date('H:i:s');
$mail->Body = 'Email system is working correctly!';

try {
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Email sent']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
}
```

- [ ] Update `YOUR_EMAIL@example.com` with your email
- [ ] Visit `https://attral.in/api/test-email.php`
- [ ] Check your inbox for test email
- [ ] **DELETE test-email.php after testing** (security)

---

### 🚀 Step 5: Configure Razorpay Webhook

1. **Login to Razorpay Dashboard**
   - [ ] Go to https://dashboard.razorpay.com

2. **Navigate to Webhooks**
   - [ ] Settings → Webhooks → Add New Webhook

3. **Configure Webhook**
   - [ ] URL: `https://attral.in/api/webhook.php`
   - [ ] Active Events:
     - [x] payment.captured
     - [x] payment.failed (optional)
     - [x] order.paid (optional)
   - [ ] Secret: Copy from Razorpay, paste in config.php as `RAZORPAY_WEBHOOK_SECRET`

4. **Test Webhook**
   - [ ] Use Razorpay's "Send Test Webhook" feature
   - [ ] Check server logs for webhook receipt
   - [ ] Verify webhook signature validation passes

---

### 🚀 Step 6: Create Required Directories

On server, create these directories if they don't exist:

```bash
mkdir -p /public_html/static-site/api/.cache
chmod 700 /public_html/static-site/api/.cache

mkdir -p /public_html/static-site/api/invoices
chmod 755 /public_html/static-site/api/invoices

mkdir -p /public_html/static-site/api/logs
chmod 755 /public_html/static-site/api/logs
```

Checklist:
- [ ] `.cache/` directory created (for token and coupon caching)
- [ ] `invoices/` directory created (for PDF generation)
- [ ] `logs/` directory created (for custom logging)

---

## Post-Deployment Testing

### ✅ Smoke Tests (30 minutes)

Run these tests IMMEDIATELY after deployment:

#### Test 1: Homepage Loads
- [ ] Visit https://attral.in
- [ ] Verify page loads without errors
- [ ] Check browser console for errors
- [ ] Verify Firebase initialized

#### Test 2: Product Browsing
- [ ] Visit https://attral.in/shop.html
- [ ] Verify products load
- [ ] Add product to cart
- [ ] Verify cart count updates

#### Test 3: Single Product Checkout (NO COUPON)
- [ ] Add product to cart
- [ ] Proceed to checkout
- [ ] Fill in details (use test data)
- [ ] Click "Pay with Razorpay"
- [ ] Complete payment (Razorpay test mode)
- [ ] **VERIFY**: Lands on order-success.html
- [ ] **VERIFY**: Cart badge shows "0"
- [ ] **VERIFY**: Order appears in Firestore within 5 seconds
- [ ] **VERIFY**: Email received within 30 seconds

#### Test 4: Order with Coupon
- [ ] Add product to cart
- [ ] Proceed to checkout
- [ ] Apply coupon code (e.g., "SAVE20" or any active coupon)
- [ ] Verify discount applied
- [ ] Complete payment
- [ ] **VERIFY**: Order created successfully
- [ ] **VERIFY**: Coupon `usageCount` incremented in Firestore
- [ ] **VERIFY**: Coupon `payoutUsage` incremented

#### Test 5: Affiliate Order
- [ ] Visit: `https://attral.in/index.html?ref=AFFILIATE_CODE`
- [ ] Add product to cart
- [ ] Complete checkout
- [ ] **VERIFY**: Order created
- [ ] **VERIFY**: Affiliate coupon `payoutUsage` increased by ₹300
- [ ] **VERIFY**: Commission record created in `affiliate_commissions`
- [ ] **VERIFY**: Affiliate receives commission email

#### Test 6: Idempotency Test
- [ ] Complete an order
- [ ] On order-success page, refresh page 5 times
- [ ] **VERIFY**: Order count in Firestore stays at 1 (no duplicates)
- [ ] **VERIFY**: Coupon counters don't increment again
- [ ] Check logs for "idempotent" messages

---

## Monitoring After Deployment

### 🔍 First 24 Hours - Intensive Monitoring

**Every 2 Hours**:
- [ ] Check Hostinger error logs for PHP errors
- [ ] Check Firestore for new orders
- [ ] Check Brevo for email delivery rate
- [ ] Check Razorpay for successful payments

**Look For**:
- ✅ "PRIMARY ORDER SYSTEM: firestore_order_manager_rest.php" in logs
- ❌ "DEPRECATION WARNING" or "NOTICE" (means fallback is being used)
- ❌ Any "FIRESTORE_MGR: INITIALIZATION FAILED" errors
- ❌ Any JWT token errors
- ❌ Any "BLOCKED redirect" messages

### 📊 First Week - Daily Monitoring

- [ ] **Daily**: Check order count in Firestore matches Razorpay
- [ ] **Daily**: Verify email delivery rate > 95%
- [ ] **Daily**: Check for any customer complaints
- [ ] **Weekly**: Review all error logs
- [ ] **Weekly**: Check Firestore quota usage

---

## Rollback Plan (If Issues Found)

### 🔄 Emergency Rollback Procedure

If critical issues found within first 24 hours:

1. **Restore Previous Versions**
   ```bash
   # Via FTP or File Manager
   # Restore these 6 files from backup:
   - order-success.html (remove cart clearing)
   - order.html (remove diagnostics)
   - firestore_order_manager_rest.php (remove PRIMARY label)
   - firestore_order_manager.php (remove DEPRECATED label)
   - order_manager.php (remove FALLBACK label)
   - webhook.php (remove enhanced logging)
   ```

2. **Verify System Restored**
   - [ ] Test basic order flow
   - [ ] Verify old behavior returns
   - [ ] Check error logs

3. **Document Issue**
   - [ ] What went wrong?
   - [ ] Error messages captured
   - [ ] Steps to reproduce

4. **Fix & Redeploy**
   - [ ] Fix identified issue locally
   - [ ] Test thoroughly
   - [ ] Re-deploy with fix

---

## Success Criteria

Deployment considered successful if:

- ✅ 100% of test orders appear in Firestore
- ✅ Cart clears automatically on order-success page
- ✅ Zero redirects to cart.html after payment
- ✅ Coupons increment exactly once per order
- ✅ Affiliate commissions tracked correctly (₹300 per order)
- ✅ Emails delivered within 30 seconds
- ✅ No critical errors in logs
- ✅ Payment flow completes in < 10 seconds
- ✅ Zero customer complaints in first 24 hours

---

## Quick Reference Commands

### Check Server Logs (Hostinger)
```bash
# Via File Manager: Navigate to error_log file
# Via SSH (if available): tail -f /path/to/error_log
```

### Check Firestore via Console
```
https://console.firebase.google.com/project/e-commerce-1d40f/firestore
```

### Check Razorpay Dashboard
```
https://dashboard.razorpay.com/app/payments
```

### Check Brevo Email Stats
```
https://app.brevo.com/statistics/email
```

### Browser Console Diagnostics
```javascript
// Run in browser console on order-success page
console.log('=== MANUAL DIAGNOSTICS ===');
console.log('Payment Success Flag:', sessionStorage.getItem('__ATTRAL_PAYMENT_SUCCESS'));
console.log('Order ID:', sessionStorage.getItem('__ATTRAL_ORDER_ID'));
console.log('Cart Items:', localStorage.getItem('attral_cart'));
console.log('Last Order Data:', sessionStorage.getItem('lastOrderData'));
```

---

## Support Contacts

### If Issues Arise

1. **Hostinger Support**
   - Live chat available 24/7
   - For server/PHP issues

2. **Firebase Support**
   - Firebase console → Support
   - For Firestore issues

3. **Razorpay Support**
   - dashboard.razorpay.com → Support
   - For payment issues

4. **Brevo Support**
   - app.brevo.com → Help
   - For email delivery issues

---

## Post-Deployment Documentation Update

After successful deployment:

- [ ] Update README.md with deployment date
- [ ] Document any issues encountered and solutions
- [ ] Update system architecture diagram (if exists)
- [ ] Archive old versions in `/deprecated/` folder
- [ ] Update this checklist with lessons learned

---

## Sign-Off

**Deployed By**: ___________________  
**Date**: ___________________  
**Time**: ___________________  
**Environment**: ☐ Staging  ☐ Production  
**Verification**: ☐ All tests passed  
**Issues Found**: ___________________  
**Rollback Required**: ☐ Yes  ☐ No  

---

## Appendix: Required Firestore Indexes

Create these indexes in Firebase Console if they don't exist:

### orders Collection
```
Composite Index 1:
- uid (Ascending)
- createdAt (Descending)

Composite Index 2:
- customer.email (Ascending)
- createdAt (Descending)

Single Field Indexes:
- razorpayPaymentId (Ascending)
- razorpayOrderId (Ascending)
- status (Ascending)
```

### coupons Collection
```
Single Field Indexes:
- code (Ascending)
- isActive (Ascending)
- affiliateCode (Ascending)
```

### affiliates Collection
```
Single Field Indexes:
- code (Ascending)
- email (Ascending)
```

### affiliate_commissions Collection
```
Composite Index:
- affiliateId (Ascending)
- createdAt (Descending)
```

**Create Indexes**: Firebase Console → Firestore → Indexes → Create Index

---

**Last Updated**: October 10, 2025  
**Version**: 1.0  
**Status**: Ready for Deployment


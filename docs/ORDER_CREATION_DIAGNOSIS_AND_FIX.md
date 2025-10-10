# 🔍 Order Creation Diagnosis & Fix Guide

## Current Status: ❌ BROKEN

According to your `KNOWN_ISSUES.md`, orders are **NOT being saved to Firestore properly**.

---

## 📋 Files Responsible for Successful Order Creation

Based on your documentation and code analysis, these are the **KEY FILES** that were working in your initial commit (`05af57c`):

### 1. **`static-site/api/firestore_order_manager.php`**
- **Purpose**: Main API endpoint for creating orders in Firestore
- **Route**: `/api/firestore_order_manager.php/create`
- **Function**: `createOrder()` - Lines 141-319
- **What it does**:
  - Receives order data from frontend
  - Validates required fields
  - Generates order number (ATRL-XXXX format)
  - Saves order to Firestore `orders` collection
  - Processes coupons and affiliate commissions
  - Returns success/failure response

### 2. **`static-site/api/webhook.php`**
- **Purpose**: Razorpay webhook handler for `payment.captured` events
- **What it does**:
  - Receives payment notifications from Razorpay
  - Validates webhook signature
  - Extracts customer data from payment notes
  - Creates order directly in Firestore
  - Calls firestore_order_manager.php as backup
  - Adds `uid` field for user association

### 3. **`static-site/order.html`**
- **Purpose**: Frontend checkout page
- **Key Functions**:
  - `initiatePayment()` - Lines 2060-2149: Creates Razorpay order
  - `handlePaymentSuccess()` - Lines 2346+: Processes successful payment
  - `postOrderWithRetry()` - Lines 2323-2340: Retries order creation
  - **Calls**: `/api/firestore_order_manager.php/create` after payment success

### 4. **`static-site/order-success.html`**
- **Purpose**: Order confirmation page
- **Key Function**: `createOrderFromSessionData()` - Creates order if not already created
- **What it does**:
  - Retrieves order data from sessionStorage
  - Calls firestore_order_manager API
  - Sends confirmation emails
  - Displays order details

---

## 🔍 What Changed Between Working → Broken

### Change #1: Firebase SDK Check Modified
**File**: `firestore_order_manager.php` (Line 35-40)

**BEFORE (Working):**
```php
if (!class_exists('\Kreait\Firebase\Factory')) {
    error_log("FIRESTORE: Firebase SDK not available, falling back to SQLite");
    define('FIRESTORE_FALLBACK', true);
}
```

**AFTER (Current - Broken):**
```php
if (!class_exists('Google\Cloud\Firestore\FirestoreClient')) {
    error_log("❌ [DEBUG] FIRESTORE_MGR: Firestore SDK not available - REQUIRED for operation");
    throw new Exception('Firestore SDK is required but not available');
}
```

**Issue**: Changed from using `Kreait\Firebase` SDK to `Google\Cloud\Firestore\FirestoreClient`
- This might mean the **wrong SDK is installed**
- Or the **required SDK is missing**

### Change #2: UID Extraction Moved
**File**: `webhook.php` (Lines 55-104)

**Change**: Moved `$notes` extraction to BEFORE it's used (this is actually a FIX)
```php
// Now extracts notes first at line 59
$notes = $payment['notes'] ?? [];

// Then uses it at line 104
'user_id' => $notes['uid'] ?? null
```

This change is **GOOD** and shouldn't cause the issue.

---

## 🚨 ROOT CAUSE ANALYSIS

Based on the code changes, the most likely issue is:

### **Wrong Firebase/Firestore SDK is being used**

Your code changed from:
- ❌ OLD: `Kreait\Firebase\Factory` (Firebase Admin SDK for PHP)
- ✅ NEW: `Google\Cloud\Firestore\FirestoreClient` (Google Cloud Firestore SDK)

**Problem**: The new SDK might not be installed or configured correctly.

---

## 🔧 SOLUTION: Fix the SDK Issue

### Option 1: Check Which SDK is Installed

Run this command on your server to check installed packages:
```bash
composer show | grep -i firebase
composer show | grep -i firestore
```

### Option 2: Install the Correct SDK

Your current code needs **Google Cloud Firestore SDK**:
```bash
cd static-site/api
composer require google/cloud-firestore
```

### Option 3: Revert to Working Version

If you want to use the Firebase Admin SDK (which was working), you need:
```bash
cd static-site/api  
composer require kreait/firebase-php
```

Then update `firestore_order_manager.php` line 36 back to:
```php
if (!class_exists('\Kreait\Firebase\Factory')) {
```

---

## 🧪 How to Test

### Test 1: Check SDK Availability
Create a test file `test-sdk.php`:
```php
<?php
require_once __DIR__ . '/static-site/api/vendor/autoload.php';

echo "Checking Firebase/Firestore SDKs:\n";
echo "Kreait\\Firebase\\Factory: " . (class_exists('\\Kreait\\Firebase\\Factory') ? '✅ YES' : '❌ NO') . "\n";
echo "Google\\Cloud\\Firestore\\FirestoreClient: " . (class_exists('Google\\Cloud\\Firestore\\FirestoreClient') ? '✅ YES' : '❌ NO') . "\n";
?>
```

Run: `php test-sdk.php`

### Test 2: Test Order Creation API
```bash
curl -X POST https://attral.in/api/firestore_order_manager.php/create \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "test_order_123",
    "payment_id": "test_pay_123",
    "customer": {"firstName": "Test", "lastName": "User", "email": "test@test.com", "phone": "1234567890"},
    "product": {"id": "test", "title": "Test Product", "price": 100},
    "pricing": {"subtotal": 100, "total": 100, "currency": "INR"},
    "shipping": {"address": "Test", "city": "Test", "state": "Test", "pincode": "123456", "country": "India"},
    "payment": {"method": "razorpay", "transaction_id": "test_123"}
  }'
```

Expected Response:
```json
{
  "success": true,
  "orderId": "...",
  "orderNumber": "ATRL-0001",
  "message": "Order created successfully"
}
```

---

## 📊 Key Files from Working GitHub Commit

To restore the working version, you need these files from commit `05af57c`:

1. **`static-site/api/firestore_order_manager.php`** (Working version)
2. **`static-site/api/webhook.php`** (Working version)  
3. **`static-site/api/composer.json`** (Contains correct SDK dependencies)
4. **`static-site/api/vendor/`** directory (Pre-installed SDKs)

You can restore these files with:
```bash
git checkout 05af57c -- static-site/api/firestore_order_manager.php
git checkout 05af57c -- static-site/api/webhook.php
git checkout 05af57c -- static-site/api/composer.json
```

Then run:
```bash
cd static-site/api
composer install
```

---

## 📝 Summary

**The Issue**: Your code switched from `Kreait\Firebase` SDK to `Google\Cloud\Firestore\FirestoreClient`, but the new SDK is likely not installed or not configured correctly.

**The Fix**: Either:
1. Install `google/cloud-firestore` package
2. OR revert to the working Firebase Admin SDK from commit `05af57c`

**Files to Check**:
- `static-site/api/composer.json` - SDK dependencies
- `static-site/api/vendor/autoload.php` - Verify SDK is loaded
- `static-site/api/firebase-service-account.json` - Service account credentials

**Next Steps**:
1. Run SDK availability test
2. Install missing SDK
3. Test order creation API
4. Place a test order on your website

---

## 🎯 Quick Fix Command

To quickly restore the working version:
```bash
cd "C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce"
git checkout 05af57c -- static-site/api/firestore_order_manager.php
git checkout 05af57c -- static-site/api/webhook.php
cd static-site/api
composer install
```

Then test the order creation.


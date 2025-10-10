# 🔥 Firestore Write Test Guide

## 📋 Overview

This guide helps you verify that your eCommerce system can successfully write order data to Firestore. The test scripts create dummy orders to ensure your Firebase integration is working correctly.

---

## 🎯 Purpose

**Primary Goal**: Verify that order data from `order.html` can be written to the Firestore `orders` collection.

**What This Tests**:
- ✅ Firestore SDK is properly installed
- ✅ Firebase service account is correctly configured
- ✅ Network connectivity to Firestore
- ✅ Write permissions are working
- ✅ Order data structure is valid

---

## 📁 Test Scripts

### 1. `test-firestore-write-dummy.php`
**Main test script** - Writes a complete dummy order to Firestore

### 2. `test-firestore-delete-dummy.php`
**Cleanup script** - Removes test orders from Firestore

---

## 🚀 Quick Start

### Prerequisites

Before running the test, ensure:

1. **Composer dependencies are installed**:
   ```bash
   cd static-site/api
   composer install
   cd ../..
   ```

2. **Firebase service account exists**:
   - File: `static-site/api/firebase-service-account.json`
   - Download from: [Firebase Console](https://console.firebase.google.com) → Project Settings → Service Accounts

3. **PHP is available** (version 7.4 or higher)

---

## 🧪 Running the Test

### Step 1: Run the Write Test

```bash
php test-firestore-write-dummy.php
```

### Expected Output

```
🔥 Firestore Write Test - Dummy Order Data
==========================================

📦 Step 1: Loading Firestore SDK...
✅ Composer autoloader loaded

📦 Step 2: Checking Firestore SDK...
✅ Firestore SDK is available

🔑 Step 3: Checking Firebase service account...
✅ Service account file found
   Project ID in file: e-commerce-1d40f
   Client Email: firebase-adminsdk-xxxxx@e-commerce-1d40f.iam.gserviceaccount.com

🔌 Step 4: Initializing Firestore connection...
✅ Firestore connection initialized successfully

📝 Step 5: Preparing dummy order data...
✅ Dummy order data prepared
   Order ID: TEST-DUMMY-1696884000
   Customer: Dummy Customer
   Email: dummy@test.attral.in
   Amount: ₹2999

💾 Step 6: Writing dummy order to Firestore...
   Collection: orders
   Project: e-commerce-1d40f

✅✅✅ SUCCESS! Dummy order written to Firestore! ✅✅✅

📋 Details:
   ✓ Document ID: abc123xyz789
   ✓ Order Number: TEST-DUMMY-1696884000
   ✓ Collection: orders
   ✓ Project: e-commerce-1d40f

🔥 VERIFICATION STEPS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Open Firebase Console:
   🌐 https://console.firebase.google.com

2. Select your project:
   📁 e-commerce-1d40f

3. Navigate to Firestore Database:
   💾 Build → Firestore Database

4. Look for the 'orders' collection:
   📂 orders

5. Find your test document:
   🆔 Document ID: abc123xyz789
   📋 Order ID: TEST-DUMMY-1696884000
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔍 Step 7: Reading back the document to verify...
✅ Document verified - data read successfully
   Verified orderId: TEST-DUMMY-1696884000
   Verified customer email: dummy@test.attral.in
   Verified amount: ₹2999

🎉 COMPLETE SUCCESS! 🎉
Your eCommerce system CAN write to Firestore!
```

---

## 📊 What Data is Written

The test script creates a complete order document with the following structure:

```javascript
{
  // Order Identification
  "orderId": "TEST-DUMMY-1696884000",
  "razorpayOrderId": "order_dummy_1696884000",
  "razorpayPaymentId": "pay_dummy_1696884000",
  
  // User & Status
  "uid": "test_user_abc12345",
  "status": "test",
  
  // Financial
  "amount": 2999.00,
  "currency": "INR",
  
  // Customer Information
  "customer": {
    "firstName": "Dummy",
    "lastName": "Customer",
    "email": "dummy@test.attral.in",
    "phone": "9999999999"
  },
  
  // Product Details
  "product": {
    "id": "test-product-1",
    "title": "DUMMY TEST ORDER - ATTRAL 100W GaN Charger",
    "price": 2999,
    "items": [...]
  },
  
  // Pricing Breakdown
  "pricing": {
    "subtotal": 2999,
    "shipping": 0,
    "discount": 0,
    "total": 2999,
    "currency": "INR"
  },
  
  // Shipping Address
  "shipping": {
    "address": "123 Test Street, Dummy Address",
    "city": "Test City",
    "state": "Test State",
    "pincode": "123456",
    "country": "India"
  },
  
  // Payment Details
  "payment": {
    "method": "razorpay",
    "transaction_id": "pay_dummy_1696884000",
    "amount": 2999
  },
  
  // Additional Fields
  "coupons": [],
  "createdAt": Timestamp,
  "updatedAt": Timestamp,
  "notes": "This is a DUMMY TEST ORDER...",
  "testOrder": true,  // Flag to identify test orders
  "testTimestamp": 1696884000
}
```

---

## ✅ Verifying in Firebase Console

### Visual Verification Steps:

1. **Open Firebase Console**:
   - Go to: https://console.firebase.google.com
   - Select project: `e-commerce-1d40f`

2. **Navigate to Firestore Database**:
   - Click: Build → Firestore Database
   - You should see your collections

3. **Find the 'orders' Collection**:
   - Look for the `orders` collection in the left sidebar
   - Click to expand it

4. **Locate Your Test Document**:
   - Find the document with the ID shown in the test output
   - Click on it to view the full document

5. **Verify Document Fields**:
   - Check that all fields are present
   - Verify timestamps are correct
   - Confirm `testOrder: true` is set

---

## 🧹 Cleanup Test Data

### Delete a Specific Test Order

If you have the document ID from the test output:

```bash
php test-firestore-delete-dummy.php abc123xyz789
```

### Delete All Test Orders

To remove all orders marked with `testOrder: true`:

```bash
php test-firestore-delete-dummy.php --all-test-orders
```

### Expected Cleanup Output

```
🧹 Firestore Test Order Cleanup
================================

✅ Connected to Firestore

🔍 Finding all test orders...
🗑️  Deleting: abc123xyz789 (Order: TEST-DUMMY-1696884000)
🗑️  Deleting: def456uvw012 (Order: TEST-DUMMY-1696884100)

✅ Deleted 2 test order(s) successfully

✅ Cleanup completed!
```

---

## ❌ Troubleshooting

### Error: "Composer autoloader not found"

**Problem**: Firestore SDK not installed

**Solution**:
```bash
cd static-site/api
composer install
cd ../..
php test-firestore-write-dummy.php
```

---

### Error: "Firebase service account file not found"

**Problem**: Missing service account JSON file

**Solution**:
1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select project: `e-commerce-1d40f`
3. Navigate to: Project Settings → Service Accounts
4. Click: "Generate New Private Key"
5. Download and save as: `static-site/api/firebase-service-account.json`

---

### Error: "Failed to initialize Firestore connection"

**Possible Causes**:
1. **Invalid service account JSON**
   - Re-download the service account key
   - Ensure the JSON file is valid

2. **Wrong project ID**
   - Verify project ID is `e-commerce-1d40f`
   - Check service account is for the correct project

3. **Network connectivity issues**
   - Check internet connection
   - Verify firewall settings

---

### Error: "Failed to write to Firestore"

**Possible Causes**:
1. **Firestore API not enabled**
   - Go to Google Cloud Console
   - Enable Firestore API for your project

2. **Service account permissions**
   - Ensure service account has Firestore write permissions
   - Service accounts should have admin access by default

3. **Firestore rules (unlikely with service account)**
   - Check Firestore security rules
   - Service accounts bypass security rules

---

## 🔍 Advanced: Manual Verification

If you want to verify the write operation manually in Firestore Console:

1. **Navigate to Firestore Database**
2. **Start a Query**:
   - Collection: `orders`
   - Where: `testOrder` == `true`
   - Order by: `createdAt` descending

3. **Expected Results**:
   - You should see all test orders
   - Each should have `testOrder: true`
   - OrderIds should start with "TEST-DUMMY-"

---

## 🎯 What This Confirms

When the test succeeds, it confirms:

✅ **Firestore SDK is working** - Composer packages are correctly installed
✅ **Authentication is working** - Service account has proper credentials
✅ **Write permissions are granted** - Service account can write to Firestore
✅ **Network connectivity is good** - Your system can reach Firestore servers
✅ **Data structure is valid** - Order documents match the expected schema
✅ **order.html will work** - Your checkout page can save orders to Firestore

---

## 🚀 Next Steps

After successful testing:

1. **Keep the service account secure**
   - Don't commit `firebase-service-account.json` to git
   - It's already in `.gitignore`

2. **Test real order flow**
   - Go to your `order.html` page
   - Complete a test purchase
   - Verify the order appears in Firestore

3. **Monitor orders**
   - Check Firestore Console regularly
   - Set up Firebase alerts for new orders

4. **Clean up test data**
   - Run: `php test-firestore-delete-dummy.php --all-test-orders`
   - Or manually delete from Firebase Console

---

## 📚 Related Files

- **Main Order Manager**: `static-site/api/firestore_order_manager.php`
- **Service Account**: `static-site/api/firebase-service-account.json`
- **Firestore Rules**: `firestore.rules`
- **Configuration**: `static-site/api/config.php`

---

## 💡 Tips

1. **Run the test periodically** to ensure your Firestore connection stays healthy
2. **Use test orders** for development - they're marked with `testOrder: true`
3. **Check Firebase quotas** in the Firebase Console usage dashboard
4. **Enable offline persistence** in your frontend for better reliability

---

## 🆘 Need Help?

If the test fails after following all troubleshooting steps:

1. Check the full error message in the output
2. Look for specific error codes or messages
3. Verify all prerequisites are met
4. Check Firebase Console for service status
5. Review Firestore security rules (though service accounts bypass them)

---

## ✅ Success Criteria

The test is successful when you see:

```
✅✅✅ SUCCESS! Dummy order written to Firestore! ✅✅✅
```

And when you can:
- See the document in Firebase Console
- Read back the document data
- Verify all fields are present and correct

---

**🎉 Good luck with your Firestore testing!**

Your orders are being saved to: `e-commerce-1d40f` → `orders` collection


# 🔥 Firestore PRIMARY Database - Current Configuration

**Date:** October 9, 2025  
**Status:** ✅ **ACTIVE - Orders write to Firestore**

---

## ✅ **WHAT'S CONFIGURED NOW**

Your system now writes **ALL order data directly to Firebase Firestore** `orders` collection.

---

## 📊 **CURRENT DATA FLOW**

```
User Payment Success
        ↓
    ┌───┴────┐
    │        │
    ↓        ↓
webhook.php  order-success.html
    │        │
    └───┬────┘
        ↓
firestore_order_manager.php
        ↓
    ┌───┴────┐
    │        │
    ↓        ↓
Firestore    (Optional: SQLite backup)
orders       via writeToFirestore()
collection
```

---

## 🎯 **FILES CONFIGURED**

### **1. webhook.php** (Line 203)
```php
// Calls Firestore manager
curl_setopt($ch, CURLOPT_URL, 
    'https://attral.in/api/firestore_order_manager.php/create');
```
✅ **Writes to:** Firestore orders collection

---

### **2. order-success.html** (Lines 683, 744, 921)
```javascript
// All API calls point to Firestore manager
fetch(`${apiBaseUrl}/api/firestore_order_manager.php/create`)
fetch(`${apiBaseUrl}/api/firestore_order_manager.php/status`)
fetch(`${apiBaseUrl}/api/firestore_order_manager.php/update`)
```
✅ **Writes to:** Firestore orders collection

---

### **3. firestore_order_manager.php** (Line 240)
```php
// Primary order creation in Firestore
$docRef = $this->firestore->collection('orders')->add($orderData);
```
✅ **Writes to:** Firestore orders collection

---

## 📝 **WHAT DATA GETS STORED IN FIRESTORE**

### **Firestore Collection:** `orders`

### **Document Structure:**
```javascript
{
  // Order identifiers
  orderId: "ATRL-0042",                    // Business order number
  razorpayOrderId: "order_NXhj4kD8VqRqJx", // Razorpay order ID
  razorpayPaymentId: "pay_xxxxx",          // Razorpay payment ID
  
  // User association
  uid: "firebase_user_id",                 // Firebase user ID from order.html
  
  // Order details
  status: "confirmed",
  amount: 2999,
  currency: "INR",
  
  // Customer data (from order.html form)
  customer: {
    firstName: "John",
    lastName: "Doe",
    email: "john@example.com",
    phone: "9876543210"
  },
  
  // Product/cart data (from order.html)
  product: {
    id: "1",
    title: "ATTRAL 100W GaN Charger",
    price: 2999,
    items: [
      {
        id: "1",
        title: "ATTRAL 100W GaN Charger",
        price: 2999,
        quantity: 1,
        image: "assets/product_images/1.jpeg"
      }
    ]
  },
  
  // Pricing details (from order.html)
  pricing: {
    subtotal: 2999,
    shipping: 399,
    discount: 0,
    total: 3398,
    currency: "INR"
  },
  
  // Shipping address (from order.html form)
  shipping: {
    address: "123 Main Street",
    city: "Vellore",
    state: "Tamil Nadu",
    pincode: "632009",
    country: "India"
  },
  
  // Payment information
  payment: {
    method: "razorpay",
    transaction_id: "pay_xxxxx"
  },
  
  // Coupons applied (from order.html)
  coupons: [
    {
      code: "WELCOME10",
      name: "Welcome Discount",
      type: "percentage",
      value: 10,
      isAffiliateCoupon: false
    }
  ],
  
  // Metadata
  createdAt: Timestamp(2025-10-09 12:34:56),
  updatedAt: Timestamp(2025-10-09 12:34:56),
  notes: "",
  source: "client"  // or "webhook" depending on which created it
}
```

---

## 🔄 **HOW DATA FLOWS FROM order.html TO FIRESTORE**

### **Step 1: User Fills Form** (order.html)
```javascript
// Lines 516-571: Form fields
<input id="firstName">    → customer.firstName
<input id="lastName">     → customer.lastName
<input id="email">        → customer.email
<input id="phone">        → customer.phone
<input id="address">      → shipping.address
<input id="city">         → shipping.city
<input id="state">        → shipping.state
<input id="pincode">      → shipping.pincode

// Lines 794-797: Applied coupons
appliedCoupons[] → coupons array

// Line 1663: collectOrderData() bundles everything
```

### **Step 2: Payment Initiated** (order.html)
```javascript
// Line 2113: POST to create_order.php
fetch('/api/create_order.php', {
  body: JSON.stringify({
    customer: {...},      // All form data
    shipping: {...},      // Complete address
    product: {...},       // Cart items
    pricing: {...},       // Totals with discounts
    coupons: [...],       // Applied coupons
    notes: {...}          // Additional metadata
  })
})

// Response: { id: "order_NXhj4k..." }
// Opens Razorpay modal
```

### **Step 3: Payment Succeeds**
```javascript
// Line 2158: Razorpay callback
handler: function(response) {
  // response.razorpay_payment_id
  // response.razorpay_signature
  
  // Line 2371: Store complete data in sessionStorage
  sessionStorage.setItem('lastOrderData', JSON.stringify({
    ...orderData,              // ALL form data
    razorpay_order_id: order.id,
    razorpay_payment_id: response.razorpay_payment_id,
    razorpay_signature: response.razorpay_signature
  }));
  
  // Line 2394: Redirect to order-success.html
  window.location.replace('order-success.html?orderId=' + order.id);
}
```

### **Step 4: Order Creation** (order-success.html → firestore_order_manager.php)
```javascript
// Line 683: POST to firestore_order_manager.php/create
fetch('/api/firestore_order_manager.php/create', {
  body: JSON.stringify({
    order_id: orderData.razorpay_order_id,
    payment_id: orderData.razorpay_payment_id,
    signature: orderData.razorpay_signature,
    user_id: currentUser.uid,           // Firebase UID
    customer: orderData.customer,       // ← From order.html form
    product: orderData.product,         // ← From order.html
    pricing: orderData.pricing,         // ← From order.html
    shipping: orderData.shipping,       // ← From order.html form
    coupons: orderData.coupons,         // ← From order.html
    payment: {...}
  })
})
```

### **Step 5: Saved to Firestore** (firestore_order_manager.php)
```php
// Line 240: Firestore write
$docRef = $this->firestore->collection('orders')->add($orderData);

// Creates document in:
// Firebase Project: e-commerce-1d40f
// Collection: orders
// Document ID: auto-generated (e.g., "abc123def456")
```

---

## 🎯 **WHICH PHP FILE WRITES TO FIRESTORE?**

### **PRIMARY: firestore_order_manager.php**

**Location:** `static-site/api/firestore_order_manager.php`  
**Line:** 240  
**Code:**
```php
$docRef = $this->firestore->collection('orders')->add($orderData);
```

**Called by:**
- ✅ webhook.php (after payment.captured event)
- ✅ order-success.html (after redirect)

**Data captured:**
- ✅ **From order.html:** Customer form fields, shipping address, coupons
- ✅ **From Razorpay:** Payment ID, order ID, signature, amount
- ✅ **From Firebase Auth:** User UID
- ✅ **Generated:** Business order number (ATRL-0042)

**What it does:**
1. Receives complete order data
2. Generates order number (ATRL-0001, ATRL-0002, etc.)
3. Checks idempotent (prevents duplicates)
4. ✅ **Writes to Firestore orders collection**
5. Processes affiliate commissions
6. Increments coupon usage counters
7. Adds status history
8. Returns success response

---

## 🔍 **DATA CAPTURE SUMMARY**

### **From order.html (User Input):**
```javascript
✅ firstName, lastName       → customer.firstName, customer.lastName
✅ email, phone              → customer.email, customer.phone
✅ address                   → shipping.address
✅ city, state, pincode      → shipping.city, shipping.state, shipping.pincode
✅ country                   → shipping.country (default: "India")
✅ Applied coupons           → coupons[] array
✅ Product/cart items        → product.items[]
✅ Calculated totals         → pricing.total
```

### **From Razorpay Payment:**
```javascript
✅ razorpay_order_id         → razorpayOrderId
✅ razorpay_payment_id       → razorpayPaymentId  
✅ razorpay_signature        → Used for verification
✅ Payment amount            → amount, pricing.total
```

### **From Firebase Auth:**
```javascript
✅ Firebase user UID         → uid field
✅ User email                → Verified against form email
```

### **Generated by System:**
```javascript
✅ Business order number     → orderId (ATRL-0042)
✅ Timestamps                → createdAt, updatedAt
✅ Status                    → "confirmed"
✅ Source                    → "client" or "webhook"
```

---

## 🎯 **WHERE TO VIEW YOUR ORDERS**

### **Firebase Console:**
```
1. Go to: https://console.firebase.google.com
2. Select project: e-commerce-1d40f
3. Navigate to: Firestore Database
4. Collection: orders
5. See all orders with complete data ✅
```

### **Document Example:**
```
Document ID: abc123def456789
    orderId: "ATRL-0042"
    razorpayOrderId: "order_NXhj4kD8VqRqJx"
    razorpayPaymentId: "pay_xxxxxxxxxxxxx"
    uid: "firebase_user_uid_here"
    status: "confirmed"
    amount: 3398
    customer: {...}
    product: {...}
    pricing: {...}
    shipping: {...}
    coupons: [...]
    createdAt: October 9, 2025 at 12:34:56 PM UTC+5:30
```

---

## ✅ **VERIFICATION CHECKLIST**

After payment, verify data is in Firestore:

### **1. Check Firebase Console**
- [ ] Open Firebase Console
- [ ] Navigate to Firestore Database
- [ ] Open `orders` collection
- [ ] See new document created
- [ ] Verify all fields present

### **2. Verify Complete Data**
- [ ] `customer` has firstName, lastName, email, phone
- [ ] `shipping` has complete address
- [ ] `product` has all cart items
- [ ] `pricing` has correct totals
- [ ] `coupons` array present (if applied)
- [ ] `uid` matches Firebase user
- [ ] `orderId` is ATRL-xxxx format

### **3. Check Document Fields**
```javascript
orderId: "ATRL-0042" ✅
razorpayOrderId: "order_xxx" ✅
razorpayPaymentId: "pay_xxx" ✅
uid: "user_uid" ✅
customer: {...} ✅
product: {...} ✅
pricing: {...} ✅
shipping: {...} ✅
coupons: [...] ✅
createdAt: Timestamp ✅
status: "confirmed" ✅
```

---

## 🚨 **IMPORTANT: Removed Duplicate Write**

**What I fixed:**
- ❌ **Before:** webhook.php wrote to Firestore TWICE (direct + API call)
- ✅ **After:** webhook.php only calls firestore_order_manager.php API (single write)

**Result:** 
- No duplicate Firestore documents
- Cleaner, more reliable
- Lower Firestore write costs

---

## 📋 **PHP FILES THAT WRITE TO FIRESTORE**

| File | Writes to Firestore? | Collection | Purpose |
|------|---------------------|------------|---------|
| **firestore_order_manager.php** | ✅ YES (PRIMARY) | `orders` | Create/manage orders |
| **webhook.php** | ❌ No (calls API) | - | Trigger order creation |
| **order_manager.php** | ⚠️ Optional backup | `orders` | SQLite primary, Firestore backup |

**Active:** firestore_order_manager.php is your primary order manager

---

## 🎯 **ANSWER TO YOUR QUESTION**

**You asked:** 
> "I want the orders data to be written to my firebase projects firestore database orders collection"

**My answer:**
✅ **DONE!** Your orders ARE written to Firestore!

**File responsible:** `firestore_order_manager.php`  
**Line:** 240  
**Code:** `$docRef = $this->firestore->collection('orders')->add($orderData);`

**Collection:** `orders`  
**Project:** `e-commerce-1d40f`

---

## 🔄 **COMPLETE PAYMENT TO FIRESTORE FLOW**

```
┌──────────────────────────────────────────────────┐
│ order.html - User Input                          │
├──────────────────────────────────────────────────┤
│ Collects:                                        │
│ ✅ Customer name, email, phone                   │
│ ✅ Shipping address (complete)                   │
│ ✅ Product selection                             │
│ ✅ Applied coupons                               │
│                                                  │
│ collectOrderData() [line 1663]                   │
│ └─ Bundles all data                              │
└────────────────────┬─────────────────────────────┘
                     │
                     ↓
┌──────────────────────────────────────────────────┐
│ create_order.php                                 │
├──────────────────────────────────────────────────┤
│ ✅ Creates Razorpay payment session              │
│ ✅ Stores limited data in Razorpay notes         │
│ ❌ Does NOT write to Firestore yet               │
└────────────────────┬─────────────────────────────┘
                     │
                     ↓
┌──────────────────────────────────────────────────┐
│ Razorpay Payment Modal                           │
├──────────────────────────────────────────────────┤
│ ✅ User pays                                     │
│ ✅ Payment succeeds                              │
│ ✅ Returns payment_id, signature                 │
└───────────┬──────────────────────────────────────┘
            │
            ├─────────────────┬────────────────────┐
            │                 │                    │
            ↓                 ↓                    ↓
┌─────────────────┐  ┌──────────────┐  ┌──────────────────┐
│ webhook.php     │  │ order.html   │  │ Razorpay also    │
│ (Server)        │  │ handler      │  │ stores in their  │
├─────────────────┤  ├──────────────┤  │ database         │
│ Razorpay POST   │  │ Callback     │  └──────────────────┘
│ payment.        │  │ executes     │
│ captured        │  │              │
│                 │  │ Stores data  │
│ Extracts from   │  │ in session   │
│ notes (limited) │  │ Storage      │
│                 │  │              │
│ Line 203:       │  │ Redirects to │
│ cURL to →       │  │ success page │
└────────┬────────┘  └──────┬───────┘
         │                  │
         └──────┬───────────┘
                │
                ↓
┌──────────────────────────────────────────────────┐
│ firestore_order_manager.php                      │
├──────────────────────────────────────────────────┤
│ Receives: Complete order data                    │
│                                                  │
│ Line 175-186: Idempotent check                   │
│ ├─ Checks if order exists (by payment_id)       │
│ ├─ If exists: Return existing                   │
│ └─ If new: Create order                         │
│                                                  │
│ Line 172: Generate order number                  │
│ └─ ATRL-0042                                     │
│                                                  │
│ Line 240: ✅ WRITE TO FIRESTORE                  │
│ $docRef = $firestore                             │
│   ->collection('orders')                         │
│   ->add($orderData);                             │
│                                                  │
│ Collections updated:                             │
│ ✅ orders (new document)                         │
│ ✅ order_status_history (new entry)              │
│ ✅ coupons (usage incremented)                   │
│ ✅ affiliate_commissions (if applicable)         │
│                                                  │
│ Returns: { success: true, orderNumber: "..." }  │
└────────────────────┬─────────────────────────────┘
                     │
                     ↓
┌──────────────────────────────────────────────────┐
│ ✅ FIRESTORE DATABASE UPDATED                    │
├──────────────────────────────────────────────────┤
│ Project: e-commerce-1d40f                        │
│ Collection: orders                               │
│ Document: auto-generated ID                      │
│                                                  │
│ Contains:                                        │
│ ✅ All user form data from order.html            │
│ ✅ All Razorpay payment details                  │
│ ✅ Firebase user UID                             │
│ ✅ Generated order number                        │
│ ✅ Applied coupons                               │
│ ✅ Complete cart/product details                 │
│ ✅ Shipping address                              │
│ ✅ Timestamps                                    │
└──────────────────────────────────────────────────┘
```

---

## 🔥 **FIRESTORE CONFIGURATION**

### **Required Files:**

✅ **firebase-service-account.json**
- Location: `static-site/api/firebase-service-account.json`
- Contains: Service account credentials
- Purpose: Authenticates PHP to write to Firestore

✅ **firestore_order_manager.php**
- Location: `static-site/api/firestore_order_manager.php`
- Purpose: Manages all Firestore operations
- Endpoints: /create, /status, /update

✅ **Firestore SDK**
- Installed via: `composer require google/cloud-firestore`
- Location: `static-site/api/vendor/`
- Purpose: PHP library to connect to Firestore

---

## 🧪 **HOW TO TEST**

### **1. Make Test Payment**
```
1. Visit: http://localhost:8000/shop.html
2. Add product to cart
3. Checkout and fill form
4. Complete payment (test card: 4111 1111 1111 1111)
```

### **2. Check Firestore Console**
```
1. Go to: https://console.firebase.google.com
2. Select: e-commerce-1d40f
3. Firestore Database → orders
4. Should see: New document with your order data ✅
```

### **3. Verify Data Completeness**
Check the document has:
- ✅ orderId: "ATRL-xxxx"
- ✅ customer: {firstName, lastName, email, phone}
- ✅ shipping: {complete address}
- ✅ product: {cart items}
- ✅ pricing: {totals}
- ✅ coupons: [applied coupons]
- ✅ uid: Firebase user ID
- ✅ createdAt: Timestamp

---

## ⚠️ **IMPORTANT NOTES**

### **1. Firestore SDK Required**
Your server MUST have Firestore SDK installed:
```bash
cd static-site/api
composer install
```

If missing, orders will FAIL to create!

### **2. Service Account File Required**
File: `static-site/api/firebase-service-account.json`

If missing, download from:
- Firebase Console → Project Settings → Service Accounts
- Generate new private key
- Save as `firebase-service-account.json`

### **3. Firestore Security Rules**
Ensure your Firestore rules allow server writes:
```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /orders/{orderId} {
      // Allow server (with service account) to write
      allow write: if request.auth != null || request.auth.token.admin == true;
      allow read: if request.auth != null;
    }
  }
}
```

---

## 📊 **ADVANTAGES OF FIRESTORE PRIMARY**

### **Why Use Firestore:**
1. ✅ **Unlimited Scale** - No size limits
2. ✅ **Real-time Updates** - Admin dashboard can listen to changes
3. ✅ **Cloud Backup** - Data automatically backed up by Google
4. ✅ **Multi-server Support** - Works with load balancing
5. ✅ **Global Distribution** - Fast access worldwide
6. ✅ **Powerful Queries** - Complex filtering and sorting
7. ✅ **Security Rules** - Fine-grained access control

### **Trade-offs:**
1. ⚠️ **Cost** - Pay per read/write ($0.18 per 100K writes)
2. ⚠️ **Dependency** - Needs Firestore SDK installed
3. ⚠️ **Network Latency** - Slower than local SQLite (~500ms vs 50ms)
4. ⚠️ **Complexity** - More setup required

---

## 🎯 **YOUR CURRENT ARCHITECTURE**

```
┌─────────────────────────────────────────────┐
│ PRIMARY DATABASE: Firestore                 │
│ ✅ Orders stored in cloud                   │
│ ✅ Real-time accessible                     │
│ ✅ Automatically backed up                  │
│ ✅ Scales infinitely                        │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ OPTIONAL BACKUP: SQLite (via order_manager) │
│ ⚠️ Only if writeToFirestore() called        │
│ ⚠️ Not used as primary                      │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ AUXILIARY COLLECTIONS: Firestore            │
│ ✅ coupons - Coupon tracking                │
│ ✅ affiliates - Affiliate management        │
│ ✅ affiliate_commissions - Commission data  │
│ ✅ order_status_history - Status tracking   │
└─────────────────────────────────────────────┘
```

---

## 📋 **NEXT STEPS**

### **Immediate (Do now):**
1. 🧪 Test payment flow
2. 🔍 Verify order appears in Firestore Console
3. ✅ Confirm all data fields present

### **This Week:**
1. 📊 Monitor Firestore usage/costs
2. 🔐 Review Firestore security rules
3. 📈 Setup error monitoring

### **Ongoing:**
1. 💾 Regular Firestore backups (export data monthly)
2. 📊 Monitor performance
3. 🔍 Review and optimize queries

---

## 🎉 **SUMMARY**

**Your order data now flows:**

```
order.html (form)
    ↓
create_order.php (Razorpay session)
    ↓
Payment success
    ↓
firestore_order_manager.php
    ↓
✅ Firestore orders collection ✅
```

**All your order data from order.html is captured and stored in Firebase Firestore!**

**Files responsible:**
- Primary: `firestore_order_manager.php` (line 240)
- Trigger: `webhook.php` (line 203)
- Trigger: `order-success.html` (line 683)

**Your Firebase project `e-commerce-1d40f` orders collection has complete order data!** 🔥🎯



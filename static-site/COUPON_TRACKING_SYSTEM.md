# 🎫 Coupon Tracking System - Complete Guide

## 📋 Overview

This system automatically tracks coupon usage across your eCommerce platform by:
1. **Transferring** coupon data from order page to success page
2. **Storing** coupon codes in Firestore orders collection
3. **Auto-incrementing** coupon usage count via Cloud Functions
4. **Maintaining** accurate usage statistics in real-time

---

## 🔄 Data Flow

```
User applies coupon(s) on order.html
        ↓
Coupons stored in appliedCoupons array
        ↓
Payment initiated via Razorpay
        ↓
Order data (including coupons) saved to sessionStorage
        ↓
Redirect to order-success.html
        ↓
Coupons retrieved from sessionStorage
        ↓
Coupons written to Firestore orders collection
        ↓
Cloud Function triggered on order creation
        ↓
Coupon usageCount incremented in coupons collection
```

---

## 🛠️ Implementation Details

### 1️⃣ Coupon Data Transfer (order.html → order-success.html)

**Location:** `static-site/order.html` (Lines 1622-1630, 2314-2327)

Coupons are transferred via sessionStorage in the `collectOrderData()` function:

```javascript
// Coupon information included in order data
coupons: appliedCoupons.map(coupon => ({
  code: coupon.code,
  name: coupon.name,
  type: coupon.type,
  value: coupon.value,
  isNewsletterCoupon: coupon.isNewsletterCoupon || false,
  isAffiliateCoupon: !!coupon.isAffiliateCoupon,
  affiliateCode: coupon.affiliateCode || null
}))
```

Stored in sessionStorage before redirect:

```javascript
sessionStorage.setItem('lastOrderData', JSON.stringify(orderDataForSuccess));
```

### 2️⃣ Coupon Storage in Firestore

**Location:** `static-site/order-success.html` (Lines 604-615, 649-661, 741-757)

The `upsertOrderCoupons()` function writes coupon data to Firestore:

```javascript
async function upsertOrderCoupons(orderNumber, coupons, order){
  const payload = {
    orderId: orderNumber,
    status: 'confirmed',
    coupons: coupons,
    amount_rupees_exact: totalRupees,
    amount_paise_exact: Math.round(totalRupees * 100)
  };
  
  await fetch(`${apiBaseUrl}/api/firestore_order_manager.php/update`, {
    method: 'POST',
    body: JSON.stringify(payload)
  });
}
```

**Triggered:** After successful payment and email operations complete.

### 3️⃣ Cloud Function - Auto Increment Usage

**Location:** `static-site/fulfillment-functions/coupon-usage-tracker.js`

**Trigger:** `onCreate` event in `orders/{orderId}` collection

```javascript
exports.onOrderCreated = functions
  .firestore
  .document('orders/{orderId}')
  .onCreate(async (snapshot, context) => {
    // Extract coupons from order
    const coupons = extractCouponsFromOrder(orderData);
    
    // Increment usage for each coupon
    for (const coupon of coupons) {
      await incrementCouponUsage(coupon.code);
    }
  });
```

**What it does:**
- Extracts all coupon codes from newly created order
- Finds each coupon in the `coupons` collection
- Increments the `usageCount` field
- Updates `updatedAt` timestamp
- Logs processing results to the order document

---

## 📊 Firestore Data Structure

### Orders Collection

```json
{
  "orderId": "order_xyz123",
  "razorpay_order_id": "order_xyz123",
  "razorpay_payment_id": "pay_abc456",
  "amount": 1299,
  "amountPaise": 129900,
  "customer": { "email": "user@example.com", ... },
  "coupons": [
    {
      "code": "WELCOME10",
      "name": "Welcome Discount",
      "type": "percentage",
      "value": 10,
      "isAffiliateCoupon": false
    },
    {
      "code": "FREESHIP",
      "name": "Free Shipping",
      "type": "shipping",
      "value": 0,
      "isAffiliateCoupon": false
    }
  ],
  "couponUsageProcessed": true,
  "couponUsageProcessedAt": "2025-10-07T10:30:00Z",
  "couponUsageResults": [
    {
      "success": true,
      "couponCode": "WELCOME10",
      "previousCount": 42,
      "newCount": 43
    },
    {
      "success": true,
      "couponCode": "FREESHIP",
      "previousCount": 15,
      "newCount": 16
    }
  ]
}
```

### Coupons Collection

```json
{
  "code": "WELCOME10",
  "name": "Welcome Discount",
  "type": "percentage",
  "value": 10,
  "minAmount": 500,
  "maxDiscount": 500,
  "isActive": true,
  "validUntil": "2025-12-31T23:59:59Z",
  "usageLimit": 1000,
  "usageCount": 43,  // ✅ Auto-incremented by Cloud Function
  "updatedAt": "2025-10-07T10:30:00Z",
  "isAffiliateCoupon": false
}
```

---

## 🚀 Deployment Instructions

### Step 1: Deploy Cloud Functions

```bash
cd static-site/fulfillment-functions
npm install
firebase deploy --only functions
```

This deploys:
- ✅ `onOrderCreated` - Auto-increment on new orders
- ✅ `incrementCouponUsageHttp` - Manual increment endpoint
- ✅ `reprocessOrderCouponsHttp` - Reprocess existing orders

### Step 2: Verify Deployment

```bash
firebase functions:log --only onOrderCreated
```

### Step 3: Test the System

Create a test order with coupons and verify:
1. Order appears in Firestore `orders` collection with `coupons` array
2. Cloud Function logs show processing
3. Coupon `usageCount` incremented in `coupons` collection
4. Order has `couponUsageProcessed: true` field

---

## 🧪 Testing & Manual Operations

### Test Cloud Function Locally

```bash
firebase emulators:start --only functions,firestore
```

### Manual Coupon Increment (HTTP Endpoint)

```bash
curl -X POST https://your-region-your-project.cloudfunctions.net/incrementCouponUsageHttp \
  -H "Content-Type: application/json" \
  -d '{"couponCode": "WELCOME10", "orderId": "order_xyz123"}'
```

**Response:**
```json
{
  "success": true,
  "message": "Coupon usage incremented successfully",
  "couponCode": "WELCOME10",
  "previousCount": 42,
  "newCount": 43
}
```

### Reprocess Existing Order

For orders created before this system was deployed:

```bash
curl -X POST https://your-region-your-project.cloudfunctions.net/reprocessOrderCouponsHttp \
  -H "Content-Type: application/json" \
  -d '{"orderId": "order_xyz123"}'
```

**Response:**
```json
{
  "success": true,
  "message": "Coupons reprocessed successfully",
  "orderId": "order_xyz123",
  "couponsProcessed": 2,
  "successful": 2,
  "failed": 0,
  "results": [...]
}
```

---

## 🔍 Monitoring & Debugging

### View Cloud Function Logs

```bash
# All logs
firebase functions:log

# Specific function
firebase functions:log --only onOrderCreated

# Real-time logs
firebase functions:log --only onOrderCreated --tail
```

### Check Processing Errors

Errors are logged to `coupon_processing_errors` collection:

```javascript
{
  "orderId": "order_xyz123",
  "error": "Coupon not found: INVALID_CODE",
  "timestamp": "2025-10-07T10:30:00Z"
}
```

### Debug Checklist

If coupons aren't incrementing:

1. ✅ **Verify Cloud Function is deployed:**
   ```bash
   firebase functions:list
   ```

2. ✅ **Check order has coupons array:**
   ```javascript
   // In Firestore console, verify order document has:
   coupons: [{code: "...", name: "...", ...}]
   ```

3. ✅ **Check Cloud Function logs:**
   ```bash
   firebase functions:log --only onOrderCreated
   ```

4. ✅ **Verify coupon exists in coupons collection:**
   - Coupon code must match exactly (case-insensitive)
   - Coupon document must exist before order creation

5. ✅ **Check Firestore permissions:**
   - Cloud Functions need read/write access to both collections

---

## 🛡️ Cart Clearing Logic - REMOVED

### What Changed:

**Before:** Cart was automatically cleared on order-success.html
**After:** Cart clearing logic completely removed from all pages

### Affected Files:

✅ **order-success.html** - Cart clearing logic removed (line 1077-1078)
✅ **order.html** - No cart clearing logic (verified)
✅ **cart.html** - No cart clearing logic (verified)

### User Experience:

- Cart **persists** after successful payment
- Users can view cart items even after purchase
- Users must manually clear cart if desired
- Cart maintains accurate item count in header

---

## 📈 Usage Analytics

### Query Total Coupon Usage

```javascript
// Get all coupons with usage stats
const coupons = await db.collection('coupons')
  .orderBy('usageCount', 'desc')
  .get();

coupons.forEach(doc => {
  const data = doc.data();
  console.log(`${data.code}: ${data.usageCount} uses`);
});
```

### Query Orders by Coupon

```javascript
// Find all orders that used a specific coupon
const orders = await db.collection('orders')
  .where('coupons', 'array-contains', {code: 'WELCOME10'})
  .get();

console.log(`${orders.size} orders used WELCOME10`);
```

### Rebuild All Coupon Counts

If counts become inconsistent, use the existing rebuild function:

```bash
curl -X POST https://your-region-your-project.cloudfunctions.net/rebuildCouponUsageHttp
```

This recalculates all usage counts from the orders collection.

---

## 🎯 Key Features

✅ **Automatic Tracking** - No manual intervention needed
✅ **Real-time Updates** - Usage increments immediately on order creation
✅ **Multiple Coupons** - Supports up to 2 coupons per order
✅ **Affiliate Support** - Tracks affiliate coupon codes separately
✅ **Error Handling** - Failed increments logged to dedicated collection
✅ **Audit Trail** - Processing results stored in order document
✅ **Manual Operations** - HTTP endpoints for corrections and reprocessing
✅ **No Cart Clearing** - Cart persists after purchase as requested

---

## 🆘 Support & Troubleshooting

### Common Issues:

**Issue:** Coupon count not incrementing
**Solution:** Check Cloud Function deployment and logs

**Issue:** Duplicate increments
**Solution:** Cloud Function runs once per order creation (onCreate event)

**Issue:** Missing coupons in order
**Solution:** Verify sessionStorage transfer and API upsert call

**Issue:** Wrong usage count
**Solution:** Run rebuild function to recalculate from all orders

---

## 📝 Summary

This coupon tracking system provides:

1. ✅ **Seamless data transfer** from checkout to success page
2. ✅ **Persistent storage** in Firestore orders collection
3. ✅ **Automatic increment** via Cloud Functions on order creation
4. ✅ **Accurate tracking** of coupon usage statistics
5. ✅ **No cart clearing** logic interfering with user experience

The system is production-ready and scales automatically with Firebase Cloud Functions.

---

**Last Updated:** October 7, 2025
**Version:** 1.0.0
**Maintainer:** ATTRAL Development Team

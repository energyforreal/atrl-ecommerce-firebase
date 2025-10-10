# 🧪 Comprehensive Testing Guide - ATTRAL E-Commerce

**System**: ATTRAL E-Commerce Platform  
**Test Environment**: Hostinger + Firestore  
**Date**: October 10, 2025

---

## Pre-Test Checklist ✅

Before running any tests, verify:

- [ ] All 6 modified files uploaded to Hostinger
- [ ] `firebase-service-account.json` exists in `/api/` directory
- [ ] `config.php` has valid Razorpay credentials (test or live)
- [ ] Firestore project `e-commerce-1d40f` is accessible
- [ ] Brevo SMTP credentials are valid
- [ ] Browser console is open (F12) for diagnostics

---

## Test Suite 1: Basic Order Flow (Critical) 🔴

**Duration**: 15 minutes  
**Priority**: CRITICAL - Must pass before production

### Test 1.1: Single Product Order (No Coupons)

**Steps**:
1. Open browser in Incognito/Private mode
2. Navigate to `https://attral.in/shop.html`
3. Click "Add to Cart" on any product
4. Click cart icon, then "Proceed to Checkout"
5. Fill in customer information (or auto-populate if logged in)
6. Click "Pay with Razorpay"
7. Complete payment using test card (Razorpay test mode):
   - Card: 4111 1111 1111 1111
   - CVV: 123
   - Expiry: Any future date
8. Click "Pay" in Razorpay modal

**Expected Results**:
- ✅ Redirect to `order-success.html?orderId=order_XXX`
- ✅ Success page shows "Order Confirmed! 🎉"
- ✅ Cart badge shows "0" items
- ✅ Order details displayed correctly
- ✅ No redirect to cart.html

**Browser Console Expected Logs**:
```
=== PAYMENT SUCCESS DIAGNOSTICS ===
💳 Razorpay Order ID: order_[ID]
💰 Amount Paid: [amount] INR
===================================
🚀 IMMEDIATE redirect to success page

=== ORDER SUCCESS PAGE DIAGNOSTICS ===
📍 Current URL: .../order-success.html?orderId=order_[ID]
🛒 Cart Items: null
===================================
🛒 Cart cleared after successful order confirmation
```

**Firestore Verification**:
1. Go to Firebase Console → Firestore
2. Navigate to `orders` collection
3. Find document with `razorpayOrderId` = your order ID
4. Verify fields:
   - `orderId`: ATRL-XXXX
   - `status`: "confirmed"
   - `amount`: [correct amount]
   - `customer`: [your details]
   - `uid`: [user ID if logged in, null otherwise]

**PASS Criteria**: All expected results + console logs + Firestore document exists

---

### Test 1.2: Cart Multi-Item Order

**Steps**:
1. Clear cart: `localStorage.removeItem('attral_cart')` in console
2. Refresh page
3. Add 2-3 different products to cart
4. Proceed to checkout
5. Complete payment (same as Test 1.1)

**Expected Results**:
- ✅ All cart items shown in order details
- ✅ Total amount calculated correctly
- ✅ Cart clears after success
- ✅ Firestore document has `product.items` array

**PASS Criteria**: Multi-item order processes correctly + cart clears

---

## Test Suite 2: Coupon & Affiliate Tracking 🟡

**Duration**: 20 minutes  
**Priority**: HIGH - Core business logic

### Test 2.1: Regular Coupon Application

**Prerequisites**: Create test coupon in Firestore or via coupon-admin.html
- Code: `TEST20`
- Type: percentage
- Value: 20
- Min Amount: 0
- Active: true

**Steps**:
1. Add product to cart
2. Go to checkout (order.html)
3. Enter coupon code: `TEST20`
4. Click "Apply"
5. Verify discount shown in summary
6. Complete payment

**Expected Results**:
- ✅ Coupon validation succeeds (server-side)
- ✅ Discount applied to total
- ✅ Order includes coupon in `coupons` array
- ✅ After order: Check Firestore `coupons` collection:
  - `usageCount` incremented by 1
  - `payoutUsage` incremented by 1
  - `updatedAt` timestamp updated

**Firestore Query** (to verify):
```javascript
// In browser console on Firestore website
db.collection('coupons').where('code', '==', 'TEST20').get()
  .then(snap => snap.forEach(doc => console.log(doc.data())))
```

**PASS Criteria**: Coupon counters increment exactly once

---

### Test 2.2: Affiliate Coupon Tracking

**Prerequisites**: 
- Affiliate exists in Firestore with code (e.g., `AFF-JOHN123`)
- Affiliate coupon created with same code
- Coupon marked as `isAffiliateCoupon: true`

**Steps**:
1. Navigate to site with affiliate link: `?ref=AFF-JOHN123`
2. Add product to cart
3. Go to checkout
4. Apply affiliate coupon code
5. Complete payment

**Expected Results**:
- ✅ Coupon validates and applies
- ✅ Order saved to Firestore
- ✅ After order: Check `coupons` collection:
  - `usageCount` +1
  - `payoutUsage` +₹300 (fixed commission)
  - `isAffiliateCoupon` = true
  - `affiliateCode` = AFF-JOHN123
- ✅ Check `affiliate_commissions` collection:
  - New commission record created
  - Amount: ₹300
  - Status: "pending"
- ✅ Affiliate receives email notification

**PASS Criteria**: Affiliate commission tracked correctly (₹300)

---

### Test 2.3: Multiple Coupons

**Steps**:
1. Apply first coupon (e.g., `SAVE10`)
2. Apply second coupon (e.g., affiliate code)
3. Complete payment

**Expected Results**:
- ✅ Both coupons applied
- ✅ Discounts stacked correctly
- ✅ Order includes both coupons in array
- ✅ Both coupons increment in Firestore
- ✅ Affiliate coupon shows ₹300 payout

**PASS Criteria**: Multi-coupon support works correctly

---

## Test Suite 3: Idempotency (Critical for Payment Systems) 🔴

**Duration**: 10 minutes  
**Priority**: CRITICAL - Prevents duplicate charges/orders

### Test 3.1: Order Creation Idempotency

**Steps**:
1. Complete an order successfully
2. On order-success.html page, note the order ID
3. **Refresh the page** (F5) 3-5 times
4. Check Firestore `orders` collection
5. Check server logs

**Expected Results**:
- ✅ Only ONE order document in Firestore
- ✅ Order number doesn't change across refreshes
- ✅ Page displays same order details
- ✅ Server logs show "Idempotent hit" messages

**Server Log Expected**:
```
✅ [DEBUG] FIRESTORE_MGR: Idempotent hit for payment pay_XXX, returning existing order
```

**PASS Criteria**: No duplicate orders created, idempotency working

---

### Test 3.2: Coupon Increment Idempotency

**Steps**:
1. Complete order with coupon (note starting `usageCount`)
2. Refresh order-success page multiple times
3. Check Firestore coupon document

**Expected Results**:
- ✅ `usageCount` incremented ONLY ONCE
- ✅ `payoutUsage` incremented ONLY ONCE
- ✅ Guard documents exist in `orders/{id}/couponIncrements/`

**PASS Criteria**: Coupon counters don't increment on page refresh

---

## Test Suite 4: Cart Clearing Verification 🟢

**Duration**: 5 minutes  
**Priority**: HIGH - Just implemented

### Test 4.1: Cart Clears After Success

**Steps**:
1. Add items to cart (verify cart badge shows count)
2. Complete order successfully
3. **On order-success.html**, check cart badge
4. Check browser console for cart clearing logs
5. Check localStorage: `localStorage.getItem('attral_cart')`

**Expected Results**:
- ✅ Cart badge shows "0"
- ✅ Console shows: "🛒 Cart cleared after successful order confirmation"
- ✅ localStorage returns `null` for `attral_cart`

**Browser Console Commands**:
```javascript
// Should return null or "[]"
localStorage.getItem('attral_cart')
```

**PASS Criteria**: Cart completely cleared after order success

---

### Test 4.2: Cart Persists on Payment Failure

**Steps**:
1. Add items to cart
2. Start checkout
3. In Razorpay modal, click "X" to close (cancel payment)
4. Check cart badge

**Expected Results**:
- ✅ Cart badge still shows item count
- ✅ Cart items NOT cleared
- ✅ User can retry payment

**PASS Criteria**: Cart only clears on successful payment

---

## Test Suite 5: Email Delivery 📧

**Duration**: 15 minutes  
**Priority**: MEDIUM - Non-blocking

### Test 5.1: Order Confirmation Email

**Steps**:
1. Complete test order with valid email address
2. Wait 30 seconds
3. Check email inbox (including spam folder)
4. Check server logs

**Expected Results**:
- ✅ Email received within 30 seconds
- ✅ Subject: "✅ Order Confirmation - [Order ID]"
- ✅ Email contains order number, amount, customer details
- ✅ Email formatting looks professional

**Server Log Expected**:
```
CUSTOMER EMAIL: ✅ Confirmation sent to customer@email.com for order ATRL-XXXX
```

**PASS Criteria**: Email received with correct information

---

### Test 5.2: Invoice Attachment

**Steps**:
1. Complete order
2. Check email for attachment
3. Download and open invoice

**Expected Results**:
- ✅ Invoice attached to email (HTML or PDF)
- ✅ Invoice contains all order details
- ✅ Invoice is readable and well-formatted

**PASS Criteria**: Invoice generated and attached correctly

---

### Test 5.3: Affiliate Commission Email

**Prerequisites**: Order with affiliate coupon

**Steps**:
1. Complete order with affiliate coupon
2. Check affiliate's email inbox

**Expected Results**:
- ✅ Affiliate receives commission notification
- ✅ Email shows ₹300 commission amount
- ✅ Email includes order number

**PASS Criteria**: Affiliate notified of commission

---

## Test Suite 6: Redirect Protection 🛡️

**Duration**: 10 minutes  
**Priority**: HIGH - Critical for user experience

### Test 6.1: No Redirect During Payment

**Steps**:
1. Start checkout process
2. Click "Pay with Razorpay"
3. In Razorpay modal, try opening browser console
4. Try typing `window.location.href = 'cart.html'` in console
5. Complete payment

**Expected Results**:
- ✅ Console shows: "🚫 BLOCKED redirect via href assignment to: cart.html"
- ✅ Stay on Razorpay modal (redirect blocked)
- ✅ After payment, redirect to order-success.html works

**PASS Criteria**: Redirects blocked during payment

---

### Test 6.2: Watchdog Timer

**Steps**:
1. Complete order and land on order-success.html
2. In browser console, try: `history.pushState({}, '', 'cart.html')`
3. Wait 1 second

**Expected Results**:
- ✅ Console shows: "🚫 BLOCKED redirect via history.pushState"
- ✅ URL restored to order-success.html by watchdog
- ✅ Console shows: "🛡️ Watchdog: restoring success page"

**PASS Criteria**: Watchdog restores URL if changed

---

## Test Suite 7: Error Scenarios ⚠️

**Duration**: 20 minutes  
**Priority**: MEDIUM - Graceful degradation

### Test 7.1: Invalid Coupon Code

**Steps**:
1. On checkout page, enter invalid coupon: `INVALID123`
2. Click "Apply"

**Expected Results**:
- ✅ Error message: "Invalid coupon code"
- ✅ No discount applied
- ✅ Can still complete order
- ✅ Order processes normally without coupon

**PASS Criteria**: Graceful handling of invalid coupon

---

### Test 7.2: Expired Coupon

**Prerequisites**: Create expired coupon in Firestore (validUntil in past)

**Steps**:
1. Try to apply expired coupon

**Expected Results**:
- ✅ Error: "This coupon has expired"
- ✅ Server-side validation catches it
- ✅ Order can proceed without coupon

**PASS Criteria**: Expired coupons rejected properly

---

### Test 7.3: Minimum Order Not Met

**Prerequisites**: Coupon with minAmount > 0

**Steps**:
1. Add low-price item to cart (less than minAmount)
2. Try to apply coupon

**Expected Results**:
- ✅ Error: "Minimum order of ₹XXX required"
- ✅ Coupon not applied
- ✅ Can proceed with order

**PASS Criteria**: Minimum amount validation works

---

### Test 7.4: Network Interruption

**Steps**:
1. Start order process
2. Complete payment successfully
3. While on order-success.html, open DevTools
4. Go to Network tab → Throttle to "Offline"
5. Refresh page

**Expected Results**:
- ✅ Fallback to sessionStorage works
- ✅ Order details still displayed
- ✅ Error logged but page doesn't crash
- ✅ Email sending may fail (non-critical)

**PASS Criteria**: Graceful degradation to cached data

---

## Test Suite 8: User Dashboard Integration 👤

**Duration**: 10 minutes  
**Priority**: MEDIUM - User-facing

### Test 8.1: Orders Appear in Dashboard

**Steps**:
1. Complete test order while logged in (Google Sign-In)
2. Navigate to `user-dashboard.html`
3. Click "Orders" tab

**Expected Results**:
- ✅ Recently placed order appears in list
- ✅ Order shows correct status
- ✅ Order shows correct amount
- ✅ Order details match Firestore

**Query to Verify**:
```javascript
// In user-dashboard.html console
const user = window.AttralFirebase.auth.currentUser;
window.AttralFirebase.db.collection('orders')
  .where('uid', '==', user.uid)
  .get()
  .then(snap => {
    console.log('User orders:', snap.size);
    snap.forEach(doc => console.log(doc.data()));
  });
```

**PASS Criteria**: Orders visible in user dashboard

---

## Test Suite 9: Affiliate Dashboard Integration 💰

**Duration**: 15 minutes  
**Priority**: MEDIUM - Affiliate-facing

### Test 9.1: Referred Orders Tracking

**Prerequisites**: Affiliate account with code

**Steps**:
1. Share affiliate link: `https://attral.in/index.html?ref=YOUR_CODE`
2. Open in Incognito mode (simulate new customer)
3. Complete purchase using affiliate link
4. Login as affiliate
5. Navigate to `affiliate-dashboard.html`

**Expected Results**:
- ✅ Referred order appears in dashboard
- ✅ Earnings updated: ₹300
- ✅ Total referrals incremented by 1
- ✅ Commission status: "pending"

**Firestore Verification**:
```javascript
// Check affiliate commission
db.collection('affiliate_commissions')
  .where('affiliateCode', '==', 'YOUR_CODE')
  .get()
  .then(snap => snap.forEach(doc => console.log(doc.data())));
```

**PASS Criteria**: Affiliate commission tracked correctly

---

## Test Suite 10: Admin Dashboard 👨‍💼

**Duration**: 10 minutes  
**Priority**: MEDIUM - Admin-facing

### Test 10.1: Orders Visible in Admin

**Steps**:
1. Navigate to `dashboard-original.html` or `admin-dashboard.html`
2. Login with admin credentials
3. Check orders list

**Expected Results**:
- ✅ Recent orders displayed
- ✅ Order status shown
- ✅ Customer information visible
- ✅ Can update order status

**PASS Criteria**: Admin can view and manage orders

---

### Test 10.2: Coupon Statistics

**Steps**:
1. Navigate to `coupon-admin.html`
2. Check coupon statistics
3. Find coupon used in test
4. Verify usage count

**Expected Results**:
- ✅ Total usage count matches Firestore
- ✅ Affiliate coupons show payout amount (₹300 × usage)
- ✅ Regular coupons show usage count

**PASS Criteria**: Coupon stats match actual usage

---

## Diagnostic Commands

### Browser Console Commands

```javascript
// Check cart status
console.log('Cart:', localStorage.getItem('attral_cart'));

// Check payment flags
console.log('Payment Success:', sessionStorage.getItem('__ATTRAL_PAYMENT_SUCCESS'));
console.log('Order ID:', sessionStorage.getItem('__ATTRAL_ORDER_ID'));

// Check last order data
console.log('Last Order:', JSON.parse(sessionStorage.getItem('lastOrderData')));

// Manually clear cart (for testing)
localStorage.removeItem('attral_cart');
window.Attral.initHeaderCartCount();

// Check Firestore connection
console.log('Firebase:', window.AttralFirebase);
console.log('Auth:', window.AttralFirebase?.auth?.currentUser);

// Query user's orders
const user = window.AttralFirebase.auth.currentUser;
if (user) {
  window.AttralFirebase.db.collection('orders')
    .where('uid', '==', user.uid)
    .orderBy('createdAt', 'desc')
    .limit(5)
    .get()
    .then(snap => {
      console.log('User orders:', snap.size);
      snap.forEach(doc => console.log(doc.id, doc.data()));
    });
}
```

### Server Log Locations (Hostinger)

1. Login to Hostinger dashboard
2. Go to File Manager → `public_html/api/`
3. Check error logs (usually in parent directory)
4. Look for files: `error_log` or `php_error.log`

**Search for**:
- "PRIMARY ORDER SYSTEM" - confirms REST API is active
- "DEPRECATION WARNING" - shows if old system used
- "ORDER SAVED TO FIRESTORE SUCCESSFULLY" - confirms write
- "Coupon results" - shows coupon processing

---

## Test Results Template

Copy this template to track your test results:

```markdown
## Test Results - [Date]

### Test Suite 1: Basic Order Flow
- [ ] Test 1.1: Single Product Order - PASS / FAIL
  - Notes: 
- [ ] Test 1.2: Cart Multi-Item Order - PASS / FAIL
  - Notes:

### Test Suite 2: Coupon & Affiliate Tracking
- [ ] Test 2.1: Regular Coupon - PASS / FAIL
  - Notes:
- [ ] Test 2.2: Affiliate Coupon - PASS / FAIL
  - Notes:
- [ ] Test 2.3: Multiple Coupons - PASS / FAIL
  - Notes:

### Test Suite 3: Idempotency
- [ ] Test 3.1: Order Creation - PASS / FAIL
  - Notes:
- [ ] Test 3.2: Coupon Increment - PASS / FAIL
  - Notes:

### Test Suite 4: Cart Clearing
- [ ] Test 4.1: Cart Clears After Success - PASS / FAIL
  - Notes:
- [ ] Test 4.2: Cart Persists on Failure - PASS / FAIL
  - Notes:

### Test Suite 5: Email Delivery
- [ ] Test 5.1: Order Confirmation - PASS / FAIL
  - Notes:
- [ ] Test 5.2: Invoice Attachment - PASS / FAIL
  - Notes:
- [ ] Test 5.3: Affiliate Email - PASS / FAIL
  - Notes:

### Overall Status
- Critical Issues: [number] found
- Medium Issues: [number] found
- Minor Issues: [number] found

### Production Readiness: YES / NO / NEEDS_WORK
```

---

## Rollback Plan (If Issues Found)

### Quick Rollback Steps

1. **Restore cart clearing removal** (if cart clearing causes issues):
   ```bash
   # Revert order-success.html changes
   git checkout HEAD -- static-site/order-success.html
   ```

2. **Use SDK version** (if REST API fails):
   - Update webhook.php line 203 to call `firestore_order_manager.php`
   - Verify Composer vendor directory exists

3. **Use SQLite** (if Firestore completely down):
   - Update order-success.html line 683 to call `order_manager.php`
   - Orders saved locally but won't appear in dashboards

### Backup Verification

Before testing, take backups:
```bash
# Backup modified files
cp static-site/order-success.html static-site/order-success.html.backup
cp static-site/order.html static-site/order.html.backup
cp static-site/api/firestore_order_manager_rest.php static-site/api/firestore_order_manager_rest.php.backup
```

---

## Success Criteria Summary

**System is production-ready when**:

- ✅ 100% of test orders appear in Firestore `orders` collection
- ✅ Cart clears on order-success.html (verified in console)
- ✅ Zero redirects to cart.html after payment
- ✅ Coupons increment exactly once per order
- ✅ Affiliate commissions track ₹300 per sale
- ✅ Emails delivered within 30 seconds
- ✅ Idempotency prevents duplicates
- ✅ Diagnostic logs show complete flow
- ✅ No critical errors in server logs
- ✅ User/admin/affiliate dashboards show correct data

**Expected Pass Rate**: 95%+ on first attempt (system is well-built)

---

## Support & Troubleshooting

### If Tests Fail

1. **Check browser console** - comprehensive diagnostics added
2. **Check server logs** - error_log statements throughout
3. **Check Firestore console** - verify documents created
4. **Review diagnostic report** - DIAGNOSTIC_REPORT_COMPLETE.md
5. **Contact developer** - include console logs + order ID

### Common Issues & Fixes

| Issue | Check | Fix |
|-------|-------|-----|
| Cart not clearing | Console logs, localStorage | Verify Attral.clearCartSafely exists |
| Order not in Firestore | Server logs, JWT token | Check service account file |
| Coupons not incrementing | Guard documents | Check coupon tracking logs |
| Email not sent | SMTP config | Verify Brevo credentials |
| Redirect blocked | Console for "🚫 BLOCKED" | This is expected during payment |

---

**Testing Status**: 📋 Ready to Execute  
**Estimated Time**: 2 hours for complete testing  
**Recommended**: Start with Test Suite 1 (Critical)


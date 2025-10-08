# ✅ Coupon Tracking System - Deployment Checklist

## 📋 Pre-Deployment Checklist

### 1. Code Verification

- [x] ✅ Coupon data transfer implemented in `order.html`
- [x] ✅ Coupon storage implemented in `order-success.html`
- [x] ✅ Cloud Function created: `coupon-usage-tracker.js`
- [x] ✅ Cloud Function exported in `index.js`
- [x] ✅ Cart clearing logic removed from `order-success.html`
- [x] ✅ No cart clearing logic in `order.html`
- [x] ✅ No cart clearing logic in `cart.html`

### 2. Dependencies

- [ ] Node.js installed (v14 or higher)
- [ ] Firebase CLI installed (`npm install -g firebase-tools`)
- [ ] Firebase project initialized
- [ ] Firebase Admin SDK configured

### 3. Firestore Setup

- [ ] `orders` collection exists
- [ ] `coupons` collection exists
- [ ] Firestore security rules allow Cloud Functions to read/write
- [ ] Indexes created if needed

---

## 🚀 Deployment Steps

### Step 1: Install Dependencies

```bash
cd static-site/fulfillment-functions
npm install
```

**Expected output:**
```
added 150 packages in 10s
```

### Step 2: Login to Firebase

```bash
firebase login
```

**Expected output:**
```
✔ Success! Logged in as your-email@example.com
```

### Step 3: Select Firebase Project

```bash
firebase use --add
```

Select your project from the list.

### Step 4: Deploy Cloud Functions

```bash
firebase deploy --only functions
```

**Expected output:**
```
✔ functions[onOrderCreated(us-central1)] Successful create operation.
✔ functions[incrementCouponUsageHttp(us-central1)] Successful create operation.
✔ functions[reprocessOrderCouponsHttp(us-central1)] Successful create operation.

✔ Deploy complete!
```

**Deployed Functions:**
- ✅ `onOrderCreated` - Automatic trigger on new orders
- ✅ `incrementCouponUsageHttp` - Manual increment endpoint
- ✅ `reprocessOrderCouponsHttp` - Reprocess existing orders

---

## 🧪 Post-Deployment Testing

### Test 1: Create Test Order with Coupon

1. **Navigate to your site**
2. **Add product to cart**
3. **Go to checkout** (`order.html`)
4. **Apply a coupon** (e.g., "WELCOME10")
5. **Complete payment** (use test mode)
6. **Verify redirect** to `order-success.html`

**Expected Results:**
- ✅ Order appears in Firestore `orders` collection
- ✅ Order has `coupons` array with coupon data
- ✅ Cloud Function logs show processing
- ✅ Coupon `usageCount` incremented in `coupons` collection
- ✅ Order has `couponUsageProcessed: true`
- ✅ Cart NOT cleared (items still in cart)

### Test 2: Check Cloud Function Logs

```bash
firebase functions:log --only onOrderCreated
```

**Expected log entries:**
```
📦 New order created: {orderId}
🎫 Processing 1 coupon(s) for order {orderId}: WELCOME10
✅ Incremented usage for coupon WELCOME10: 42 → 43
📊 Coupon processing complete for order {orderId}: 1 successful, 0 failed
```

### Test 3: Verify Firestore Data

**Check Orders Collection:**
```javascript
{
  "orderId": "order_xxx",
  "coupons": [
    {
      "code": "WELCOME10",
      "name": "Welcome Discount",
      "type": "percentage",
      "value": 10
    }
  ],
  "couponUsageProcessed": true,
  "couponUsageProcessedAt": {timestamp},
  "couponUsageResults": [
    {
      "success": true,
      "couponCode": "WELCOME10",
      "previousCount": 42,
      "newCount": 43
    }
  ]
}
```

**Check Coupons Collection:**
```javascript
{
  "code": "WELCOME10",
  "usageCount": 43,  // ✅ Incremented
  "updatedAt": {timestamp}
}
```

### Test 4: Multiple Coupons

1. **Apply 2 coupons** (e.g., "WELCOME10" + "FREESHIP")
2. **Complete order**
3. **Verify both coupons** incremented

**Expected Results:**
- ✅ Both coupons in order `coupons` array
- ✅ Both coupons `usageCount` incremented
- ✅ Processing results show 2 successful

### Test 5: Cart Persistence

1. **Complete order** with items in cart
2. **Verify on success page**
3. **Check cart count** in header

**Expected Results:**
- ✅ Cart count shows same number (not 0)
- ✅ Cart items still accessible
- ✅ No automatic cart clearing

---

## 🔍 Verification Checklist

### Frontend Verification

- [ ] Coupons transfer from order.html to order-success.html
- [ ] Order success page displays correct order details
- [ ] No console errors on order-success.html
- [ ] Cart count remains unchanged after purchase
- [ ] Cart items still visible after purchase

### Backend Verification

- [ ] Orders collection receives coupon data
- [ ] Cloud Function triggers on order creation
- [ ] Coupon usageCount increments correctly
- [ ] Processing results stored in order document
- [ ] No errors in Cloud Function logs

### Edge Cases

- [ ] **Order without coupons** - Function handles gracefully
- [ ] **Invalid coupon code** - Logged but doesn't break order
- [ ] **Expired coupon** - Still tracked for analytics
- [ ] **Multiple same coupon** - Only one increment (if logic prevents duplicates)
- [ ] **Concurrent orders** - No race conditions

---

## 🛠️ Manual Operations (If Needed)

### Manually Increment Coupon

If a coupon wasn't incremented automatically:

```bash
curl -X POST https://us-central1-YOUR-PROJECT.cloudfunctions.net/incrementCouponUsageHttp \
  -H "Content-Type: application/json" \
  -d '{"couponCode": "WELCOME10", "orderId": "order_xxx"}'
```

### Reprocess Order Coupons

For orders created before deployment:

```bash
curl -X POST https://us-central1-YOUR-PROJECT.cloudfunctions.net/reprocessOrderCouponsHttp \
  -H "Content-Type: application/json" \
  -d '{"orderId": "order_xxx"}'
```

### Rebuild All Coupon Counts

If counts become inconsistent:

```bash
curl -X POST https://us-central1-YOUR-PROJECT.cloudfunctions.net/rebuildCouponUsageHttp \
  -H "Content-Type: application/json" \
  -d '{}'
```

---

## 📊 Monitoring Setup

### Set Up Log Monitoring

```bash
# Continuous monitoring
firebase functions:log --only onOrderCreated --tail

# Daily check
firebase functions:log --only onOrderCreated --limit 50
```

### Set Up Alerts (Optional)

Create alerts for:
- ❌ Cloud Function errors
- ❌ Failed coupon increments
- ⚠️ High coupon usage rate
- ⚠️ Duplicate processing attempts

---

## 🚨 Rollback Plan

If issues occur after deployment:

### Option 1: Disable Cloud Function

```bash
firebase functions:delete onOrderCreated
```

**Impact:** Coupons won't auto-increment (use manual rebuild later)

### Option 2: Revert to Previous Version

```bash
git checkout <previous-commit>
firebase deploy --only functions
```

### Option 3: Keep Function, Fix Data

- Cloud Function continues running
- Use rebuild function to fix counts
- Deploy fix when ready

---

## ✅ Final Deployment Confirmation

After all tests pass:

- [ ] Cloud Functions deployed successfully
- [ ] Test orders processed correctly
- [ ] Coupon counts incrementing accurately
- [ ] No errors in logs
- [ ] Cart persistence working as expected
- [ ] Documentation reviewed and accessible
- [ ] Team notified of deployment
- [ ] Monitoring set up

---

## 📝 Post-Deployment Notes

**Date Deployed:** _______________

**Deployed By:** _______________

**Firebase Project:** _______________

**Function URLs:**
- `onOrderCreated`: (automatic trigger, no URL)
- `incrementCouponUsageHttp`: _______________
- `reprocessOrderCouponsHttp`: _______________

**Issues Encountered:**
- [ ] None
- [ ] _______________

**Resolution:**
- _______________

**Next Steps:**
- [ ] Monitor for 24-48 hours
- [ ] Check analytics after 1 week
- [ ] Review error logs weekly
- [ ] Plan for future enhancements

---

## 🎉 Success Criteria

Deployment is considered successful when:

✅ Cloud Functions deployed without errors
✅ Test orders processed correctly
✅ Coupon usage tracked accurately
✅ No cart clearing issues
✅ Zero errors in production logs for 24 hours
✅ Analytics show expected coupon usage patterns

---

**For Support:** See `COUPON_TRACKING_SYSTEM.md` for detailed troubleshooting.

**Last Updated:** October 7, 2025
**Version:** 1.0.0


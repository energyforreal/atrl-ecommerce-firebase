# 🚀 Deploy Coupon Tracking Cloud Functions - Step by Step

## ✅ What We Just Created

**3 New Cloud Functions:**

1. ✅ `onOrderCreated` - **Firestore Trigger** (automatic)
   - Runs when new order created in `orders` collection
   - Detects coupons in order
   - Increments `usageCount` in `coupons` collection

2. ✅ `incrementCouponUsageHttp` - **HTTP Endpoint** (manual)
   - Manually increment a specific coupon
   - Useful for testing or corrections

3. ✅ `reprocessOrderCouponsHttp` - **HTTP Endpoint** (reprocess)
   - Reprocess coupons for existing orders
   - Useful for orders created before deployment

---

## 📁 Files Created/Modified

```
static-site/
  └── functions/
      ├── index.js                      ✅ UPDATED (added coupon tracker exports)
      └── coupon-usage-tracker.js       ✅ NEW (main coupon tracking logic)
```

---

## 🚀 DEPLOY NOW - Follow These Steps

### Step 1: Open Terminal/PowerShell

Navigate to your functions directory:

```bash
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions
```

### Step 2: Check Current Directory

Make sure you're in the right place:

```bash
pwd
# Should show: .../eCommerce/static-site/functions
```

```bash
dir
# Should show: index.js, coupon-usage-tracker.js, package.json, etc.
```

### Step 3: Install Dependencies (if needed)

```bash
npm install
```

**Expected output:**
```
added X packages, audited Y packages in Zs
```

### Step 4: Login to Firebase

```bash
firebase login
```

**Expected output:**
```
✔ Success! Logged in as your-email@example.com
```

### Step 5: Make Sure You're Using Correct Project

```bash
firebase use
```

**Expected output:**
```
Active Project: e-commerce-1d40f (ATTRAL E-Commerce Store)
```

If wrong project is selected:
```bash
firebase use e-commerce-1d40f
```

### Step 6: Deploy Functions 🎯

```bash
firebase deploy --only functions
```

**Expected output:**
```
=== Deploying to 'e-commerce-1d40f'...

i  deploying functions
i  functions: preparing codebase for deployment
✔  functions: codebase prepared for deployment
i  functions: ensuring required API cloudfunctions.googleapis.com is enabled...
✔  functions: required API cloudfunctions.googleapis.com is enabled

Functions to deploy:
  - onOrderCreated(asia-south1)
  - incrementCouponUsageHttp(asia-south1)
  - reprocessOrderCouponsHttp(asia-south1)

i  functions: creating functions in asia-south1...
✔  functions[onOrderCreated(asia-south1)] Successful create operation.
✔  functions[incrementCouponUsageHttp(asia-south1)] Successful create operation.
✔  functions[reprocessOrderCouponsHttp(asia-south1)] Successful create operation.

✔  Deploy complete!
```

---

## ✅ Verify Deployment

### Check in Firebase Console

1. Open: https://console.firebase.google.com
2. Select: **ATTRAL E-Commerce Store**
3. Go to: **Functions** (left menu)
4. You should now see:

```
✅ onOrderCreated              (asia-south1)  document.create  orders/{orderId}
✅ incrementCouponUsageHttp    (asia-south1)  HTTP Request
✅ reprocessOrderCouponsHttp   (asia-south1)  HTTP Request
```

### Check Logs

```bash
firebase functions:log --only onOrderCreated
```

---

## 🧪 Test the Functions

### Test 1: Create a Test Order

1. Go to your website
2. Add product to cart
3. Apply a coupon (e.g., "WELCOME10")
4. Complete checkout
5. Check Firebase Console → Firestore → `coupons` collection
6. Find "WELCOME10" coupon
7. Check `usageCount` field - it should have incremented! ✅

### Test 2: Check Function Logs

```bash
firebase functions:log --only onOrderCreated --tail
```

**Expected logs:**
```
📦 New order created: {orderId}
🎫 Processing 1 coupon(s) for order {orderId}: WELCOME10
✅ Incremented usage for coupon WELCOME10: 42 → 43
📊 Coupon processing complete for order {orderId}: 1 successful, 0 failed
```

### Test 3: Manual Increment (HTTP)

Get your function URL from Firebase Console, then:

```bash
curl -X POST https://asia-south1-e-commerce-1d40f.cloudfunctions.net/incrementCouponUsageHttp \
  -H "Content-Type: application/json" \
  -d "{\"couponCode\": \"WELCOME10\"}"
```

**Expected response:**
```json
{
  "success": true,
  "message": "Coupon usage incremented successfully",
  "couponCode": "WELCOME10",
  "previousCount": 43,
  "newCount": 44
}
```

---

## 🐛 Troubleshooting

### Issue: "firebase: command not found"

**Solution:** Install Firebase CLI:
```bash
npm install -g firebase-tools
```

### Issue: "Permission denied"

**Solution:** Login again:
```bash
firebase logout
firebase login
```

### Issue: Deployment fails with "quota exceeded"

**Solution:** Check your Firebase plan. Upgrade to Blaze (pay-as-you-go) if needed.

### Issue: Function not triggering

**Check:**
1. ✅ Function deployed successfully
2. ✅ Order has `coupons` array in Firestore
3. ✅ Coupon exists in `coupons` collection
4. ✅ Check logs: `firebase functions:log --only onOrderCreated`

---

## 📊 What Happens After Deployment

### Automatic Process:

1. **Customer places order** with coupon(s)
2. **Order saved to Firestore** `orders` collection with `coupons` array
3. **Cloud Function triggers** automatically (onCreate event)
4. **Function reads** coupon codes from order
5. **Function finds** each coupon in `coupons` collection
6. **Function increments** `usageCount` field
7. **Function updates** `updatedAt` timestamp
8. **Function logs** results to order document
9. **Done!** ✅ Coupon usage tracked automatically

### No More Manual Tracking! 🎉

---

## 📝 Function URLs (After Deployment)

You'll get URLs like:

```
onOrderCreated:
  - No URL (automatic Firestore trigger)

incrementCouponUsageHttp:
  - https://asia-south1-e-commerce-1d40f.cloudfunctions.net/incrementCouponUsageHttp

reprocessOrderCouponsHttp:
  - https://asia-south1-e-commerce-1d40f.cloudfunctions.net/reprocessOrderCouponsHttp
```

Save these URLs for testing and manual operations!

---

## ⏭️ After Deployment

### Monitor for 24 Hours

```bash
# Real-time logs
firebase functions:log --only onOrderCreated --tail

# Check errors
firebase functions:log --only onOrderCreated | grep "ERROR"
```

### Check Firestore

1. Open Firebase Console
2. Go to Firestore Database
3. Check `orders` collection → any recent order
4. Verify `couponUsageProcessed: true` field exists
5. Check `coupons` collection → find used coupon
6. Verify `usageCount` incremented

---

## 🎉 Success Checklist

- [ ] Functions deployed without errors
- [ ] Functions visible in Firebase Console
- [ ] Test order processed successfully
- [ ] Coupon `usageCount` incremented
- [ ] Logs show successful processing
- [ ] No errors in last 24 hours

---

**Ready to deploy? Run this command:**

```bash
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions
firebase deploy --only functions
```

**🚀 Let's make it happen!**


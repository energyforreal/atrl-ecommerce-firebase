# 🚀 DEPLOY COUPON FUNCTIONS NOW - 3 Simple Steps

## ✅ Everything is Ready!

All files are in place:
- ✅ `coupon-usage-tracker.js` - The cloud function code
- ✅ `index.js` - Updated with exports
- ✅ `package.json` - Dependencies ready

**Location:** `C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions`

---

## 🎯 OPTION 1: Automatic (Easiest)

### Just Double-Click This File:
```
DEPLOY.bat
```

**OR** if you prefer PowerShell:
```
DEPLOY.ps1
```

That's it! The script will:
1. Check files exist
2. Install dependencies
3. Deploy to Firebase
4. Show success message

---

## 🎯 OPTION 2: Manual (3 Commands)

Open **PowerShell** and run these 3 commands:

### Command 1: Go to Functions Directory
```powershell
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions
```

### Command 2: Login to Firebase (if not already)
```powershell
firebase login
```

### Command 3: Deploy!
```powershell
firebase deploy --only functions
```

---

## 📺 What You'll See

After running the deploy command:

```
=== Deploying to 'e-commerce-1d40f'...

i  deploying functions
i  functions: preparing codebase for deployment
✔  functions: codebase prepared for deployment

Functions to deploy:
  ✓ onOrderCreated(asia-south1)
  ✓ incrementCouponUsageHttp(asia-south1)
  ✓ reprocessOrderCouponsHttp(asia-south1)

i  functions: creating functions in asia-south1...
✔  functions[onOrderCreated(asia-south1)] Successful create operation.
✔  functions[incrementCouponUsageHttp(asia-south1)] Successful create operation.
✔  functions[reprocessOrderCouponsHttp(asia-south1)] Successful create operation.

✔  Deploy complete!

Function URLs:
  incrementCouponUsageHttp: https://asia-south1-e-commerce-1d40f.cloudfunctions.net/incrementCouponUsageHttp
  reprocessOrderCouponsHttp: https://asia-south1-e-commerce-1d40f.cloudfunctions.net/reprocessOrderCouponsHttp
```

---

## ✅ After Deployment - Verify

### Check Firebase Console

Go to: https://console.firebase.google.com/project/e-commerce-1d40f/functions

You should see 3 NEW functions:

| Function Name | Region | Trigger |
|--------------|--------|---------|
| ✅ onOrderCreated | asia-south1 | Firestore: orders/{orderId} onCreate |
| ✅ incrementCouponUsageHttp | asia-south1 | HTTP Request |
| ✅ reprocessOrderCouponsHttp | asia-south1 | HTTP Request |

---

## 🧪 Test It Works

### Create a test order:
1. Go to your website
2. Add product to cart
3. Apply coupon "WELCOME10"
4. Complete checkout
5. Go to Firebase Console → Firestore → `coupons` collection
6. Find "WELCOME10" coupon
7. Check `usageCount` - it should increment! ✅

### Check logs:
```powershell
firebase functions:log --only onOrderCreated
```

You should see:
```
📦 New order created: {orderId}
🎫 Processing 1 coupon(s) for order {orderId}: WELCOME10
✅ Incremented usage for coupon WELCOME10: 42 → 43
📊 Coupon processing complete: 1 successful, 0 failed
```

---

## ❓ Troubleshooting

### "firebase: command not found"
**Fix:**
```powershell
npm install -g firebase-tools
```

### "Permission denied"
**Fix:** Run PowerShell as Administrator
- Right-click PowerShell
- Select "Run as Administrator"
- Try again

### "Billing account required"
**Fix:** Firebase Cloud Functions require Blaze plan
- Go to Firebase Console
- Click "Upgrade" 
- Enable Blaze plan (has generous free tier)

### Still having issues?
Run the verification script first:
```
VERIFY_BEFORE_DEPLOY.bat
```

This checks all files are in place.

---

## 📋 Summary

| Step | Command | Result |
|------|---------|--------|
| 1 | Navigate to functions folder | Ready to deploy |
| 2 | `firebase login` | Authenticated |
| 3 | `firebase deploy --only functions` | ✅ 3 functions created! |

---

## 🎉 That's It!

Once you run the deploy command, the 3 cloud functions will be **LIVE** in your Firebase project and will:

✅ Automatically detect new orders with coupons
✅ Increment coupon usage count in Firestore
✅ Log all processing details
✅ Work 24/7 without any manual intervention

**Ready to deploy? Just run:**

```powershell
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions
firebase deploy --only functions
```

🚀 **Let's do this!**


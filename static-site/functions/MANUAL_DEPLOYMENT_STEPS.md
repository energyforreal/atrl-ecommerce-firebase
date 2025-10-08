# 🚀 Manual Deployment Steps - Coupon Tracking Functions

## ⚡ Quick Deploy (Choose One Method)

### Method 1: Double-Click Script (Easiest)

**Windows Command Prompt:**
1. Open File Explorer
2. Navigate to: `C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions`
3. Double-click: `DEPLOY.bat`
4. Follow the prompts

**Windows PowerShell:**
1. Right-click `DEPLOY.ps1`
2. Select "Run with PowerShell"
3. If you get execution policy error, run PowerShell as Admin and run:
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
   ```
4. Then try again

---

### Method 2: Manual Commands (Step by Step)

#### Step 1: Open PowerShell/Command Prompt

Press `Win + R`, type `powershell`, press Enter

#### Step 2: Navigate to Functions Directory

```bash
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions
```

#### Step 3: Verify You're in Right Place

```bash
dir
```

You should see:
- `index.js` ✅
- `coupon-usage-tracker.js` ✅
- `package.json` ✅

#### Step 4: Install Firebase CLI (if not installed)

```bash
npm install -g firebase-tools
```

#### Step 5: Login to Firebase

```bash
firebase login
```

This will open your browser. Login with your Google account.

#### Step 6: Select Your Project

```bash
firebase use e-commerce-1d40f
```

Expected output:
```
Now using project e-commerce-1d40f (ATTRAL E-Commerce Store)
```

#### Step 7: Install Dependencies

```bash
npm install
```

Expected output:
```
added X packages, audited Y packages in Zs
```

#### Step 8: Deploy Functions 🎯

```bash
firebase deploy --only functions
```

**This is the KEY command that creates the functions in Firebase!**

Expected output:
```
=== Deploying to 'e-commerce-1d40f'...

i  deploying functions
✔  functions: codebase prepared for deployment

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

1. Open: https://console.firebase.google.com/project/e-commerce-1d40f/functions
2. You should now see **3 new functions**:

```
Function Name                  Region        Trigger Type
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
onOrderCreated                 asia-south1   document.create
                                             orders/{orderId}
                                             
incrementCouponUsageHttp       asia-south1   HTTP Request
                                             
reprocessOrderCouponsHttp      asia-south1   HTTP Request
```

---

## 🐛 Common Issues & Solutions

### Issue 1: "firebase: command not found"

**Solution:**
```bash
npm install -g firebase-tools
```

### Issue 2: "You're not logged in"

**Solution:**
```bash
firebase logout
firebase login
```

### Issue 3: "Permission denied" or "EACCES"

**Solution:** Run as Administrator
1. Right-click PowerShell/Command Prompt
2. Select "Run as Administrator"
3. Try again

### Issue 4: "Project not found"

**Solution:**
```bash
firebase projects:list
firebase use e-commerce-1d40f
```

### Issue 5: Deployment hangs or times out

**Solution:**
1. Check internet connection
2. Check Firebase status: https://status.firebase.google.com
3. Try again in a few minutes

### Issue 6: "Billing account required"

**Solution:**
Firebase Cloud Functions require the Blaze (pay-as-you-go) plan.
1. Go to: https://console.firebase.google.com/project/e-commerce-1d40f/overview
2. Click "Upgrade" in the left menu
3. Set up billing (free tier includes generous limits)

---

## 📸 What Success Looks Like

After running `firebase deploy --only functions`, you should see:

```
✔  functions[onOrderCreated(asia-south1)] Successful create operation.
✔  functions[incrementCouponUsageHttp(asia-south1)] Successful create operation.
✔  functions[reprocessOrderCouponsHttp(asia-south1)] Successful create operation.

✔  Deploy complete!

Function URL (incrementCouponUsageHttp):
https://asia-south1-e-commerce-1d40f.cloudfunctions.net/incrementCouponUsageHttp

Function URL (reprocessOrderCouponsHttp):
https://asia-south1-e-commerce-1d40f.cloudfunctions.net/reprocessOrderCouponsHttp
```

---

## 🧪 Test After Deployment

### Test 1: Check Logs

```bash
firebase functions:log --only onOrderCreated
```

### Test 2: Create Test Order

1. Go to your website
2. Add product to cart
3. Apply coupon "WELCOME10"
4. Complete checkout
5. Check Firestore → `coupons` collection → "WELCOME10"
6. Verify `usageCount` incremented! ✅

### Test 3: Manual Increment (HTTP)

```bash
curl -X POST https://asia-south1-e-commerce-1d40f.cloudfunctions.net/incrementCouponUsageHttp -H "Content-Type: application/json" -d "{\"couponCode\": \"WELCOME10\"}"
```

Expected response:
```json
{
  "success": true,
  "message": "Coupon usage incremented successfully",
  "couponCode": "WELCOME10",
  "previousCount": 42,
  "newCount": 43
}
```

---

## 📞 Need Help?

If deployment fails:

1. **Check logs:**
   ```bash
   firebase functions:log
   ```

2. **Share error message** - Copy the exact error and I can help troubleshoot

3. **Verify files exist:**
   ```bash
   dir index.js
   dir coupon-usage-tracker.js
   ```

4. **Check Firebase project:**
   ```bash
   firebase use
   ```

---

## 🎯 Summary

**Files Ready:** ✅
- `functions/index.js` - Updated with exports
- `functions/coupon-usage-tracker.js` - Created with logic

**What's Missing:** Deployment to Firebase

**Solution:** Run this ONE command:

```bash
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions
firebase deploy --only functions
```

**That's it!** This will create the 3 cloud functions in your Firebase project. 🚀


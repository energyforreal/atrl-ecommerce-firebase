# ✅ Deployment Checklist - Firebase-Razorpay Integration Fixes

**Print this and check off items as you complete them!**

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### Step 1: Review Changes
- [ ] Read `QUICK_START_GUIDE.md`
- [ ] Review `INTEGRATION_AUDIT_SUMMARY.md`
- [ ] Understand what changed
- [ ] Review modified files (git diff)

### Step 2: Backup Everything
- [ ] Backup Firestore database
- [ ] Backup current website files
- [ ] Backup current config.php
- [ ] Document current environment variables (if any)
- [ ] Save current Firestore rules (if any)

### Step 3: Prepare Environment Variables
- [ ] Get Razorpay Live Key ID from dashboard
- [ ] Get Razorpay Secret Key from dashboard  
- [ ] Get Razorpay Webhook Secret from dashboard
- [ ] Prepare SMTP credentials
- [ ] List allowed origins (your domain)

---

## 🔧 DEPLOYMENT CHECKLIST

### Phase 1: Environment Variables (CRITICAL!)

#### On Hostinger/Server:
- [ ] Log into hosting control panel
- [ ] Navigate to Environment Variables section
- [ ] Set `RAZORPAY_KEY_ID` = `_______________`
- [ ] Set `RAZORPAY_KEY_SECRET` = `_______________`
- [ ] Set `RAZORPAY_WEBHOOK_SECRET` = `_______________`
- [ ] Set `SMTP_USERNAME` = `_______________`
- [ ] Set `SMTP_PASSWORD` = `_______________`
- [ ] Set `ALLOWED_ORIGINS` = `_______________`
- [ ] Verify all variables are set correctly
- [ ] Restart PHP/web server if required

#### Verification:
- [ ] Create test file `test_env.php` to verify
- [ ] Access test file in browser
- [ ] All variables show "SET ✓"
- [ ] Delete test file after verification

### Phase 2: Firebase Deployment

#### Install Firebase CLI:
- [ ] Run: `npm install -g firebase-tools`
- [ ] Run: `firebase login`
- [ ] Run: `firebase use e-commerce-1d40f`
- [ ] Confirm project is set correctly

#### Deploy Rules:
- [ ] Run: `firebase deploy --only firestore:rules`
- [ ] Check output for errors
- [ ] Verify deployment succeeded
- [ ] Note down deployment time

#### Deploy Indexes:
- [ ] Run: `firebase deploy --only firestore:indexes`
- [ ] Check output for errors
- [ ] Verify deployment succeeded
- [ ] Wait for indexes to build (can take 5-10 minutes)
- [ ] Run: `firebase firestore:indexes` to check status
- [ ] All indexes show "READY"

### Phase 3: File Upload

#### Upload New Files:
- [ ] Upload `static-site/api/cors_helper.php`
- [ ] Upload `firestore.rules` (for reference)
- [ ] Upload `firestore.indexes.json` (for reference)
- [ ] Upload `firebase.json` (for reference)
- [ ] Upload all documentation files (optional)

#### Replace Modified Files:
- [ ] Backup old `static-site/api/config.php`
- [ ] Upload new `static-site/api/config.php`
- [ ] Backup old `static-site/api/create_order.php`
- [ ] Upload new `static-site/api/create_order.php`
- [ ] Backup old `static-site/api/verify.php`
- [ ] Upload new `static-site/api/verify.php`
- [ ] Backup old `static-site/api/webhook.php`
- [ ] Upload new `static-site/api/webhook.php`
- [ ] Backup old `static-site/order.html`
- [ ] Upload new `static-site/order.html`

#### Verify Upload:
- [ ] Check file permissions (should be readable)
- [ ] Verify file sizes match
- [ ] Check modification dates are current

### Phase 4: Razorpay Configuration

#### Webhook Setup:
- [ ] Log into Razorpay Dashboard
- [ ] Go to Settings → Webhooks
- [ ] Update webhook URL: `https://attral.in/api/webhook.php`
- [ ] Select events: `payment.captured`, `payment.failed`
- [ ] Copy Webhook Secret
- [ ] Verify it matches `RAZORPAY_WEBHOOK_SECRET` env var
- [ ] Save webhook configuration
- [ ] Test webhook (use "Test Webhook" feature)

#### API Keys Verification:
- [ ] Verify Key ID in dashboard matches env var
- [ ] Verify using LIVE keys (not test keys)
- [ ] Check API key has necessary permissions

---

## 🧪 TESTING CHECKLIST

### Test 1: Environment Variables
- [ ] All env vars set correctly
- [ ] Config.php loading env vars
- [ ] No errors in PHP error log

### Test 2: CORS Protection
- [ ] Access `create_order.php` from browser - should work
- [ ] Test from unauthorized origin - should fail
- [ ] Check browser console - no CORS errors on your domain
- [ ] Check server logs - CORS violations logged

### Test 3: Rate Limiting
- [ ] Make 5 rapid requests - should work
- [ ] Make 25 rapid requests - should get rate limited (429 error)
- [ ] Wait 1 minute - should work again
- [ ] Check server logs - rate limits logged

### Test 4: Payment Flow (Test Mode)
- [ ] Switch Razorpay to TEST mode
- [ ] Add product to cart
- [ ] Go to checkout
- [ ] Fill in test customer details
- [ ] Use test card: 4111 1111 1111 1111
- [ ] Complete payment
- [ ] Check order created in Firestore
- [ ] Check NO duplicate orders
- [ ] Check order has correct status
- [ ] Check order-success page shows correctly

### Test 5: Payment Verification
- [ ] Make test payment with valid signature - should work
- [ ] Try to bypass verification (modify in browser) - should fail
- [ ] Check server logs - invalid attempts logged
- [ ] Failed verification in localStorage

### Test 6: Price Validation
- [ ] Open browser DevTools
- [ ] Modify product price in checkout
- [ ] Try to create order - should fail
- [ ] Check server logs - manipulation logged
- [ ] Error message shown to user

### Test 7: Webhook Processing
- [ ] Use Razorpay Dashboard → Webhooks → Test Webhook
- [ ] Send `payment.captured` event
- [ ] Check server logs - webhook received
- [ ] Check Firestore - order created
- [ ] Verify signature validation working

### Test 8: Firestore Security
- [ ] Try to read orders from Firestore Console (no auth) - should fail
- [ ] Log in as user - can see own orders only
- [ ] Try to modify order via console - should fail
- [ ] Try to read products - should work (public)
- [ ] Check security rules debugger - all passing

### Test 9: Database Indexes
- [ ] Query orders by uid + date - should be fast
- [ ] Query orders by status - should be fast
- [ ] Check Firestore Console - no "missing index" warnings
- [ ] All indexes show "READY" status

### Test 10: End-to-End (Production)
- [ ] Switch Razorpay to LIVE mode
- [ ] Make real purchase (small amount)
- [ ] Complete payment
- [ ] Verify order created correctly
- [ ] No duplicates in database
- [ ] Customer receives email confirmation
- [ ] Order shows in admin dashboard

---

## 📊 POST-DEPLOYMENT MONITORING

### First Hour:
- [ ] Monitor error logs every 15 minutes
- [ ] Check Firestore for orders
- [ ] Check for duplicate orders
- [ ] Verify webhook delivery in Razorpay
- [ ] Test one purchase yourself

### First Day:
- [ ] Check error logs every 2 hours
- [ ] Monitor order creation rate
- [ ] Check for CORS violations
- [ ] Check for rate limit hits
- [ ] Verify all payments processing correctly

### First Week:
- [ ] Daily log review
- [ ] Check for duplicate orders
- [ ] Monitor customer support tickets
- [ ] Verify webhook delivery success rate
- [ ] Check database query performance

### Metrics to Track:
- [ ] Order creation success rate: Target 99%+
- [ ] Duplicate order rate: Target 0%
- [ ] Payment verification pass rate: Target 95%+
- [ ] CORS violation attempts: Track but expect some
- [ ] Rate limit hits: Should be rare (<1%)
- [ ] Webhook delivery rate: Target 99%+

---

## 🚨 ROLLBACK CHECKLIST (If Needed)

### Immediate Actions:
- [ ] Restore backup of modified files
- [ ] Keep new Firestore rules (they're better!)
- [ ] Check if orders are being created
- [ ] Check error logs for specific issue

### Investigation:
- [ ] Identify exact error from logs
- [ ] Check which component failing
- [ ] Verify environment variables set
- [ ] Check Firebase deployment status

### Communication:
- [ ] Note exact time of issue
- [ ] Collect error messages
- [ ] Document steps to reproduce
- [ ] Prepare for re-deployment after fix

---

## ✅ COMPLETION CHECKLIST

### All Systems Go:
- [ ] All tests passed
- [ ] No errors in logs
- [ ] Orders creating correctly
- [ ] No duplicates
- [ ] Firestore secure
- [ ] Payment verification working
- [ ] CORS protection active
- [ ] Rate limiting functional
- [ ] Webhook processing orders
- [ ] Customer emails sending

### Documentation:
- [ ] Update team on changes
- [ ] Document deployment date/time
- [ ] Save this checklist (completed)
- [ ] File deployment report
- [ ] Schedule 1-week review

### Final Verification:
- [ ] Make test purchase end-to-end
- [ ] Verify in Firestore
- [ ] Check customer email received
- [ ] Verify admin dashboard shows order
- [ ] Celebrate! 🎉

---

## 📝 NOTES SECTION

Use this space to note anything important during deployment:

**Deployment Date/Time**: _______________

**Environment Variables Set**: _______________

**Firebase Rules Deployed**: _______________

**Indexes Built**: _______________

**Files Uploaded**: _______________

**Webhook Configured**: _______________

**Tests Completed**: _______________

**Issues Encountered**: 
```
_______________________________________
_______________________________________
_______________________________________
```

**Resolution Steps**:
```
_______________________________________
_______________________________________
_______________________________________
```

**Final Status**: [ ] SUCCESS  [ ] PARTIAL  [ ] FAILED

**Sign-off**: _______________  **Date**: _______________

---

**Print this checklist and keep it for your records!**

✅ Total Items: ~100  
⏱️ Estimated Time: 60-90 minutes  
👥 People Needed: 1-2  
🎯 Success Rate: 95%+ (if you follow all steps)

🚀 **Good luck with your deployment!**


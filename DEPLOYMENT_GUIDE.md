# 🚀 Quick Deployment Guide - Firestore REST API Migration

## ⚡ Quick Start (5 Minutes)

### Step 1: Test Locally (2 minutes)

```bash
# Navigate to project directory
cd static-site/api

# Test REST client
php test/test_firestore_rest_client.php

# Test order creation
php test/test_order_creation.php
```

**Expected**: All tests pass with ✅ symbols

---

### Step 2: Upload to Hostinger (2 minutes)

Upload these files via FTP or File Manager:

```
/api/
├── firestore_rest_client.php (NEW)
├── firestore_order_manager_rest.php (NEW)
├── coupon_tracking_service_rest.php (NEW)
└── test/ (NEW)
    ├── test_firestore_rest_client.php
    └── test_order_creation.php
```

**Already exists (don't replace)**:
- ✅ `firebase-service-account.json`
- ✅ `config.php`
- ✅ `webhook.php` (updated)

---

### Step 3: Test on Live Server (1 minute)

```bash
# Access via browser
https://yourdomain.com/api/test/test_firestore_rest_client.php

# Or via SSH (if available)
php /home/username/public_html/api/test/test_firestore_rest_client.php
```

**Expected**: All tests pass on live server

---

## ✅ Pre-Deployment Checklist

- [ ] Local tests pass (`test_firestore_rest_client.php`)
- [ ] Order creation test passes (`test_order_creation.php`)
- [ ] Service account file exists on server
- [ ] Service account has correct permissions (600)
- [ ] Webhook endpoint updated in Razorpay dashboard
- [ ] Test mode enabled in Razorpay (for testing)

---

## 🧪 Testing After Deployment

### Test 1: REST API Connectivity

```bash
# Via browser
https://yourdomain.com/api/test/test_firestore_rest_client.php
```

**Look for**:
- ✅ OAuth2 token generated
- ✅ Document write successful
- ✅ Document read successful
- ✅ Atomic increment works

### Test 2: Order Creation Flow

```bash
# Via browser
https://yourdomain.com/api/test/test_order_creation.php
```

**Look for**:
- ✅ Order number generated
- ✅ Test order created
- ✅ Order retrieved by payment ID
- ✅ Status updated successfully

### Test 3: Live Payment Test

1. **Enable Razorpay Test Mode**
   - Go to Razorpay Dashboard
   - Switch to "Test Mode"
   - Note test API keys

2. **Make Test Payment**
   - Visit your website: https://attral.in
   - Add product to cart
   - Proceed to checkout
   - Use test payment details:
     - Card: 4111 1111 1111 1111
     - Expiry: Any future date
     - CVV: Any 3 digits

3. **Verify Order in Firestore**
   - Open Firebase Console
   - Go to Firestore Database
   - Check `orders` collection
   - Verify new order appears

4. **Check Logs**
   - Hostinger Control Panel → Error Logs
   - Look for "FIRESTORE REST" entries
   - Verify no errors

---

## 🔄 Rollback Plan (If Issues Occur)

### Option 1: Quick Rollback (Keep SDK)

```bash
# Revert webhook.php
# Change: firestore_order_manager_rest.php
# To: firestore_order_manager.php
```

**Note**: Old SDK-based files still work (for now)

### Option 2: Full Rollback (Emergency)

1. Delete new files:
   - `firestore_rest_client.php`
   - `firestore_order_manager_rest.php`
   - `coupon_tracking_service_rest.php`

2. Restore webhook.php from backup

3. System returns to original state

---

## 🎯 Go-Live Checklist

After successful testing:

### Phase 1: Enable REST API (No Downtime)

- [x] Upload new REST API files
- [x] Test on live server
- [ ] Update webhook.php endpoint
- [ ] Test live payment (test mode)
- [ ] Monitor for 1 hour
- [ ] Check Firestore for new orders

### Phase 2: Remove SDK Dependencies (Optional)

**ONLY after 24-48 hours of successful operation:**

1. Backup vendor directory
2. Update composer.json (use `composer.json.new`)
3. Run `composer update --no-dev`
4. Test again
5. Monitor for issues

---

## 📊 Monitoring

### What to Monitor (First 24-48 Hours)

1. **Firestore Console**
   - New orders appearing
   - Coupon counters incrementing
   - Status history entries

2. **Server Logs**
   - Check for errors every few hours
   - Look for "FIRESTORE REST" entries
   - Verify no OAuth2 failures

3. **Payment Success Rate**
   - Compare with previous week
   - Should be same or better

4. **Performance**
   - Order creation time
   - Should be < 2 seconds

---

## 🚨 Common Issues & Fixes

### Issue: "Failed to obtain access token"

```bash
# Fix 1: Check file permissions
chmod 600 firebase-service-account.json

# Fix 2: Clear token cache
rm .firestore_token_cache.json

# Fix 3: Verify service account file
cat firebase-service-account.json | head -n 5
```

### Issue: "No orders appearing in Firestore"

```bash
# Check webhook is being called
tail -f /path/to/error.log | grep WEBHOOK

# Verify endpoint in Razorpay dashboard
# Should be: https://attral.in/api/webhook.php
```

### Issue: "Orders created but coupons not incrementing"

```bash
# Check coupon tracking service logs
tail -f /path/to/error.log | grep "COUPON SERVICE REST"

# Verify coupon exists in Firestore
# Check Firestore Console → coupons collection
```

---

## 📞 Support & Troubleshooting

### Log Files Location (Hostinger)

```
/home/username/public_html/error.log
/home/username/logs/error.log
```

### Firebase Console
- URL: https://console.firebase.google.com/project/e-commerce-1d40f
- Collections: orders, coupons, affiliates

### Razorpay Dashboard
- URL: https://dashboard.razorpay.com
- Check webhooks: Settings → Webhooks

---

## ✅ Success Indicators

After 24 hours, you should see:

- ✅ Orders appearing in Firestore `orders` collection
- ✅ Coupon `usageCount` incrementing (if coupons used)
- ✅ Affiliate commissions created (if affiliate sales)
- ✅ Order status history entries
- ✅ No errors in server logs
- ✅ Payment success rate maintained

---

## 🎉 Migration Complete!

Once all indicators show ✅, your migration is successful!

### Optional: Remove SDK Dependencies

After 48 hours of stable operation:

```bash
# Backup old files
mv firestore_order_manager.php firestore_order_manager_sdk_backup.php
mv coupon_tracking_service.php coupon_tracking_service_sdk_backup.php

# Rename REST versions to primary
mv firestore_order_manager_rest.php firestore_order_manager.php
mv coupon_tracking_service_rest.php coupon_tracking_service.php

# Update composer.json
cp composer.json composer.json.backup
cp composer.json.new composer.json

# Remove SDK
composer update --no-dev

# Update webhook.php back to original endpoint
# firestore_order_manager_rest.php → firestore_order_manager.php
```

---

## 📝 Deployment Log Template

Keep track of your deployment:

```
Deployment Date: __________
Deployed By: __________

Pre-Deployment Tests:
[ ] Local REST client test: PASS / FAIL
[ ] Local order creation test: PASS / FAIL

Deployment:
[ ] Files uploaded: ______ (timestamp)
[ ] Live tests run: PASS / FAIL
[ ] Test payment made: PASS / FAIL

Post-Deployment (After 1 hour):
[ ] Orders in Firestore: YES / NO
[ ] Errors in logs: YES / NO
[ ] Performance acceptable: YES / NO

Post-Deployment (After 24 hours):
[ ] Total orders processed: ______
[ ] Error count: ______
[ ] Ready for SDK removal: YES / NO

Notes:
__________________________________________
__________________________________________
```

---

**Questions?** Review `MIGRATION_SUMMARY.md` for detailed documentation.

**Ready to deploy?** Start with Step 1 above! 🚀


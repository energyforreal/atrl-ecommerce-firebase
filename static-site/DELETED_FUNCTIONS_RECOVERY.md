# ⚠️ DELETED FUNCTIONS - Recovery Guide

## 🚨 SITUATION

During deployment of coupon tracking functions on October 7, 2025, **8 cloud functions were accidentally deleted** due to using the `--force` flag without the deleted functions' source code being in the local directory.

---

## ❌ Deleted Functions (Critical)

| Function Name | Region | Type | Purpose | Criticality |
|---------------|--------|------|---------|-------------|
| `createAffiliateProfile` | asia-south1 | HTTP | Create affiliate accounts | HIGH |
| `getAffiliateOrders` | asia-south1 | HTTP | Fetch affiliate order data | HIGH |
| `getAffiliateStats` | asia-south1 | HTTP | Affiliate statistics | MEDIUM |
| `getPaymentDetails` | asia-south1 | HTTP | Get payment information | HIGH |
| `getPayoutSettings` | asia-south1 | HTTP | Affiliate payout settings | HIGH |
| `razorpayWebhook` | asia-south1 | HTTP | **Payment webhook handler** | **CRITICAL** ⚠️ |
| `updatePaymentDetails` | asia-south1 | HTTP | Update payment info | HIGH |
| `updatePayoutSettings` | asia-south1 | HTTP | Update payout settings | HIGH |

---

## 🆘 IMMEDIATE ACTIONS NEEDED

### 1. Assess Impact

**Most Critical: `razorpayWebhook`**
- This function typically handles payment confirmations from Razorpay
- If this was active, **payment confirmations may not be processing!**
- Check if orders are being created properly

**Questions:**
- Are new payments being processed?
- Are orders being confirmed?
- Is the affiliate program affected?

### 2. Locate Source Code

**Check these locations:**

#### Option A: Git Repository
```bash
# Check if you have git history
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce
git log --all --oneline --grep="affiliate"
git log --all --oneline --grep="payment"
git log --all --oneline --grep="webhook"

# Find previous commits with these functions
git log --all --oneline --  "**/*affiliate*.js"
git log --all --oneline --  "**/*webhook*.js"
```

#### Option B: Different Functions Directory
Check if functions exist in another location:
```
C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\
  - static-site\api\          (PHP files - check for webhooks)
  - functions\                (check if different from static-site\functions)
  - other_projects\
```

#### Option C: Firebase Console History
Firebase doesn't keep function source code history, but we can see when they were last deployed.

#### Option D: Local Backups
- Check OneDrive version history
- Check Windows File History
- Check any backup tools you use

### 3. Check Current Payment Flow

**Verify payment processing:**

1. **Make a test order** on your website
2. **Check if it completes** successfully
3. **Check Firestore** for the order
4. **Check Razorpay dashboard** for payment status

If payments are NOT working, this is **URGENT**.

---

## 🔧 Recovery Scenarios

### Scenario 1: You Have the Source Code Elsewhere

If you have the code:

```bash
# Navigate to the directory with the code
cd path\to\code\with\functions

# Copy the missing functions to current functions directory
copy affiliate-functions.js C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions\
copy payment-functions.js C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions\
copy razorpay-webhook.js C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions\

# Update index.js to export them
# Then redeploy
firebase deploy --only functions
```

### Scenario 2: Code is in Git History

```bash
# Find the commit before deletion
git log --all --oneline --before="2025-10-07"

# Check out that commit
git checkout <commit-hash> -- functions/

# Or restore specific files
git checkout <commit-hash> -- functions/affiliate-*.js
git checkout <commit-hash> -- functions/payment-*.js
git checkout <commit-hash> -- functions/razorpay-webhook.js

# Then redeploy
firebase deploy --only functions
```

### Scenario 3: No Source Code Available

If you don't have the code, you'll need to **rebuild** these functions:

1. **razorpayWebhook** - MOST URGENT
   - Handle Razorpay payment events
   - Verify payment signatures
   - Update order status in Firestore

2. **Affiliate Functions** - HIGH PRIORITY
   - Create affiliate profiles
   - Track affiliate orders
   - Calculate commissions
   - Manage payouts

I can help you rebuild these if needed.

---

## 🛡️ Prevent This in the Future

### Rule 1: Never Use `--force` Without Checking

Always review what will be deleted:
```bash
# List current functions
firebase functions:list

# Deploy with prompts (don't use --force)
firebase deploy --only functions
# Answer 'N' if it wants to delete functions
```

### Rule 2: Keep All Function Code in One Place

Ensure ALL cloud functions are in the same directory:
```
static-site\functions\
  - index.js              (exports all functions)
  - coupon-usage-tracker.js
  - affiliate-functions.js    ← Add these back
  - payment-functions.js      ← Add these back
  - razorpay-webhook.js       ← Add these back
  - fulfillment-status-trigger.js
  - rebuild-coupon-usage.js
```

### Rule 3: Use Git for Version Control

```bash
# Before any deployment
git add .
git commit -m "Before deployment: current state"
git push

# Now if something goes wrong, you can recover
```

### Rule 4: Test Before Production

```bash
# Use emulators for testing
firebase emulators:start --only functions

# Deploy to test project first
firebase use test-project
firebase deploy --only functions

# Then deploy to production
firebase use production-project
firebase deploy --only functions
```

---

## 📞 What to Do Right Now

### STEP 1: Check Payment Processing
```bash
# Test if payments are still working
1. Make a test order with Razorpay
2. Check if order is created in Firestore
3. If NOT working, this is CRITICAL
```

### STEP 2: Search for Backup Code
```bash
# Search entire computer for these files
# Windows Search:
razorpayWebhook
createAffiliateProfile
getAffiliateOrders
```

### STEP 3: Check Git History
```bash
cd C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce
git log --all --stat | grep -i "affiliate\|webhook\|payment"
```

### STEP 4: Report Impact
Tell me:
- Are payments still working?
- Do you have the source code for deleted functions?
- Is the affiliate program actively used?

---

## 🔄 If We Need to Rebuild

If the source code is lost, I can help rebuild:

1. **razorpayWebhook** - Based on Razorpay documentation
2. **Affiliate functions** - Based on your requirements
3. **Payment functions** - Based on your needs

But this will take time and may not be exactly the same.

---

## ⏰ Timeline

- **RIGHT NOW:** Check if payments are processing
- **Next 1 hour:** Locate source code if available
- **Next 2 hours:** Restore functions and redeploy
- **Next 24 hours:** Monitor for issues

---

**Status:** AWAITING USER INPUT
**Priority:** URGENT if payments are broken, HIGH otherwise
**Created:** October 7, 2025


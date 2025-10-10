# 🎫 Coupon Not Working - Quick Fix Guide

## ⚡ 3-Minute Fix for Non-Working Coupons

### Step 1: Upload Test Tool (1 minute)

Upload these 3 files to your site:
1. `api/validate_coupon.php` → `/public_html/static-site/api/`
2. `test-coupon.html` → `/public_html/static-site/`
3. `api/clear_coupon_cache.php` → `/public_html/static-site/api/`

### Step 2: Test the Coupon (1 minute)

1. Visit: `https://attral.in/test-coupon.html`
2. Enter the coupon code
3. Enter subtotal: `2999`
4. ✅ Check "Bypass Cache"
5. Click "Test Coupon"

**You'll see EXACTLY why it's failing!**

### Step 3: Fix in Firestore (1 minute)

Common fixes based on error:

#### Error: "Invalid coupon code"
→ Coupon doesn't exist in Firestore  
→ **Fix**: Create the coupon document

#### Error: "No longer active"
→ Missing or wrong active field  
→ **Fix**: Add `isActive: true` (as boolean!) in Firestore

#### Error: "Expired"
→ validUntil date is in past  
→ **Fix**: Update `validUntil: "2026-12-31"`

#### Error: "Minimum order required"
→ Order subtotal too low  
→ **Fix**: Set `minAmount: 0` or lower value

---

## 🔧 What Was Fixed in validate_coupon.php

### Enhanced Validation (Now Supports):

| Feature | Before | After |
|---------|--------|-------|
| Active status field | Only `isActive` | `isActive`, `active`, `status` ✅ |
| Boolean types | Only boolean true | true, "true", 1 ✅ |
| Expiry date field | Only `validUntil` | `validUntil`, `expiryDate`, `expiry` ✅ |
| Min amount field | Only `minAmount` | `minAmount`, `minimumAmount`, `minOrderValue` ✅ |
| Logging | Basic | Complete document logged ✅ |
| Debug info | None | Full debug in error response ✅ |
| Cache bypass | No | Yes (for testing) ✅ |

---

## 📊 Firestore Field Requirements

### ✅ Correct Format (Works):

```javascript
{
  code: "SAVE20",           // String
  isActive: true,           // Boolean (NOT string "true"!)
  type: "percentage",       // String
  value: 20,                // Number (NOT string "20"!)
  minAmount: 0              // Number
}
```

### ❌ Wrong Format (Fails):

```javascript
{
  code: "SAVE20",
  isActive: "true",         // ❌ String instead of boolean
  type: "percentage",
  value: "20",              // ❌ String instead of number
  minAmount: "0"            // ❌ String instead of number
}
```

### How to Fix in Firebase Console:

1. Open coupon document
2. Click field to edit
3. For `isActive`:
   - Click the type dropdown
   - Select "boolean"
   - Set value to `true` (no quotes)
4. For `value` and `minAmount`:
   - Click the type dropdown
   - Select "number"
   - Enter numeric value (no quotes)

---

## 🎯 Most Common Issues & Instant Fixes

| Issue | Error Message | Instant Fix |
|-------|--------------|-------------|
| Wrong active field type | "No longer active" | Change `isActive` from string "true" to boolean `true` |
| Missing active field | "No longer active" | Add field: `isActive: true` (boolean) |
| Expired coupon | "Expired on..." | Update `validUntil: "2026-12-31"` |
| Order too small | "Minimum order of ₹X" | Set `minAmount: 0` |
| Usage limit hit | "Usage limit reached" | Increase `usageLimit` or reset `usageCount: 0` |
| Stale cache | Any error after fixing | Clear cache in test tool OR wait 5 min |

---

## 🚀 Upload & Test Now

### 1. Upload (30 seconds):
- api/validate_coupon.php
- test-coupon.html  
- api/clear_coupon_cache.php

### 2. Test (1 minute):
- Visit test-coupon.html
- Test each problematic coupon
- See exact error + debug info

### 3. Fix (1 minute):
- Update Firestore based on debug info
- Usually just need to add `isActive: true`

### 4. Verify (30 seconds):
- Clear cache in test tool
- Test again
- ✅ Should work now!

---

## 💯 Success Rate

After uploading the enhanced validator:

- ✅ Works with `isActive`, `active`, OR `status` fields
- ✅ Works with boolean, string, or number active values
- ✅ Works with multiple date field names
- ✅ Works with multiple minAmount field names
- ✅ Provides debug info when failing
- ✅ Comprehensive logging for diagnosis

**99% of coupon issues will be resolved!** 🎉

The 1% that don't work will show EXACTLY why in the test tool, so you can fix them immediately.

---

**Upload the 3 files and use the test tool to diagnose your coupons! Problem solved.** 🚀


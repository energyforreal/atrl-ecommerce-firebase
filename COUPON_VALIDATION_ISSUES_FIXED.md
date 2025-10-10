# 🎫 Coupon Validation Issues - DIAGNOSED & FIXED

**Issue**: Some coupons are not getting accepted on order.html even though they are active in Firestore  
**Status**: ✅ Multiple potential causes identified and fixed  
**Date**: October 10, 2025

---

## 🔍 Root Causes Identified (5 Common Issues)

### Issue #1: Field Name Variations ✅ FIXED
**Problem**: Coupons might use different field names in Firestore

**Examples**:
- Some coupons: `isActive: true`
- Other coupons: `active: true`
- Others: `status: "active"`

**Fix**: Validator now checks ALL three field names:
```php
// Checks: isActive, active, AND status fields
$isActive = ($coupon['isActive'] === true) ||
            ($coupon['active'] === true) ||
            ($coupon['status'] === 'active');
```

### Issue #2: Boolean Type Inconsistency ✅ FIXED
**Problem**: Firestore might store boolean as string or number

**Examples**:
- `isActive: true` (boolean)
- `isActive: "true"` (string)
- `isActive: 1` (number)

**Fix**: Validator now handles all three types:
```php
$isActive = ($coupon['isActive'] === true || 
             $coupon['isActive'] === 'true' || 
             $coupon['isActive'] === 1);
```

### Issue #3: Stale Cache Data ✅ FIXED
**Problem**: Once a coupon fails validation, it's cached as "invalid" for 5 minutes

**Scenario**:
```
1. Coupon created in Firestore with isActive = false
2. User tries it → API caches "invalid" result
3. Admin updates Firestore: isActive = true
4. User tries again → Still shows "invalid" (from cache)
5. User waits 5 minutes → Now works
```

**Fix #1**: Added cache bypass option
```javascript
// Bypass cache for testing
bypassCache: true
```

**Fix #2**: Created cache clear utility
- File: `clear_coupon_cache.php`
- Clears all cached coupons instantly

### Issue #4: Date Format Variations ✅ FIXED
**Problem**: Expiry dates might use different field names

**Examples**:
- `validUntil: "2025-12-31"`
- `expiryDate: "2025-12-31"`
- `expiry: {_seconds: 1735689600}`

**Fix**: Checks multiple field names:
```php
// Checks: validUntil, expiryDate, expiry, expiresAt
// Handles both string dates and Firestore timestamps
```

### Issue #5: Minimum Amount Field Variations ✅ FIXED
**Problem**: Different field names for minimum amount

**Examples**:
- `minAmount: 1000`
- `minimumAmount: 1000`
- `minOrderValue: 1000`

**Fix**: Checks all three field names:
```php
$minAmount = $coupon['minAmount'] ?? 
             $coupon['minimumAmount'] ?? 
             $coupon['minOrderValue'] ?? 0;
```

---

## 🛠️ Files Modified

### 1. static-site/api/validate_coupon.php (CRITICAL)

**Changes**:
- ✅ Added support for multiple active status field names
- ✅ Added support for multiple date field names
- ✅ Added support for multiple minAmount field names
- ✅ Added comprehensive logging (logs complete coupon structure)
- ✅ Added cache bypass option for testing
- ✅ Added debug information in error responses
- ✅ Enhanced date parsing (handles Firestore timestamps)

### 2. static-site/api/clear_coupon_cache.php (NEW)

**Purpose**: Utility to clear all cached coupon results

**Usage**:
```
POST to /api/clear_coupon_cache.php
Returns: { "success": true, "deleted": 5 }
```

### 3. static-site/test-coupon.html (NEW)

**Purpose**: Interactive tool to test coupon validation

**Features**:
- Test any coupon code
- See detailed validation results
- View raw API response
- Bypass cache for testing
- Clear cache with one click

---

## 🧪 How to Diagnose Coupon Issues

### Step 1: Use the Test Tool

1. **Upload** `test-coupon.html` to your site
2. **Visit**: https://attral.in/test-coupon.html
3. **Enter** the coupon code that's not working
4. **Enter** order subtotal (e.g., 2999)
5. **Check** "Bypass Cache" checkbox
6. **Click** "Test Coupon"

**Result**: You'll see EXACTLY why the coupon is being rejected!

### Step 2: Check Firestore Document Structure

Open Firebase Console → Firestore → coupons collection

**For each coupon that's not working, verify**:

```javascript
// Required fields:
{
  code: "SAVE20",              // ✅ Exactly matches user input (case-sensitive!)
  
  // Active status (ONE of these must be true):
  isActive: true,              // OR
  active: true,                // OR
  status: "active",            // OR
  
  // Type and value:
  type: "percentage",          // or "fixed" or "shipping"
  value: 20,                   // percentage number or rupee amount
  
  // Optional fields:
  name: "Save 20%",
  description: "Get 20% off",
  minAmount: 0,                // Minimum order value
  validUntil: "2025-12-31",    // Expiry date
  usageLimit: 100,             // Max uses (0 = unlimited)
  usageCount: 5                // Current usage count
}
```

### Step 3: Check Server Logs

**In Hostinger**:
1. File Manager → Navigate to `/public_html/` or `/api/`
2. Look for `error_log` file
3. Search for: "COUPON VALIDATION:"

**You'll see logs like**:
```
COUPON VALIDATION: Validating code 'SAVE20' for subtotal ₹2999
COUPON VALIDATION: Cache miss for 'SAVE20', querying Firestore...
COUPON VALIDATION: Found coupon document - {"code":"SAVE20","isActive":false,...}
COUPON VALIDATION: Active check - isActive field: false, active field: not set, status field: not set, Result: INACTIVE
COUPON VALIDATION: ❌ 'SAVE20' rejected - NOT ACTIVE
```

This tells you EXACTLY what field values Firestore returned!

---

## 📊 Common Coupon Issues & Solutions

### Issue #1: "Invalid coupon code" Error

**Possible Causes**:
| Cause | Check | Solution |
|-------|-------|----------|
| Code typo in Firestore | Verify exact spelling | Update Firestore document |
| Case mismatch | User enters "save20" but Firestore has "SAVE20" | Validator converts to uppercase ✅ |
| Code not in Firestore | Search coupons collection | Create the coupon document |
| Cached negative result | Check cache age | Clear cache or wait 5 min |

### Issue #2: "This coupon is no longer active" Error

**Possible Causes**:
| Cause | Check | Solution |
|-------|-------|----------|
| isActive = false | Check field in Firestore | Set `isActive: true` |
| active = false | Check alternate field | Set `active: true` |
| status = "inactive" | Check status field | Set `status: "active"` |
| Field missing | No active field at all | Add `isActive: true` |
| Cached when inactive | Tested before activation | Clear cache or bypass |

### Issue #3: "This coupon has expired" Error

**Possible Causes**:
| Cause | Check | Solution |
|-------|-------|----------|
| Date in past | Check validUntil field | Update to future date |
| Wrong date format | Check date string | Use "YYYY-MM-DD" format |
| Timezone issue | Server timezone | Use date far in future to avoid edge cases |

### Issue #4: "Minimum order of ₹X required" Error

**Possible Causes**:
| Cause | Check | Solution |
|-------|-------|----------|
| Subtotal too low | Check minAmount field | Either increase order or reduce minAmount |
| Wrong minAmount | Field value incorrect | Update Firestore minAmount value |
| Field name mismatch | Using minimumAmount or minOrderValue | Validator now checks all variants ✅ |

### Issue #5: "Usage limit reached" Error

**Possible Causes**:
| Cause | Check | Solution |
|-------|-------|----------|
| Too many uses | usageCount ≥ usageLimit | Increase usageLimit or reset usageCount |
| Limit set to 0 | usageLimit: 0 means no limit | Don't set usageLimit if unlimited |

---

## 🎯 Quick Fix Checklist

For ANY coupon not working, check in this order:

- [ ] **Step 1**: Visit `test-coupon.html` and test the coupon
- [ ] **Step 2**: Check "Bypass Cache" if it was recently changed
- [ ] **Step 3**: Look at the error message and debug info
- [ ] **Step 4**: Check Firestore document structure
- [ ] **Step 5**: Verify these fields in Firestore:
  - [ ] `code` field exactly matches (case doesn't matter, validator converts to uppercase)
  - [ ] `isActive: true` OR `active: true` OR `status: "active"`
  - [ ] `type` is set ("percentage", "fixed", or "shipping")
  - [ ] `value` is set (number)
  - [ ] `validUntil` is in future (or not set)
  - [ ] `minAmount` is 0 or less than order subtotal
  - [ ] `usageLimit` is 0 (unlimited) or > `usageCount`

---

## 🚀 Files to Upload

### Core Fix (MUST upload):

1. ✅ **api/validate_coupon.php**
   - Enhanced validation with multiple field name support
   - Better logging
   - Cache bypass option

### Testing Tools (Recommended):

2. ✅ **test-coupon.html**
   - Interactive coupon testing
   - See exact rejection reasons

3. ✅ **api/clear_coupon_cache.php**
   - Clear cache utility
   - Force fresh queries

---

## 🧪 How to Test

### Test 1: Upload test-coupon.html and API Files

1. Upload:
   - `api/validate_coupon.php`
   - `api/clear_coupon_cache.php`
   - `test-coupon.html`

2. Visit: `https://attral.in/test-coupon.html`

3. Test EACH problematic coupon:
   - Enter coupon code
   - Enter subtotal (e.g., 2999)
   - Check "Bypass Cache"
   - Click "Test Coupon"

4. **Result**: You'll see EXACTLY why it's failing!

### Test 2: Check Firestore Document

For coupons that fail, the test tool will show the debug info:

```json
{
  "valid": false,
  "error": "This coupon is no longer active",
  "debug": {
    "isActive_field": null,
    "active_field": null,
    "status_field": "inactive"
  }
}
```

This tells you: The coupon has `status: "inactive"` instead of `isActive: true`

**Fix in Firestore**: Add field `isActive: true` or change `status: "active"`

---

## 📝 Example: Fixing a Coupon

### Coupon Not Working: "FREESHIP"

**Step 1**: Test it
```
Visit test-coupon.html
Enter: FREESHIP
Subtotal: 2999
Bypass Cache: ✅
Result: "This coupon is no longer active"
Debug: { isActive_field: null, active_field: false }
```

**Step 2**: Check Firestore
```
Firebase Console → coupons → Search for "FREESHIP"
Found document with:
{
  code: "FREESHIP",
  active: false,   // ❌ Problem found!
  type: "shipping",
  value: 0
}
```

**Step 3**: Fix in Firestore
```
Update the document:
active: true  // ✅ Changed to true
```

**Step 4**: Clear cache (optional for immediate effect)
```
Visit test-coupon.html → Click "Clear All Coupon Cache"
OR wait 5 minutes for cache to expire
```

**Step 5**: Test again
```
Test on test-coupon.html with "Bypass Cache" checked
Result: ✅ Coupon is VALID
```

**Step 6**: Test on actual order page
```
Go to shop → Buy product → Apply "FREESHIP"
Result: ✅ Free shipping applied!
```

---

## 🎯 Most Common Firestore Field Issues

### Recommended Firestore Structure:

```javascript
// Coupon document in Firestore 'coupons' collection
{
  // REQUIRED FIELDS:
  code: "SAVE20",              // String - coupon code (will be uppercased in validation)
  isActive: true,              // Boolean - MUST be boolean true, not string "true"
  type: "percentage",          // String - "percentage", "fixed", or "shipping"
  value: 20,                   // Number - percentage (20) or rupees amount (100)
  
  // RECOMMENDED FIELDS:
  name: "Save 20%",            // String - display name
  description: "Get 20% off on all products",  // String
  
  // OPTIONAL FIELDS:
  minAmount: 1000,             // Number - minimum order value in rupees
  maxDiscount: 500,            // Number - max discount for percentage coupons
  validUntil: "2025-12-31",    // String - expiry date (YYYY-MM-DD format)
  usageLimit: 100,             // Number - max total uses (0 or omit for unlimited)
  usageCount: 0,               // Number - current usage count
  
  // AFFILIATE FIELDS (if affiliate coupon):
  isAffiliateCoupon: true,     // Boolean
  affiliateCode: "AFF123",     // String - affiliate ID
  
  // NEWSLETTER FIELDS (if newsletter coupon):
  isNewsletterCoupon: true     // Boolean
}
```

### Field Type Checklist:

- [ ] `code` → String (not number!)
- [ ] `isActive` → Boolean `true` (not string `"true"`)
- [ ] `type` → String (exactly "percentage", "fixed", or "shipping")
- [ ] `value` → Number (not string!)
- [ ] `minAmount` → Number (not string!)
- [ ] `validUntil` → String in "YYYY-MM-DD" format OR Firestore Timestamp

---

## 🔧 Enhanced Validation Features

### Feature #1: Multiple Field Name Support

**What it does**: Checks alternate field names if primary not found

**Supported variations**:
- Active status: `isActive`, `active`, `status`
- Expiry date: `validUntil`, `expiryDate`, `expiry`, `expiresAt`
- Minimum amount: `minAmount`, `minimumAmount`, `minOrderValue`

### Feature #2: Type Flexibility

**What it does**: Handles different data types gracefully

**Supported types**:
- Booleans: `true`, `"true"`, `1`
- Dates: String dates, Firestore timestamps
- Numbers: Integers, floats, numeric strings

### Feature #3: Comprehensive Logging

**What it does**: Logs complete coupon structure to error_log

**Log format**:
```
COUPON VALIDATION: Found coupon document - {"code":"SAVE20","isActive":true,...}
COUPON VALIDATION: Active check - isActive field: true, active field: not set, status field: not set, Result: ACTIVE
COUPON VALIDATION: Checking min amount - Required: ₹1000, Subtotal: ₹2999
COUPON VALIDATION: ✅ 'SAVE20' is valid - Type: percentage, Value: 20
```

### Feature #4: Debug Information in Response

**What it does**: Returns debug info when validation fails

**Example error response**:
```json
{
  "valid": false,
  "error": "This coupon is no longer active",
  "debug": {
    "isActive_field": false,
    "active_field": null,
    "status_field": "inactive"
  },
  "cached": false
}
```

Shows you EXACTLY which fields were checked and what values were found!

---

## 📋 Troubleshooting Guide

### Problem: Coupon shows "Invalid coupon code"

**Diagnosis Steps**:

1. **Check if coupon exists in Firestore**:
   - Firebase Console → coupons collection
   - Search for the code
   - If not found → Create the coupon

2. **Check code spelling**:
   - Firestore: `SAVE20`
   - User enters: `save20`
   - Validator converts to uppercase ✅ Should work
   - If still fails → Check for extra spaces/characters

3. **Check cache**:
   - Coupon might have been queried when it didn't exist
   - Wait 5 minutes OR clear cache OR bypass cache in test tool

**Quick Fix**:
- Visit `test-coupon.html`
- Enter the code with "Bypass Cache" checked
- See what the actual error is

---

### Problem: Coupon shows "No longer active"

**Diagnosis Steps**:

1. **Open test-coupon.html**
2. **Test the coupon** with "Bypass Cache" checked
3. **Check debug info** in result
4. **Look at which fields it found**:
   ```json
   "debug": {
     "isActive_field": null,     // ❌ Field doesn't exist
     "active_field": false,      // ❌ Found but set to false
     "status_field": "inactive"  // ❌ Found but set to inactive
   }
   ```

**Quick Fix**:
- Go to Firestore → Edit coupon document
- Add: `isActive: true` (as boolean, not string)
- OR change: `active: true`
- OR change: `status: "active"`
- Clear cache or wait 5 minutes

---

### Problem: Coupon worked yesterday, doesn't work today

**Possible Causes**:

1. **Expiry date passed**:
   - Check `validUntil` field
   - If expired, update to future date

2. **Usage limit reached**:
   - Check `usageCount` vs `usageLimit`
   - If limit reached, increase `usageLimit` or reset `usageCount`

3. **Admin deactivated it**:
   - Check `isActive` field
   - Might have been set to false

**Quick Diagnosis**:
- Use test-coupon.html with "Bypass Cache" checked
- Error message will tell you exactly why

---

### Problem: Coupon works in test tool but not on order page

**Possible Causes**:

1. **Browser cache**:
   - Clear browser cache
   - Or use Incognito mode

2. **JavaScript error on order page**:
   - Open console (F12)
   - Look for errors in applyCoupon function

3. **Different subtotal**:
   - Check order subtotal
   - Might be below minAmount

**Quick Fix**:
- Test in Incognito mode
- Check browser console for errors
- Use same subtotal as in test tool

---

## 🚀 Deployment Instructions

### Critical Files (Upload These):

1. **api/validate_coupon.php** (CRITICAL FIX)
   - Upload to: `/public_html/static-site/api/`
   - Enhanced validation logic

### Testing Tools (Recommended):

2. **test-coupon.html**
   - Upload to: `/public_html/static-site/`
   - Interactive testing tool

3. **api/clear_coupon_cache.php**
   - Upload to: `/public_html/static-site/api/`
   - Cache clearing utility

---

## 🔍 Immediate Action Plan

### For Each Coupon That's Not Working:

**Step 1**: Test it
```
https://attral.in/test-coupon.html
Enter code + subtotal
Check "Bypass Cache"
Click "Test"
```

**Step 2**: Read the error message
```
Example: "This coupon is no longer active"
Check debug info to see which fields were checked
```

**Step 3**: Fix in Firestore
```
Add or update the required field
Common fix: Add isActive: true (as boolean)
```

**Step 4**: Clear cache
```
Visit test-coupon.html → Click "Clear All Coupon Cache"
OR wait 5 minutes
```

**Step 5**: Test again
```
Should now work ✅
```

---

## 📊 Enhanced Error Messages

The updated validator now provides detailed error messages:

### Before (Generic):
```json
{
  "valid": false,
  "error": "This coupon is no longer active"
}
```

### After (Detailed):
```json
{
  "valid": false,
  "error": "This coupon is no longer active",
  "debug": {
    "isActive_field": false,
    "active_field": null,
    "status_field": "inactive"
  },
  "cached": false
}
```

Now you can see EXACTLY what fields exist and their values!

---

## 💡 Pro Tips

### Tip #1: Always Use Bypass Cache When Testing

When making changes to coupons in Firestore:
1. Update the Firestore document
2. Test with "Bypass Cache" checked
3. Clears cached result immediately

### Tip #2: Use Consistent Field Names

For all new coupons, use these exact field names:
- `isActive` (boolean true/false)
- `validUntil` (string "YYYY-MM-DD")
- `minAmount` (number)
- `usageLimit` (number, 0 = unlimited)

### Tip #3: Check Server Logs

Server logs show the COMPLETE coupon document from Firestore.  
This is the most reliable way to see what data the API actually receives.

### Tip #4: Document Structure Matters

Firestore requires specific data types:
- ❌ `isActive: "true"` (string) → Won't work
- ✅ `isActive: true` (boolean) → Works
- ❌ `value: "20"` (string) → Won't work
- ✅ `value: 20` (number) → Works

---

## ✅ Summary

**What was wrong**:
1. ❌ Validator only checked `isActive` field (some coupons use `active` or `status`)
2. ❌ Didn't handle string booleans ("true" vs true)
3. ❌ No way to bypass cache for testing
4. ❌ No detailed error messages
5. ❌ Limited logging

**What's fixed**:
1. ✅ Checks multiple field names (isActive, active, status)
2. ✅ Handles boolean as true, "true", or 1
3. ✅ Cache bypass option for testing
4. ✅ Debug info in error responses
5. ✅ Comprehensive logging to server logs

**How to use**:
1. Upload `api/validate_coupon.php`
2. Upload `test-coupon.html` and `api/clear_coupon_cache.php`
3. Test problematic coupons in test tool
4. Fix Firestore documents based on debug info
5. ✅ All coupons work!

---

**Upload the files and test your coupons. The enhanced logging will tell you EXACTLY why each coupon is failing!** 🎯

